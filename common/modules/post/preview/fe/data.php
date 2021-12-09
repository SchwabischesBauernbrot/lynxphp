<?php
return array(
  array(
    'handlers' => array(
      array(
        'method'  => 'GET',
        'route'   => '/:board/preview/:id',
        'handler' => 'preview',
      ),
      /*
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
      // add preview link to post links
      array(
        'pipeline' => 'PIPELINE_POST_LINKS',
        'module' => 'post_link',
      ),
      /*
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
      */
    ),
  ),
);
?>
