<?php
return array(
  'name' => 'board_over',
  'version' => 1,
  'resources' => array(
    array(
      'name' => 'overboard',
      'params' => array(
        'endpoint' => 'lynx/overboard.json',
        'sendSession' => true,
        //'querystring' => array('portals' => 'overboard'),
        'unwrapData' => true,
        //'requires' => array('boardUri'),
        //'params' => 'querystring',
      ),
      'cacheSettings' => array(
        'databaseTables' => array('overboard_threads'),
      ),
    ),
  ),
);
?>
