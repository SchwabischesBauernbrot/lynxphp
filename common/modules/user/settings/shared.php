<?php

// this data and functions used for all module php code
// function are automatically exported
// allow export of data as $shared in your handlers and modules

return array(
  'CategoryFields' => array(
    'post' => array(
      /*
      'name' => array(
        'label' => 'Default post name',
        'type'  => 'text',
        'placeholder' => 'anonymous',
      ),
      'postpass' => array(
        'label' => 'Default post password',
        'type'  => 'textpass',
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
        'default' => 'local time',
      ),
      'miltime' => array(
        'label' => 'Use 24h (military) time',
        'type'  => 'checkbox',
        'default' => false,
      ),
      'numreply' => array(
        'label' => 'Clicking on post number replies',
        'type'  => 'checkbox',
        'default' => false,
      ),
    ),
    'media' => array(
      'hover' => array(
        'label' => 'Hover to expand media',
        'type'  => 'checkbox',
        'default' => true,
      ),
      'hover' => array(
        'label' => 'Hover to expand media',
        'type'  => 'checkbox',
        'default' => true,
      ),
      /*
      'volume' => array(
        'label' => 'Default volume, enter number between 0 to 100',
        'type'  => 'integer',
      ),
      */
      'mute' => array(
        'label' => 'Mute audio on videos',
        'type'  => 'checkbox',
        'default' => true,
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
    ),
    'general' => array (
      'nojs' => array(
        'label' => 'Disable all JavaScript',
        'type'  => 'checkbox',
        'default' => false,
      ),
      /*
      'nsfw' => array(
        'label' => 'Show Not Safe For Work content',
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
      */
    ),
    // has to be set to make it a valid category
    'theme' => array (
    ),
  ),
);

?>