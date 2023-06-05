<?php
return array(
  'name' => 'base_board_settings',
  'version' => 1,
  'portals' => array(
    'boardSettings' => array(
      // parameters?
      //'be_pipelines' => array('PIPELINE_BE_BOARD_SETTING_PORTAL'),
      // or we could put them in fe/data.php
      // but the functions that execute this pipeline is here...
      'fePipelines' => array('PIPELINE_BOARD_SETTING_HEADER_TMPL'),
      'requires' => array('boardUri'),
      // options for the future
      //'resources' => array(
        // we only need one, w/e it is
        // don't need to pollute the route space
        // endpoints
        // we could have a generic one
        // module?
        // what structure data do we need?
        // a guess a true works for now
        // but we don't have a need for a false...
      //),
    ),
  ),
  'resources' => array(
    array(
      'name' => 'list',
      'params' => array(
        'endpoint' => 'lynx/setBoardSettings.js',
        'requireSession' => true,
        'unwrapData' => true,
        'requires' => array('boardUri'),
        'params' => 'querystring',
      ),
    ),
    array(
      'name' => 'save_settings',
      'params' => array(
        'endpoint' => 'lynx/setBoardSettings.js',
        'method' => 'POST',
        'requireSession' => true,
        'unwrapData' => true,
        'requires' => array('boardUri'),
        'params' => 'querystring',
      ),
    ),
/*
    array(
      'name' => 'add',
      'params' => array(
        'endpoint' => 'lynx/createBanners',
        'method' => 'POST',
        'sendSession' => true,
        'unwrapData' => true,
        'requires' => array('boardUri'),
        'params' => array(
          'querystring' => 'boardUri',
          'formData' => 'files',
        ),
      ),
    ),
    array(
      'name' => 'del',
      'params' => array(
        'endpoint' => 'lynx/deleteBanner',
        'method' => 'POST',
        'sendSession' => true,
        'unwrapData' => true,
        'requires' => array('bannerId'),
        'params' => 'querystring',
      ),
    ),
*/
  ),
  'settings' => array(
    array(
      'level' => 'bo', // constant?
      'location' => 'board', // /tab/group
      'addFields' => array(
        'uri' => array(
          'label' => 'URI',
          'type'  => 'text',
        ),
        'title' => array(
          'label' => 'Title',
          'type'  => 'text',
        ),
        'description' => array(
          'label' => 'Description',
          'type'  => 'text',
        ),
        'settings_nsfw' => array(
          'label' => 'Allow Not Safe For Work content',
          'type'  => 'checkbox',
        ),
        'settings_notpublic' => array(
          'label' => 'Not publicly indexed',
          'type'  => 'checkbox',
        ),
        // disable thread-wise ids
        // require file for new threads
        // don't allow users to delete their post
        // allow code tags
        // force anon
        // early404
        // location flags
        // unique files
        // unique post
        // hourly thread limit
        // enable captcha after threads per hour hit
        // tags
        // file whitelist?
        // filters
        // custom css?
        //
        // language
        // custom spoiler
        // attachment deleted
        // overboard icon

      )
    ),
  ),

);
?>