<?php

namespace App\Controller;

use Silex\ControllerProviderInterface,
    Symfony\Component\HttpFoundation\Request,
    App\Exception\BlogArticleNotFound;


/**
 * Summary :
 *  -> __construct
 *  -> connect
 *  -> retrieveFiltersAndPage [protected]
 *  -> slugToArticle
 *  -> renderTagsFilter  [protected]
 *  -> renderDatesFilter [protected]
 *
 *  -> GLOBAL ACTIONS :
 *      => home
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
 *
 *  -> OTHER / TECHNICAL ACTIONS :
 *      => changeCaptcha
 */
class BlogController implements ControllerProviderInterface
{
    // On "home" page, max number of articles
    protected $limitArticles;


    public function __construct(\App\Application $app)
    {
        $this->limitArticles = 'prod' === $app['env'] ? 10 : 2;

        $this->app            = $app;
        $this->repository     = $app['model.repository.blog'];
        $this->factoryArticle = $app['model.factory.article'];
        $this->factoryComment = $app['model.factory.comment'];
        $this->markdownTypo   = $app['markdownTypo'];
        $this->captchaManager = $app['captcha.manager'];
    }

    public function connect(\Silex\Application $app)
    {
        $blog = $app['controllers_factory'];

        $slugToArticle = [$this, 'slugToArticle'];

        // Home : a list of articles, for basic users
        $blog->get('/{page}', [$this, 'home'])
            ->value('page', 1)
            ->assert('page', '\d+');

        // ...filtered by tag
        $blog->get('/tag-{tag}/{page}', [$this, 'home'])
            ->value('page', 1)
            ->assert('page', '\d+');

        // ...filtered by year
        $blog->get('/year-{year}/{page}', [$this, 'home'])
            ->value('page', 1)
            ->assert('page', '\d+')
            ->assert('year', '\d{4}');

        // ...filtered by year and month
        $blog->get('/year-{year}/month-{month}/{page}', [$this, 'home'])
            ->value('page', 1)
            ->assert('page' , '\d+')
            ->assert('year' , '\d{4}')
            ->assert('month', '\d{1,2}');

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

        // Other / technical routes :
        $blog->get('/captcha-change', [$this, 'changeCaptcha']);

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
            return $this->app->getSession('blog_filters_and_page',
            [
                'hasTagFilter'   => false,
                'hasYearFilter'  => false,
                'hasMonthFilter' => false,
                'hasPage'        => false,
            ]);
        }

        $tag = $year = $month = $page = null;

        // Do we come from Blog home page ?
        if ('blog' === strtok($url, '/'))
        {
            $params = strtok('');
            $filter = strtok($params, '-');

            if ('tag' === $filter)
            {
                $tag  = strtok('/');
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
            if (! (null === $year  || is_numeric($year))  ||
                ! (null === $month || is_numeric($month)) ||
                ! (''   === $page  || is_numeric($page)))
            {
                $tag = $year = $month = $page = null;
            }
        }

        $this->app->setSession('blog_filters_and_page', $result =
        [
            'hasTagFilter'   => null !== $tag,
            'hasYearFilter'  => null !== $year && null === $month,
            'hasMonthFilter' => null !== $month,
            'hasPage'        => is_numeric((string) $page),
            'tag'            => urldecode($tag),
            'year'           => $year,
            'month'          => $month,
            'page'           => (string) $page,
        ]);

        return $result;
    }

    /**
     * From its slug ($article param.), retrieve an article as array.
     * If $article doesn't match any article, add a flash error and return null.
     */
    public function slugToArticle($article)
    {
        try {
            return $this->repository->getBySlug(
                $article, $this->app->isGranted('ROLE_ADMIN'));
        }
        catch (BlogArticleNotFound $e)
        {
            $this->app->addFlash('error', $this->app->trans(
                'blog.notFound', [$article]
            ));

            return null;
        }
    }

    // TODO : cache HTML until one article is edit/create.
    protected function renderTagsFilter()
    {
        return $this->app->renderView('blog/filter-by-tags.html.twig', [
            'tags' => $this->repository->listTags()
        ]);
    }

    // TODO : cache HTML until one article is edit/create.
    protected function renderDatesFilter()
    {
        return $this->app->renderView('blog/filter-by-dates.html.twig', [
            'countByYearMonth' => $this->repository->countArticlesByYearMonth(),
        ]);
    }


    /***************************************************************************
     * GLOBAL ACTIONS
     **************************************************************************/

