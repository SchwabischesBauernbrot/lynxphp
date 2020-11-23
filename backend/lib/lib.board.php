<?php

/*
getBoardByUri($uri)
getBoardSetting($field)

// collect all these events don't need to put into a database
createBoardSetting($field, $value)

getBoardsWith($field, $value)
getBoardSettingForm($uri)
*/

function getBoardByUri($boardUri) {
  global $db, $models;
  $res = $db->find($models['board'], array('criteria'=>array(
      array('uri', '=', $boardUri),
  )));
  if (!$res) return;
  $row = mysqli_fetch_assoc($res);
  if ($row['json']) $row['json'] = json_decode($row['json'], true);
  return $row;
}

function getPostsModel($boardUri) {
  global $db, $models;

  $res = $db->find($models['board'], array('criteria'=>array(
      array('uri', '=', $boardUri),
  )));
  if (!mysqli_num_rows($res)) {
    return false;
  }
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

function getPostFilesModel($boardUri) {
  global $db, $models;

  $res = $db->find($models['board'], array('criteria'=>array(
      array('uri', '=', $boardUri),
  )));
  if (!mysqli_num_rows($res)) {
    return false;
  }
  $public_post_file_model = array(
    'name' => 'board_' . $boardUri . '_public_post_file',
    //'indexes' => array('boardUri'),
    'fields' => array(
      'postid' => array('type'=>'int'),
      'sha256' => array('type'=>'str', 'length'=>255),
      'path' => array('type'=>'str', 'length'=>255),
      //'sha512' => array('type'=>'str', 'length'=>255),
      'browser_type' => array('type'=>'str', 'length'=>255),
      //'tim' => array('type'=>'int'),
      'filename' => array('type'=>'str', 'length'=>128),
      'ext' => array('type'=>'str', 'length'=>128),
      // b64 encoded
      //'md5' => array('type'=>'str', 'length'=>24),
      'w' => array('type'=>'int'),
      'h' => array('type'=>'int'),
      //'tn_w' => array('type'=>'int'),
      //'tn_h' => array('type'=>'int'),
      'filedeleted' => array('type'=>'bool'),
      'spoiler' => array('type'=>'bool'),
      // custom_spoiler
      // 'replies' => array('type'=>'integer'),
      // 'images' => array('type'=>'integer'),
      // 'bumplimit' => array('type'=>'boolean'),
      // 'imagelimit' => array('type'=>'boolean'),
      // tag (.swf category)
      // semantic_url (seo slug)
      // since4pass
      //'unique_ips' => array('type'=>'integer'),
      //'m_img' => array('type'=>'bool'),
      //'archived' => array('type'=>'bool'),
      //'archived_on' => array('type'=>'int'),
    )
  );
  $db->autoupdate($public_post_file_model);
  return $public_post_file_model;
}

?>
