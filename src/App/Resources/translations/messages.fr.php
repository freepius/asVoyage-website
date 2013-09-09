<?php

/**
 * Summary :
 *  -> A list of various messages
 *  -> Captcha
 *  -> Contact
 *  -> Error
 *  -> Filter
 *  -> Languages
 *  -> Literal numbers
 *  -> Months
 *  -> Navigation
 *  -> Placeholder
 */

return [
    'Actions'                 => 'Actions',
    'at'                      => 'à',
    'Bad credentials'         => 'Mot de passe erroné',
    'by date'                 => 'par date',
    'by tag'                  => 'par mot-clé',
    'Cancel'                  => 'Annuler',
    'Comments'                => 'Commentaires',
    'Delete'                  => 'Supprimer',
    'Delete filter(s)'        => 'Supprimer le(s) filtre(s)',
    'Edit'                    => 'Éditer',
    'Error'                   => 'Erreur',
    'Error(s)'                => "Il y a <b>0</b> erreur(s) : à 1.",
    'General'                 => 'Général',
    'Login'                   => 'Connexion',
    'Logout'                  => 'Déconnexion',
    'No'                      => 'Non',
    'none'                    => 'aucun',
    'Older'                   => 'Plus anciens',
    'Password'                => 'Mot de passe',
    'Post'                    => 'Poster',
    'Posted by'               => 'Posté par',
    'Preview'                 => 'Prévisualiser',
    'Read'                    => 'Lire',
    'Read more'               => 'Lire plus',
    'Run search'              => 'Lancer la recherche',
    'Search'                  => 'Chercher',
    'See more articles'       => "Voir plus d'articles",
    'See more images'         => "Voir plus d'images",
    'Submit'                  => 'Envoyer',
    'to'                      => 'à',
    'Top'                     => 'Haut',
    'Update'                  => 'Mettre à jour',
    'website'                 => 'site web',
    'Yes'                     => 'Oui',
    'Younger'                 => 'Plus récents',

    // Captcha
    'captcha' => [
        'field' => 'Système anti-spam',

        'help' => '<i class="icon-warning-sign"></i> '.
            "Veuillez recopier les caractères affichés dans l'image, afin d'empêcher les systèmes automatiques de <i>spammer</i>.",

        'help.change' => "Cliquez ici pour changer l'image",
    ],

    // Contact
    'contact' => [
        'sent'  => 'Votre pigeon voyageur a bien été envoyé !',
        'title' => 'Nous contacter',

        'field' => [
            'captcha' => 'Système anti-spam',
            'email'   => 'Votre email',
            'message' => 'Votre message',
            'name'    => 'Qui êtes-vous ?',
            'subject' => 'Le sujet',
        ],
    ],

    // Error
    'error' => [
        'go2home'   => 'Peut-être devriez-vous <a href="/home">retourner à l\'accueil</a> ?!',
        'contactUs' => 'Ou <a href="/contact">contactez-nous</a> si le problème vous paraît louche...',

        'title' => [
            '404'   => 'Hu hu 404 ! Vous êtes perdus !',
            'other' => 'Hu hu ! Erreur fatale !',
        ],

        'message' => [
            '404'   => "La page que vous cherchez n'existe pas (ou plus).",
            'other' => "Quelque chose a mal tourné. C'est terrible !",
        ],
    ],

    // Filter
    'filter' => [
        'tags'       => 'Filtré par le mot-clé <b>0</b>|'.
                        'Filtré par les mots-clés <b>0</b>',
        'year'       => "Filtré par l'année <b>0</b>",
        'year-month' => "Filtré par la date <b>1 0</b>",
    ],

    // Languages
    'lang' => [
        'fr' => 'Français',
        'en' => 'Anglais',
    ],

    // Literal numbers
    'literal' => [
        '8'  => 'huit',
        '9'  => 'neuf',
        '10' => 'dix',
        '11' => 'onze',
        '12' => 'douze',
        '13' => 'treize',
        '14' => 'quatorze',
        '15' => 'quinze',
    ],

    // Months
    'month' => [
        '1'  => 'Janvier',
        '2'  => 'Février',
        '3'  => 'Mars',
        '4'  => 'Avril',
        '5'  => 'Mai',
        '6'  => 'Juin',
        '7'  => 'Juillet',
        '8'  => 'Août',
        '9'  => 'Septembre',
        '10' => 'Octobre',
        '11' => 'Novembre',
        '12' => 'Décembre',
    ],
    'January'   => 'Janvier',
    'February'  => 'Février',
    'March'     => 'Mars',
    'April'     => 'Avril',
    'May'       => 'Mai',
    'June'      => 'Juin',
    'July'      => 'Juillet',
    'August'    => 'Août',
    'September' => 'Septembre',
    'October'   => 'Octobre',
    'November'  => 'Novembre',
    'December'  => 'Décembre',

    // Navigation
    'nav' => [
        'about'     => 'À propos',
        'blog'      => 'Blog',
        'contact'   => 'Contact',
        'home'      => 'Accueil',
        'media'     => 'Nos médias',
        'our-trips' => 'Nos voyages',
    ],

    // Placeholder
    'placeholder' => [
        'tags' => 'Mots-clés séparés par une virgule',
    ],
];
