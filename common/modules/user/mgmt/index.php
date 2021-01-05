<?php

$package = new package('user_mgmt', 1, __dir__);

// be/handler/list
$package->addResource('list', array(
  'endpoint' => 'opt/admin/users',
  'unwrapData' => true,
  'sendSession'=> true,
  /*
  'requires' => array(
    'boardUri'
  ),
  'params' => 'querystring',
  */
));

return $package;

?>