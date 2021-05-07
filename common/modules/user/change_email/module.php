<?php
return array(
  'name' => 'change_email',
  'version' => 1,
  'resources' => array(
    array(
      'name' => 'change_email',
      'params' => array(
        'endpoint' => 'opt/changeEmail',
        'method' => 'POST',
        'sendSession' => true,
        'unwrapData' => true,
        'requires' => array('em'),
        'params' => 'postdata',
      ),
    ),
  ),
);
?>