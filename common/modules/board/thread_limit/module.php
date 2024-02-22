<?php
return array(
  'name' => 'board_thread_limit',
  'version' => 1,
  'settings' => array(
    array(
      'level' => 'bo', // constant?
      'location' => 'board', // /tab/group
      'addFields' => array(
        'thread_limit' => array(
          'label' => 'Thread limit',
          'type'  => 'number',
        ),
      )
    ),
  ),
  'resources' => array(
    /*
    array(
      'name' => 'random',
      'params' => array(
        'endpoint' => 'lynx/randomBanner',
        'unwrapData' => true,
        'requires' => array('boardUri'),
        'params' => 'querystring',
      ),
    ),
    */
  ),
);
?>
