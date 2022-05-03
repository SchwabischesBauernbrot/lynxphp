<?php
return array(
  'name' => 'board_list',
  'version' => 1,
  'resources' => array(
    array(
      'name' => 'list',
      'params' => array(
        'endpoint' => 'opt/boards.json',
        // probably could...
        //'unwrapData' => true,
        'expectJson' => true,
        'sendSession' => true,
        // we do have to inform where to put them
        'params' => 'querystring',
      ),
    ),
  ),
);
?>