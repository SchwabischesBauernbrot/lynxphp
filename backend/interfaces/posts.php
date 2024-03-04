<?php

// thread or reply...
// should be thread AND reply, right?

function postDBtoAPI(&$row, $boardUri) {
  global $db;

  // filter out any file_ or post_ field
  $row = array_filter($row, function($v, $k) {
    $f5 = substr($k, 0, 5);
    return $f5 !== 'file_' && $f5 !== 'post_';
  }, ARRAY_FILTER_USE_BOTH);

  $row['no'] = empty($row['postid']) ? 0 : $row['postid'];
  unset($row['postid']);
  //unset($row['ip']);

  $data = empty($row['json']) ? array() : json_decode($row['json'], true);

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

  // should be able to remove this in the future
  unset($row['password']); //never send password field...
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
  // old fix me: should be a pipeline
  // don't like this N+1 but we don't always need capcode

  // the frontend just needs to send what's public
  // and we'll just pass that through
  /*
  if ($row['capcode']) {
    //echo "<pre>row", print_r($row, 1), "</pre>\n";
    // decode value
    if ($row['capcode'] === 'useName' || $row['capcode'] === 'admin' || $row['capcode'] === 'global') {
      // simple don't need lookups
    } else {
      // N+1 time
      //$posts_priv_model = getPrivatePostsModel($boardUri);
      // it's not being saved...
      //echo "no[", $row['no'], "]<br>\n";
      //echo "boardUri[$boardUri]<br>\n";

      $res = $db->find($posts_priv_model, array('criteria'=> array('post_id' => $row['no'])), 'json');
      $priv_row = $db->get_row($res);
      $db->free($res);
      if ($priv_row) {
        $privData = json_decode($priv_row['json'], true);
        //echo "<pre>", $row['no'], ": privData[", print_r($privData, 1), "]</pre><br>\n";
        // session is worthless in capcode
        //'identity'  => $privData['identity'],
        $row['capcode'] = array(
          'type' => $row['capcode'],
          'publickey' => $privData['publickey'],
        );
      }
    }
  }
  */
  // decode user_id
}

function getThreadNum($boardUri, $pno) {
  //echo "getThreadNum - boardUri[$boardUri]<br>\n";
  $posts_model = getPostsModel($boardUri);
  if ($posts_model === false) {
    return false;
  }
  global $db;
  $row = $db->findById($posts_model, $pno);
  $tno = $pno;
  if ($row['threadid']) {
    $tno = $row['threadid'];
  }
  return $tno;
}

// could be a cacheable getter
// what's static
// what's dynamic
// false = board 404
function getPostEngine($boardUri, $postNo, $options = false) {
  // unpack options
  extract(ensureOptions(array(
    'posts_model' => false,
    'includeFiles' => true,
    'post_files_model' => false,
  ), $options));

  if ($posts_model === false) {
    $posts_model = getPostsModel($boardUri);
    if ($posts_model === false) {
      // this board does not exist
      return false;
    }
  }
  if ($includeFiles) {
    if ($post_files_model === false) {
      $post_files_model = getPostFilesModel($boardUri);
      if ($post_files_model === false) {
        // this board does not exist
        return false;
      }
    }

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
  }
  global $db;
  $row = $db->findById($posts_model, $postNo);
  // prevent a bunch of warnings
  if (!$row) return false;
  //echo "<pre>Thread/File", print_r($row, 1), "</pre>\n";
  return $row;
}

