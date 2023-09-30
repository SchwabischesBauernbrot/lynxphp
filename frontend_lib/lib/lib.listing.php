<?php

include '../frontend_lib/handlers/boards.php'; // preprocessPost

function component_listing($template, $addAction, $name, $fields, $options = false) {
  extract(ensureOptions(array(
    'top_actions' => array(),
  ), $options));
  if (!count($top_actions)) {
    $top_actions = array(
      array(
        //
      ),
    );
  }
  $html = '';
  $addLink = '<a href="">add ' . $name . '</a>';
  $html = $template['header'] . $addLink. $template['footer'];
  return $html;
}

function component_thread_listing($posts) {
  $nPosts = array();
  if (isset($overboardData['threads'])) {
    foreach($overboardData['threads'] as $i => $t) {
      if (0 && DEV_MODE) {
        echo "<pre>thread[", print_r($t, 1), "]</pre>\n";
      }
      if (!isset($t['posts'])) continue;
      foreach($t['posts'] as $j => $post) {
        if (0 && DEV_MODE) {
          echo "<pre>post[", print_r($post, 1), "]</pre>\n";
        }
        $overboardData['threads'][$i]['posts'][$j]['boardUri'] = $t['boardUri'];
        preprocessPost($overboardData['threads'][$i]['posts'][$j]);
        $nPosts[] = $post;
      }
    }
  }

  global $pipelines;
  $post_io = array(
    'posts' => $nPosts,
    //'boardThreads' => $boardThreads,
    //'pagenum' => $pagenum
  );
  $pipelines[PIPELINE_POST_POSTPREPROCESS]->execute($post_io);
  unset($nPosts);

  /*
  $boards = getBoards();
  foreach($boards as $c=>$b) {
    $tmp = $templates['loop0'];
    $boards_html .= $tmp . "\n";
  }
  */
  $threads_html = '';

  if (isset($overboardData['threads'])) {
    $userSettings = getUserSettings();
    global $boards_settings;
    foreach($overboardData['threads'] as $thread) {
      if (!isset($thread['posts'])) continue;
      //echo "<pre>thread[", print_r($thread, 1), "]</pre>\n";
      $posts = $thread['posts'];
      //echo "count[", count($posts), "]<br>\n";
      $bUri = $thread['boardUri'];
      // we use base tag I believe...
      $threads_html .= '<h2><a href="/' . $bUri . '/">&gt;&gt;&gt;/' . $bUri . '/</a></h2>' . $threadhdr_template;
      // FIXME: render the OP
      $threads_html .= renderPost($bUri, $thread, array(
        'checkable' => true, 'postCount' => $thread['thread_reply_count'],
        'inMixedBoards' => true, 'boardSettings' => $boards_settings[$bUri],
        'userSettings' => $userSettings,
      ));

      foreach($posts as $i => $post) {
        $threads_html .= renderPost($bUri, $post, array(
          'checkable' => true, 'postCount' => $thread['thread_reply_count'],
          'inMixedBoards' => true, 'boardSettings' => $boards_settings[$bUri],
          'userSettings' => $userSettings,
        ));
      }
      $threads_html .= $threadftr_template;
    }
  }

  $pagenum = 1; // FIXME:
  if (0) {
    $boardData = array(
      'pageCount' => 1,
      'title' => 'All Boards',
      'description' => 'posts across the site',
      'settings' => array(),
    );

    $boardPortal = getBoardPortal('overboard', $boardData, array(
      'pagenum' => $pagenum,
      'noBoardHeaderTmpl' => true, // controls banner
      'isThread' => true, // turn off paging
      //'isCatalog' => true, // prefix title
      'threadClosed' => true, // turn off post form
    ));
    // need to turn off over-catalog? over-logs? over banner?
  } else {
    $boardPortal = array(
      'header' => '',
      'footer' => '',
    );
  }

  $tags = array(
    'uri' => 'overboard',
    'title' => 'Overboard Index',
    'description' => 'content from all of our boards',
    'boards' => $boards_html,

    'pagenum' => $pagenum,
    'boardNav' => '',
    'threads' => $threads_html,
  );

  $content = replace_tags($templates['header'], $tags);

}

