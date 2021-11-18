<?php

function getThread($boardUri, $threadNum, $options = false) {
  // unpack options
  extract(ensureOptions(array(
    'posts_model' => false,
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
  $post_files_model = getPostFilesModel($boardUri);

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
        postDBtoAPI($posts[$row['postid']]);
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