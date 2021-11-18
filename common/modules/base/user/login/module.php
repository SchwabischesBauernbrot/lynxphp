<?php
return array(
  'name' => 'base_user_login',
  'version' => 1,
  'resources' => array(
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