<?php

namespace App\Model\Repository;

use App\Exception\BlogArticleNotFound;
use Freepius\Model\MongoRepository;
use Freepius\Util\Group;

/**
 * Summary :
 *  -> init
 *  -> filter   [protected]
 *  -> listAll
 *  -> getBySlug
 *  -> count
 *  -> find
 *  -> listTags
 *  -> countByYearMonth
 *  -> storeComment
 *  -> deleteComment
 *  -> getCommentsById
 */
class Blog extends MongoRepository
{
    /**
     * Create some indexes.
     */
    protected function init()
    {
        $this->collection->ensureIndex('slug', ['unique' => true]);
        $this->collection->ensureIndex('pubDatetime');
        $this->collection->ensureIndex('tags');
    }

    /**
     * Return some filters for querying articles :
     *    -> depending on your admin rights ($isAdmin param)
     *    -> between two publication datetime ("from" and "to", included)
     *    -> having all "tags"
     *
     * Null (or equivalent) filter are ignored.
     *
     * $filters =
     * {
     *      from :  The old bound date   ; format = Y-m-d (H:i:s)
     *      to   :  The young bound date ; format = Y-m-d (H:i:s)
     *      tags :  An array of strings
     * }
     *
     * TODO: replace the $isAdmin parameter by a "default filters" mechanism (same as Media repository)
     *
     * @return array
     */
    protected function filter($isAdmin = false, array $filters = [])
    {
        $query = [];

        $from = @ $filters['from'];
        $to   = @ $filters['to'];
        $tags = @ $filters['tags'];

        if ($from) { $query['pubDatetime']['$gte'] = $from; }
        if ($to)   { $query['pubDatetime']['$lte'] = $to; }
        if ($tags) { $query['tags']['$all'] = $tags; }

        // if not admin => unpublished or postdated articles stay hidden !
        if (! $isAdmin)
        {
            $query['isPublished'] = true;

            $query['pubDatetime']['$lte'] = min
            (
                $to ?: '9999',
                date('Y-m-d H:i:s') // < or == to 'now'
            );
        }

        return $query;
    }

    /**
     * Return the main data of all articles.
     * WARNING : use this function only for admin purpose (eg: admin dashboard).
     */
    public function listAll()
    {
        return $this->collection->find([],
        [
            'title'         => 1,
            'slug'          => 1,
            'pubDatetime'   => 1,
            'isPublished'   => 1,
            'beCommented'   => 1,
            'tags'          => 1,
            'countComments' => 1,
            'comments'      => ['$slice' => -1],  // last comment ; TODO: one day, have a 'dateLastComment' field?
        ])
        ->sort(['pubDatetime' => -1]); // desc = younger first
    }

    /**
     * From its slug, retrieve an article as array (without its comments).
     * If $slug doesn't match any article, throw a BlogArticleNotFound exception.
     */
    public function getBySlug($slug, $isAdmin = false)
    {
        $query = ['slug' => $slug] + $this->filter($isAdmin);

        $article = $this->collection->findOne($query, ['comments' => 0]);

        if (! is_array($article)) {
            throw new BlogArticleNotFound("slug = $slug");
        }

        return $article;
    }

    /**
     * Count articles depending on some filters { @see filter }.
     *
     * @return integer
     */
    public function count(array $filters = [])
    {
        return $this->collection->count($this->filter(false, $filters));
    }

    /**
     * Search articles depending on some filters { @see filter } and :
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
    public function find($limit = 0, $skip = 0, array $filters = [])
    {
        $query = $this->filter(false, $filters);

        // Articles without comments
        $articles = $this->collection->find($query, ['comments' => 0])
            ->sort(['pubDatetime' => -1]); // desc = younger first

        if ($skip > 0)  { $articles->skip($skip); }
        if ($limit > 0) { $articles->limit($limit); }

        return $articles;
    }

    /**
     * @see \Freepius\Util\Group::byTags
     */
    public function listTags()
    {
        return Group::byTags(
            $this->collection->find($this->filter(false), ['tags' => 1])
        );
    }

    /**
     * @see \Freepius\Util\Group::byYearMonth
     */
    public function countByYearMonth()
    {
        return Group::byYearMonth(
            $this->collection->find($this->filter(false), ['pubDatetime' => 1]),
            'pubDatetime'
        );
    }

    /**
     * Create or update a comment in an article.
     * Return true if the operation succeed ; false, else.
     */
    public function storeComment(\MongoId $idArticle, $idComment, array $comment)
    {
        if (null === $idComment)
        {
            $update = [
                '$push' => ['comments' => $comment],
                '$inc'  => ['countComments' => 1],
            ];
        }
        else {
            $update = ['$set' => ["comments.$idComment" => $comment]];
        }

        $result = $this->collection->update(['_id' => $idArticle], $update);

        return $result['n'] > 0;
    }

    /**
     * Delete a comment in an article (ie: replace its content by null).
     * Return true if the operation succeed ; false, else.
     */
    public function deleteComment(\MongoId $idArticle, $idComment)
    {
        // Change comments[$idComment] by null
        $result = $this->collection->update(
            ['_id' => $idArticle],
            [
                '$unset' => ["comments.$idComment" => 1],
                '$inc'   => ['countComments' => -1],
            ]
        );

        return $result['err'] === null;
    }

    /**
     * Retrieve the comments of a given article.
     */
    public function getCommentsById(\MongoId $idArticle)
    {
        $article = $this->collection->findOne(['_id' => $idArticle], ['comments' => 1]);

        return $article['comments'];
    }
}
