<?php

namespace App\Controller;

use Silex\Application,
    Silex\ControllerProviderInterface,
    Symfony\Component\HttpFoundation\Request,
    App\Exception\BlogArticleNotFound;


class BlogController implements ControllerProviderInterface
{
    // On "home" page, max number of articles
    const LIMIT_ARTICLES = 2;


    public function __construct(Application $app)
    {
        $this->app        = $app;
        $this->flashBag   = $app['session']->getFlashBag();
        $this->translator = $app['translator'];
        $this->twig       = $app['twig'];
        $this->repository = $app['model.repository.blog.article'];
        $this->factory    = $app['model.factory.blog.article'];
    }

    public function connect(Application $app)
    {
        $blog = $app['controllers_factory'];

        $slugToArticle = array($this, 'slugToArticle');

        // Home : a list of articles, for basic users
        $blog->get('/{page}', array($this, 'home'))
            ->value('page', 1)
            ->assert('page', '\d+');

        // ...filter by tag
        $blog->get('/tag-{tag}/{page}', array($this, 'home'))
            ->value('page', 1)
            ->assert('page', '\d+');

        // ...filter by year
        $blog->get('/year-{year}/{page}', array($this, 'home'))
            ->value('page', 1)
            ->assert('page', '\d+')
            ->assert('year', '\d\d\d\d');

        // ...filter by year and month
        $blog->get('/year-{year}/month-{month}/{page}', array($this, 'home'))
            ->value('page', 1)
            ->assert('page' , '\d+')
            ->assert('year' , '\d\d\d\d')
            ->assert('month', '\d||\d\d');

        // Admin dashboard
        $blog->get('/dashboard', array($this, 'dashboard'));

        // CRUD !
        $blog->match('/create', array($this, 'post'));

        $blog->get('/{article}/read', array($this, 'read'))
            ->convert('article', $slugToArticle);

        $blog->match('/{article}/update', array($this, 'post'))
            ->convert('article', $slugToArticle);

        $blog->match('/{article}/delete', array($this, 'delete'))
            ->convert('article', $slugToArticle);

        return $blog;
    }

    /**
     * From its slug ($article param.), retrieve an article as array.
     * If $article doesn't match any article, add a flash error and return null.
     */
    public function slugToArticle($article)
    {
        try { return $this->repository->getBySlug($article, true); }

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
     * ACTIONS
     **************************************************************************/

    public function home(Request $request, $page = 1, $tag = null, $year = null, $month = null)
    {
        /**
         * Process the filters.
         * User can used them only one by one !
         *
         * TODO : escape / striptags
         */
        $tag   = $tag;
        $year  = (int) $year;
        $month = (int) $month;

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

    public function read($article)
    {
        if (null === $article)
        {
            return $this->app->redirect('/blog/dashboard');
        }

        return $this->twig->render('blog/read.html.twig', array
        (
            'article' => $article,
        ));
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
            $article = $this->factory->instantiate();
        }

        $errors = array();

        // Process of the creation / updating
        if ($request->isMethod('POST'))
        {
            $httpData = $request->request->all(); // http POST data

            $violations = $this->factory->bind($article, $httpData);

            // No error => store the article + redirect to dashboard
            if (0 === count($violations))
            {
                $this->repository->store($article);

                $this->flashBag->add('success', $this->translator->trans(
                    'blog.' . ($isCreation ? 'created' : 'updated'),
                    array($article['slug'])
                ));

                return $this->app->redirect('/blog/dashboard');
            }

            // Some errors => retrieve them
            foreach ($violations as $violation)
            {
                $field = $violation->getPropertyPath();         // eg: "[My field]"
                $field = substr($field, 1, strlen($field)-2);   //  => "My field"

                $errors[$field] = $violation->getMessage();
            }
        }

        return $this->twig->render('blog/post.html.twig', array
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

        return $this->twig->render('blog/delete.html.twig', array
        (
            'article' => $article,
        ));
    }
}
