<?php
$params = $get();

$uri = $params['params']['boardUri'];
// FIXME: bo check

// get a list of tags
$tags = tagPost_getAll();

// get board settings
$board = getBoard($uri, array('jsonFields' => array('settings')));

// generate values
$values = array();

// make sure each tag has a value
foreach($tags as $key => $tag) {
  $values[$key] = empty($board['settings']['post_queueing'][$key]) ? '' : $board['settings']['post_queueing'][$key];
}

// send values
sendResponse2(array(
  'tags' => $tags,
  'values' => $values
), array(
  'meta' => array('boardUri' => $uri, 'board' => $board)
));

?>