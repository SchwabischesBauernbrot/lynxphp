<?php

// seems to be aleady included... not in setup.php
//ldr_require('../frontend_lib/handlers/boards.php');

// option to turn off refresh thread
function renderThread($boardData, $tno, $options = false) {
  $threadNum = $tno;
  $boardUri = $boardData['uri'];

  $templates = loadTemplates('thread_details');
  $tmpl = $templates['header'];

  //$boardNav_template = $templates['loop0'];
  $file_template = $templates['loop1'];
  $hasReplies_template = $templates['loop2'];
  $reply_template = $templates['loop3'];
  $post_template = $templates['loop4'];

  /*
  $tmp = $boardNav_template;
  $tmp = str_replace('{{uri}}', $boardUri, $tmp);
  $boardnav_html = $tmp;
  */

  //$boardData = getBoardThread($boardUri, $threadNum);
  // need to git mv handler

  /*
  global $boardData; // make it cachable
  $boardData = $pkg->useResource('board_thread', array('uri' => $boardUri, 'num' => $threadNum));
  if ($boardData === false) {
    http_response_code(404);
    wrapContent('Board ' . $boardUri . ' does not exist');
    return;
  }

  // MISSING_BOARD just means no board key in data...
  // empty may pick up an valid empty array
  if (!isset($boardData['title']) || !isset($boardData['posts']) || $boardData['posts'] === false) {
    http_response_code(404);
    wrapContent('This thread does not exist');
    return;
  }

  // lynxchan bridge error handling:
  // uri and settings: array(), pageCount: 15 will be set
  if (!isset($boardData['title'])) {
    http_response_code(404);
    wrapContent('Board ' . $boardUri . ' does not exist');
    return;
  }
  if (!isset($boardData['posts'])) {
    http_response_code(404);
    wrapContent('This thread does not exist');
    return;
  }
  */
  //echo "<pre>", $boardData['sageLimit'], "</pre>\n";

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

  foreach($boardData['posts'] as $j => $post) {
    //if (DEV_MODE) {
      //echo "<pre>2", print_r($boardData['posts'][$j], 1),"</pre>\n";
    //}
    // inject uri for >> quotes
    $boardData['posts'][$j]['boardUri'] = $boardUri;
    // PIPELINE_POST_PREPROCESS
    preprocessPost($boardData['posts'][$j]);
  }

  global $pipelines;
  $data = array(
    'posts' => $boardData['posts'],
    'boardData' => $boardData,
    'threadNum' => $threadNum
  );
  $pipelines[PIPELINE_POST_POSTPREPROCESS]->execute($data);

  $posts_html = '';
  $files = 0;
  $cnt = count($boardData['posts']);
  $closed = false;
  // FIXME: move into a pipeline
  if (count($boardData['posts'])) {
    $closed = empty($boardData['posts'][0]['closed']) ? false : true;
  }
  if ($cnt > $replyLimit) {
    $closed = true;
  }
  $saged = $cnt > $sageLimit;
  //echo "cnt[$cnt / $sageLimit / $replyLimit]<br>\n";
  // hack for now
  // well if we put it in the thread then that's less cachable
  // but what's more costly, multiple (partial) requests or one full request
  // a minimum partial request is like always 100-120ms on galaxy regardless if it's full or not
  $userSettings = getUserSettings();
  //echo "<pre>userSettings:", print_r($userSettings, 1), "</pre>\n";
  foreach($boardData['posts'] as $post) {
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
      'threadNum' => $threadNum,
      'title' => htmlspecialchars($boardData['title']),
      'description' => htmlspecialchars($boardData['description']),
      //$tmpl = str_replace('{{boardNav}}', $boardnav_html, $tmpl);
      'posts' => $posts_html,
      'replies' => count($boardData['posts']) - 1,
      'files' => $files,
      // mixins
      //'postform' => renderPostForm($boardUri, $boardUri . '/catalog'),
      //'postactions' => renderPostActions($boardUri),
    )
  );
  $pipelines[PIPELINE_BOARD_DETAILS_TMPL]->execute($p);
  $tmpl = replace_tags($tmpl, $p['tags']);

  /*
  $boardPortal = getBoardPortal($boardUri, $boardData, array(
    'isThread' => true,
    'threadNum' => $threadNum,
    'threadClosed' => $closed,
    'threadSaged'  => $saged,
    'maxMessageLength' => $boardData['maxMessageLength'],
  ));
  */
  // this will include all scripts, not just this one...
  global $packages;
  js_add_script($packages['base_thread_view'], 'refresh_thread.js');

  //echo "<pre>", print_r($boardData['posts'][0], 1), "</pre>\n";
  $title = ''; // -
  if (!empty($boardData['posts'][0]['sub'])) {
    $title .= $boardData['posts'][0]['sub'] . ' - ';
  }
  if ($boardData['title']) {
    $title .= $boardData['title'];
  }
  // then site title

  // $boardPortal['header'] .  . $boardPortal['footer']
  //wrapContent($tmpl);
  return array(
    'title' => $title,
    'html' => $tmpl,
  );
}

?>
