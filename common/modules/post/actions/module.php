<?php
// maybe should be a base module
// keeping it out of base incase people want to manage their sites
// in a different way or with different approaches/systems
// so they can just turn this off and build better
return array(
  'name' => 'post_actions',
  'version' => 1,
  'resources' => array(
    array(
      'name' => 'content_actions',
      'params' => array(
        // form api
        // POST multipart/form
        // https://gitgud.io/InfinityNow/LynxChan/-/blob/master/doc/Form.txt#L266
        'endpoint' => 'lynx/contentActions.js',
        'method' => 'POST',
        // this is a lynxchan ep
        //'unwrapData' => true,
        'expectJson' => true,
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