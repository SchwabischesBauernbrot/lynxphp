<?php

// An API mainly for modules to use to get what they need
// also interfaces

// posts/post_files are individually tables
// this keeps these tables small and therefore fast
// as the cost of easily being able to search across all boards

/*
getBoardByUri($uri)
getBoardSetting($field)

// collect all these events don't need to put into a database
createBoardSetting($field, $value)

getBoardsWith($field, $value)
getBoardSettingForm($uri)
*/

// lock/unlock board?

// getBoardUri to updateBoard URI, should create an object...
// and bitch if it's held too long...
// needs to return ID
// what's the difference between this and getBoard?
// well getBoard doesn't include boardid which somethings use
function getBoardByUri($boardUri) {
  /*
  global $db, $models;
  $res = $db->find($models['board'], array('criteria'=>array(
      array('uri', '=', $boardUri),
  )));
  if (!$res) return;
  $row = $db->get_row($res);
  $db->free($res);
  if ($row) {
    if ($row['json']) {
      //echo "decode[", print_r($row['json'], 1), "]<br>\n";
      while(is_string($row['json'])) {
        $row['json'] = json_decode($row['json'], true);
      }
      //echo "decoded[", print_r($row['json'], 1), "]<br>\n";
    }
  }
  */
  // definitely include the settings from json field
  //$boardData = getBoard($boardUri, array('jsonFields' => 'settings'));

  $row = getBoardRaw($boardUri);
  $json = json_decode($row['json'], true);
  unset($row['json']);
  $field = 'settings';
  if (isset($json[$field])) {
    $row[$field] = $json[$field];
  } else {
    // most are arrays
    $row[$field] = array();
  }

  // don't worry about error handling
  return $row;
}

function updateBoard($boardUri,$row) {
  global $db, $models;
  if ($row['json']) $row['json'] = json_encode($row['json']);
  // feels really dangerous...
  return $db->update($models['board'], $row, array('criteria'=>array(
    array('uri', '=', $boardUri),
  )));
}

$rateLimitsTTL['type'] = 0;
function checkLimit($type, $ip = '') {
  global $db, $models;
  if ($ip === '') $ip = getip();
  $res = $db->find($models['request'], array('criteria' => array( 'ip' => $ip, 'type' => $type)));
  $row = $db->get_row($res); // should only be one
  $db->free($res);
}
function recordRequest($type, $ip = '') {
  global $db, $models;
  if ($ip === '') $ip = getip();
}
function boardDealer($connections, $boardUri) {
  $mc = strlen($boardUri);
  $v = 0;
  for($c = 0; $c < $mc; $c++) {
    $v += ord($boardUri[$c]) - 65;
  }
  return $connections[$v % count($connections)];
}

$getPostsModel = array();
function getPostsModel($boardUri) {
  global $db, $models, $getPostsModel;

  // FIXME: just implement a cache here...
  /*
  if (!empty($getPostsModel[$boardUri])) {
    echo "getPostsModel called for [$boardUri] last call[", print_r($getPostsModel, 1), "] trace[", gettrace(), "]<br>\n";
  } else {
    $getPostsModel[$boardUri] = gettrace();
  }
  */

  $cnt = $db->count($models['board'], array('criteria'=>array(
      array('uri', '=', $boardUri),
  )));
  if (!$cnt) {
    //echo "getPostsModel no such [$boardUri]<br>\n";
    return false;
  }
  $public_post_model = array(
    'name' => 'board_' . $boardUri . '_public_post',
    //'indexes' => array('boardUri'),
    'fields' => array(
      // can just be postid...
      //'no' => array('type'=>'integer'),
      'threadid' => array('type'=>'int'), // thread has threaid = postid
      'resto' => array('type'=>'int'),
      'sticky' => array('type'=>'bool'),
      'closed' => array('type'=>'bool'),
      'deleted' => array('type'=>'bool'),
      // 'now' => array('type'=>'integer'),
      //'time' => array('type'=>'integer'),
      'name' => array('type'=>'str', 'length'=>128),
      'trip' => array('type'=>'str', 'length'=>128),
      'capcode' => array('type'=>'str', 'length'=>32),
      'country' => array('type'=>'str', 'length'=>2),
      //'country_name' => array('type'=>'string', 'length'=>128),
      'sub' => array('type'=>'str', 'length'=>128),
      'com' => array('type'=>'text'),
      'ip' => array('type'=>'str'),
      'password' => array('type'=>'str'),
    )
  );
  $db->autoupdate($public_post_model);
  return $public_post_model;
}

$getPostFilesModel = array();
function getPostFilesModel($boardUri) {
  global $db, $models, $getPostFilesModel;

  // just use an internal cache
  /*
  if (!empty($getPostFilesModel[$boardUri])) {
    echo "getPostFilesModel called for [$boardUri] last call[", print_r($getPostFilesModel[$boardUri], 1), "]
    <br>\n<br>\n
    current trace[", gettrace(), "]<br>\n";
  } else {
    $getPostFilesModel[$boardUri] = $boardUri . '_' . gettrace();
  }
  */

  $cnt = $db->count($models['board'], array('criteria'=>array(
      array('uri', '=', $boardUri),
  )));
  if (!$cnt) {
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
      'mime_type' => array('type'=>'str', 'length'=>255),
      'type' => array('type'=>'str', 'length'=>255),
      //'tim' => array('type'=>'int'),
      'filename' => array('type'=>'str', 'length'=>128),
      'size' => array('type'=>'int'),
      'ext' => array('type'=>'str', 'length'=>128),
      // b64 encoded
      //'md5' => array('type'=>'str', 'length'=>24),
      'w' => array('type'=>'int'),
      'h' => array('type'=>'int'),
      'tn_w' => array('type'=>'int'),
      'tn_h' => array('type'=>'int'),
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
