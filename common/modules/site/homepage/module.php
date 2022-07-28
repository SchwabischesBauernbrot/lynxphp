<?php
return array(
  'name' => 'site_homepage',
  'version' => 1,
  'resources' => array(
    array(
      'name' => 'homepage',
      'params' => array(
        'endpoint' => 'opt/homepage.json',
        'unwrapData' => true,
        'sendSession' => true,
        'cacheSettings' => array(
          // FIXME: all public posts...
          // user settings?
          'databaseTables' => array('boards', 'site_settings'),
          //'files' => array(),
        ),
      ),
    ),
  ),
);
?>