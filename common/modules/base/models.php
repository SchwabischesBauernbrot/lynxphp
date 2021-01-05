<?php

$migration_model = array(
  'name'   => 'migration',
  'fields' => array(
    'version' => array('type' => 'int'),
  )
);

// config

$config_model = array(
  'name'   => 'config',
  'fields' => array(
    'category' => array('type' => 'str'),
    'key'      => array('type' => 'str'),
    'value'    => array('type' => 'str'),
  )
);

$user_model = array(
  'name'   => 'user',
  'fields' => array(
    'username' => array('type' => 'str'),
    'email'    => array('type' => 'str'),
    'password' => array('type' => 'str'),
  )
);

$user_session_model = array(
  'name'   => 'user_session',
  'fields' => array(
    'session'  => array('type' => 'str'),
    'user_id'  => array('type' => 'int'),
    'expires'  => array('type' => 'int'),
    // if IP gets leaked somehow, make the session invalid
    'ip'       => array('type' => 'str'),
  )
);

// kind of a single point of failure
// we need to make data silos that don't depend on each other
// usage of ID maybe an issue then...
$board_model = array(
  'name'   => 'board',
  'fields' => array(
    'uri'         => array('type' => 'str'),
    'owner_id'    => array('type' => 'int'),
    'title'       => array('type' => 'str'),
    'description' => array('type' => 'text'),
    //'sfw_board' => array('type' => 'bool'),
    // stats? stat summary?
    // super basic settings?
  )
);

$public_post_model = array(
  'name' => 'public_post',
  'indexes' => array('boardUri'),
  'fields' => array(
    'no' => array('type'=>'integer'),
    'resto' => array('type'=>'integer'),
    'sticky' => array('type'=>'boolean'),
    'closed' => array('type'=>'boolean'),
    // 'now' => array('type'=>'integer'),
    'time' => array('type'=>'integer'),
    'name' => array('type'=>'string', 'length'=>128),
    'trip' => array('type'=>'string', 'length'=>128),
    'capcode' => array('type'=>'string', 'length'=>32),
    'country' => array('type'=>'string', 'length'=>2),
    //'country_name' => array('type'=>'string', 'length'=>128),
    'sub' => array('type'=>'string', 'length'=>128),
    'com' => array('type'=>'text'),
  )
);

$private_post_model = array(
  'name' => 'private_post',
  'indexes' => array('boardUri'),
  'fields' => array(
    'no' => array('type'=>'integer'),
    // ip and non-public info and such...
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
    'tim' => array('type'=>'integer'),
    'filename' => array('type'=>'string', 'length'=>128),
    'ext' => array('type'=>'string', 'length'=>128),
    // b64 encoded
    'md5' => array('type'=>'string', 'length'=>24),
    'w' => array('type'=>'integer'),
    'h' => array('type'=>'integer'),
    'tn_w' => array('type'=>'integer'),
    'tn_h' => array('type'=>'integer'),
    'filedeleted' => array('type'=>'boolean'),
    'spoiler' => array('type'=>'boolean'),
    // custom_spoiler
    // 'replies' => array('type'=>'integer'),
    // 'images' => array('type'=>'integer'),
    // 'bumplimit' => array('type'=>'boolean'),
    // 'imagelimit' => array('type'=>'boolean'),
    // tag (.swf category)
    // semantic_url (seo slug)
    // since4pass
    //'unique_ips' => array('type'=>'integer'),
    'm_img' => array('type'=>'boolean'),
    'archived' => array('type'=>'boolean'),
    'archived_on' => array('type'=>'integer'),
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

$usergroup_model = array(
  'name' => 'usergroup',
  'indexes' => array('userid', 'groupid'),
  'fields' => array(
    // automatically made...
    'groupid' => array('type'=>'int'),
    'userid' => array('type'=>'int'),
  ),
  'seed' => array(
    array('userid'=>1, 'groupid'=>1),
  )
);

$group_model = array(
  'name' => 'group',
  'fields' => array(
    'name' => array('type'=>'str', 'length'=>100),
  ),
  'seed' => array(
    array('name'=>'admin'),
  )
);

global $db, $models;

$db->autoupdate($migration_model);
$db->autoupdate($user_model);
$db->autoupdate($user_session_model);
$db->autoupdate($board_model);
$db->autoupdate($usergroup_model);
$db->autoupdate($group_model);

// for each board set up:
// a posts_model table
// module:board banners
// module:board flags
// logs
// calculated
// temporary

$models = array(
  'migration' => $migration_model,
  'config'    => $config_model,
  'board'     => $board_model,
  'session'   => $user_session_model,
  'user'      => $user_model,
  'group'     => $group_model,
  'usergroup' => $usergroup_model,
);


?>
