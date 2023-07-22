<?php
return array(
  'name' => 'hyperfy',
  'version' => 1,
  'settings' => array(
    array(
      'level' => 'bo', // constant?
      'location' => 'hyperfy', // /tab/group
      'locationLabel' => 'Hyperfy options',
      'addFields' => array(
        'hyperfy_uri' => array(
          'label' => 'Hyperfy URI',
          'type'  => 'text',
        ),
        'hyperfy_label' => array(
          'label' => 'Board Nav Label',
          'type'  => 'text',
          'default' => 'Multiplayer',
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
