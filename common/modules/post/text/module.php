<?php
return array(
  'name' => 'post_text',
  'version' => 1,
  'resources' => array(
    array(
      'name' => 'boardthreadlookup',
      'params' => array(
        'endpoint' => 'opt/boardthreadlookup',
        'method' => 'POST',
        'sendSession' => true,
        'unwrapData' => true,
        'params' => 'postdata',
      ),
    ),
  ),
);
?>
