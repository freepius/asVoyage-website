<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request,
    Silex\ControllerCollection,
    App\Util\StringUtil;


/**
 * Summary :
 *  -> addHomeRoutes        [protected]
 *  -> getFilters           [protected]
 *  -> renderTagsFilter     [protected]
 *  -> renderDatesFilter    [protected]
 *  -> home
 */
Trait TaggedDatedTrait
{
    protected function addHomeRoutes(ControllerCollection $factory)
    {
        // Home : a list of elements
        $factory->get('/', [$this, 'home'])
            ->value('page', 1);

        $factory->get('/{page}', [$this, 'home'])
            ->assert('page', '\d+');

        // ...filtered by one tag
        $factory->get('/tag-{tags}/{page}', [$this, 'home'])
            ->value('page', 1)
            ->assert('page', '\d+');

        // ...filtered by multiple tags
        $factory->get('/tags-{tags}/{page}', [$this, 'home'])
            ->value('page', 1)
            ->assert('page', '\d+');

        // ...filtered by year
        $factory->get('/year-{year}/{page}', [$this, 'home'])
            ->value('page', 1)
            ->assert('page', '\d+')
            ->assert('year', '\d{4}');

        // ...filtered by year and month
        $factory->get('/year-{year}/month-{month}/{page}', [$this, 'home'])
            ->value('page', 1)
            ->assert('page' , '\d+')
            ->assert('year' , '\d{4}')
            ->assert('month', '\d{1,2}');
    }

    /**
     * Retrieve and process the filters for home page.
     */
    protected function getFilters(Request $request)
    {
        /**
         * Retrieve filters :
         *  -> first from the request attributes (parsed from PATH_INFO)
         *  -> next from the POST parameters
         *  -> then from the GET parameters
         */
        $attr = $request->attributes;
        $post = $request->request;
        $get  = $request->query;

        $getFilter = function ($filter) use ($attr, $post, $get)
        {
            return $attr->get($filter) ?: $post->get($filter) ?: $get->get($filter);
        };

        $tags  = StringUtil::normalizeTags($getFilter('tags'));
        $year  = (int) $getFilter('year');
        $month = min((int) $getFilter('month'), 12);

        $filters = [
            'tags'  => implode(', ', $tags),
            'year'  => $year,
            'month' => $month,
        ];

        // Transform $year and $month in period (from and to) for the DB query
        if ($year) {
            switch ($month) {
                case 0 :
                    $fromDate = $year.'-01';
                    $toDate   = ($year + 1).'-01';
                    break;

                case 12 :
                    $fromDate = $year.'-12';
                    $toDate   = ($year + 1).'-01';
                    break;

                default :
                    $fromDate = $year .'-'. $month;
                    $toDate   = $year .'-'. ($month + 1);
            }

            $fromDate = date('Y-m-d H:i:s', strtotime($fromDate));
            $toDate   = date('Y-m-d H:i:s', strtotime($toDate));
        }
        else { $fromDate = $toDate = null; }

        return [
            // for view
            0 => $filters + [
                'countFilters' => (bool) $tags + (bool) $year + (bool) $month,
                'countTags'    => count($tags),
                'filters'      => $filters,
            ],
            // for DB query
            1 => [
                'tags' => $tags,
                'from' => $fromDate,
                'to'   => $toDate,
            ],
        ];
    }

    protected function renderTagsFilter()
    {
        return $this->app->renderView('generic/filter-by-tags.html.twig', [
            'module' => self::MODULE,
            'tags'   => $this->getRepository()->listTags()
        ]);
    }

    protected function renderDatesFilter()
    {
        return $this->app->renderView('generic/filter-by-dates.html.twig', [
            'module'           => self::MODULE,
            'countByYearMonth' => $this->getRepository()->countByYearMonth()
        ]);
    }

    /**
     * CACHE: public ; validation
     */
    public function home(Request $request, $page)
    {
        list($viewFilters, $queryFilters) = $this->getFilters($request);

        // Http cache
        $cacheKey = self::MODULE.'.home.'.serialize($queryFilters).'.'.$page;

        $response = $this->app['http_cache.mongo']->response(
            $request, $cacheKey, [self::MODULE]
        );

        if ($response->isNotModified($request)) { return $response; }


        // Total number of elements depending on filters
        $total = $this->getRepository()->count($queryFilters);

        $totalPages = (int) ceil($total / $this->limitInHome);

        $page = (int) min($totalPages, $page);

        // Number of previous articles
        $skip = ($page - 1) * $this->limitInHome;

        // Retrieve elements depending on filters
        $elements = iterator_to_array($this->getRepository()->find(
            $this->limitInHome, $skip, $queryFilters
        ));

        // Render view of "Title and filters"
        $titleAndFilters = $this->app->renderView
        (
            'generic/title-and-filters.html.twig',
            $viewFilters + ['module' => self::MODULE]
        );

        // Render view of "Counter and navigation"
        $countAndNav = $this->app->renderView(
            'generic/counter-and-navigation.html.twig', $viewFilters +
            [
                'module'        => self::MODULE,
                'count'         => count($elements),
                'countPrevious' => $skip,
                'page'          => $page,
                'total'         => $total,
                'totalPage'     => $totalPages,
            ]
        );

        return $this->app->render(self::MODULE.'/home.html.twig',
        [
            'elements'             => $elements,
            'titleAndFilters'      => $titleAndFilters,
            'counterAndNavigation' => $countAndNav,
            'tagsFilter'           => $this->renderTagsFilter(),
            'datesFilter'          => $this->renderDatesFilter(),
        ], $response);
    }
}