// I'm not sure it's our responsibility to format the result set
// maybe just get it...
function getPost($boardUri, $postNo, $posts_model) {
  global $db;

  /*
  if ($posts_model === false) {
    $posts_model = getPostsModel($boardUri);
    if ($posts_model === false) {
      // this board does not exist

      // FIXME: we probably shouldn't be doing UI
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

  $row = $db->findById($posts_model, $postNo);
  */
  $row = getPostEngine($boardUri, $postNo, array('posts_model' => $posts_model));
  // prevent a bunch of warnings
  if (!$row) return false;

  $posts = array();
  //echo "<pre>Thread/File", print_r($row, 1), "</pre>\n";
  if ($db->isTrue($row['deleted'])) return array();

  // have data
  if (!isset($posts[$row['postid']])) {
    //echo "<pre>Thread", print_r($row, 1), "</pre>\n";
    $posts[$row['postid']] = $row;
    if (!$row['threadid'] || $row['threadid'] === $row['postid']) {
      threadDBtoAPI($posts[$row['postid']], $boardUri);
    } else {
      postDBtoAPI($posts[$row['postid']], $boardUri);
    }
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

  foreach($posts as $pk => $p) {
    $posts[$pk]['files'] = array_values($posts[$pk]['files']);
  }
  $posts = array_values($posts);
  $post = count($posts) ? $posts[0] : array();
  return $post;
}

// board,permissions checks must be done by here
// FIXME: take posts_model as an option
function createPost($boardUri, $post, $files, $privPost, $options = false) {
  global $db, $models, $now;

  extract(ensureOptions(array(
    'bumpBoard' => true,
  ), $options));
  //echo "bumpBoard[$bumpBoard]<br>\n";

  $inow = (int)$now;
  // thread (0) or reply (!0)
  $threadid = $post['threadid'];
  $io = array(
    'boardUri' => $boardUri,
    'p' => $post,
    'priv' => $privPost,
    'files' => $files,
    'inow' => $inow,
    'threadNum' => $threadid,
  );
  // do not insert the tags field
  unset($post['tags']);

  // handle post
  $posts_model = getPostsModel($boardUri);
  $id = $db->insert($posts_model, array($post));
  $io['id'] = $id;
  if (!$io['threadNum']) {
    // just created
    $io['threadNum'] = $io['id'];
  }

  // handle priv
  //echo "boardUri[$boardUri]<br>\n";
  // can't use findById on posts_priv_model
  $posts_priv_model = getPrivatePostsModel($boardUri);
  $privPost['post_id'] = $id; // update postid
  if (!isset($privPost['json'])) $privPost['json'] = array();
  // would be good to record the userAgent too
  $privPost['json']['identity'] = getIdentity();
  $user_id = getUserID();
  if ($user_id) {
    $userRes = getAccount($user_id);
    $privPost['json']['publickey'] = $userRes['publickey'];
  }
  //print_r($privPost);
  // there's an ip field and that's it atm
  $safePrivPost = array(
    'post_id' => $privPost['post_id'],
    'ip' => $privPost['ip'],
    'password' => $privPost['password'],
    'json' => $privPost['json'],
  );
  // promote anything not these 3 (schema/hardcoded) fields to json fields
  foreach($privPost as $k => $v) {
    if ($k === 'post_id') continue;
    if ($k === 'ip') continue;
    if ($k === 'password') continue;
    if ($k === 'json') continue;
    if (!isset($safePrivPost['json'][$k])) {
      // missing, need to upgrade
      $safePrivPost['json'][$k] = $v;
    }
  }
  $res = $db->insert($posts_priv_model, array($safePrivPost));
  if (!$res) {
    return false;
  }

  // handle files
  $issues = processFiles($boardUri, $files, $io['threadNum'], $id);

  // bump board
  if ($bumpBoard) {
    $urow = array('last_post' => $inow);
    if (!$threadid) {
      // new thread
      $urow['last_thread'] = $inow;
    }
    //echo "Bumping [$boardUri]<br>\n";
    //print_r($urow);
    $db->update($models['board'], $urow, array('criteria'=>array(
      array('uri', '=', $boardUri),
    )));
  }

  // immediate work
  global $pipelines;
  $pipelines[PIPELINE_POST_ADD]->execute($io);

  // background work
  // create an option for some tasks to be backgrounded
  global $workqueue;
  $workqueue->addWork(PIPELINE_WQ_POST_ADD, $io);

  if ($issues) {
    return array(
      'issues' => $issues,
      'id' => (int)$id,
      'debug' => array(
        'post' => $post,
        'safePrivPost' => $safePrivPost,
      ),
    );
  }

  return array(
    'id' => (int)$id,
  );
}

// this scrubs it
// could consider taking post through postid
// option.post
// option.posts_model
// option.deleteReplies:bool
function deletePost($boardUri, $postid, $options = false) {
  global $db, $now, $models;

  extract(ensureOptions(array(
    'deleteReplies' => false,
    'posts_model' => false,
    'post' => false,
  ), $options));

  //echo "deletePost[$boardUri]<br>\n";

  // ensure $posts_model
  if (!$posts_model) {
    $posts_model = getPostsModel($boardUri);
  }

  // ensure post
  if (!$post) {
    if ($posts_model !== false) {
      $post = $db->findById($posts_model, $postid);
    }
  }

  $inow = (int)$now;
  $urow = array('last_post' => $inow);

  // is this a thread...
  if (!$post['threadid']) {
    // is thread
    $urow['last_thread'] = $inow;
    if ($deleteReplies) {
      // thread deletion request
      //$res = $db->find($posts_model, array('criteria' => array('threadid' => $postid)));
      // FIXME: write me!
      echo "Write me";
    } else {
      // only disable OP
      $post['deleted'] = true;
      if (!$db->updateById($posts_model, $postid, $post)) {
        // FIXME: log error?
        return false;
      }
    }
  } else {
    // is reply
    // try to delete it
    if (!$db->deleteById($posts_model, $postid)) {
      // FIXME: log error?
      return false;
    }
  }

  // communicate it to the caches
  $db->update($models['board'], $urow, array('criteria' => array(
    array('uri', '=', $boardUri),
  )));

  // check files
  // FIXME: use deletePostFiles
  // function deletePostFiles($boardUri, $postid, $options = false) {
  // deletePostFiles($boardUri, $postid);
  $files_model = getPostFilesModel($boardUri);
  $res = $db->find($files_model, array('criteria' => array('postid' => $postid)));
  $files = $db->toArray($res);
  $db->free($res);

  if (count($files)) {
    $file_ids = array();
    foreach($files as $f) {
      if (file_exists($f['path'])) {
        unlink($f['path']);
      } else {
        $issues[$boardUri . '_' . $postid] = 'file missing: ' . $f['path'];
      }
      $file_ids[]= $f['fileid'];
    }
    if (!$db->delete($files_model, array(
        'criteria' => array(array('fileid', 'in', $file_ids))
      ))) {
      return false;
    }
  }

  return true;
}

?>