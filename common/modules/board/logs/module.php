<?php
return array(
  'name' => 'board_logs',
  'version' => 1,
  'resources' => array(
    array(
      'name' => 'list',
      'params' => array(
        'endpoint' => 'lynx/logs.js',
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
);
?>