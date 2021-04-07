<?php
return array(
  array(
    'handlers' => array(
      array(
        'method'  => 'GET',
        'route'   => '/.youtube/vi/:videoid/default.jpg',
        'handler' => 'ytthumb',
      ),
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
        'module' => 'insert',
      ),
    ),
  ),
);
?>
