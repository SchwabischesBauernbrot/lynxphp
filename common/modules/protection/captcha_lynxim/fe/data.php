<?php

$fePkgs = array(
  array(
    'handlers' => array(
      /*
      array(
        'method'  => 'GET',
        'route'   => '/:uri/banners',
        'handler' => 'public_list',
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
      // enable captcha on form
      array(
        'pipeline' => 'PIPELINE_POST_FORM_FIELDS',
        'module' => 'post_field',
      ),
      // genereate field html
      array(
        'pipeline' => 'PIPELINE_FORM_CAPTCHA',
        'module' => 'captcha',
      ),
      // verify captcha
      array(
        'pipeline' => 'PIPELINE_POST_VALIDATION',
        'module' => 'post_validate',
      ),
      // clean up captcha
      array(
        'pipeline' => 'PIPELINE_AFTER_WORK',
        'module' => 'captcha_clean',
      ),
      // allow BOs to control this
      array(
        'pipeline' => 'PIPELINE_BOARD_SETTING_GENERAL',
        'module' => 'board_settings',
      ),

    ),
  ),
);
return $fePkgs;

?>
