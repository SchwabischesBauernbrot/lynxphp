<?php

/*
getBoardByUri($uri)
getBoardSetting($field)

// collect all these events don't need to put into a database
createBoardSetting($field, $value)

getBoardsWith($field, $value)
getBoardSettingForm($uri)
*/


function getPostsModel($boardUri) {
  global $db;
  $public_post_model = array(
    'name' => 'board_' . $boardUri . '_public_post',
    //'indexes' => array('boardUri'),
    'fields' => array(
      // can just be postid...
      //'no' => array('type'=>'integer'),
      'threadid' => array('type'=>'int'),
      'resto' => array('type'=>'int'),
      'sticky' => array('type'=>'bool'),
      'closed' => array('type'=>'bool'),
      // 'now' => array('type'=>'integer'),
      //'time' => array('type'=>'integer'),
      'name' => array('type'=>'str', 'length'=>128),
      'trip' => array('type'=>'str', 'length'=>128),
      'capcode' => array('type'=>'str', 'length'=>32),
      'country' => array('type'=>'str', 'length'=>2),
      //'country_name' => array('type'=>'string', 'length'=>128),
      'sub' => array('type'=>'str', 'length'=>128),
      'com' => array('type'=>'text'),
    )
  );
  $db->autoupdate($public_post_model);
  return $public_post_model;
}

?>
