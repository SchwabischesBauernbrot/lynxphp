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
// why? for locking and multiuser edits, so we don't lose json data

// what's the difference between this and getBoard?
// well getBoard doesn't include boardid which somethings (like banner) use

// this needs to return falsish if the board doesn't exist
function getBoardByUri($boardUri, $options = false) {
  extract(ensureOptions(array(
    'include_fields' => array('settings'),
  ), $options));
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
  // this doesn't return the boardid
  // definitely include the settings from json field
  //$boardData = getBoard($boardUri, array('jsonFields' => 'settings'));

  $row = getBoardRaw($boardUri);
  //echo "boardDataRaw[", print_r($row, 1), "]<br>\n";

  // this needs to return falsish if the board doesn't exist
  if (!$row) return false;

  // this will insert fields that might not exist
  $json = json_decode($row['json'], true);
  unset($row['json']);
  foreach($include_fields as $field) {
    if (isset($json[$field])) {
      $row[$field] = $json[$field];
    } else {
      // most are arrays
      $row[$field] = array();
    }
  }

  // don't worry about error handling
  return $row;
}

function updateBoard($boardUri,$row) {
  global $db, $models;
  if ($row['json']) $row['json'] = json_encode($row['json']);
  // feels really dangerous...
  // well the timestamps are breaking postgres
  return $db->update($models['board'], $row, array('criteria'=>array(
    array('uri', '=', $boardUri),
  )));
}

function updateBoardJson($boardUri, $json) {
  global $db, $models;
  if (!$json) return; // never stomp all the data
  $row = array('json' => json_encode($json));
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

// what uses this? nothing
function boardDealer($connections, $boardUri) {
  $mc = strlen($boardUri);
  $v = 0;
  for($c = 0; $c < $mc; $c++) {
    $v += ord($boardUri[$c]) - 65;
  }
  return $connections[$v % count($connections)];
}

//$getPostsModel = array();
function getPostsModel($boardUri, $options = false) {
  global $db, $models, $getPostsModel;

  extract(ensureOptions(array(
    'checkBoard' => true,
  ), $options));

  // FIXME: just implement a cache here...
  /*
  if (!empty($getPostsModel[$boardUri])) {
    echo "getPostsModel called for [$boardUri] last call[", print_r($getPostsModel, 1), "] trace[", gettrace(), "]<br>\n";
  } else {
    $getPostsModel[$boardUri] = gettrace();
  }
  */
  if ($checkBoard) {
    $cnt = $db->count($models['board'], array('criteria'=>array(
        array('uri', '=', $boardUri),
    )));
    if (!$cnt) {
      //echo "getPostsModel no such [$boardUri]<br>\n";
      return false;
    }
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
      //'email' => array('type'=>'str', 'length'=>255),
      'trip' => array('type'=>'str', 'length'=>128),
      'capcode' => array('type'=>'str', 'length'=>32),
      'country' => array('type'=>'str', 'length'=>2),
      //'country_name' => array('type'=>'string', 'length'=>128),
      'sub' => array('type'=>'str', 'length'=>128),
      'com' => array('type'=>'text'),
      'type' => array('type'=>'str', 'length'=>255),
      'email' => array('type'=>'str', 'length'=>255), // public option to reduce extra query
      'deleted_by' => array('type'=>'str', 'length'=> 12), // password or specific user
      // this is private, not public info
      //'password' => array('type'=>'str'),
      // 'replies' => array('type'=>'integer'),
      // 'images' => array('type'=>'integer'),
      //'archived' => array('type'=>'bool'),
      //'archived_on' => array('type'=>'int'),
      // semantic_url (seo slug)
      // since4pass
      //'unique_ips' => array('type'=>'integer'),
      //'m_img' => array('type'=>'bool'),

      // RT
      // thread/reply (uri, postid) can all be done in JSON tbh
      // maybe a post.type (like adn annotation type) that can drive modular post viewing
      )
  );
  $db->autoupdate($public_post_model);
  return $public_post_model;
}

function getPrivatePostsModel($boardUri) {
  global $db, $models;

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

  $private_post_model = array(
    'name' => 'board_' . $boardUri . '_private_post',
    //'indexes' => array('boardUri'),
    'fields' => array(
      // can just be postid...
      // the internal id is aleady postid
      // and we expect findById postid to work
      // pg won't allow it to be postid
      'post_id'  => array('type'=>'integer'),
      'ip'       => array('type'=>'str'),
      'email'    => array('type'=>'str', 'length'=>255),
      'password' => array('type'=>'str'),
    )
  );
  $db->autoupdate($private_post_model);
  return $private_post_model;
}

