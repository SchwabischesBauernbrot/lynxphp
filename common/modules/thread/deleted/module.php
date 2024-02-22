<?php
return array(
  'name' => 'thread_delete',
  'version' => 1,
  'settings' => array(
    array(
      'level' => 'bo', // constant?
      'location' => 'thread', // /tab/group
      'locationLabel' => 'Thread settings',
      'addFields' => array(
        'vaccum_threads' => array(
          'label' => 'nuke deleted threads after 30 days',
          'type'  => 'checkbox',
        ),
      )
    ),
  ),
  'resources' => array(
    array(
      'name' => 'list',
      'params' => array(
        'endpoint' => 'doubleplus/:uri/threads/deleted',
        'unwrapData' => true,
        'sendSession' => true,
        'requires' => array('uri'),
        //'params' => 'querystring',
      ),
    ),
  ),
);
?>
