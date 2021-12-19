<?php
return array(
  'name' => 'board_over',
  'version' => 1,
  'resources' => array(
    array(
      'name' => 'overboard',
      'params' => array(
        'endpoint' => 'lynx/overboard.json',
        'unwrapData' => true,
        //'requires' => array('boardUri'),
        //'params' => 'querystring',
      ),
    ),
  ),
);
?>
