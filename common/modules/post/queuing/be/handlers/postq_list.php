<?php
$params = $get();

global $db, $models, $pipelines;
$adjustedModel = $models['post_queue'];
$adjustedModel['children'] = array(
  array(
    'type' => 'left',
    'model' => $models['post_queue_vote'],
    'pluck' => array('count(ALIAS.voteid) as votes'),
    'on' => array(
      // LEFT: not post_queueid but queueid
      array('queueid', '=', $db->make_direct('post_queues.queueid'))
    ),
    'groupby' => array('post_queues.queueid'),
  ),
);
$res = $db->find($adjustedModel);
$qposts = $db->toArray($res);

$post_files = array();
// FIXME: simulate thumbnail sizes...

// there can be 2k of these
// maybe segment by board?

foreach($qposts as $i => $qp) {
  // row from qposts passes through
  $data = json_decode($qp['data'], true);
  $post = $data['post'];
  postDBtoAPI($post, $qp['board_uri']);
  $qposts[$i]['post'] = $post;
  $qposts[$i]['post']['created_at'] = $qp['created_at'];
  $qposts[$i]['post']['files'] = $post_files;
  $io = $qposts[$i];
  $pipelines[PIPELINE_BE_ADMIN_QUEUE_DATA]->execute($io);
  $qposts[$i] = $io;
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