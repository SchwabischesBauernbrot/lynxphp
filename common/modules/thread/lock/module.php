<?php
return array(
  'name' => 'thread_lock',
  'version' => 1,
  'resources' => array(
    array(
      'name' => 'lock',
      'params' => array(
        'endpoint' => 'opt/:uri/thread/:threadNum/lock',
        'method' => 'POST',
        'requireSession' => true,
        'unwrapData' => true,
        'requires' => array('uri', 'threadNum'),
        //'params' => 'querystring',
      ),
    ),
    array(
      'name' => 'unlock',
      'params' => array(
        'endpoint' => 'opt/:uri/thread/:threadNum/lock',
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