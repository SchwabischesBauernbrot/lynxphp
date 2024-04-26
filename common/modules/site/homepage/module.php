<?php
return array(
  // default homepage module, others could be made
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
        /*
        'showShortlist' => array(
          'label' => 'Show Board Short List',
          'type'  => 'checkbox',
          'default' => true,
        ),
        */
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
        'shortlistMode' => array(
          'label' => 'Short List mode',
          'type'  => 'select',
          'options' => array(
            // have to be strings for select to work
            '0' => 'Off',
            '2' => 'donkey',
            '1' => 'Custom', // use customBoardShortlistList
            // 2 software driven? 4 (include inactive versions)?
            // last post (latest activity), most posts (popular)
          )
        ),
        'customBoardShortlistList' => array(
          'label' => 'Short List Boards (comma separated list of board URIs)',
          'type'  => 'text',
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