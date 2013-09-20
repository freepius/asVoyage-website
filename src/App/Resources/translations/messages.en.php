<?php

/**
 * Summary :
 *  -> A list of various messages
 *  -> Admin
 *  -> Captcha
 *  -> Contact
 *  -> Error
 *  -> Filter
 *  -> Languages
 *  -> Literal numbers
 *  -> Map
 *  -> Months
 *  -> Navigation
 *  -> Placeholder
 */

return [
    //'Actions'                    => '',
    //'at'                         => '',
    //'Bad credentials'            => '',
    //'by date'                    => '',
    //'by tag'                     => '',
    //'Cancel'                     => '',
    //'Comments'                   => '',
    //'Contents under CC0 license' => '',
    //'Current trip'               => '',
    //'Delete'                     => '',
    //'Delete filter(s)'           => '',
    //'Edit'                       => '',
    //'Error'                      => '',
    'Error(s)'                   => "There is/are <b>0</b> error/s : at 1.",
    //'General'                    => '',
    //'Home'                       => '',
    //'Last published articles'    => '',
    //'Login'                      => '',
    //'Logout'                     => '',
    //'No'                         => '',
    //'none'                       => '',
    //'Older'                      => '',
    //'Other'                      => '',
    //'Password'                   => '',
    //'Post'                       => '',
    //'Posted by'                  => '',
    //'Preview'                    => '',
    //'Read'                       => '',
    //'Read more'                  => '',
    //'Run search'                 => '',
    //'Search'                     => '',
    //'See more articles'          => '',
    //'See more images'            => '',
    //'Submit'                     => '',
    //'They support us!'           => '',
    //'to'                         => '',
    //'Top'                        => '',
    //'Update'                     => '',
    //'Visit RayonRando.com'       => '',
    //'website'                    => '',
    //'Yes'                        => '',
    //'Younger'                    => '',

    // Admin
    'admin' => [
        'cacheClear'   => 'Clear the cache',
        'cacheCleared' => 'Cache cleared.',
        'home'         => 'Administration',
    ],

    // Captcha
    'captcha' => [
        'field' => 'Anti-spam system',

        'help' => '<i class="icon-warning-sign"></i> '.
            'Please enter the characters displayed in the picture to prevent automated spam systems.',

        'help.change' => 'Please click here to change the picture',
    ],

    // Contact
    'contact' => [
        'sent'  => 'Your pigeon carrier has been sent !',
        'title' => 'Contact us',

        'field' => [
            'captcha' => 'Anti-spam system',
            'email'   => 'Your email',
            'message' => 'Your message',
            'name'    => 'Who are you ?',
            'subject' => 'The subject',
        ],
    ],

    // Error
    'error' => [
        'go2home'   => 'Maybe you should <a href="/home">go back to home</a> ?!',
        'contactUs' => 'Or <a href="/contact">contact us</a> if the problem seems fishy...',

        'title' => [
            '404'   => 'Hu hu 404 ! You are lost !',
            'other' => 'Hu hu ! Fatal error !',
        ],

        'message' => [
            '404'   => 'The requested page could not be found.',
            'other' => 'We are sorry, but something went terribly wrong.',
        ],
    ],

    // Filter
    'filter' => [
        'tags'       => 'Filtered by tag <b>0</b>|'.
                        'Filtered by tags <b>0</b>',
        'year'       => "Filtered by year <b>0</b>",
        'year-month' => "Filtered by date <b>1 0</b>",
    ],

    // Languages
    'lang' => [
        'fr' => 'French',
        'en' => 'English',
    ],

    // Literal numbers
    'literal' => [
        '8'  => 'eight',
        '9'  => 'nine',
        '10' => 'ten',
        '11' => 'eleven',
        '12' => 'twelve',
        '13' => 'thirteen',
        '14' => 'forteen',
        '15' => 'fifteen',
    ],

    // Map
    'map' => [
        'home' => 'The map',

        'clusterLabel.other' => 'and OTHER_L other points...',
        'clusterLabel.all'   => 'Click to see the TOTAL_L points of the area.',
    ],

    // Months
    'month' => [
        '1'  => 'January',
        '2'  => 'February',
        '3'  => 'March',
        '4'  => 'April',
        '5'  => 'May',
        '6'  => 'June',
        '7'  => 'July',
        '8'  => 'August',
        '9'  => 'September',
        '10' => 'October',
        '11' => 'November',
        '12' => 'December',
    ],
    //'January'   => '',
    //'February'  => '',
    //'March'     => '',
    //'April'     => '',
    //'May'       => '',
    //'June'      => '',
    //'July'      => '',
    //'August'    => '',
    //'September' => '',
    //'October'   => '',
    //'November'  => '',
    //'December'  => '',

    // Navigation
    'nav' => [
        'about'     => 'About',
        'blog'      => 'Blog',
        'contact'   => 'Contact',
        'home'      => 'Home',
        'media'     => 'Our medias',
        'our-trips' => 'Our trips',
    ],

    // Placeholder
    'placeholder' => [
        'tags' => 'Tags separated by a comma',
    ],
];
