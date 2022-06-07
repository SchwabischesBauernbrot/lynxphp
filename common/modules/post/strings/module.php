<?php
return array(
  'name' => 'module_subname',
  'version' => 1,
  'resources' => array(
    array(
      'name' => 'string_list',
      'params' => array(
        'endpoint' => 'opt/admin/strings',
        'unwrapData' => true,
        'requireSession'=> true,
        //'requires' => array('boardUri'),
        //'params' => 'querystring',
      ),
    ),
    array(
      'name' => 'add',
      'params' => array(
        'endpoint' => 'opt/admin/strings',
        'method' => 'POST',
        'requireSession' => true,
        'unwrapData' => true,
        'requires' => array('strings', 'action'),
        'params' => 'postdata',
      ),
    ),
  ),
);
?>
