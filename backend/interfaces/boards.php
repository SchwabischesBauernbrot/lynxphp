<?php

function boardDBtoAPI(&$row) {
  global $db, $models;
  unset($row['boardid']);
  //if ($row['json']) $row['json'] = json_decode($row['json'], true);
  unset($row['json']);
  // decode user_id
  /*
  $res = $db->find($models['user'], array('criteria'=>array(
    array('userid', '=', $row['userid']),
  )));
  $urow = $db->get_row($res)
  $row['user'] = $urpw['username'];
  */
  unset($row['userid']);
}

// get list of boards
function listBoards() {
  global $db, $models;
  $res = $db->find($models['board']);
  $boards = array();
  while($row = $db->get_row($res)) {
    boardDBtoAPI($row);
    $boards[] = $row;
  }
  $db->free($res);
  return $boards;
}

// get single board
function getBoard($boardUri) {
  global $db, $models;
  $res = $db->find($models['board'], array('criteria'=>array(
    array('uri', '=', $boardUri),
  )));
  $row = $db->get_row($res);
  $db->free($res);
  boardDBtoAPI($row);
  return $row;
}

function getBoards($boardUris) {
  global $db, $models;
  if (is_array($boardUris)) {
    $res = $db->find($models['board'], array('criteria'=>array(
      array('uri', 'in', $boardUris),
    )));
  } else {
    $res = $db->find($models['board'], array('criteria'=>array(
      array('uri', 'in', explode(',', $boardUris)),
    )));
  }
  $data = array();
  while($row = $db->get_row($res)) {
    boardDBtoAPI($row);
    $data[] = $row;
  }
  return $row;
}

// get board thread
// create board

