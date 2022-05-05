<?php
return array(
  'name' => 'user_boards',
  'version' => 1,
  'resources' => array(
    array(
      'name' => 'create_board',
      'params' => array(
        'endpoint' => 'lynx/createBoard',
        'method' => 'POST',
        'sendSession' => true,
        'unwrapData' => true,
        'requires' => array('boardUri', 'boardName', 'boardDescription'),
        'params' => 'postdata',
      ),
    ),
  ),
);
?>