<?php

$params = $getModule();

// $io is fields
$io['settings_captcha_mode'] = array(
  'label' => 'CAPTCHA mode',
  'type'  => 'select',
  'options' => array(
    'no' => 'No captcha',
    'threads' => 'Only for threads',
    'posts' => 'For all posts',
  ),
);

?>