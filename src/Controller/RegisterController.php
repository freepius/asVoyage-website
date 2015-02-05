<?php

namespace App\Controller;

use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Summary :
 *  -> __construct
 *  -> connect
 *  -> initPost
 *  -> post
 *  -> postOneFromSms
 *  -> isMultiSms       [protected]
 *  -> processMultiSms  [protected]
 *  -> clearCache       [protected]
 *  -> getRepository    [protected]
 */
class RegisterController implements ControllerProviderInterface
{
    public function __construct(\Freepius\Silex\Application $app)
    {
        $this->app     = $app;
        $this->factory = $app['model.factory.register'];

        $this->twilioAccount = $app['register.config']['twilio.account'];
        $this->twilioNumber  = $app['register.config']['twilio.number'];
    }

    public function connect(\Silex\Application $app)
    {
        $ctrl = $app['controllers_factory'];

        // List all entries
        $ctrl->get('/list', [$this, 'listAll']);

        // Post multiple entries in the travel register
        $ctrl->get('/post' , [$this, 'initPost']);
        $ctrl->post('/post', [$this, 'post']);

        // Post one entry from SMS (currently throught Twilio services : http://twilio.com)
        $ctrl->post('/public-post-one', [$this, 'postOneFromSms']);

        return $ctrl;
    }

    public function listAll()
    {
        return $this->app->render('register/list.html.twig', [
            'entries' => $this->getRepository()->find()
        ]);
    }

    public function initPost(Request $request)
    {
        $entries = [];

        $filters = $request->query->all();  // http GET data

        /* If there are filters :
         *  -> try to retrieve some travel register entries
         *  -> limit to 100
         */
        if ($filters)
        {
            $entries = iterator_to_array(
                $this->getRepository()->find(100, $filters)
            );

            foreach ($entries as & $e) {
                $e = [$e['_id'], $e['geoCoords'], $e['temperature'], $e['meteo'], $e['message']];
                $e = implode(' # ', $e);
            }
        }

        return $this->app->render('register/post.html.twig', [
            'entries' => implode("\n", $entries)
        ]);
    }

    public function post(Request $request)
    {
        $entriesInError = [];

        // Some counters
        $countInError = 0;
        $countCreated = 0;
        $countUpdated = 0;

        // Retrieve multiple entries from http POST data
        $entries = $request->request->get('entries', '');

        $entries = str_replace(["\r\n", "\r"], "\n", $entries);

        // No php/html tags
        $entries = explode("\n", strip_tags($entries));

        // No blank value
        $entries = array_filter(array_map('trim', $entries));

        foreach ($entries as $httpEntry)
        {
            $entry  = [];

            $errors = $this->factory->bind($entry, explode('#', $httpEntry));

            if ($errors)
            {
                $countInError ++;
                $entriesInError[] = implode(' # ', $entry);
            }
            else
            {
                $res = $this->getRepository()->store($entry);

                if     ($res === 0) { $countCreated ++; }
                elseif ($res === 1) { $countUpdated ++; }
            }
        }

        // Display the counters in flash messages

        if ($countInError > 0)
        {
            $this->app->addFlash('danger', $this->app->transChoice(
                'register.inError', $countInError, [$countInError]
            ));
        }

        if ($countCreated > 0)
        {
            $this->app->addFlash('success', $this->app->transChoice(
                'register.created', $countCreated, [$countCreated]
            ));
        }

        if ($countUpdated > 0)
        {
            $this->app->addFlash('success', $this->app->transChoice(
                'register.updated', $countUpdated, [$countUpdated]
            ));
        }

        // Clear caches depending on 'register' entries
        if ($countCreated > 0 || $countUpdated > 0)
        {
            $this->clearCache();
        }

        return (0 === $countInError) ?

            $this->app->redirect('/home') :

            $this->app->render('register/post.html.twig', [
                'entries' => implode("\n", $entriesInError)
            ]);
    }

