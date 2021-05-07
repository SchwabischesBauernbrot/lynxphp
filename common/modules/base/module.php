<?php
return array(
  'name' => 'base',
  'version' => 1,
  'resources' => array(
    array(
      'name' => 'work',
      'params' => array(
        'endpoint' => 'opt/work',
      ),
    ),
    // admin settings
    array(
      'name' => 'settings',
      'params' => array(
        'endpoint' => 'opt/settings',
        'sendSession' => true,
        'unwrapData' => true,
        'requires' => array(),
        'params' => 'querystring',
      ),
    ),
    array(
      'name' => 'save_settings',
      'params' => array(
        'endpoint' => 'opt/settings',
        'method' => 'POST',
        'requireSession' => true,
        'unwrapData' => true,
        'requires' => array('logo'),
        'params' => 'postdata',
      ),
    ),
    // login
    array(
      'name' => 'get_challenge',
      'params' => array(
        'endpoint' => 'opt/getChallenge',
        'method' => 'POST',
        'sendIP' => true,
        'unwrapData' => true,
        'requires' => array('i'),
        'params' => 'postdata',
      ),
    ),
    // should only work over TLS unless same ip/localhost
    array(
      'name' => 'verify_account',
      'params' => array(
        'endpoint' => 'opt/verifyAccount',
        'method' => 'POST',
        'sendIP' => true,
        'unwrapData' => true,
        // u, p are optional
        'requires' => array('chal', 'sig'),
        'params' => 'postdata',
      ),
    ),
  ),
);
?>