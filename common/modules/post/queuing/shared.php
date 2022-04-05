<?php

// this data and functions used for all module php code

// function are automatically exported

// allow export of data as $shared in your handlers and modules
return array(
  'board_settings_fields' => array(
    'queueing_mode' => array(
      'label' => 'New Posts',
      'type'  => 'select',
      'options' => array(
        '' => 'immediate',
        'community' => 'community queue',
        //'moderator' => 'moderator queue',
      )
    ),
  ),
  'community_moderate_fields' => array(
    'captcha' => array(
      'label' => 'CAPTCHA',
      'type'  => 'captcha',
    ),
  ),
);

?>