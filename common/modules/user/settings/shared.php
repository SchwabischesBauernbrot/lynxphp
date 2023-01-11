<?php

// this data and functions used for all module php code
// function are automatically exported
// allow export of data as $shared in your handlers and modules

return array(
  'CategoryFields' => array(
    'general' => array (
      'hover' => array(
        'label' => 'Hover to expand media',
        'type'  => 'checkbox',
      ),
      'mute' => array(
        'label' => 'Mute audio on videos',
        'type'  => 'checkbox',
      ),
      'videoloop' => array(
        'label' => 'Loop videos by default',
        'type'  => 'checkbox',
        'default' => true,
      ),
      'audioloop' => array(
        'label' => 'Make audio player loop by default',
        'type'  => 'checkbox',
        'default' => false,
      ),
/*
      'volume' => array(
        'label' => 'Default volume, enter number between 0 to 100',
        'type'  => 'integer',
      ),
      'name' => array(
        'label' => 'Default post name',
        'type'  => 'text',
        'placeholder' => 'anonymous',
      ),
      'postpass' => array(
        'label' => 'Default post password',
        'type'  => 'textpass',
      ),
      'nsfw' => array(
        'label' => 'Show Not Safe For Work content',
        'type'  => 'checkbox',
      ),
      'noncolorids' => array(
        'label' => 'Non color IDs',
        'type'  => 'checkbox',
      ),
      */
      'time' => array(
        'label' => 'Display time as',
        'type'  => 'select',
        'options' => array(
          'utc time',
          'local time',
          'relative time',
        ),
      ),
      'miltime' => array(
        'label' => 'Use 24h (military) time',
        'type'  => 'checkbox',
      ),
      'nojs' => array(
        'label' => 'Disable all JavaScript',
        'type'  => 'checkbox',
      ),
      /*
      // should be per board
      'disablecustomcss' => array(
        'label' => 'Disable board custom CSS',
        'type'  => 'checkbox',
      ),
      'sitecustomcss' => array(
        'label' => 'Site custom CSS',
        'type'  => 'textarea',
      ),
      */
    ),
    'theme' => array (
    ),
  ),
);

?>