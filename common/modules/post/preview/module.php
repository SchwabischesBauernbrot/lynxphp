<?php
return array(
  'name' => 'post_preview',
  'version' => 1,
  'resources' => array(
    array(
      'name' => 'preview',
      'params' => array(
        'endpoint' => 'lynx/:board/preview/:id',
        //'method' => 'POST',
        //'sendSession' => true,
        //'unwrapData' => true,
        //'params' => 'postdata',
      ),
    ),
  ),
);
?>
