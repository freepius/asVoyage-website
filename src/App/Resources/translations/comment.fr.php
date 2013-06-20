<?php

return array('comment' => array
(
    'create'            => 'Ajouter un commentaire',
    'created'           => 'Votre commentaire a bien été ajouté.',
    'creation.error(s)' => '<a href="#comment-post">Ajout d\'un commentaire</a> : il y a une/des erreur/s !',
    'count'             => '0 commentaire(s)',
    'deleted'           => 'Le commentaire <b>0</b> a bien été supprimé.',
    'deleting.confirm'  => 'Confirmez-vous la suppression du commentaire ?',
    'notFound'          => "Le commentaire <b>0</b> n'existe pas.",
    'update'            => 'Mise à jour du commentaire',
    'updateX'           => 'Mise à jour du <a href="#comment-0">commentaire 0</a>',
    'updated'           => 'Le <a href="#comment-0">commentaire 0</a> a bien été mis à jour.',
    'updating.error(s)' => '<a href="#comment-post">Mise à jour du commentaire</a> : il y a une/des erreur/s !',

    'field' => array
    (
        'captcha' => 'Système anti-spam',
        'name'    => 'Nom ou pseudo',
        'text'    => 'Commentaire',
    ),

    'help' => array
    (
        'captcha' => '<i class="icon-warning-sign"></i> '.
            "Veuillez recopier les caractères affichés dans l'image, afin d'empêcher les systèmes automatiques de <i>spammer</i>.",

        'captcha-change' => "Cliquez ici pour changer l'image",

        'text' => '<i class="icon-info-sign"></i> '.
            'Le code HTML est affiché comme du texte et les adresses web sont automatiquement transformées.',
    ),
));