<?php
return array(
  'name' => 'post_files',
  'version' => 1,
  'resources' => array(
    array(
      'name' => 'thumbnail_ready',
      'params' => array(
        'endpoint' => 'opt/boards/:uri/posts/:pid/thumbnailReady',
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