<?php
return array(
  'name' => 'post_password',
  'version' => 1,
  'dependencies' => array('post/actions'),
  'settings' => array(
    array(
      'level' => 'bo', // constant?
      'location' => 'board', // /tab/group
      'addFields' => array(
        'delete_disallow' => array(
          'label' => 'Disallow user to delete their posts',
          'type'  => 'checkbox',
        ),
      )
    ),
  ),
  // similar to post_actions, if not the same...
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
