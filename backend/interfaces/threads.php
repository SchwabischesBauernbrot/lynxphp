<?php

// definitely an OP

function threadDBtoAPI(&$row) {
  global $db, $models;
  if ($db->isTrue($row['deleted'])) {
    // non-OPs are automatically hidden...
    $nrow = array(
      'postid' => $row['postid'],
      'no' => $row['postid'],
      // threadid isn't set sometimes?
      'threadid' => $row['postid'],
      'deleted' => 1,
      'com' => 'Thread OP has been deleted but this placeholder is kept, so replies can be read',
      'sub' => '',
      'name' => '',
      // no reasons to hide these...
      'created_at' => $row['created_at'],
      'updated_at' => $row['updated_at'],
      'files' => array(),
      // catalog uses this
      //'reply_count' => $row['reply_count'],
      //'file_count' => $row['file_count'],
    );
    if (isset($row['reply_count'])) $nrow['reply_count'] = $row['reply_count'];
    if (isset($row['file_count'])) $nrow['file_count'] = $row['file_count'];
    $row = $nrow;
    return;
  }

  // filter out any file_ or post_ field
  $row = array_filter($row, function($v, $k) {
    $f5 = substr($k, 0, 5);
    return $f5 !== 'file_' && $f5 !== 'post_';
  }, ARRAY_FILTER_USE_BOTH);

  $row['no'] = empty($row['postid']) ? 0 : $row['postid'];
  unset($row['postid']);
  //unset($row['ip']);

  $data = empty($row['json']) ? array() : json_decode($row['json'], true);

  // FIXME: pipeline
  if (empty($data['cyclic'])) {
    // is this even needed
    unset($row['cyclic']);
  } else {
    $row['cyclic'] = 1;
  }

  unset($row['json']);
  // ensure frontend doesn't have to worry about database differences
  $bools = array('deleted', 'sticky', 'closed');
  foreach($bools as $f) {
    // follow 4chan spec
    if ($db->isTrue($row[$f])) {
      $row[$f] = 1;
    } else {
      unset($row[$f]);
    }
  }
  // decode user_id
}

function getThread($boardUri, $threadNum, $options = false) {
  // unpack options
  extract(ensureOptions(array(
    'posts_model' => false,
    'post_files_model' => false,
    'since_id'    => false,
    'includeOP'   => true,
  ), $options));

  global $db;
  if ($posts_model === false) {
    $posts_model = getPostsModel($boardUri);
    if ($posts_model === false) {
      // this board does not exist
      sendResponse(array(), 404, 'Board not found');
      return;
    }
  }
  if ($post_files_model === false) {
    $post_files_model = getPostFilesModel($boardUri);
    if ($post_files_model === false) {
      // this board does not exist
      sendResponse(array(), 404, 'Board files not found');
      return;
    }
  }

  //$filesFields = array_keys($post_files_model['fields']);
  //$filesFields[] = 'fileid';

  $filesFields = array('postid', 'sha256', 'path', 'browser_type', 'mime_type',
    'type', 'filename', 'size', 'ext', 'w', 'h', 'filedeleted', 'spoiler',
    'tn_w', 'tn_h', 'fileid');


  $posts_model['children'] = array(
    array(
      'type' => 'left',
      'model' => $post_files_model,
      'pluck' => array_map(function ($f) { return 'ALIAS.' . $f . ' as file_' . $f; }, $filesFields)
    )
  );

  // get OP
  // FIXME: only gets first image on OP
  // why? /:board/thread/:thread uses this too
  $posts = array();
  if ($includeOP) {
    $res = $db->find($posts_model, array('criteria'=>array(
      array('postid', '=', $threadNum),
    )));
    while($row = $db->get_row($res)) {
      //echo "<pre>Thread/File", print_r($row, 1), "</pre>\n";
      if (!isset($posts[$row['postid']])) {
        //echo "<pre>Thread", print_r($row, 1), "</pre>\n";
        $posts[$row['postid']] = $row;
        threadDBtoAPI($posts[$row['postid']]);
        //echo "<pre>4chan", print_r($row, 1), "</pre>\n";
        $posts[$row['postid']]['files'] = array();
      }
      if (!empty($row['file_fileid'])) {
        if (!isset($posts[$row['postid']]['files'][$row['file_fileid']])) {
          $frow = $row;
          fileDBtoAPI($frow, $boardUri);
          $posts[$row['postid']]['files'][$row['file_fileid']] = $frow;
        }
      }
    }
    $db->free($res);
  }

  // get replies

  $crit = array(
    array('threadid', '=', $threadNum),
    array('deleted', '=', 0),
  );
  if ($since_id !== false) {
    $crit[] = array('postid', '>', $since_id);
  }

  $res = $db->find($posts_model, array(
    'criteria' => $crit, 'order' => 'created_at')
  );
  while($row = $db->get_row($res)) {
    //echo "<pre>", print_r($row, 1), "</pre>\n";
    $orow = $row;
    if (!isset($posts[$row['postid']])) {
      postDBtoAPI($row);
      $posts[$row['no']] = $row;
      $posts[$row['no']]['files'] = array();
    }
    if (!empty($orow['file_fileid'])) {
      if (!isset($posts[$orow['postid']]['files'][$orow['file_fileid']])) {
        $frow = $orow;
        fileDBtoAPI($frow, $boardUri);
        $posts[$orow['postid']]['files'][$orow['file_fileid']] = $frow;
      }
    }
  }
  foreach($posts as $pk => $p) {
    $posts[$pk]['files'] = array_values($posts[$pk]['files']);
  }
  $posts = array_values($posts);
  return $posts;
}

?>