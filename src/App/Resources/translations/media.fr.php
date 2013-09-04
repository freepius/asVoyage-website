<?php

return ['media' => [

    'addLocal'         => 'Ajouter des éléments locaux',
    'caption'          => 'Légende',
    'creation'         => 'Ajout d\'éléments médias',
    'creationDate'     => 'Date de création',
    'counter'          => '<span class="counter-selected-elements">0</span> '.
                          'sur <span class="counter-elements">0</span> sélectionné(s)',
    'deleting.confirm' => 'Confirmez-vous la suppression de 0 élément/s média/s ?',
    'elements'         => 'Éléments médias',
    'file'             => 'Fichier',
    'geoCoords'        => 'Coordonées géo.',
    'home'             => 'Plateforme média',
    'metadata'         => 'Métadonnées',
    'noElement'        => 'Aucun élément',
    'preview'          => 'Aperçu',
    'selectAll'        => 'Sélectionner tous les éléments',
    'setCreationDate'  => 'Définir les dates de création',
    'setGeoCoords'     => 'Définir les coordonées géo.',
    'setTags'          => 'Définir les mots-clés',
    'tags'             => 'Mots-clés',
    'updating'         => 'Mise à jour d\'éléments média',
    'view-short'       => 'Vue mini',
    'view-medium'      => 'Vue avec légendes',
    'view-full'        => 'Vue complète',

    'created' => 'Un élément  média  ajouté.|'.
                 '0  éléments médias ajoutés.',

    'deleted' => 'Un élément  média  supprimé.|'.
                 '0  éléments médias supprimés.',

    'inError' => 'Un élément  média  contient    des erreurs. Corrigez-le.|'.
                 '0  éléments médias contiennent des erreurs. Corrigez-les.',

    'unfound' => 'Un élément  média  ignoré  car non trouvé.|'.
                 '0  éléments médias ignorés car non trouvés.',

    'updated' => 'Un élément  média  mis à jour.|'.
                 '0  éléments médias mis à jour.',


    'placeholder' => [
        'caption'      => '',
        'creationDate' => 'AAAA-MM-JJ hh:mm:ss',
        'geoCoords'    => 'ex : 42.123456 , 0.987654',
        'tags'         => 'Séparateur = virgule',
    ],


    'setMeta' => [
        'creationDate' => 'Entrez la date de création à donner aux éléments sélectionnés.\n'.
                          'Le format doit être AAAA-MM-JJ hh:mm:ss (ex : 2013-10-26 12:42:00).',

        'geoCoords' => 'Entrez les coordonées geo. à donner aux éléments sélectionnés.\n'.
                       'Le format doit être « Latitude , Longitude » (ex : 42.123456 , 0.987654).',

        'tags' => 'Entrez les mots-clés à donner aux éléments sélectionnés.\n'.
                  'Le séparateur est la virgule (ex : Famille, Nature, Pique-nique).\n'.
                  "Préfixez d'un '+' pour ajouter et d'un '-' pour supprimer.",
    ],
]];
