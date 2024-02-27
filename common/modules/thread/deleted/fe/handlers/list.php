<?php

$params = $getHandler();

$boardUri = $params['request']['params']['uri'];

// backend will verify if BO

$res = $pkg->useResource('list', array('uri' => $boardUri));
if (!$res) {
  // maybe a 401
  wrapContent('Access denied or backend error');
  return;
}
global $pipelines;

// should probably list like normal but ?
// or at least be in the board portal
//   we can't display like a board page...
// with portal pagination? hrm...

//$html = '<pre>' . print_r($threads, 1)  . '</pre>' . "\n";
$html = '';
$html .= '<table>';
$html .= '<tr><th>Number<th>sub<th>replies<th>op del<th>del replies<Th>actions' . "\n";
$post_actions_reset = action_getLevels();
$boardSettings = getter_getBoardSettings($boardUri);
$userSettings = getUserSettings();
$nojs  = empty($userSettings['nojs'])  ? false : true;
foreach($res['threads'] as $t) {
  $wtd = $t['deleted'] && $t['replies'] === $t['del_replies'];
  // maybe a param for the link to expose content in deleted posts?
  
  // /:uri/threads/deleted/:num.html
  $link = $boardUri. '/thread/' . $t['postid'] . '.html';
  $link = $boardUri. '/threads/deleted/' . $t['postid'] . '.html';
  // pinned/cyclic? file count?
  $html .= '<tr><td><a href="' . $link . '" target=_blank>' . $t['postid'] . '</a><td>' . $t['sub'] . ($wtd ? ' (DELETED)' : '') . '<td>' . $t['replies'] . '<td>' . $t['deleted'] . '<td>' . $t['del_replies'];
  // FIXME: thread actions...
  // pretext processing...?
  $postCount = $t['replies'] - $t['del_replies'];
  $t['no'] = $t['postid'];
  $t['threadid'] = $t['postid'];
  $post_actions = $post_actions_reset;
  if ($wtd) {
    // insert undelete thread
  }
  $action_io = array(
    'boardUri' => $boardUri,
    'p' => $t,
    'actions'  => $post_actions,
    // disable because?
    // use because?
    //'postCount' => $postCount, // # of posts in thread, pass it if we have it

    // what uses this and what data does it need?
    // probably to see if things like reacts are enabled...
    'boardSettings' => $boardSettings,
  );
  if ($postCount !== false) {
    $action_io['postCount'] = $postCount;
  }
  $pipelines[PIPELINE_THREAD_ACTIONS]->execute($action_io);
  $pipelines[PIPELINE_POST_ACTIONS]->execute($action_io);
  $post_actions = $action_io['actions']; // pipeline output
  $post_actions_html = action_getExpandHtml($post_actions, array(
    'boardUri' => $boardUri, 'where' => $link, 'nojs' => $nojs));
 $html .= '<td>' . $post_actions_html . "\n";
}
$html .= '</table>';

wrapContent($html);