<?php
return array(
  'name' => 'last_posts',
  'version' => 1,
  'resources' => array(
    array(
      'name' => 'last_posts',
      'params' => array(
        'endpoint' => 'opt/:boardUri/thread/:thread/last50',
        'unwrapData' => true,
        'requires' => array('boardUri', 'thread'),
        //'params' => 'params',
      ),
    ),
  ),
);
?>
