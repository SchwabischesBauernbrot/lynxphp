<?php
$params = $get();

global $db, $models;
$res = $db->find($models['post_queue']);
$qposts = $db->toArray($res);

$post_files = array();
// FIXME: simulate thumbnail sizes...

foreach($qposts as $i => $qp) {
  $data = json_decode($qp['data'], true);
  $post = $data['post'];
  postDBtoAPI($post);
  $qposts[$i]['post'] = $post;
  $qposts[$i]['post']['created_at'] = $qp['created_at'];
  $qposts[$i]['post']['files'] = $post_files;

}

// would be nice to extract all the links from the posts

// a list of board settings
$res = $db->find($models['board']);
$boards = $db->toArray($res);

// only report ones with it on
$boardsThatQ = array();
foreach($boards as $b) {
  $cf = json_decode($b['json'], true);
  //print_r($cf['settings']);
  if (!empty($cf['settings']['post_queueing'])) {
    $boardsThatQ[$b['uri']] = $cf['settings']['post_queueing'];
  }
}

// send values
sendResponse2(array(
  'queue_posts' => $qposts,
  'boards' => $boardsThatQ,
));

?>