<?php

$params = $getHandler();

$boardUri = $request['params']['uri'];

// get a list of banners from backend
$qp = $pkg->useResource('get_pending_post', array('boardUri' => $boardUri));

/*
if (!empty($res['setCookie'])) {
  setcookie('session', $res['setCookie']['session'], $res['setCookie']['ttl'], '/');
}
*/

global $boardData;
if (empty($boardData)) {
  //echo "Pulling boardData<br>\n";
  $boardData = getBoard($boardUri);
  //print_r($boardData);
}
//print_r($boardData);
$boardPortal = getBoardPortal($boardUri, $boardData);

if (!$qp) {
  // all sorts of errors end up here
  $templates = moduleLoadTemplates('empty', __DIR__);
  return wrapContent($boardPortal['header'] . $templates['header']  . $boardPortal['footer']);
}
if ($qp['board_uri'] !== $boardUri) {
  return wrapContent($boardPortal['header'] . 'Backend returned wrong board, please reload.' . $boardPortal['footer']);
}

$templates = moduleLoadTemplates('moderate', __DIR__);

//$boardUri = $qp['board_uri'];
$id = $qp['queueid']; // even if I hash it, they can refer to a single
// and what's the issue if they can predict the next
$post = $qp['post'];
//print_r($post['files'][0]);

// probably could be common
$fields = $shared['community_moderate_fields']; // imported from shared.php

unset($fields['captcha']);

$form_html = generateForm($params['action'], $fields, array(), array(
  'actionName' => 'vote',
  'firstAction' => 'allow',
  'buttonLabel' => 'Others would approve',
  'secondAction' => 'deny',
  'button2Label' => 'Others wouldn\'t approve',
));

//print_r($post);
$tags = array(
  //
  'post' => renderPost($boardUri, $post),
  'form' => $form_html,
  //'others_allow_link' => $boardUri . '/moderate/' . $id . '/allow',
  //'others_deny_link' => $boardUri . '/moderate/' . $id . '/deny',
);

wrapContent($boardPortal['header'] . replace_tags($templates['header'], $tags) . $boardPortal['footer']);

?>
