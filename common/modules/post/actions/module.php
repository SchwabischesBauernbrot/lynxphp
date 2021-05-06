<?php
return array(
  'name' => 'post_actions',
  'version' => 1,
  'resources' => array(
    array(
      'name' => 'content_actions',
      'params' => array(
        'endpoint' => 'lynx/contentActions.js',
        'method' => 'POST',
        'unwrapData' => true,
        'sendIP' => true,
        'sendSession' => true,
        'requires' => array('action'),
        'params' => 'querystring',
      ),
    ),
    array(
      'name' => 'open_reports',
      'params' => array(
        'endpoint' => 'lynx/openReports.js',
        'method' => 'GET',
        'unwrapData' => true,
        'requires' => array(),
        'params' => 'querystring',
      ),
    ),
    array(
      'name' => 'close_reports',
      'params' => array(
        'endpoint' => 'lynx/closeReports.js',
        'method' => 'POST',
        'unwrapData' => true,
        'requireSession' => true,
        'requires' => array('banTarget', 'closeAllFromReporter', 'deleteContent'),
        'params' => 'querystring',
      ),
    ),
  ),
);
?>