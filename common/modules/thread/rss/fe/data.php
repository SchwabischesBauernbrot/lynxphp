<?php

$fePkgs = array(
  array(
    'handlers' => array(
      array(
        'route'   => '/:uri/thread/:number.rss',
        'handler' => 'thread_rss',
        'cacheSettings' => array(
          'files' => array(
            '../common/modules/thread/rss/fe/views/rss.tmpl',
          ),
          'backend' => array(
            array('route' => 'opt/:boardUri/thread/:number.json'),
          ),
        ),
      ),
    ),
    'forms' => array(
      /*
      array(
        'route' => '/:uri/settings/banners/add',
        'handler' => 'add',
      ),
      array(
        'route' => '/:uri/settings/banners/:id/delete',
        'handler' => 'delete',
      ),
      */
    ),
    'modules' => array(
      // add RSS feed
      array(
        'pipeline' => 'PIPELINE_SITE_HEAD',
        'module' => 'site_head',
      ),
    ),
  ),
);
return $fePkgs;

?>
