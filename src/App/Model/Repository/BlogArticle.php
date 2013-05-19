<?php

namespace App\Model\Repository;

use App\Exception\BlogArticleNotFound;


/**
 * Summary :
 *  -> filterArticles [protected]
 *  -> listAllArticles
 *  -> getBySlug
 *  -> countArticles
 *  -> listArticles
 *  -> listTags
 *  -> countArticlesByYearMonth
 */
class BlogArticle extends MongoRepository
{
    /**
     * Return some filters for querying articles :
     *    -> depending on your admin rights
     *    -> between two publication datetime ($from and $to, included)
     *    -> having at least one of $tags
     *
     * Null (or equivalent) parameters are ignored.
     *
     * @param bool          $isAdmin  Query with admin rights or not
     * @param string        $from     The old bound date   ; format = Y-m-d (H:i:s)
     * @param string        $to       The young bound date ; format = Y-m-d (H:i:s)
     * @param string|array  $tags     A string or an array of strings
     *
     * @return array
     */
    protected function filterArticles($isAdmin = false, $from = null, $to = null, $tags = null)
    {
        $query = array();

        if ($from) { $query['pubdatetime']['$gte'] = (string) $from; }
        if ($to)   { $query['pubdatetime']['$lte'] = (string) $to; }
        if ($tags) { $query['tags']['$in'] = (array) $tags; }

        // if not admin => unpublished articles, as those postdated, stay hidden !
        if (! $isAdmin)
        {
            $query['ispublished'] = true;

            $query['pubdatetime']['$lte'] = min
            (
                (string) $to ?: '9999',
                date('Y-m-d H:i:s') // < or == to 'now'
            );
        }

        return $query;
    }

    /**
     * Return the main data of all articles.
     * WARNING : use this function only for admin purpose (eg: admin dashboard).
     */
    public function listAllArticles()
    {
        return $this->collection->find(array(), array(
            'title', 'slug', 'pubdatetime', 'ispublished', 'becommented', 'tags'
        ));
    }

    /**
     * From its slug, retrieve an article as array.
     * If $slug doesn't match any article, throw a BlogArticleNotFound exception.
     */
    public function getBySlug($slug, $isAdmin = false)
    {
        $query = $this->filterArticles($isAdmin);

        $query['slug'] = $slug;

        $article = $this->collection->findOne($query);

        if (! is_array($article)) {
            throw new BlogArticleNotFound("slug = $slug");
        }

        return $article;
    }

    /**
     * Count articles depending on some filters { @see filterArticles }.
     *
     * @return integer
     */
    public function countArticles($from = null, $to = null, $tags = null)
    {
        return $this->collection->count(
            $this->filterArticles(false, $from, $to, $tags)
        );
    }

    /**
     * Search articles depending on some filters { @see filterArticles } and :
     *
     *    -> sorted DESC by publication datetime (ie, younger first)
     *    -> with $limit occurences max.
     *    -> skipping the $skip-th first found
     *
     * @param integer $limit    Must be >= 0
     * @param integer $skip     Must be >= 0
     *
     * @return \MongoCursor
     */
    public function listArticles($limit = 0, $skip = 0, $from = null, $to = null, $tags = null)
    {
        $query = $this->filterArticles(false, $from, $to, $tags);

        $articles = $this->collection->find($query)
            ->sort(array('pubdatetime' => -1)); // desc = younger first

        if ($skip > 0)  { $articles->skip($skip); }
        if ($limit > 0) { $articles->limit($limit); }

        return $articles;
    }

    /**
     * Return an array, "natural sorted" by keys, whose :
     *    Key   = a unique tag
     *    Value = [0 => occurrences of it ; 1 => its percentage on the total]
     */
    public function listTags()
    {
        $total  = 0;
        $result = array();

        $articles = $this->collection->find(
            $this->filterArticles(false), array('tags' => 1)
        );

        // Retrieve all tags + their occurrences
        foreach ($articles as $article)
        {
            $tags = $article['tags'];

            foreach ($tags as $tag)
            {
                $total++;
                $occ = & $result[$tag];
                $occ++;
            }
        }

        // Compute the percentage of each
        $result = array_map(function ($nOcc) use ($total)
        {
            return array($nOcc, (int) round($nOcc / $total * 100));
        },
        $result);

        // "Natural sort" by keys
        uksort($result, '\strnatcmp');

        return $result;
    }

    /**
     * Return a two-dimensional array whose :
     *    Key   = a year
     *    Value = { a month (1 to 12) : number of articles on this "year-month" }+
     */
    public function countArticlesByYearMonth($from = null, $to = null)
    {
        $result = array();

        $articles = $this->collection->find(
            $this->filterArticles(false, $from, $to), array('pubdatetime' => 1)
        );

        foreach ($articles as $article)
        {
            $year  = (int) substr($article['pubdatetime'], 0, 4);
            $month = (int) substr($article['pubdatetime'], 5, 2);

            $occ = & $result[$year][$month];
            $occ++;
        }

        // Sort by year DESC
        krsort($result);

        // Sort by month DESC
        foreach ($result as $year) { krsort($year); }

        return $result;
    }
}
