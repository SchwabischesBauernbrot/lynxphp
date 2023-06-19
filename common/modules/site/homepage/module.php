<?php
return array(
  'name' => 'site_homepage',
  'version' => 1,
  'settings' => array(
    array(
      'level' => 'admin', // constant?
      'location' => 'homepage', // /tab/group
      'addFields' => array(
        'showSiteName' => array(
          'label' => 'Show Site Name',
          'type'  => 'checkbox',
          'default' => true,
        ),
        'showWelcome' => array(
          'label' => 'Show Welcome to SITE NAME',
          'type'  => 'checkbox',
          'default' => true,
        ),
        'slogan' => array(
          'label' => 'Site Slogan',
          'type'  => 'text',
        ),
        'showSlogan' => array(
          'label' => 'Show Slogan',
          'type'  => 'checkbox',
          'default' => true,
        ),
        'showLogo' => array(
          'label' => 'Show Logo',
          'type'  => 'checkbox',
          'default' => true,
        ),
        'showShortlist' => array(
          'label' => 'Show Board Short List',
          'type'  => 'checkbox',
          'default' => true,
        ),
        // SFW/NSFW?
        'showRecentImages' => array(
          'label' => 'Show Recent Images (All)',
          'type'  => 'checkbox',
          'default' => true,
        ),
        // threads/replies/all?
        'showRecentPosts' => array(
          'label' => 'Show Recent Posts',
          'type'  => 'checkbox',
          'default' => true,
        ),
      )
    ),
  ),
  'resources' => array(
    array(
      'name' => 'homepage',
      'params' => array(
        'endpoint' => 'opt/homepage.json',
        'unwrapData' => true,
        'sendSession' => true,
        'cacheSettings' => array(
          // board table should be updated on new post
          // user settings?
          'databaseTables' => array('boards', 'site_settings'),
          //'files' => array(),
        ),
      ),
    ),
  ),
);
?>