<?php
return array(
  'name' => 'thread_autosage',
  'version' => 1,
  'settings' => array(
    array(
      'level' => 'bo', // constant?
      'location' => 'board', // /tab/group
      'addFields' => array(
        'bump_limit' => array(
          'label' => 'Bump limit (auto-sage after)',
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