    /**
     * Receive a http POST request and check that :
     *
     *  -> 'AccountSid' param. is the good Twilio account
     *
     *  -> 'To' param. is the good Twilio number
     *
     *  -> 'Boby' param. respects the following format :
     *
     *      multi-SMS format (see isMultiSms() function)
     *      OR
     *      date # geoCoords # temperature # meteo and security code # message
     *
     *      => date          : mandatory ; format = MMDDhhmm (implicitly : year === current year ; seconds === 00)
     *      => geoCoords     : optional  ; if invalid => set to null
     *      => temperature   : optional  ; if invalid => set to null
     *      => meteo         : optional  ; if invalid => set to null
     *      => security code : mandatory ; one digit === sum of all date digits MOD 10
     *      => message       : optional  ; if > 500 chars => truncate it
     *
     *      eg: 12311242 # 42.123, -2.456 # 20 # 76 # Hello world !
     *
     * Then, from the checked http POST request, post a new entry in the travel register.
     *
     *      eg: 2013-12-31 12:42:00 # 42.1234, -2.5678 # 20 # 7 # Hello world!
     *
     * NOTE    : If the SMS body is invalid, responds by a SMS describing the problem!
     * WARNING : This response isn't free (with Twilio)
     */
    public function postOneFromSms(Request $request)
    {
        // http POST data
        $httpData = $request->request;

        // Possible responses
        $emptyResponse = '<Response/>';                                // nothing sent
        $smsResponse   = '<Response><Message>%s</Message></Response>'; // SMS sent ; 0.07$ (with Twilio)

        // SMS handled by the good Twilio account ?
        if ($this->twilioAccount !== $httpData->get('AccountSid'))
        {
            return false;
        }

        // SMS sent to the good Twilio number ?
        if ($this->twilioNumber !== $httpData->get('To'))
        {
            return false;
        }

        $smsBody = trim($httpData->get('Body', ''));

        /**
         * If the current SMS is part of a multi-SMS :
         *  -> if this one is not complete => return an empty response without any storing !
         *  -> else => $smsBody var. contains the full SMS (because it is passed by reference).
         */
        if ($this->isMultiSms($smsBody) &&
            ! $this->processMultiSms($smsBody, $httpData->get('From'))
        ) {
            return $emptyResponse;
        }

        // Extract data from SMS body
        $httpEntry = array_map('trim', explode('#', $smsBody));

        if (count($httpEntry) !== 5)
        {
            return sprintf($smsResponse, 'Bad number of parameters: '.count($httpEntry).' found');
        }

        // Check the security code
        $securityCode       = (int) substr(@ $httpEntry[3], -1, 1);       // last char. of 4-th field
        $waitedSecurityCode = array_sum(str_split(@ $httpEntry[0])) % 10; // sum of all date digits MOD 10

        if ($securityCode !== $waitedSecurityCode)
        {
            return sprintf($smsResponse, 'Bad security code');
        }

        // Transform date from MMDDhhmm to YYYY-MM-DD hh:mm:ss format
        $d =& $httpEntry[0];
        $d = sprintf('%s-%s-%s %s:%s:00',
            date('Y'), substr($d, 0, 2), substr($d, 2, 2), substr($d, 4, 2), substr($d, 6, 2)
        );

        // Remove security code in the meteo field
        $httpEntry[3] = substr($httpEntry[3], 0, -1);

        // Try to validate the register entry
        $entry = [];
        $errors = $this->factory->bind($entry, $httpEntry);

        // If date is invalid => reject the entry
        if (@ $errors['_id'])
        {
            return sprintf($smsResponse, 'Bad date: '.$entry['_id']);
        }

        // Clear or correct invalid data
        if (@ $errors['geoCoords'])   { $entry['geoCoords']   = null; }
        if (@ $errors['temperature']) { $entry['temperature'] = null; }
        if (@ $errors['meteo'])       { $entry['meteo']       = null; }
        if (@ $errors['message'])     { $entry['message'] = substr($entry['message'], 0, 500); }

        $this->getRepository()->store($entry);
        $this->clearCache();

        return true;
    }

    /**
     * A SMS is part of a multi-SMS if :
     *  -> 1-st char is a digit (except 0)
     *  -> 2-nd char is '/' or '$' (for the last part)
     */
    protected function isMultiSms($smsBody)
    {
        return ($smsBody[0] >= '1' && $smsBody[0] <= '9') &&
               ($smsBody[1] === '/' || $smsBody[1] === '$');
    }

    /**
     * Store in session each part of a multi-SMS (one multi-SMS per sender).
     * When it is complete => assemble the entire SMS in $smsBody + return true.
     * Else => return false.
     */
    protected function processMultiSms(& $smsBody, $fromNumber)
    {
        // From session, retrieve the list of incomplete multi-SMS
        $multiList = $this->app->getSession('register.multiSms', []);

        // Get multi-SMS of the current sender
        $multi =& $multiList[$fromNumber];

        $numPart = (int) $smsBody[0];
        $code    = $smsBody[1];
        $smsBody = substr($smsBody, 2);

        // Store the new part
        $multi[$numPart] = $smsBody;

        // If last part => store the number of expected parts
        if ('$' === $code || 9 === $numPart) {
            $multi['count'] = $numPart;
        }

        $this->app->setSession('register.multiSms', $multiList);

        // If the multi-SMS is not complete => return false
        if (! array_key_exists('count', $multi)) { return false; }

        for ($i = 1; $i <= $multi['count']; $i++)
        {
            if (! array_key_exists($i, $multi)) { return false; }
        }

        // Else => assemble it in $smsBody + return true
        $smsBody = '';

        for ($i = 1; $i <= $multi['count']; $i++)
        {
            $smsBody .= $multi[$i];
        }

        // Remove it from the incomplete multi-SMS list
        unset($multiList[$fromNumber]);
        $this->app->setSession('register.multiSms', $multiList);

        return true;
    }

    /**
     * Clear caches that depend on register entries.
     */
    protected function clearCache()
    {
        $this->app['http_cache.mongo']->drop('register');

        $this->getRepository()->clearCacheDir();
    }

    protected function getRepository()
    {
        return $this->app['model.repository.register'];
    }
}
