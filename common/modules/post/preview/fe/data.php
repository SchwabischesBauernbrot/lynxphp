<?php
return array(
  array(
    'handlers' => array(
      array(
        'route'   => '/:board/preview/:id',
        'handler' => 'preview',
      ),
      /*
      array(
        'route'   => '/:uri/threads/:threadId/posts/:postid/preview.html',
        'handler' => 'preview',
      ),
      array(
        'route'   => '/:uri/posts/:postid/preview.html',
        'handler' => 'preview',
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
    ),
  ),
);
?>
