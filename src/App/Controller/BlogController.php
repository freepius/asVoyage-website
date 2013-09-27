<?php

namespace App\Controller;

use Silex\ControllerProviderInterface,
    Symfony\Component\HttpFoundation\Request,
    App\Exception\BlogArticleNotFound,
    App\Util\StringUtil;


/**
 * Summary :
 *  -> __construct
 *  -> connect
 *  -> retrieveFiltersAndPage [protected]
 *  -> slugToArticle
 *  -> clearCache             [protected]
 *  -> getRepository          [protected]
 *
 *  -> GLOBAL ACTIONS :
 *      => dashboard
 *
 *  -> ACTIONS ON ARTICLE :
 *      => read
 *      => post
 *      => delete
 *
 *  -> ACTIONS ON COMMENT :
 *      => crudComment
 *      => actionsOnComment [protected]
 *      => postComment      [protected]
 */
class BlogController implements ControllerProviderInterface
{
    use TaggedDatedTrait;

    const MODULE = 'blog';

    // On "home" page, max number of articles
    protected $limitInHome;


    public function __construct(\App\Application $app)
    {
        $this->limitInHome = $app['debug'] ? 2 : 10;

        $this->app            = $app;
        $this->repository     = $app['model.repository.blog'];
        $this->factoryArticle = $app['model.factory.article'];
        $this->factoryComment = $app['model.factory.comment'];
    }

    public function connect(\Silex\Application $app)
    {
        $blog = $app['controllers_factory'];

        $slugToArticle = [$this, 'slugToArticle'];

        // Home page
        $this->addHomeRoutes($blog);

        // Admin dashboard
        $blog->get('/dashboard', [$this, 'dashboard']);

        // CRUD for article :
        $blog->match('/create', [$this, 'post']);

        $blog->get('/{article}/read', [$this, 'read'])
            ->convert('article', $slugToArticle);

        $blog->match('/{article}/update', [$this, 'post'])
            ->convert('article', $slugToArticle);

        $blog->match('/{article}/delete', [$this, 'delete'])
            ->convert('article', $slugToArticle);

        // CRUD for comment :

        // ...on the article reading page
        $blog->match('/{article}/read/{idComment}', [$this, 'read'])
            ->convert('article', $slugToArticle)
            ->value('idComment', null)
            ->assert('idComment', '\d+');

        // ...on a specific admin page
        $blog->match('/{article}/comments/{idComment}', [$this, 'crudComment'])
            ->convert('article', $slugToArticle)
            ->value('idComment', null)
            ->assert('idComment', '\d+');

        return $blog;
    }

    /**
     * If HTTP_REFERER is Blog home page :
     *  -> determine the filters and/or the page number by reverse-engineering on HTTP_REFERER
     *  -> store them in session
     *
     * If HTTP_REFERER is the reading page of a Blog article :
     *  -> try to retrieve the filters and/or the page number from session
     *
     * Url of Blog home page is one of the following ({page} is optional) :
     *  -> blog/{page}
     *  -> blog/tag-{tag}/{page}
     *  -> blog/tags-{tags}/{page}
     *  -> blog/year-{year}/{page}
     *  -> blog/year-{year}/mont-{month}/{page}
     */
    protected function retrieveFiltersAndPage(Request $request)
    {
        $referer = $request->headers->get('referer');

        strtok($referer, '/'); // skip the protocol (eg: http://)
        strtok('/');           // skip the host     (eg: anarchos-semitas.net/)

        $url = strtok('');

        // Do we come from the reading page of a Blog article ?
        if (preg_match('{^blog/.*/read}', $url))
        {
            return $this->app->getSession('blog.filters_and_page',
            [
                'hasTagsFilter'  => false,
                'hasYearFilter'  => false,
                'hasMonthFilter' => false,
                'hasPage'        => false,
            ]);
        }

        $year = $month = $page = null;
        $tags = [];

        // Do we come from Blog home page ?
        if ('blog' === strtok($url, '/'))
        {
            $params = strtok('');
            $filter = strtok($params, '-');

            if ('tag' === $filter || 'tags' === $filter)
            {
                $tags = StringUtil::normalizeTags(urldecode(strtok('/')));
                $page = strtok('');
            }
            elseif ('year' === $filter)
            {
                $year = strtok('/');

                if ('month' === $page = strtok('-'))
                {
                    $month = strtok('/');
                    $page  = strtok('');
                }
            }
            else { $page = $params; }

            // Despite appearances, we don't come from Blog home page !
            if (! (null  === $year  || is_numeric($year))  ||
                ! (null  === $month || is_numeric($month)) ||
                ! (false === $page  || is_numeric($page)))
            {
                $year = $month = $page = null;
                $tags = [];
            }
        }

        $this->app->setSession('blog.filters_and_page', $result =
        [
            'hasTagsFilter'  => (bool) $tags,
            'hasYearFilter'  => $year && !$month,
            'hasMonthFilter' => (bool) $month,
            'hasPage'        => is_numeric((string) $page),
            'countTags'      => count($tags),
            'tags'           => implode(',', $tags),
            'year'           => $year,
            'month'          => $month,
            'page'           => (string) $page,
        ]);

        return $result;
    }

