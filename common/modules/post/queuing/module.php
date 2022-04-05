<?php
return array(
  'name' => 'post_queueing',
  'version' => 1,
  'resources' => array(
    array(
      'name' => 'get_pending_post',
      'params' => array(
        'endpoint' => 'doubleplus/post_queue',
        'sendSession' => true,
        'sendIP' => true,
        'unwrapData' => true,
        'requires' => array('boardUri'),
        'params' => 'querystring',
      ),
    ),
    array(
      'name' => 'vote_pending_post',
      'params' => array(
        'endpoint' => 'doubleplus/post_queue/vote',
        'method' => 'POST',
        'sendSession' => true,
        'sendIP' => true,
        'unwrapData' => true,
        'requires' => array('boardUri'),
        'params' => 'querystring',
      ),
    ),
  ),
);
?>
