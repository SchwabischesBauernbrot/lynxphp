<?php
return array(
  'name' => 'board_catalog',
  'version' => 1,
  'resources' => array(
    // https://a.4cdn.org/po/catalog.json
    array(
      'name' => 'catalog',
      'params' => array(
        'endpoint' => 'opt/:boardUri/catalog.json',
        'unwrapData' => true,
        'requires' => array('boardUri'),
        //'params' => 'querystring',
      ),
    ),
  ),
);
?>
