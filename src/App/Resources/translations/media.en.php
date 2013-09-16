<?php

return ['media' => [

    'addLocal'         => 'Add local elements',
    'caption'          => 'Caption',
    'creation'         => 'Creation of media elements',
    'creationDate'     => 'Creation date',
    'counter'          => '<span class="counter-selected-element">0</span> '.
                          'on <span class="counter-elements">0</span> selected',
    'deleting.confirm' => 'Do you confirm the deleting of 0 media elements ?',
    'elements'         => 'Media elements',
    'file'             => 'File',
    'geoCoords'        => 'Geo. coordinates',
    'home'             => 'Media platform',
    'metadata'         => 'Metadata',
    'noElement'        => 'No element',
    'preview'          => 'Preview',
    'seeOriginal'      => 'See the original file',
    'selectAll'        => 'Select all elements',
    'setCreationDate'  => 'Set the creations dates',
    'setGeoCoords'     => 'Set the geo. coordinates',
    'setTags'          => 'Set the tags',
    'tags'             => 'Tags',
    'updating'         => 'Updating of media elements',
    'view-short'       => 'Short view',
    'view-medium'      => 'View with captions',
    'view-full'        => 'Full view',

    'created' => 'One media element  added.|'.
                 '0   media elements added.',

    'deleted' => 'One media element  deleted.|'.
                 '0   media elements deleted.',

    'inError' => 'One media element  has  errors. Please correct it.|'.
                 '0   media elements have errors. Please correct them.',

    'unfound' => 'One media element  ignored because not found.|'.
                 '0   media elements ignored because not found.',

    'updated' => 'One media element  updated.|'.
                 '0   media elements updated.',


    'placeholder' => [
        'caption'      => '',
        'creationDate' => 'YYYY-MM-DD hh:mm:ss',
        'geoCoords'    => 'eg: 42.123456 , 0.987654',
        'tags'         => 'Comma-separated',
    ],


    'setMeta' => [
        'creationDate' => 'Please enter the creation date to give to selected elements.\n'.
                          'Date format must be YYYY-MM-DD hh:mm:ss (eg: 2013-10-26 12:42:00).',

        'geoCoords' => 'Please enter the geo. coordinates to give to selected elements.\n'.
                       'Coordinates format must be "Longitude , Latitude" (eg: 42.123456 , 0.987654).',

        'tags' => 'Please enter the tags to give to selected elements.\n'.
                  'Tags must be comma-separated (eg: Family, Nature, Picnic).\n'.
                  "Prefix with '+' to add and with '-' to delete.",
    ],
]];