function component_post_listing($posts, $boardUri, $boardData, $options = false) {
  $templates = loadTemplates('thread_details');
  $tmpl = $templates['header'];

  //$boardNav_template = $templates['loop0'];
  $file_template = $templates['loop1'];
  $hasReplies_template = $templates['loop2'];
  $reply_template = $templates['loop3'];
  $post_template = $templates['loop4'];

  // only need this if we're talking about adding posting forms...

  // FIXME: wired this up with the new existing modules for these...
  // maybe even move this functionality into that module...
  $sageLimit  = empty($boardData['sageLimit']) ? 500 : $boardData['sageLimit'];
  $replyLimit = empty($boardData['replyLimit']) ? 1000 : $boardData['replyLimit'];

  /*
  if (DEV_MODE) {
    //echo "<pre>", print_r($boardData['posts'], 1),"</pre>\n";
    global $pipelines;

    $pipelines[PIPELINE_POST_PREPROCESS]->debug();
  }
  */

  // j might be pno but can't be relied on
  // FIXME sort by board and thread
  foreach($posts as $j => $post) {
    //if (DEV_MODE) {
      //echo "<pre>2", print_r($boardData['posts'][$j], 1),"</pre>\n";
    //}
    // inject uri for >> quotes
    $posts[$j]['boardUri'] = $boardUri;
    // PIPELINE_POST_PREPROCESS
    preprocessPost($posts[$j]);
  }
  global $pipelines;
  $data = array(
    'posts' => $posts,
    //'boardData' => $boardData,
    //'threadNum' => $threadNum
  );
  $pipelines[PIPELINE_POST_POSTPREPROCESS]->execute($data);

  $posts_html = '';
  $files = 0;
  $cnt = count($posts);
  $closed = false;
  // FIXME: move into a pipeline
  if ($cnt) {
    $closed = empty($posts[0]['closed']) ? false : true;
  }
  if ($cnt > $replyLimit) {
    $closed = true;
  }
  $saged = $cnt > $sageLimit;
  //echo "cnt[$cnt / $sageLimit / $replyLimit]<br>\n";
  // hack for now
  $userSettings = getUserSettings();
  //$boardSettings = getter_getBoardSettings($boardUri);
  //echo "<pre>userSettings:", print_r($userSettings, 1), "</pre>\n";
  //echo "<pre>boardUri:", print_r($boardUri, 1), "</pre>\n";
  //echo "<pre>boardData:", print_r($boardData, 1), "</pre>\n";
  foreach($posts as $post) {
    //echo "<pre>", print_r($post, 1), "</pre>\n";
    $tmp = $post_template;
    $posts_html .= renderPost($boardUri, $post, array(
      'checkable' => true, 'postCount' => $cnt,
      'noOmit' => true, 'boardSettings' => $boardData['settings'],
      'userSettings' => $userSettings,
    ));
    if (isset($post['files'])) {
      $files += count($post['files']);
    }
  }

  $p = array(
    'boardUri' => $boardUri,
    'tags' => array(
      // need this for form actions
      'uri' => $boardUri,
      //'threadNum' => $threadNum,
      //'title' => htmlspecialchars($boardData['title']),
      //'description' => htmlspecialchars($boardData['description']),

      //$tmpl = str_replace('{{boardNav}}', $boardnav_html, $tmpl);
      'posts' => $posts_html,
      'replies' => count($posts) - 1,
      'files' => $files,
      // mixins
      //'postform' => renderPostForm($boardUri, $boardUri . '/catalog'),
      //'postactions' => renderPostActions($boardUri),
    )
  );
  // what is this for?
  $pipelines[PIPELINE_BOARD_DETAILS_TMPL]->execute($p);

  // no need for thread refresh
  // maybe an option
  if (0) {
    // this will include all scripts, not just this one...
    js_add_script($pkg, 'refresh_thread.js');
  }

  $tmpl = replace_tags($tmpl, $p['tags']);
  return $tmpl;
}

?>
