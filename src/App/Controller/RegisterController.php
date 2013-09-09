<?php

namespace App\Controller;

use Silex\ControllerProviderInterface,
    Symfony\Component\HttpFoundation\Request;


/**
 * Summary :
 *  -> __construct
 *  -> connect
 *  -> initPost
 */
class RegisterController implements ControllerProviderInterface
{
    public function __construct(\App\Application $app)
    {
        $this->app        = $app;
        $this->factory    = $app['model.factory.register'];
        $this->repository = $app['model.repository.register'];
    }

    public function connect(\Silex\Application $app)
    {
        $register = $app['controllers_factory'];

        // Post multiple entries in the travel register
        $register->get('/post' , [$this, 'initPost']);
        $register->post('/post', [$this, 'post']);

        return $register;
    }

    public function initPost(Request $request)
    {
        $entries = [];

        $filters = $request->query->all();  // http GET data

        /* If there are filters :
         *  -> try to retrieve some travel register entries
         *  -> limit to 50
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

            $errors = $this->factory->bind($entry, [$httpEntry]);

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
}