$getPostFilesModel = array();
function getPostFilesModel($boardUri, $options = false) {
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
  extract(ensureOptions(array(
    'checkBoard' => true,
  ), $options));

  if ($checkBoard) {
    $cnt = $db->count($models['board'], array('criteria'=>array(
      array('uri', '=', $boardUri),
    )));
    if (!$cnt) {
      return false;
    }
  }
  $public_post_file_model = array(
    'name' => 'board_' . $boardUri . '_public_post_file',
    //'indexes' => array('boardUri'),
    'fields' => array(
      'postid' => array('type'=>'int'),
      'path' => array('type'=>'str', 'length'=>255),
      // b64 encoded
      //'md5' => array('type'=>'str', 'length'=>24),
      'sha256' => array('type'=>'str', 'length'=>255),
      //'sha512' => array('type'=>'str', 'length'=>255),
      'browser_type' => array('type'=>'str', 'length'=>255),
      'mime_type' => array('type'=>'str', 'length'=>255),
      'type' => array('type'=>'str', 'length'=>255),
      //'tim' => array('type'=>'int'),
      'filename' => array('type'=>'str', 'length'=>128),
      'size' => array('type'=>'int'),
      'ext' => array('type'=>'str', 'length'=>128),
      'w' => array('type'=>'int'),
      'h' => array('type'=>'int'),
      'tn_w' => array('type'=>'int'),
      'tn_h' => array('type'=>'int'),
      'filedeleted' => array('type'=>'bool'),
      'spoiler' => array('type'=>'bool'),
      // custom_spoiler
      // tag (.swf category)
    )
  );
  $db->autoupdate($public_post_file_model);
  return $public_post_file_model;
}

// maybe a lib.post in the future...
// tag and process post
// privPost could just be passing the optional password and then we'd have to do the getIP here...
function precreatePost($boardUri, $post, $files, $privPost) {

  // tag post (common/lib.post_tags.php)
  // tagPost and newpost both get board data for settings (could reduce queries)
  // actually which newpost needs getBoardData?
  $post['tags'] = tagPost($boardUri, $post, $files, $privPost);

  // now other systems can react to the tags
  // PIPELINE_REPLY_ALLOWED
  // PIPELINE_NEWPOST_PROCESS

  // FIXME: is board locked? is thread locked?
  global $pipelines;
  $reply_allowed_io = array(
    'p'        => $post,
    'boardUri' => $boardUri,
    'allowed'  => true,
    'issues'   => array(),
  );
  $pipelines[PIPELINE_REPLY_ALLOWED]->execute($reply_allowed_io);

  if (!$reply_allowed_io['allowed']) {
    // maybe a 400 is more appropriate
    return array(
      'issues' => array('Reply not allowed: '.join("\n", $reply_allowed_io['issues'])),
    );
  }

  // FIXME: make sure threadId exists...
  $newpost_process_io = array(
    'boardUri'     => $boardUri,
    'p'            => $post,
    'priv'         => $privPost,
    'files'        => $files,
    'addToPostsDB' => true,
    'processFilesDB' => true,
    'bumpThread' => true,
    'returnId' => true,
    'issues'   => array(),
    'log'      => array(),
    'createPostOptions' => array('bumpBoard' => true),
  );
  //$newpost_process_io['log'][] = $privPost;
  $pipelines[PIPELINE_NEWPOST_PROCESS]->execute($newpost_process_io);
  if ($newpost_process_io['addToPostsDB']) {
    $post = $newpost_process_io['p']; // update post
    $privPost = $newpost_process_io['priv']; // update privPost
    $files = $newpost_process_io['files']; // update files
    //$newpost_process_io['log'][] = $privPost;
  
    // can be an array (issues,id) if file errors
    $data = createPost($boardUri, $post, $files, $privPost, $newpost_process_io['createPostOptions']);
    
    // probably should be part of createPost
    // do we bump the thread?
    $threadid = $post['threadid'];
    // issues are usually file upload problems...
    $hasId = !empty($data['id']);
    $notSage = empty($newpost_process_io['createPostOptions']['sage']);
    //echo "[$hasId][$threadid]bump[", $newpost_process_io['bumpThread'], "][$notSage]<br>\n";
    if ($hasId && $threadid && $newpost_process_io['bumpThread'] && $notSage) {
      global $db;
      // bump thread
      $posts_model = getPostsModel($boardUri);
      $urow = array();
      //echo "bumping [$threadid]<br>\n";
      $db->update($posts_model, $urow, array('criteria'=>array(
        array('postid', '=', $threadid),
      )));
    }
    //print_r($newpost_process_io['log']);
    //$data['log'] = $newpost_process_io['log'];
  } else {
    $data = array(
      'id' => $newpost_process_io['returnId']
    );
    // inject issues
    if (count($newpost_process_io['issues'])) {
      $data['issues'] = $newpost_process_io['issues'];
    }
  }
  return $data;
}

?>
