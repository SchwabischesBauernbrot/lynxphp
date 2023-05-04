<?php
return array(
  'name' => 'thread_reply_limit',
  'version' => 1,
  'settings' => array(
    array(
      'level' => 'bo', // constant?
      'location' => 'board', // /tab/group
      'addFields' => array(
        'reply_limit' => array(
          'label' => 'Reply limit',
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
