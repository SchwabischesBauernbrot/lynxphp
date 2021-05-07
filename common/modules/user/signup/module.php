<?php
return array(
  'name' => 'signup',
  'version' => 1,
  'resources' => array(
    array(
      'name' => 'register_account',
      'params' => array(
        'endpoint' => 'opt/registerAccount',
        'method' => 'POST',
        'unwrapData' => true,
        //'sendSession'=> true,
        // email is optional
        'requires' => array('chal', 'sig'),
        'params' => 'postdata',
      ),
    ),
  ),
);
?>