<?php
return array(
  'name' => 'change_account',
  'version' => 1,
  'resources' => array(
    array(
      'name' => 'migrate_account',
      'params' => array(
        'endpoint' => 'opt/migrateAccount',
        'method' => 'POST',
        'sendSession' => true,
        'unwrapData' => true,
        'requires' => array('pk'),
        'params' => 'postdata',
      ),
    ),
  ),
);
?>