<?php

namespace App\Controller;

use Silex\ControllerProviderInterface,
    Symfony\Component\HttpFoundation\Request;


/**
 * Summary :
 *  -> __construct
 *  -> connect
 *  -> initPost
 *  -> post
 *  -> postOneFromSms
 */
class RegisterController implements ControllerProviderInterface
{
    public function __construct(\App\Application $app, $twilioAccount, $twilioNumber)
    {
        $this->app        = $app;
        $this->factory    = $app['model.factory.register'];
        $this->repository = $app['model.repository.register'];

        $this->twilioAccount = $twilioAccount;
        $this->twilioNumber  = $twilioNumber;
    }

    public function connect(\Silex\Application $app)
    {
        $register = $app['controllers_factory'];

        // Post multiple entries in the travel register
        $register->get('/post' , [$this, 'initPost']);
        $register->post('/post', [$this, 'post']);

        // Post one entry from a SMS (currently throught Twilio services : http://twilio.com)
        $register->post('/public-post-one', [$this, 'postOneFromSms']);

        return $register;
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
                $this->repository->find(100, $filters)
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
                $res = $this->repository->store($entry);

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
     *      eg: 2013-12-31 12:42:00 # 42.1234, -2.5678 # 20 # 7 # Hello world !
     *
     * In all cases, log the operation !
     */
    public function postOneFromSms(Request $request)
    {
        // http POST data
        $httpData = $request->request;

        // SMS handled by the good Twilio account ?
        if ($this->twilioAccount !== $httpData->get('AccountSid'))
        {
            // TODO : log BAD ACCOUNT
            return false;
        }

        // SMS sent to the good Twilio number ?
        if ($this->twilioNumber !== $httpData->get('To'))
        {
            // TODO : log BAD NUMBER
            return false;
        }

        // Retrieve the SMS body
        $httpEntry = array_map('trim', explode('#', $httpData->get('Body')));

        if (count($httpEntry) !== 5)
        {
            // TODO : log BAD PARAMS NUMBER
            return false;
        }

        // Check the security code
        $securityCode       = (int) substr(@ $httpEntry[3], -1, 1);       // last char. of 4-th field
        $waitedSecurityCode = array_sum(str_split(@ $httpEntry[0])) % 10; // sum of all date digits MOD 10

        if ($securityCode !== $waitedSecurityCode)
        {
            // TODO : log BAD SECURITY CODE
            return false;
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
            // TODO: log BAD DATE
            return false;
        }

        // Clear or correct invalid data
        if (@ $errors['geoCoords'])   { $entry['geoCoords']   = null; }
        if (@ $errors['temperature']) { $entry['temperature'] = null; }
        if (@ $errors['meteo'])       { $entry['meteo']       = null; }
        if (@ $errors['message'])     { $entry['message'] = substr($entry['message'], 500); }

        $this->repository->store($entry);

        // TODO: log the good insertion
        return true;
    }
}
