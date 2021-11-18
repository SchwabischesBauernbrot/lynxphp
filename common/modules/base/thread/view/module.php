<?php
return array(
  'name' => 'thread_view',
  'version' => 1,
  'resources' => array(
    array(
      'name' => 'refresh',
      'params' => array(
        'endpoint' => 'opt/threadRefresh',
        'unwrapData' => true,
        'requires' => array('boardUri', 'thread', 'last'),
        'params' => 'querystring',
      ),
    ),
  ),
);
?>
