<?php
return array(
  'name' => 'media_thumbnail',
  'version' => 1,
  'resources' => array(
    // maybe belongs in post? but it is media related output...
    array(
      'name' => 'thumbnail_ready',
      'params' => array(
        'endpoint' => 'opt/boards/:uri/posts/:pid/thumbnailReady',
        'expectJson' => true,
        'requires' => array('uri', 'pid'),
      ),
    ),
    array(
      'name' => 'media_debug',
      'params' => array(
        'endpoint' => 'opt/boards/:uri/posts/:pid/media_debug',
        'expectJson' => true,
        'requires' => array('uri', 'pid'),
      ),
    ),
    /*
    array(
      'name' => 'settings',
      'params' => array(
        'endpoint' => 'opt/settings',
        'sendSession' => true,
        'unwrapData' => true,
        'requires' => array(),
        'params' => 'querystring',
      ),
    ),
    */
  ),
);
?>