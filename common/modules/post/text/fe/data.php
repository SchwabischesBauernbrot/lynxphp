<?php
return array(
  array(
    'handlers' => array(
      /*
      array(
        'method'  => 'GET',
        'route'   => '/:uri/banners',
        'handler' => 'public_list',
      ),
      array(
        'method'  => 'GET',
        'route'   => '/:uri/settings/banners',
        'handler' => 'settings_list',
      ),
      */
    ),
    'forms' => array(
      /*
      array(
        'route' => '/:uri/settings/banners/add',
        'handler' => 'add',
      ),
      */
    ),
    'modules' => array(
      // format text
      array(
        'pipeline' => 'PIPELINE_POST_TEXT_FORMATTING',
        'module' => 'format',
      ),
      array(
        'pipeline' => 'PIPELINE_POST_PREPROCESS',
        'module' => 'preformat',
      ),
      array(
        'pipeline' => 'PIPELINE_POST_POSTPREPROCESS',
        'module' => 'postpreformat',
      ),
    ),
  ),
);
?>
