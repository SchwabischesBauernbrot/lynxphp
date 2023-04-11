<?php
return array(
  'name' => 'protect_floodcleanup',
  'version' => 1,
  /*
  'settings' => array(
    array(
      'level' => 'bo', // constant?
      'location' => 'board', // /tab/group
      'addFields' => array(
        'default_theme' => array(
          'label' => 'Default Theme',
          'type'  => 'select',
          'options' => $shared['themes'],
          //'optionsExec' => 'getThemes',
        ),
      )
    ),
  ),
  */
  'resources' => array(
    array(
      'name' => 'list',
      'params' => array(
        'endpoint' => 'doubleplus/boards/:uri/flooddata',
        'requireSession' => true,
        'unwrapData' => true,
        'requires' => array('uri'),
        //'params' => 'querystring',
      ),
    ),
  ),
);
?>
