<?php
return array(
  'name' => 'thread_pin',
  'version' => 1,
  'resources' => array(
    array(
      'name' => 'pin',
      'params' => array(
        'endpoint' => 'opt/:uri/thread/:threadNum/pin',
        'method' => 'POST',
        'requireSession' => true,
        'unwrapData' => true,
        'requires' => array('uri', 'threadNum'),
        //'params' => 'querystring',
      ),
    ),
    array(
      'name' => 'unpin',
      'params' => array(
        'endpoint' => 'opt/:uri/thread/:threadNum/pin',
        'method' => 'DELETE',
        'requireSession' => true,
        'unwrapData' => true,
        'requires' => array('uri', 'threadNum'),
        //'params' => 'querystring',
      ),
    ),
  ),
);
?>