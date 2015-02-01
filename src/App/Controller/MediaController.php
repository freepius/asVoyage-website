<?php

namespace App\Controller;

use App\Exception\MediaElementNotFound;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Summary :
 *  -> __construct
 *  -> connect
 *  -> clearCache       [protected]
 *  -> getRepository    [protected]
 *
 *  -> ONLY FOR CREATION MULTIPLE :
 *      => initCreateMultiple
 *      => getUploadParams      [protected]
 *      => upload               [ajax]
 *      => deleteUploaded       [ajax]
 *
 *  -> ONLY FOR UPDATING MULTIPLE :
 *      => initUpdateMultiple
 *
 *  -> POSTING / DELETING :
 *      => postMultiple
 *      => deleteMultiple
 */
class MediaController implements ControllerProviderInterface
{
    use \Freepius\Controller\TaggedDatedTrait;

    const MODULE = 'media';

    // On "home" page, max number of elements
    protected $limitInHome;


    public function __construct(\Freepius\Silex\Application $app)
    {
        $this->limitInHome = $app['debug'] ? 10 : 100;

        $this->app             = $app;
        $this->config          = $app['media.config'];
        $this->factory         = $app['model.factory.media'];
        $this->factoryUploaded = $app['model.factory.media.uploaded'];
    }

    public function connect(\Silex\Application $app)
    {
        $ctrl = $app['controllers_factory'];

        // Home page
        $this->addHomeRoutes($ctrl);

        // Create multiple
        $ctrl->get('/create' , [$this, 'initCreateMultiple']);
        $ctrl->post('/create', [$this, 'postMultiple'])
            ->value('isCreation', true);

        // Upload a single file
        $ctrl->post('/upload', [$this, 'upload'])
            ->mustBeAjax();

        // Delete a single uploaded file
        $ctrl->get('/delete-uploaded/{id}', [$this, 'deleteUploaded'])
            ->mustBeAjax();

        // Update multiple
        $ctrl->post('/init-update', [$this, 'initUpdateMultiple']);
        $ctrl->post('/update'     , [$this, 'postMultiple'])
            ->value('isCreation', false);

        // Delete multiple
        $ctrl->post('/delete', [$this, 'deleteMultiple']);

        return $ctrl;
    }

    /**
     * Clear caches that depend on media elements.
     */
    protected function clearCache()
    {
        $this->app['http_cache.mongo']->drop('media');

        $this->getRepository()->clearCacheDir();
    }

    protected function getRepository()
    {
        return $this->app['model.repository.media'];
    }


    /***************************************************************************
     * ONLY FOR CREATION MULTIPLE
     **************************************************************************/

    public function initCreateMultiple()
    {
        $this->getRepository()->collectGarbage();

        return $this->app->render('media/post-multiple.html.twig',
            $this->getUploadParams() + ['isCreation' => true]
        );
    }

    /**
     * Parameters used to upload a file.
     */
    protected function getUploadParams()
    {
        return [
            'acceptFileTypes' => $this->config['acceptTypes.jsRegexp'],
            'maxFileSize'     => $this->config['maxFileSize'],
            'previewSize'     => $this->config['image.thumb.size'],
        ];
    }

    /**
     * Upload a single file through Ajax.
     */
    public function upload(Request $request)
    {
        $media = $this->factoryUploaded->instantiate();

        $errors = $this->factoryUploaded->bind($media, $request->files->get('files'));

        if (! $errors) {
            $this->getRepository()->store($media, true);
        }

        return $this->app->json(['files' =>
            [[
                'view' => $this->app->renderView(
                    'media/'.($errors ? 'uploading-error' : 'post-one-media').'.html.twig',
                    $media
                ),
                'hasError' => (bool) $errors,
            ]]
        ]);
    }

    /**
     * If $id matches with an uploaded media, delete it.
     */
    public function deleteUploaded($id)
    {
        if (! $this->getRepository()->deleteById($id, true))
        {
            $this->app->abort(500, "$id is not a valid uploaded media element.");
        }
        return true;
    }


    /***************************************************************************
     * ONLY FOR UPDATING MULTIPLE
     **************************************************************************/

    public function initUpdateMultiple(Request $request)
    {
        $countUnfound = 0;

        $listMedia = [];

        $httpData = $request->request->all();  // http POST data

        // Retrieve all elements to update
        foreach ($httpData as $id => $value)
        {
            try {
                $listMedia[] = $this->getRepository()->getById($id);
            }
            catch (MediaElementNotFound $e)
            {
                $countUnfound ++;
            }
        }

        if ($countUnfound > 0)
        {
            $this->app->addFlash('warning', $this->app->transChoice(
                'media.unfound', $countUnfound, [$countUnfound])
            );
        }

        return $this->app->render('media/post-multiple.html.twig', [
            'isCreation' => false,
            'listMedia'  => $listMedia,
        ]);
    }


    /***************************************************************************
     * POSTING / DELETING
     **************************************************************************/

    public function postMultiple($isCreation, Request $request)
    {
        $mediaInError = [];
        $httpData     = $request->request->all(); // http POST data

        // Some counters
        $countInError = 0;
        $countPosted  = 0;
        $countUnfound = 0;

        // Process each posted element
        foreach ($httpData as $id => $httpMedia)
        {
            try {
                $media = $this->getRepository()->getById($id, $isCreation);

                $errors = $this->factory->bind($media, $httpMedia);

                if ($errors)
                {
                    $countInError ++;
                    $mediaInError[] = $media + ['errors' => $errors];
                }
                else
                {
                    $countPosted ++;
                    $this->getRepository()->store($media);
                }
            }
            catch (MediaElementNotFound $e)
            {
                $countUnfound ++;
            }
        }

        // Display the counters in flash messages

        if ($countInError > 0)
        {
            $this->app->addFlash('danger', $this->app->transChoice(
                'media.inError', $countInError, [$countInError]
            ));
        }

        if ($countPosted > 0)
        {
            $this->app->addFlash('success', $this->app->transChoice(
                $isCreation ? 'media.created' : 'media.updated',
                $countPosted, [$countPosted]
            ));
            $this->clearCache();
        }

        if ($countUnfound > 0)
        {
            $this->app->addFlash('warning', $this->app->transChoice(
                'media.unfound', $countUnfound, [$countUnfound])
            );
        }

        return (0 === $countInError) ?

            $this->app->redirect('/media') :

            $this->app->render('media/post-multiple.html.twig', $this->getUploadParams() + [
                'isCreation'   => $isCreation,
                'listMedia'    => $mediaInError,
            ]);
    }

    public function deleteMultiple(Request $request)
    {
        $countUnfound = 0;
        $countDeleted = 0;

        $httpData = $request->request->all();  // http POST data

        // Retrieve all elements to delete
        foreach ($httpData as $id => $value)
        {
            if ($this->getRepository()->deleteById($id)) { $countDeleted ++; }
            else                                         { $countUnfound ++; }
        }

        if ($countDeleted > 0)
        {
            $this->app->addFlash('success', $this->app->transChoice(
                'media.deleted', $countDeleted, [$countDeleted])
            );
            $this->clearCache();
        }

        if ($countUnfound > 0)
        {
            $this->app->addFlash('warning', $this->app->transChoice(
                'media.unfound', $countUnfound, [$countUnfound])
            );
        }

        return $this->app->redirect('/media');
    }
}
