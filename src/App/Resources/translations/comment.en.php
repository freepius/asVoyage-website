<?php

return array('comment' => array
(
    'create'            => 'Add a comment',
    'created'           => 'Your comment has been added.',
    'creation.error(s)' => '<a href="#comment-post">Adding a comment</a> : one or more errors !',
    'count'             => '0 comment(s)',
    'deleted'           => 'The comment has been deleted.',
    'deleting.confirm'  => 'Do you confirm the comment deleting ?',
    'notFound'          => "Comment <b>0</b> doesn't exist.",
    'update'            => 'Updating of the comment',
    'updateX'           => 'Updating of the <a href="#comment-0">comment 0</a>',
    'updated'           => 'The <a href="#comment-0">comment 0</a> has been updated',
    'updating.error(s)' => '<a href="#comment-post">Updating of the comment</a> : one or more errors !',

    'field' => array
    (
        'captcha' => 'Anti-spam system',
        'name'    => 'Name or nickname',
        'text'    => 'Comment',
    ),

    'help' => array
    (
        'captcha' => '<i class="icon-warning-sign"></i> '.
            'Please enter the characters displayed in the picture to prevent automated spam systems.',

        'text' => '<i class="icon-info-sign"></i> '.
            'HTML code is displayed as text and URL are automatically converted.',
    ),
));