    /**
     * From its slug ($article param.), retrieve an article as array.
     * If $article doesn't match any article, abort with 404 error code.
     */
    public function slugToArticle($article)
    {
        try {
            return $this->repository->getBySlug(
                $article, $this->app->isGranted('ROLE_ADMIN'));
        }
        catch (BlogArticleNotFound $e)
        {
            $article = strip_tags($article);
            $this->app->abort(404, $this->app->trans('blog.notFound', [$article]));
        }
    }

    /**
     * Clear caches that depend on blog articles/comments.
     */
    protected function clearCache()
    {
        $this->app['http_cache.mongo']->drop('blog');
    }

    protected function getRepository()
    {
        return $this->app['model.repository.blog'];
    }


    /***************************************************************************
     * GLOBAL ACTIONS
     **************************************************************************/

    public function dashboard()
    {
        return $this->app->render('blog/dashboard.html.twig',
        [
            'articles' => $this->repository->listAll(),
        ]);
    }


    /***************************************************************************
     * ACTIONS ON ARTICLE
     **************************************************************************/

    /**
     * Read an article + CRUD for comment.
     */
    public function read(Request $request, $article, $idComment = null)
    {
        $article['comments'] = $this->repository->getCommentsById($article['_id']);

        $opComment = $this->actionsOnComment($request, $article, $idComment);

        if (false === $opComment || true === $opComment) {
            return $this->app->redirect("/blog/{$article['slug']}/read");
        }

        return $this->app->render('blog/read.html.twig',
            $this->retrieveFiltersAndPage($request) + $opComment + ['article' => $article]
        );
    }

    /**
     * Create or update a blog article
     * (if $article === [] then create else update).
     */
    public function post(Request $request, $article = [])
    {
        if ($isCreation = [] === $article)
        {
            $article = $this->factoryArticle->instantiate();
        }

        $errors = [];

        $originalSlug = $article['slug'];

        // Process of the creation / updating
        if ($request->isMethod('POST'))
        {
            $httpData = $request->request->all(); // http POST data

            $errors = $this->factoryArticle->bind($article, $httpData);

            // No error => store the article + redirect to dashboard
            if (! $errors)
            {
                $this->repository->store($article);
                $this->clearCache();

                $this->app->addFlash('success', $this->app->trans(
                    'blog.' . ($isCreation ? 'created' : 'updated'),
                    [$article['slug']]
                ));

                return $this->app->redirect('/blog/dashboard');
            }
        }

        return $this->app->render('blog/post-general.html.twig',
        [
            'originalSlug' => $originalSlug,
            'article'      => $article,
            'errors'       => $errors,
            'isCreation'   => $isCreation,
        ]);
    }

    public function delete(Request $request, $article)
    {
        if ($request->isMethod('POST'))
        {
            $this->repository->deleteById($article['_id']);
            $this->clearCache();

            $this->app->addFlash('success', $this->app->trans(
                'blog.deleted', [$article['slug']]
            ));

            return $this->app->redirect('/blog/dashboard');
        }

        return $this->app->render('blog/delete.html.twig',
        [
            'article' => $article,
        ]);
    }


