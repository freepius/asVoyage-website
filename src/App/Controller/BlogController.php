<?php

namespace App\Controller;

use Silex\Application,
    Silex\ControllerProviderInterface,
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
    const LIMIT_ARTICLES = 2; // TODO: change this before PROD


    public function __construct(Application $app)
    {
        /**
         * Hack: call 'security.firewall' allows to call
         * 'security' and 'twig' services at this point !
         */
        $app['security.firewall'];

        $this->app            = $app;
        $this->session        = $app['session'];
        $this->flashBag       = $app['session']->getFlashBag();
        $this->translator     = $app['translator'];
        $this->security       = $app['security'];
        $this->twig           = $app['twig'];
        $this->repository     = $app['model.repository.blog'];
        $this->factoryArticle = $app['model.factory.article'];
        $this->factoryComment = $app['model.factory.comment'];
        $this->markdownTypo   = $app['markdownTypo'];
        $this->captchaManager = $app['captcha.manager'];
    }

    public function connect(Application $app)
    {
        $blog = $app['controllers_factory'];

        $slugToArticle = array($this, 'slugToArticle');

        // Home : a list of articles, for basic users
        $blog->get('/{page}', array($this, 'home'))
            ->value('page', 1)
            ->assert('page', '\d+');

        // ...filtered by tag
        $blog->get('/tag-{tag}/{page}', array($this, 'home'))
            ->value('page', 1)
            ->assert('page', '\d+');

        // ...filtered by year
        $blog->get('/year-{year}/{page}', array($this, 'home'))
            ->value('page', 1)
            ->assert('page', '\d+')
            ->assert('year', '\d{4}');

        // ...filtered by year and month
        $blog->get('/year-{year}/month-{month}/{page}', array($this, 'home'))
            ->value('page', 1)
            ->assert('page' , '\d+')
            ->assert('year' , '\d{4}')
            ->assert('month', '\d{1,2}');

        // Admin dashboard
        $blog->get('/dashboard', array($this, 'dashboard'));

        // CRUD for article :
        $blog->match('/create', array($this, 'post'));

        $blog->get('/{article}/read', array($this, 'read'))
            ->convert('article', $slugToArticle);

        $blog->match('/{article}/update', array($this, 'post'))
            ->convert('article', $slugToArticle);

        $blog->match('/{article}/delete', array($this, 'delete'))
            ->convert('article', $slugToArticle);

        // CRUD for comment :

        // ...on the article reading page
        $blog->match('/{article}/read/{idComment}', array($this, 'read'))
            ->convert('article', $slugToArticle)
            ->value('idComment', null)
            ->assert('idComment', '\d+');

        // ...on a specific admin page
        $blog->match('/{article}/comments/{idComment}', array($this, 'crudComment'))
            ->convert('article', $slugToArticle)
            ->value('idComment', null)
            ->assert('idComment', '\d+');

        // Other / technical routes :
        $blog->get('/captcha-change', array($this, 'changeCaptcha'));

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
            return $this->session->get('blog_filters_and_page', array
            (
                'hasTagFilter'   => false,
                'hasYearFilter'  => false,
                'hasMonthFilter' => false,
                'hasPage'        => false,
            ));
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

        $this->session->set('blog_filters_and_page', $result = array
        (
            'hasTagFilter'   => null !== $tag,
            'hasYearFilter'  => null !== $year && null === $month,
            'hasMonthFilter' => null !== $month,
            'hasPage'        => is_numeric((string) $page),
            'tag'            => urldecode($tag),
            'year'           => $year,
            'month'          => $month,
            'page'           => (string) $page,
        ));

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
                $article, $this->security->isGranted('ROLE_ADMIN'));
        }
        catch (BlogArticleNotFound $e)
        {
            $this->flashBag->add('error', $this->translator->trans(
                'blog.notFound', array($article)
            ));

            return null;
        }
    }

    // TODO : cache HTML until one article is edit/create.
    protected function renderTagsFilter()
    {
        return $this->twig->render('blog/filter-by-tags.html.twig', array(
            'tags' => $this->repository->listTags(),
        ));
    }

    // TODO : cache HTML until one article is edit/create.
    protected function renderDatesFilter()
    {
        return $this->twig->render('blog/filter-by-dates.html.twig', array(
            'countByYearMonth' => $this->repository->countArticlesByYearMonth(),
        ));
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

        $totalPages = (int) ceil($total / self::LIMIT_ARTICLES);

        $page = (int) min($totalPages, $page);

        // Number of previous articles
        $skip = ($page - 1) * self::LIMIT_ARTICLES;

        // Retrieve articles depending on filters
        $articles = iterator_to_array($this->repository->listArticles(
            self::LIMIT_ARTICLES, $skip, $fromDate, $toDate, $tag
        ));

        // For text and summary : MarkdownTypo to Html
        foreach ($articles as & $article)
        {
            // avoid conflicts for footnote ids
            $this->markdownTypo->markdown->fn_id_prefix = $article['slug'];

            $article['text']    = $this->markdownTypo->transform($article['text']);
            $article['summary'] = trim($this->markdownTypo->transform($article['summary']));
        }

        return $this->twig->render('blog/home.html.twig', array
        (
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
        ));
    }

    public function dashboard()
    {
        return $this->twig->render('blog/dashboard.html.twig', array
        (
            'articles' => $this->repository->listAllArticles(),
        ));
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

        return $this->twig->render('article/read.html.twig',
            $this->retrieveFiltersAndPage($request) + $opComment + array('article' => $article)
        );
    }

    /**
     * Create or update a blog article
     * (if $article === array() then create else update).
     */
    public function post(Request $request, $article = array())
    {
        if (null === $article)
        {
            return $this->app->redirect('/blog/dashboard');
        }

        if ($isCreation = array() === $article)
        {
            $article = $this->factoryArticle->instantiate();
        }

        $errors = array();

        // Process of the creation / updating
        if ($request->isMethod('POST'))
        {
            $httpData = $request->request->all(); // http POST data

            $errors = $this->factoryArticle->bind($article, $httpData);

            // No error => store the article + redirect to dashboard
            if (array() === $errors)
            {
                $this->repository->store($article);

                $this->flashBag->add('success', $this->translator->trans(
                    'blog.' . ($isCreation ? 'created' : 'updated'),
                    array($article['slug'])
                ));

                return $this->app->redirect('/blog/dashboard');
            }
        }

        return $this->twig->render('article/post-general.html.twig', array
        (
            'article'    => $article,
            'errors'     => $errors,
            'isCreation' => $isCreation,
        ));
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

            $this->flashBag->add('success', $this->translator->trans(
                'blog.deleted', array($article['slug'])
            ));

            return $this->app->redirect('/blog/dashboard');
        }

        return $this->twig->render('article/delete.html.twig', array
        (
            'article' => $article,
        ));
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

        return $this->twig->render('comment/crud.html.twig',
            $opComment + array('article' => $article)
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
            $this->flashBag->add('error', $this->translator->trans(
                'comment.notFound', array($idComment)
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
            $this->flashBag->add('success', $this->translator->trans(
                'comment.deleted', array($idComment)
            ));

            $this->repository->deleteComment($article['_id'], $idComment);

            return true;
        }

        $isUpdating = @ $comment ? true : false;

        $comment = @ $comment ?: $this->factoryComment->instantiate();

        $this->factoryComment->addCaptchaIfNeeded($comment);

        return array('comment' => array
        (
            'id'              => $idComment,
            'entity'          => $comment,
            'errors'          => array(),
            'isCreation'      => ! $isUpdating,
            'isFirstCreation' => ! $isUpdating,
            'isUpdating'      =>   $isUpdating,
        ));
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
        if (array() === $errors)
        {
            $this->repository->storeComment($article['_id'], $idComment, $comment);

            $this->flashBag->add('success', $this->translator->trans(
                (null === $idComment) ? 'comment.created' : 'comment.updated',
                array($idComment)
            ));

            return true;
        }

        // Some errors => add a flash message + a captcha if needed

        $this->flashBag->add('error', $this->translator->trans(
            (null === $idComment) ? 'comment.creation.error(s)' : 'comment.updating.error(s)'
        ));

        $this->factoryComment->addCaptchaIfNeeded($comment);

        return array('comment' => array
        (
            'id'              => $idComment,
            'entity'          => $comment,
            'errors'          => $errors,
            'isCreation'      => null === $idComment,
            'isFirstCreation' => false,
            'isUpdating'      => null !== $idComment,
        ));
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
