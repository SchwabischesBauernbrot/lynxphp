<?php

// this data and functions used for all module php code

// function are automatically exported

// allow export of data as $shared in your handlers and modules
return array(
  'fields' => array(
    'current_theme' => array(
      'label' => 'Theme',
      'type'  => 'select',
      'options' => array(
        'default' => 'Default',
        'yotsuba-b' => 'Yotsuba-B',
        'yotsuba' => 'Yotsuba',
        'amoled' => 'Amoled',
        'army-green' => 'Army-green',
        'cancer' => 'Cancer',
        'chaos' => 'Chaos',
        'choc' => 'Choc',
        'darkblue' => 'Dark Blue',
        'gurochan' => 'Gurochan',
        'lain' => 'Lain',
        'miku' => 'Miku',
        'mushroom' => 'Mushroom',
        'navy' => 'Navy',
        'pink' => 'Pink',
        'rei-zero' => 'Robot',
        'solarized-dark' => 'Solarized Dark',
        'solarized-light' => 'Solarized Light',
        'tempus-cozette' => 'Tempus Cozette',
        'tomorrow' => 'Tomorrow',
        'tomorrow2' => 'Tomorrow 2',
        'vapor' => 'Vapor',
        'win95' => 'Windows 95',
        'snerx' => 'SnerxWerx',
      ),
    ),
    'code_theme' => array(
      'label' => 'Code Theme',
      'type'  => 'select',
      'options' => array(),
    ),
    'name' => array(
      'label' => 'Default post name',
      'type'  => 'text',
    ),
    'postpass' => array(
      'label' => 'Default post password',
      'type'  => 'password',
    ),
    'volume' => array(
      'label' => 'Default volume, enter number between 0 to 100',
      'type'  => 'integer',
    ),
    'nsfw' => array(
      'label' => 'Show Not Safe For Work content',
      'type'  => 'checkbox',
    ),
    'noncolorids' => array(
      'label' => 'Non color IDs',
      'type'  => 'checkbox',
    ),
    'time' => array(
      'label' => 'Display time as',
      'type'  => 'select',
      'options' => array(),
    ),
    'miltime' => array(
      'label' => 'Use 24h (military) time',
      'type'  => 'checkbox',
    ),
    'nojs' => array(
      'label' => 'Disable all JavaScript',
      'type'  => 'checkbox',
    ),
    // should be per board
    'disablecustomcss' => array(
      'label' => 'Disable board custom CSS',
      'type'  => 'checkbox',
    ),
    'sitecustomcss' => array(
      'label' => 'Site custom CSS',
      'type'  => 'textarea',
    ),
    /*
    'logo' => array(
      'label' => 'Site Logo',
      'type'  => 'image',
    ),
    */
  ),
);

?>