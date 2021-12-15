<?php
return array(
  'name' => 'thread_cyclical',
  'version' => 1,
  'resources' => array(
    array(
      'name' => 'cyclic',
      'params' => array(
        'endpoint' => 'opt/:uri/thread/:threadNum/cyclic',
        'method' => 'POST',
        'requireSession' => true,
        'unwrapData' => true,
        'requires' => array('uri', 'threadNum'),
        //'params' => 'querystring',
      ),
    ),
    array(
      'name' => 'uncyclic',
      'params' => array(
        'endpoint' => 'opt/:uri/thread/:threadNum/cyclic',
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