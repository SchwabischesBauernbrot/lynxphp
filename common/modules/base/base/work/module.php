<?php
return array(
  'name' => 'base_work',
  'version' => 1,
  'resources' => array(
    array(
      'name' => 'work',
      'params' => array(
        'endpoint' => 'opt/work',
      ),
    ),
    array(
      'name' => 'workq_summary',
      'params' => array(
        'endpoint' => 'doubleplus/admin/workq',
        'unwrapData' => true,
        'requireSession'=> true,
        //'requires' => array('boardUri'),
        //'params' => 'querystring',
      ),
    ),
  ),
);
?>