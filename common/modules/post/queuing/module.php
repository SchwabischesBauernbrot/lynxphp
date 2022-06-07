<?php
return array(
  'name' => 'post_queueing',
  'version' => 1,
  'resources' => array(
    array(
      'name' => 'get_settings',
      'params' => array(
        'endpoint' => 'doubleplus/boards/:boardUri/settings/post_queue',
        'requireSession' => true,
        'unwrapData' => true,
        'requires' => array('boardUri'),
      ),
    ),
    array(
      'name' => 'save_settings',
      'params' => array(
        'endpoint' => 'doubleplus/boards/:boardUri/settings/post_queue',
        'method' => 'POST',
        'requireSession' => true,
        'unwrapData' => true,
        'requires' => array('boardUri'),
      ),
    ),
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
    array(
      'name' => 'queue_list',
      'params' => array(
        'endpoint' => 'opt/admin/queues',
        'unwrapData' => true,
        'requireSession'=> true,
        //'requires' => array('boardUri'),
        //'params' => 'querystring',
      ),
    ),
  ),
);
?>