    public function home(Request $request, $page = 1, $tag = null, $year = null, $month = null)
    {
        /**
         * Process the filters.
         * User can used them only one by one !
         */
        $tag   = $tag;
        $year  = (int) $year;
        $month = min((int) $month, 12);

        $hasTagFilter   = null !== $tag;                      // priority = 1
        $hasYearFilter  = !$hasTagFilter && $year && !$month; //          = 2
        $hasMonthFilter = !$hasTagFilter && $year && $month;  //          = 3

        if ($hasYearFilter || $hasMonthFilter)
        {
            $nextDate = ($year + (int) $hasYearFilter) .'-'. ($month + (int) $hasMonthFilter);

            $fromDate = date('Y-m-d H:i:s', strtotime("$year-$month"));
            $toDate   = date('Y-m-d H:i:s', strtotime($nextDate));
        }
        else { $fromDate = $toDate = null; }

        // Total number of articles depending on filters
        $total = $this->repository->countArticles($fromDate, $toDate, $tag);

        $totalPages = (int) ceil($total / $this->limitArticles);

        $page = (int) min($totalPages, $page);

        // Number of previous articles
        $skip = ($page - 1) * $this->limitArticles;

        // Retrieve articles depending on filters
        $articles = iterator_to_array($this->repository->listArticles(
            $this->limitArticles, $skip, $fromDate, $toDate, $tag
        ));

        // For text and summary : MarkdownTypo to Html
        foreach ($articles as & $article)
        {
            // avoid conflicts for footnote ids
            $this->markdownTypo->markdown->fn_id_prefix = $article['slug'];

            $article['text']    = $this->markdownTypo->transform($article['text']);
            $article['summary'] = trim($this->markdownTypo->transform($article['summary']));
        }

        return $this->app->render('blog/home.html.twig',
        [
            'articles' => $articles,

            // For counter and navigation
            'count'         => count($articles),
            'countPrevious' => $skip,
            'page'          => $page,
            'total'         => $total,
            'totalPage'     => $totalPages,

            // Related to filter
            'tagsFilter'     => $this->renderTagsFilter(),
            'datesFilter'    => $this->renderDatesFilter(),
            'hasTagFilter'   => $hasTagFilter,
            'hasYearFilter'  => $hasYearFilter,
            'hasMonthFilter' => $hasMonthFilter,
            'tag'            => $tag,
            'year'           => $year,
            'month'          => $month,
        ]);
    }

    public function dashboard()
    {
        return $this->app->render('blog/dashboard.html.twig',
        [
            'articles' => $this->repository->listAllArticles(),
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
        if (null === $article)
        {
            return $this->app->redirect('/blog');
        }

        $article['comments'] = $this->repository->getCommentsById($article['_id']);

        $opComment = $this->actionsOnComment($request, $article, $idComment);

        if (false === $opComment || true === $opComment) {
            return $this->app->redirect("/blog/{$article['slug']}/read");
        }

        $article['text'] = $this->markdownTypo->transform($article['text']);

        return $this->app->render('article/read.html.twig',
            $this->retrieveFiltersAndPage($request) + $opComment + ['article' => $article]
        );
    }

    /**
     * Create or update a blog article
     * (if $article === [] then create else update).
     */
    public function post(Request $request, $article = [])
    {
        if (null === $article)
        {
            return $this->app->redirect('/blog/dashboard');
        }

        if ($isCreation = [] === $article)
        {
            $article = $this->factoryArticle->instantiate();
        }

        $errors = [];

        // Process of the creation / updating
        if ($request->isMethod('POST'))
        {
            $httpData = $request->request->all(); // http POST data

            $errors = $this->factoryArticle->bind($article, $httpData);

            // No error => store the article + redirect to dashboard
            if ([] === $errors)
            {
                $this->repository->store($article);

                $this->app->addFlash('success', $this->app->trans(
                    'blog.' . ($isCreation ? 'created' : 'updated'),
                    [$article['slug']]
                ));

                return $this->app->redirect('/blog/dashboard');
            }
        }

        return $this->app->render('article/post-general.html.twig',
        [
            'article'    => $article,
            'errors'     => $errors,
            'isCreation' => $isCreation,
        ]);
    }

    public function delete(Request $request, $article)
    {
        if (null === $article)
        {
            return $this->app->redirect('/blog/dashboard');
        }

        if ($request->isMethod('POST'))
        {
            $this->repository->deleteById($article['_id']);

            $this->app->addFlash('success', $this->app->trans(
                'blog.deleted', [$article['slug']]
            ));

            return $this->app->redirect('/blog/dashboard');
        }

        return $this->app->render('article/delete.html.twig',
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
        if (null === $article)
        {
            return $this->app->redirect('/blog/dashboard');
        }

        $article['comments'] = $this->repository->getCommentsById($article['_id']);

        $opComment = $this->actionsOnComment($request, $article, $idComment);

        if (false === $opComment || true === $opComment) {
            return $this->app->redirect("/blog/{$article['slug']}/comments");
        }

        return $this->app->render('comment/crud.html.twig',
            $opComment + ['article' => $article]
        );
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
        // Comment not found !
        if (null !== $idComment && ! isset($article['comments'][$idComment]))
        {
            $this->app->addFlash('error', $this->app->trans(
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
        if ([] === $errors)
        {
            $this->repository->storeComment($article['_id'], $idComment, $comment);

            $this->app->addFlash('success', $this->app->trans(
                (null === $idComment) ? 'comment.created' : 'comment.updated',
                [$idComment]
            ));

            return true;
        }

        // Some errors => add a flash message + a captcha if needed

        $this->app->addFlash('error', $this->app->trans(
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


    /***************************************************************************
     * OTHER / TECHNICAL ACTIONS
     **************************************************************************/

    /**
     * Generate a new captcha for current user, and return the associated filename.
     */
    public function changeCaptcha()
    {
        $this->captchaManager->revoke();

        return $this->captchaManager->getFilename();
    }
}