function boardPage($boardUri, $page = 1) {
  global $db, $tpp;
  $page = (int)$page;
  $lastXreplies = 5;
  // get threads for this page
  $posts_model = getPostsModel($boardUri);
  if ($posts_model === false) {
    // this board does not exist
    sendResponse(array(), 404, 'Board not found');
    return;
  }
  $post_files_model = getPostFilesModel($boardUri);
  $limitPage = $page - 1; // make it start at 0
  //echo "page[$page] limitPage[$limitPage]<br>\n";


  // get threads, join posts filter out
  //   deleted posts
  //   and deleted threads without any (non-deleted) posts
  // and not affect paging/count

  $postTable = modelToTableName($posts_model);
  $filesTable = modelToTableName($post_files_model);

  $postFields = array(
    'postid', 'threadid', 'resto', 'sticky', 'closed', 'name', 'trip',
    'capcode', 'country', 'sub', 'com', 'deleted', 'json', 'created_at', 'updated_at',
  );
  $filesFields = array_keys($post_files_model['fields']);
  $filesFields[] = 'fileid';

  // mysql uses this
  $posts_extended_model = $posts_model; // copy array
  $posts_extended_model2 = $posts_model;

  // join all non-deleted posts
  $posts_extended_model['children'] = array(
    array(
      'type' => 'left',
      'model' => $posts_model,
      'srcField' => 'threadid',
      'pluck' => array('count(ALIAS.postid) as cnt'),
      'groupby' => $postTable . '.postid',
      'having' => '('.$postTable.'.deleted=\'0\' or ('.$postTable.'.deleted=\'1\' and count(ALIAS.postid)>0))',
      'where' => array(
        array('deleted', '=', 0)
      ),
    ),
  );

  /*
  // select * from () as t
  $subselect2 = $db->makeSubselect($posts_extended_model, array('criteria'=>array(
      array('threadid', '=', 0),
      // we need the thread tombstones...
      //array('deleted', '=', 0),
    ),
    'order'=>'updated_at desc',
    'limit' => ($limitPage ? ($limitPage * $tpp) . ',' : '') . $tpp,
  ));

  //   join board_test_public_posts as p on (p.threadid = t.postid or p.postid = t.postid)
  $subselect2['children'] = array(
    array(
      'model' => $posts_extended_model2,
      'pluck' => array_map(function ($f) { return 'ALIAS.' . $f . ' as post_' . $f; }, $postFields),
      'useField' => 'bob',
      'onAlias' => 't1',
      'on' => array(
        array('postid', 'IN', array('board_test_public_posts.threadid', 'board_test_public_posts.postid')),
      ),
    ),
  );
  $subselect = $db->makeSubselect($subselect2);

  //   left join board_test_public_post_files f on p.postid=f.postid
  $subselect['children'] = array(
    array(
      'type' => 'left',
      'pluck' => array_map(function ($f) { return 'ALIAS.' . $f . ' as file_' . $f; }, $filesFields),
      'model' => $post_files_model,
    ),
  );


  $res = $db->find($subselect, array(
    'order'        => true,
    'orderNoAlias' => 't1.updated_at desc, board_test_public_posts.created_at asc',
  ));
  */
  //echo "count [", $db->num_rows($res), "]<br>\n";
  if (get_class($db) === 'pgsql_driver') {
    $sql = 'select '.join(',', array_map(function ($f) { return 'f.' . $f . ' as file_' . $f; }, $filesFields)).', ranked_post.*
              from
              (
                select t1.*, tf.*, t1.created_at as thread_created_at, p.postid as replyid, t.postid as thread_postid, rank() OVER (PARTITION BY p.threadid ORDER BY p.created_at DESC) AS "rank",
                  '.join(',', array_map(function ($f) { return 'p.' . $f . ' as post_' . $f; }, $postFields)).',
                  '.join(',', array_map(function ($f) { return 'tf.' . $f . ' as threadfile_' . $f; }, $filesFields)).'
                from (
                  select p1.*,count(jt1.postid) as cnt
                      from '.$postTable.' as p1
                        left join '.$postTable.' as jt1 on (jt1.postid=p1.threadid and jt1.deleted = \'0\')
                      where p1.threadid = \'0\'
                      group by p1.postid
                      having  (p1.deleted=\'0\' or (p1.deleted=\'1\' and count(jt1.postid)>0))
                      order by p1.updated_at desc
                  ) as t1
                  left join '.$postTable.' as t on (t1.postid = t.postid)
                  left join '.$filesTable.' tf on (tf.postid = t.postid)
                  left join '.$postTable.' as p on (t1.postid = p.threadid)
                order by t.updated_at desc, p.created_at desc
              ) as ranked_post
              left join '.$filesTable.' f on f.postid = ranked_post.replyid
            where rank <= ' . $lastXreplies . '
            order by ranked_post.thread_postid desc, ranked_post.replyid asc';
    $res = pg_query($db->conn, $sql);
    $err = pg_last_error($db->conn);
    if ($err) {
      echo "boards::boardPage:pgsql - err[$err]<br>\nSQL[<code>$sql</code>]<br>\n";
    }
    $data = array();
    $threads = array();
    while($row = $db->get_row($res)) {
      //echo '<pre>row', print_r($row, 1), "</pre>\n";

      // don't stomp posts from last record
      if (!isset($threads[$row['thread_postid']])) {
        $threads[$row['thread_postid']] = array_filter($row, function($v, $k) {
          $f5 = substr($k, 0, 5);
          return $f5 !== 'post_' && $f5 !=='file_' && $f5 !== 'threa';
        }, ARRAY_FILTER_USE_BOTH);
        // process op
        $threads[$row['thread_postid']]['postid'] = $row['thread_postid'];
        $threads[$row['thread_postid']]['created_at'] = $row['thread_created_at'];
        postDBtoAPI($threads[$row['thread_postid']]);
        $threads[$row['thread_postid']]['posts'] = array();
        $threads[$row['thread_postid']]['files'] = array();
        //echo "<pre>", print_r($threads[$row['thread_postid']], 1), "</pre>\n";
      }
      // process threadfiles
      if ($row['threadfile_fileid'] && !isset($threads[$row['thread_postid']]['files'][$row['threadfile_fileid']])) {
        $threads[$row['thread_postid']]['files'][$row['threadfile_fileid']] = key_map(function($v) { return substr($v, 6); }, array_filter($row, function($v, $k) {
          $f11 = substr($k, 0, 11);
          return $f11 ==='threadfile_';
        }, ARRAY_FILTER_USE_BOTH));
        //echo "<pre>", print_r($threads[$row['thread_postid']]['files'][$row['threadfile_fileid']], 1), "</pre>\n";
        fileDBtoAPI($threads[$row['thread_postid']]['files'][$row['threadfile_fileid']]);
      }

      // don't stomp files from last record
      if ($row['post_postid'] && !isset($threads[$row['thread_postid']]['posts'][$row['post_postid']])) {
        $threads[$row['thread_postid']]['posts'][$row['post_postid']] = key_map(function($v) { return substr($v, 5); }, array_filter($row, function($v, $k) {
          $f5 = substr($k, 0, 5);
          return $f5 === 'post_';
        }, ARRAY_FILTER_USE_BOTH));
        // process post
        postDBtoAPI($threads[$row['thread_postid']]['posts'][$row['post_postid']]);
        $threads[$row['thread_postid']]['posts'][$row['post_postid']]['files'] = array();
      }
      if ($row['file_fileid']) {
        /*
        $threads[$row['thread_postid']]['posts'][$row['file_postid']]['files'][$row['file_fileid']] = key_map(function($v) { return substr($v, 5); }, array_filter($row, function($v, $k) {
          $f5 = substr($k, 0, 5);
          return $f5 ==='file_';
        }, ARRAY_FILTER_USE_BOTH));
        */
        $threads[$row['thread_postid']]['posts'][$row['file_postid']]['files'][$row['file_fileid']] = $row;
        // process file
        fileDBtoAPI($threads[$row['thread_postid']]['posts'][$row['file_postid']]['files'][$row['file_fileid']]);
      }
    }
    $db->free($res);
    //echo "<pre>list", print_r($threads, 1), "</pre>\n";
    foreach($threads as $tk => $t) {
      foreach($t['posts'] as $pk => $p) {
        $threads[$tk]['posts'][$pk]['files'] = array_values($p['files']);
      }
      $threads[$tk]['posts'] = array_values($threads[$tk]['posts']);
      // find op
      $op = $threads[$tk];
      if (!isset($op['no'])) {
        echo "<pre>problem missing no, op[$tk]: ", print_r($op, 1), "</pre>\n";
      }
      if (!isset($op['created_at'])) {
        echo "<pre>problem missing created_at, op[$tk]: ", print_r($op, 1), "</pre>\n";
      }
      unset($op['posts']); // remove replies
      $op['threadid'] = $op['no'];
      $op['files'] = array_values($t['files']); // restore files
      // put at top
      //array_unshift($threads[$tk]['posts'], $op);
      $threads[$tk] = array(
        'posts' => array_merge(array($op), $threads[$tk]['posts'])
      );
    }
    $threads = array_values($threads);
    return $threads;
  }

  //
  // MySQL version
  //

  $posts_model['children'] = array(
    array(
      'type' => 'left',
      'model' => $post_files_model,
      'pluck' => array_map(function ($f) { return 'ALIAS.' . $f . ' as file_' . $f; }, $filesFields)
    )
  );
  $posts_extended_model['children'] = array(
    array(
      'type' => 'left',
      'model' => $post_files_model,
      'pluck' => array_map(function ($f) { return 'ALIAS.' . $f . ' as file_' . $f; }, $filesFields)
    )
  );

  $res = $db->find($posts_extended_model, array('criteria'=>array(
      array('threadid', '=', 0),
      // we need the thread tombstones...
      //array('deleted', '=', 0),
    ),
    'order'=>'updated_at desc',
    'limit' => ($limitPage ? ($limitPage * $tpp) . ',' : '') . $tpp,
  ));
  $threads = array();
  while($row = $db->get_row($res)) {
    $orow = $row;
    // do we have this thread?
    if (!isset($threads[$row['postid']])) {
      $threads[$row['postid']] = array(
        // add op to posts
        'posts' => array($row)
      );
      // filter OP
      postDBtoAPI($threads[$row['postid']]['posts'][0]);
      $threads[$row['postid']]['posts'][0]['files'] = array();
    }

    // do we have this file?
    if (!empty($orow['file_fileid']) && !isset($threads[$row['postid']]['posts'][0]['files'][$orow['file_fileid']])) {
      $threads[$row['postid']]['posts'][0]['files'][$orow['file_fileid']] = $orow;
      fileDBtoAPI($threads[$row['postid']]['posts'][0]['files'][$orow['file_fileid']]);
    }

    // add remaining posts
    $postRes = $db->find($posts_model, array('criteria'=>array(
      array('threadid', '=', $orow['postid']),
      array('deleted', '=', 0),
    ), 'order'=>'created_at desc', 'limit' => $lastXreplies));
    $posts = array_reverse($db->toArray($postRes));
    $db->free($postRes);
    foreach($posts as $i => $post) {
      // do we have this post
      if (!isset($threads[$orow['postid']]['posts'][$post['postid']])) {
        $threads[$orow['postid']]['posts'][$post['postid']] = $post;
        postDBtoAPI($threads[$orow['postid']]['posts'][$post['postid']]);
        // set up files
        $threads[$orow['postid']]['posts'][$post['postid']]['files'] = array();
      }
      // do we have this file
      if (!empty($post['file_fileid']) && !isset($threads[$orow['postid']]['posts'][$post['postid']]['files'][$post['file_fileid']])) {
        $threads[$orow['postid']]['posts'][$post['postid']]['files'][$post['file_fileid']] = $post;
        fileDBtoAPI($threads[$orow['postid']]['posts'][$post['postid']]['files'][$post['file_fileid']]);
      }
    }
  }
  $db->free($res);

  // remove keys nested...
  foreach($threads as $tk => $t) {
    foreach($t['posts'] as $pk => $p) {
      $threads[$tk]['posts'][$pk]['files'] = array_values($p['files']);
    }
    $threads[$tk]['posts'] = array_values($t['posts']);
  }
  $threads = array_values($threads);
  return $threads;
}

function boardCatalog($boardUri) {
  global $db, $tpp;
  $posts_model = getPostsModel($boardUri);
  // make sure board exists...
  if ($posts_model === false) {
    return false;
  }
  $post_files_model = getPostFilesModel($boardUri);

  // pages, threads
  // get a list of threads sorted by bump

  // would be good to get the post count too
  // and all non-deleted files
  $fileTable = modelToTableName($post_files_model);
  // why are we stripping children here?
  //$posts_model['children'] = array();

  $filesFields = array_keys($post_files_model['fields']);
  $filesFields[] = 'fileid';

  //
  $postTable = modelToTableName($posts_model);
  $posts_model['children'] = array(
    array(
      'type' => 'left',
      'model' => $posts_model,
      'useField' => 'threadid',
      'pluck' => array('count(ALIAS.postid) as reply_count'),
      'groupby' => $postTable . '.postid, files2.fileid',
      //'having' => '('.$postTable.'.deleted=\'0\' or ('.$postTable.'.deleted=\'1\' and count(ALIAS.postid)>0))',
      'where' => array(
        array('deleted', '=', 0)
      ),
    ),
    array(
      'type' => 'left',
      'model' => $post_files_model,
      'tableOverride' => 'jt1',
      'pluck' => array('count(ALIAS.fileid) as file_count'),
      //'groupby' => $postTable . '.postid',
    ),
    array(
      'type' => 'left',
      'model' => $post_files_model,
      'alias' => 'files2',
      'tableOverride' => $postTable,
      'pluck' => array_map(function ($f) { return 'ALIAS.' . $f . ' as file_' . $f; }, $filesFields),
      //'pluck' => array('count(ALIAS.fileid) as file_count'),
      //'groupby' => $postTable . '.postid',
    ),
  );


  $res = $db->find($posts_model, array('criteria' => array(
    array('threadid', '=', 0),
  ), 'order'=>'updated_at desc'));
  $page = 1;
  // FIXME: rewrite to be more memory efficient
  // How?
  $threads = array();
  while($row = $db->get_row($res)) {
    // handle thread
    if (!isset($threads[$page][$row['postid']])) {
      // add thread
      $threads[$page][$row['postid']] = $row;
      postDBtoAPI($threads[$page][$row['postid']], $post_files_model);
      $threads[$page][$row['postid']]['file_count'] = $row['file_count']; // preserve file_count
      $threads[$page][$row['postid']]['files'] = array();
    }
    // handle files
    if (!empty($row['file_fileid']) && !isset($threads[$page][$row['postid']]['files'][$row['file_fileid']])) {
      $threads[$page][$row['postid']]['files'][$row['file_fileid']] = $row;
      fileDBtoAPI($threads[$page][$row['postid']]['files'][$row['file_fileid']]);
    }
    // do we need to add a page...
    if (count($threads[$page]) === $tpp) {
      $page++;
      $threads[$page] = array();
    }
  }
  $db->free($res);
  foreach($threads as $page => $ts) {
    foreach($ts as $tk => $t) {
      $threads[$page][$tk]['files'] = array_values($t['files']);
    }
    $threads[$page] = array_values($threads[$page]);
  }
  //echo "page[$page]<br>\n";
  return $threads;
}

function isBO($boardUri, $userid = false) {
  if ($userid === false) {
    $userid = getUserID();
    if (!$userid) {
      return NULL;
    }
  }
  global $db, $models;
  $res = $db->find($models['board'], array('criteria'=>array(
    array('uri', '=', $boardUri),
  )));
  $row = $db->get_row($res);
  $db->free($res);
  return $row['owner_id'] === $userid;
}

// optimization
function getBoardThreadCount($boardUri) {
  global $db;
  $posts_model = getPostsModel($boardUri);
  $threadCount = $db->count($posts_model, array('criteria'=>array(
      array('threadid', '=', 0),
  )));
  return $threadCount;
}

function getBoardPostCount($boardUri) {
  global $db;
  $posts_model = getPostsModel($boardUri);
  $postCount = $db->count($posts_model);
  return $postCount;
}

function getBoardSettings($boardUri) {
  global $db, $models;
  global $db, $models;
  $res = $db->find($models['board'], array('criteria'=>array(
    array('uri', '=', $boardUri),
  )));
  $row = $db->get_row($res);
  $db->free($res);
  //boardDBtoAPI($row);
  /*
  $settings = $db->findById($models['setting'], 1);
  // create ID 1 if needed
  if ($settings === false) {
    $db->insert($models['setting'], array(
      // 'settingid'=>1,
      array('changedby' => 0),
    ));
    $settings = array('json' => '[]', 'changedby' => 0, 'settingsid' => 1);
  }
  return json_decode($settings['json'], true);
  */
  return $row;
}

?>
