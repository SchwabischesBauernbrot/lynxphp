<?php

// definitely an OP

function threadDBtoAPI(&$row, $boardUri) {
  global $db, $models, $pipelines;
  if ($db->isTrue($row['deleted'])) {
    // non-OPs are automatically hidden...
    $nrow = array(
      'postid' => $row['postid'],
      'no' => $row['postid'],
      // threadid isn't set sometimes?
      'threadid' => 0, // threads don't have this set
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
    $io = array(
      'boardUri' => $boardUri,
      'thread' => $nrow,
    );
    $pipelines[PIPELINE_THREAD_DATA]->execute($io);
    $row = $io['thread'];
    return;
  }

  // filter out any file_ or post_ field
  $row = array_filter($row, function($v, $k) {
    $f5 = substr($k, 0, 5);
    return $f5 !== 'file_' && $f5 !== 'post_';
  }, ARRAY_FILTER_USE_BOTH);
  // prevent a bunch of warnings
  if (!$row) return false;

  // call postToDB here?

  $row['no'] = empty($row['postid']) ? 0 : $row['postid'];
  unset($row['postid']);
  //unset($row['ip']);

  $data = empty($row['json']) ? array() : json_decode($row['json'], true);

  // copied from postDBtoAPI
  $publicFields = array();
  global $pipelines;
  $public_fields_io = array(
    'fields' => $publicFields,
  );
  $pipelines[PIPELINE_BE_POST_EXPOSE_DATA_FIELD]->execute($public_fields_io);
  $publicFields = $public_fields_io['fields'];

  $exposedFields = array();
  foreach($publicFields as $f) {
    $exposedFields[$f] = empty($data[$f]) ? '' : $data[$f];
  }

  $public_fields_io = array(
    'fields' => $exposedFields,
  );
  $pipelines[PIPELINE_BE_POST_FILTER_DATA_FIELD]->execute($public_fields_io);
  $row['exposedFields'] = $public_fields_io['fields'];
  //

  // FIXME: pipeline
  $io = array(
    'boardUri' => $boardUri,
    'thread' => $row,
  );
  $pipelines[PIPELINE_THREAD_DATA]->execute($io);
  $row = $io['thread'];

  if (empty($data['cyclic'])) {
    // is this even needed
    unset($row['cyclic']);
  } else {
    $row['cyclic'] = 1;
  }

  unset($row['json']);
  // should be able to remove this in the future
  unset($row['password']); //never send password field...

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

// getThreadReplyCount
function getThreadPostCount($boardUri, $threadNum, $options = false) {
  // unpack options
  extract(ensureOptions(array(
    'posts_model' => false,
    //'post_files_model' => false,
    'since_id'    => false,
    //'includeOP'   => true,
    'includeDeleted'   => false,
  ), $options));

  if ($posts_model === false) {
    $posts_model = getPostsModel($boardUri);
    if ($posts_model === false) {
      // this board does not exist
      return false;
    }
  }
  $posts = array();
  $crit = array(
    array('threadid', '=', $threadNum),
  );
  if (!$includeDeleted) {
    $crit[] = array('deleted', '=', 0);
  }
  if ($since_id !== false) {
    $crit[] = array('postid', '>', $since_id);
  }
  // , 'order' => 'created_at'
  // can't use order by on a count
  global $db;
  return $db->count($posts_model, array(
    'criteria' => $crit)
  );
}

// could be a cacheable getter
// what's static
// what's dynamic
function getThreadEngine($boardUri, $threadNum, $options = false) {
  // unpack options
  extract(ensureOptions(array(
    'posts_model' => false,
    'post_files_model' => false,
    'since_id'    => false,
    'includeOP'   => true,
    'includeReplies' => true,
    'includeDeleted' => false,
    'sortField' => 'created_at',
  ), $options));

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
  global $db;
  // UNION? maybe with subqueries?
  if ($includeOP && $includeReplies) {
    // postid = X || (threadid = Y and deleted = 0)
  }

  // get OP
  $posts = array();
  if ($includeOP) {
    $res = $db->find($posts_model, array('criteria' => array(
      array('postid', '=', $threadNum),
    )));
    // OP does not exist (no tombstone)
    $cnt = $db->num_rows($res);
    if (!$cnt) {
      return false;
    }
    /*
    if ($cnt !== 1) {
      // if dev mode warn?
    }
    */
    while($row = $db->get_row($res)) {
      // could key/gather on postid
      $posts[] = $row;
    }
    $db->free($res);
  }

  // get replies
  if ($includeReplies) {
    $crit = array(
      array('threadid', '=', $threadNum),
      
    );
    if (!$includeDeleted) {
      $crit[] = array('deleted', '=', 0);
    }
    if ($since_id !== false) {
      $crit[] = array('postid', '>', $since_id);
    }

    // deletion gather doesn't need it
    $res = $db->find($posts_model, array(
      'criteria' => $crit, 'order' => $sortField),
    );
    // no OP and no replies, consider 404
    if (($includeOP && !count($posts)) && ($since_id === false && !$db->num_rows($res))) {
      return false;
    }
    while($row = $db->get_row($res)) {
      //echo "<pre>", print_r($row, 1), "</pre>\n";
      $posts[] = $row;
    }
    $db->free($res);
  }
  return $posts;
}

// false means thread does not exist
// and also means no replies (since)
// FIXME: we need differentiate because all sorts of error codes
// - board (posts/files/board) 404
// - thread 404
// - no replies since array()
// board/thread not found needs to be separate from results like no post
function getThread($boardUri, $threadNum, $options = false) {
  $iposts = getThreadEngine($boardUri, $threadNum, $options);
  if (!$iposts) return false;

  $posts = array();
  global $db;
  foreach($iposts as $row) {
    //echo "<pre>", print_r($row, 1), "</pre>\n";
    // ensure we don't already have ot
    $pid = $row['postid'];
    if (!isset($posts[$pid])) {
      $posts[$pid] = $row;
      $isDeleted = $db->isTrue($row['deleted']);
      if (!empty($options['includeDeleted'])) {
        $posts[$pid]['deleted'] = false;
      }
      if (!$row['threadid']) {
        //echo "<pre>Thread", print_r($row, 1), "</pre>\n";
        threadDBtoAPI($posts[$pid], $boardUri);
      } else {
        postDBtoAPI($posts[$pid], $boardUri);
      }
      if (!empty($options['includeDeleted'])) {
        if ($isDeleted) {
          $posts[$pid]['deleted'] = '1';
        }
      }
      $posts[$pid]['files'] = array();
    }
    if (!empty($row['file_fileid'])) {
      $fid = $row['file_fileid'];
      if (!isset($posts[$pid]['files'][$fid])) {
        $frow = $row;
        fileDBtoAPI($frow, $boardUri);
        $posts[$pid]['files'][$fid] = $frow;
      }
    }
  }

  // simplify files (strip out fileids)
  foreach($posts as $pk => $p) {
    $posts[$pk]['files'] = array_values($posts[$pk]['files']);
  }
  // simplify posts (strip out postids)
  $posts = array_values($posts);
  return $posts;
}

// board/thread_list uses this
function requestDeleteThread($boardUri, $threadNum, $options = false) {
  // unpack options
  extract(ensureOptions(array(
    'posts_model' => false,
    'post_files_model' => false,
  ), $options));

  if ($posts_model === false) {
    $posts_model = getPostsModel($boardUri);
    if ($posts_model === false) {
      // this board does not exist
      return false;
    }
  }
  if ($post_files_model === false) {
    $post_files_model = getPostFilesModel($boardUri);
    if ($post_files_model === false) {
      // this board does not exist
      return false;
    }
  }

  // any verification or validation we need to do?
  // uri/num need to exist and not already nuked
    // uri verification is done above
  // do we do the permissions check at this level?

  global $pipelines;
  $io = array(
    'boardUri'  => $boardUri,
    'threadNum' => $threadNum,
    'thread' => false, // if one needs it, they could at least communicate it to prevent any additionan calls or we could do a getter with caching layer
    'posts_model' => $posts_model,
    'post_files_model' => $posts_model,
    'deleteNow' => true,
    'deleteOptions' => array(),
  );

  global $workqueue;
  $workqueue->addWork(PIPELINE_WQ_REQUEST_DELETE_THREAD, $io);

  if ($io['deleteNow']) {
    // upload cache if we already have it
    if (empyt($io['deleteOptions']['posts_model'])) $io['deleteOptions']['posts_model'] = $io['posts_model'];
    if (empyt($io['deleteOptions']['post_files_model'])) $io['deleteOptions']['post_files_model'] = $io['post_files_model'];
    if (empyt($io['deleteOptions']['thread'])) $io['deleteOptions']['thread'] = $io['thread'];
    // doesn't make much sense to allow change to uri/num
    deleteThread($boardUri, $threadNum, $io['deleteOptions']);
  }
  // do we return if there was an error
  // or if we actually nuked it or not
  // our responsibility is just to make sure it eventually gets deleted
  // so maybe if there was an error
  return true;
}

// maybe should be scrub so not to confuse with the soft delete version?
function deleteThread($boardUri, $threadNum, $options = false) {
  // unpack options
  extract(ensureOptions(array(
    'posts_model' => false,
    'post_files_model' => false,
    'thread' => false,
  ), $options));
  global $db, $models, $pipelines;

  if ($posts_model === false) {
    $posts_model = getPostsModel($boardUri);
    if ($posts_model === false) {
      // this board does not exist
      return false;
    }
  }
  if ($post_files_model === false) {
    $post_files_model = getPostFilesModel($boardUri);
    if ($post_files_model === false) {
      // this board does not exist
      return false;
    }
  }

  // we don't need to load it if we don't already have it
  //if ($thread === false)

  $io = array(
    'boardUri'  => $boardUri,
    'threadNum' => $threadNum,
    'thread' => $thread, // if one needs it, they could at least communicate it to prevent any additionan calls or we could do a getter with caching layer
    'posts_model' => $posts_model,
    'post_files_model' => $post_files_model,
  );

  // we soft delete posts, do we soft thread threads
  // definitely should be an option (delete vs scrub)
  // could be used by archive model or cold storage system
  $pipelines[PIPELINE_THREAD_PRE_DELETE]->execute($io);

  // could we check thread to see if we have
  // op and replies?
  // count of posts...

  // remove from overboard
  // delete files / storage folder
  // clean files records
  // I think we need a list of posts first
  $iposts = getThreadEngine($boardUri, $threadNum, array(
    'includeOP' => true, // explicit
    'includeReplies' => true, // explicit
    //'since_id'    => false, // expectation
    'posts_model' => $posts_model,
    'post_files_model' => $post_files_model,
  ));
  //$postids = array();
  //$fileids = array();
  foreach($iposts as $row) {
    //$postids[] = $row['postid'];
    // FIXME: overboard cleaning, maybe overboard can hook into PIPELINE_THREAD_PRE_DELETE

    // not as efficient sql-wise
    // but will clean the files
    scrubPost($boardUri, $row['postid'], array(
      'posts_model' => $posts_model,
      'post_files_model' => $post_files_model,
    ));
    /*
    if (!empty($row['file_fileid'])) {
      $fileids[] = $row['file_fileid'];
    }
    */
  }
  /*
  // FIXME: clean disk files
  $fres = $db->delete($post_files_model, array(
    array('fileids', 'IN', $fileids)
  ));
  // clean posts records
  $pres = $db->delete($posts_model, array(
    array('postids', 'IN', $postids)
  ));
  */
  $pipelines[PIPELINE_THREAD_POST_DELETE]->execute($io);
  return true;
}


?>