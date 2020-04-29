<?php

include 'lib.model.php';

$board_model = array(
  'name' => 'board',
  'uniques' => array('uri'),
  'indexes' => array('uri', 'ownerid'),
  'fields' => array(
    'uri' => array('type'=>'string', 'length'=>100),
    'ownerid' => array('type'=>'integer'),
    // stats? stat summary?
    // super basic settings?
  )
);

$board_user_model = array(
  'name' => 'board_user',
  'indexes' => array('boarid', 'userid'),
  'fields' => array(
    'boardid' => array('type'=>'integer'),
    'userid' => array('type'=>'integer'),
    'groupid' => array('type'=>'integer'),
  )
);

$post_model = array(
  'name' => 'post',
  'indexes' => array('boardUri'),
  'fields' => array(
  )
);

$post_file_model = array(
  'name' => 'post_file',
  'indexes' => array('postid', 'fileid'),
  'fields' => array(
    'postid' => array('type'=>'integer'),
    'fileid' => array('type'=>'integer'),
    'originalFilename' => array('type'=>'string', 'length'=>100),
  )
);

$files_model = array(
  'name' => 'file',
  'indexes' => array(),
  'fields' => array(
    'sha512' => array('type'=>'string', 'length'=>255),
  )
);

$user_model = array(
  'name' => 'user',
  'uniques' => array('username', 'email'),
  'indexes' => array('username', 'email'),
  'fields' => array(
    'username' => array('type'=>'string', 'length'=>100),
    'password' => array('type'=>'string', 'length'=>255),
    'email' => array('type'=>'string', 'length'=>255),
  )
);

$group_model = array(
  'name' => 'group',
  'fields' => array(
    'name' => array('type'=>'string', 'length'=>100),
  )
);

// logs
// calculated
// temporary

?>
