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
 *  -> storeComment
 *  -> deleteComment
 *  -> getCommentsById
 */
class Blog extends MongoRepository
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

        if ($from) { $query['pubDatetime']['$gte'] = (string) $from; }
        if ($to)   { $query['pubDatetime']['$lte'] = (string) $to; }
        if ($tags) { $query['tags']['$in'] = (array) $tags; }

        // if not admin => unpublished articles, as those postdated, stay hidden !
        if (! $isAdmin)
        {
            $query['isPublished'] = true;

            $query['pubDatetime']['$lte'] = min
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
            'title', 'slug', 'pubDatetime',
            'isPublished', 'beCommented', 'tags', 'countComments',
        ));
    }

    /**
     * From its slug, retrieve an article as array (without its comments).
     * If $slug doesn't match any article, throw a BlogArticleNotFound exception.
     */
    public function getBySlug($slug, $isAdmin = false)
    {
        $query = $this->filterArticles($isAdmin);

        $query['slug'] = $slug;

        $article = $this->collection->findOne($query, array('comments' => 0));

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

        // Articles without comments
        $articles = $this->collection->find($query, array('comments' => 0))
            ->sort(array('pubDatetime' => -1)); // desc = younger first

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
            $this->filterArticles(false, $from, $to), array('pubDatetime' => 1)
        );

        foreach ($articles as $article)
        {
            $year  = (int) substr($article['pubDatetime'], 0, 4);
            $month = (int) substr($article['pubDatetime'], 5, 2);

            $occ = & $result[$year][$month];
            $occ++;
        }

        // Sort by year DESC
        krsort($result);

        // Sort by month DESC
        foreach ($result as $year) { krsort($year); }

        return $result;
    }

    /**
     * Create or update a comment in an article.
     * Return true if the operation succeed ; false, else.
     */
    public function storeComment(\MongoId $idArticle, $idComment, array $comment)
    {
        if (null === $idComment)
        {
            $update = array(
                '$push' => array('comments' => $comment),
                '$inc'  => array('countComments' => 1),
            );
        }
        else {
            $update = array('$set' => array("comments.$idComment" => $comment));
        }

        $result = $this->collection->update(array('_id' => $idArticle), $update);

        return $result['n'] > 0;
    }

    /**
     * Delete a comment in an article.
     * Return true if the operation succeed ; false, else.
     */
    public function deleteComment(\MongoId $idArticle, $idComment)
    {
        // Change comments[$idComment] to null
        $result = $this->collection->update(
            array('_id' => $idArticle),
            array(
                '$unset' => array("comments.$idComment" => 1),
                '$inc'   => array('countComments' => -1),
            )
        );

        // Remove the null
        $this->collection->update(
            array('_id' => $idArticle),
            array('$pull' => array('comments' => null))
        );

        return $result['n'] > 0;
    }

    /**
     * Retrieve the comments of a given article.
     */
    public function getCommentsById(\MongoId $idArticle)
    {
        $article = $this->collection->findOne(array('_id' => $idArticle), array('comments' => 1));

        return $article['comments'];
    }
}
