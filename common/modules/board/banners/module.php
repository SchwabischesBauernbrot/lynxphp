<?php
return array(
  'name' => 'board_banners',
  'version' => 1,
  'resources' => array(
    array(
      'name' => 'random',
      'params' => array(
        'endpoint' => 'lynx/randomBanner',
        'unwrapData' => true,
        'requires' => array('boardUri'),
        'params' => 'querystring',
        'dontCache' => true,
      ),
    ),
    array(
      'name' => 'list',
      'params' => array(
        'endpoint' => 'lynx/bannerManagement',
        'unwrapData' => true,
        'requires' => array('boardUri'),
        'params' => 'querystring',
      ),
    ),
    array(
      'name' => 'add',
      'params' => array(
        'endpoint' => 'lynx/createBanners',
        'method' => 'POST',
        'requireSession' => true,
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
        'requireSession' => true,
        'unwrapData' => true,
        'requires' => array('boardUri', 'bannerId'),
        'params' => array (
          'querystring' => 'boardUri',
          'formData' => 'bannerId',
        ),
      ),
    ),
  ),
);
?>
