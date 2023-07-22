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
      'name' => 'postq_list',
      'params' => array(
        'endpoint' => 'doubleplus/admin/queues',
        'unwrapData' => true,
        'requireSession'=> true,
        //'requires' => array('boardUri'),
        //'params' => 'querystring',
      ),
    ),
    array(
      'name' => 'admin_del',
      'params' => array(
        'endpoint' => 'doubleplus/admin/queues/:queueid',
        'method' => 'DELETE',
        'requireSession' => true,
        'unwrapData' => true,
        'requires' => array('queueid'),
        //'params' => 'params',
      ),
    ),
    array(
      'name' => 'admin_del',
      'params' => array(
        'endpoint' => 'doubleplus/admin/queues/:queueid',
        'method' => 'DELETE',
        'requireSession' => true,
        'unwrapData' => true,
        'requires' => array('queueid'),
        //'params' => 'params',
      ),
    ),
    array(
      'name' => 'admin_dels',
      'params' => array(
        'endpoint' => 'doubleplus/admin/queues',
        'method' => 'DELETE',
        'requireSession' => true,
        'unwrapData' => true,
        'requires' => array('ids'),
        // querystring or postdata
        'params' => 'querystring',
      ),
    ),
    /*
    array(
      'name' => 'admin_del_strings',
      'params' => array(
        'endpoint' => 'doubleplus/admin/queues/strings',
        'method' => 'DELETE',
        'requireSession' => true,
        'unwrapData' => true,
        //'requires' => array('queueid'),
        //'params' => 'params',
      ),
    ),
    */
  ),
);
?>