    /***************************************************************************
     * ACTIONS ON COMMENT
     **************************************************************************/

    /**
     * CRUD for comment.
     */
    public function crudComment(Request $request, $article, $idComment = null)
    {
        $article['comments'] = $this->repository->getCommentsById($article['_id']);

        $opComment = $this->actionsOnComment($request, $article, $idComment);

        if (false === $opComment || true === $opComment) {
            return $this->app->redirect("/blog/{$article['slug']}/comments");
        }

        return $this->app->render('comment/crud.html.twig', $opComment +
        [
            'originalSlug' => $article['slug'],
            'article'      => $article
        ]);
    }

    /**
     * Manage the possible actions for a comment :
     *  -> GET    + idComment === null <=> ready to create a comment
     *  -> POST   + idComment === null <=> create a comment
     *  -> GET    + idComment !== null <=> request to update a comment
     *  -> POST   + idComment !== null <=> update a comment
     *  -> DELETE + idComment !== null <=> delete a comment
     *
     * Return :
     *  -> false : if $idComment doesn't match any existing comment.
     *  -> true  : if a creating / updating / deleting operation succeeded.
     *  -> Else, the following associative array :
     *     {
     *         comment:
     *         {
     *             id              : id of the comment or null for a new
     *             entity          : comment as array
     *             errors          : possible errors as array
     *             isCreation      : boolean
     *             isFirstCreation : boolean
     *             isUpdating      : boolean
     *         }
     *     }
     */
    protected function actionsOnComment(Request $request, array $article, $idComment)
    {
        // Some security
        if (null !== $idComment)
        {
            $idComment = strip_tags($idComment);
        }

        // Comment not found !
        if (null !== $idComment && ! isset($article['comments'][$idComment]))
        {
            $this->app->addFlash('danger', $this->app->trans(
                'comment.notFound', [$idComment]
            ));

            return false;
        }

        // Request to update a comment
        if ($request->isMethod('GET') && null !== $idComment)
        {
            $comment = $article['comments'][$idComment];
        }
        // Create (only if article can be commented) or update a comment
        elseif ($request->isMethod('POST') &&
                ($article['beCommented'] || null !== $idComment))
        {
            return $this->postComment($article, $idComment, $request->request->all());
        }
        // Delete a comment
        elseif ($request->isMethod('DELETE') && null !== $idComment)
        {
            $this->app->addFlash('success', $this->app->trans(
                'comment.deleted', [$idComment]
            ));

            $this->repository->deleteComment($article['_id'], $idComment);
            $this->clearCache();

            return true;
        }

        $isUpdating = @ $comment ? true : false;

        $comment = @ $comment ?: $this->factoryComment->instantiate();

        $this->factoryComment->addCaptchaIfNeeded($comment);

        return ['comment' =>
        [
            'id'              => $idComment,
            'entity'          => $comment,
            'errors'          => [],
            'isCreation'      => ! $isUpdating,
            'isFirstCreation' => ! $isUpdating,
            'isUpdating'      =>   $isUpdating,
        ]];
    }

    /**
     * Create or update a comment.
     */
    protected function postComment(array $article, $idComment, array $inputData)
    {
        $comment = null === $idComment ?
            $this->factoryComment->instantiate() :
            $article['comments'][$idComment];

        $errors = $this->factoryComment->bind($comment, $inputData);

        // No error => store the created/updated comment
        if (! $errors)
        {
            $this->repository->storeComment($article['_id'], $idComment, $comment);
            $this->clearCache();

            $this->app->addFlash('success', $this->app->trans(
                (null === $idComment) ? 'comment.created' : 'comment.updated',
                [$idComment]
            ));

            return true;
        }

        // Some errors => add a flash message + a captcha if needed

        $this->app->addFlash('danger', $this->app->trans(
            (null === $idComment) ? 'comment.creation.error(s)' : 'comment.updating.error(s)'
        ));

        $this->factoryComment->addCaptchaIfNeeded($comment);

        return ['comment' =>
        [
            'id'              => $idComment,
            'entity'          => $comment,
            'errors'          => $errors,
            'isCreation'      => null === $idComment,
            'isFirstCreation' => false,
            'isUpdating'      => null !== $idComment,
        ]];
    }
}
