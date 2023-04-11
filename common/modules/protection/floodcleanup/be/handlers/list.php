<?php
$params = $get();

$boardUri = boardOwnerMiddleware($request);
if (!$boardUri) {
  return;
}

global $db, $models, $tpp;


// group by ip
// group by sha
// group by message
$posts_model = getPostsModel($boardUri);
if ($posts_model === false) {
  // this board does not exist
  sendResponse(array(), 404, 'Board not found');
  return;
}
$priv_model = getPrivatePostsModel($boardUri);

$res = $db->find($posts_model);
$messages = array();
$com2posts = array();
while($row=$db->get_row($res)) {
  // take the sting out of xss injections
  $com = htmlspecialchars($row['com']);
  if (empty($messages[$com])) $messages[$com] = 0;
  if (empty($com2posts[$com])) $com2posts[$com] = array();
  $messages[$com]++;
  $com2posts[$com][] = $row['postid'];
}
$db->free($res);

$res = $db->find($priv_model);
$ips = array();
$ip2posts = array();
// last, first, list of post ids?
while($row=$db->get_row($res)) {
  $ip = md5(BACKEND_KEY . $boardUri . $row['ip']); // could hash for BOs
  if (empty($ips[$ip])) $ips[$ip] = 0;
  if (empty($ip2posts[$ip])) $ip2posts[$ip] = array();
  $ips[$ip]++;
  $ip2posts[$ip][] = $row['postid'];
}
$db->free($res);

$post_files_model = getPostFilesModel($boardUri);
$res = $db->find($post_files_model);
$hashes = array();
$hash2posts = array();
while($row=$db->get_row($res)) {
  if (empty($hashes[$row['sha256']])) $hashes[$row['sha256']] = 0;
  if (empty($hash2posts[$row['sha256']])) $hash2posts[$row['sha256']] = array();
  $hashes[$row['sha256']]++;
  $hash2posts[$row['sha256']][] = $row['postid'];
}
$db->free($res);

function filterMoreThanOne($var) {
  return $var > 1;
}
function filterJustOnce($var) {
  return $var === 1;
}

$interestingHashes   = array_filter($hashes, "filterMoreThanOne");
$interestingMessages = array_filter($messages, "filterMoreThanOne");
// single singles are interesting for a ddos
// multiple are normal posters or a lot is a single-ip floodergv
$interestingIps = array_filter($ips, "filterJustOnce");

$postScores = array();
foreach($interestingHashes as $hash => $cnt) {
  $postids = $hash2posts[$hash];
  foreach($postids as $id) {
    if (empty($postScores[$id])) $postScores[$id] = 0;
    $postScores[$id]+= 1; // one point for hash
  }
}
foreach($interestingMessages as $msg => $cnt) {
  $postids = $com2posts[$msg];
  foreach($postids as $id) {
    if (empty($postScores[$id])) $postScores[$id] = 0;
    $postScores[$id]+= 1; // one point for non-unique comment
  }
}

sendResponse(array(
  'hashes' => $interestingHashes,
  'messages' => $interestingMessages,
  'usedOnceIps' => $interestingIps,
  'ips' => $ips,
  'com2posts' => $com2posts,
  'ip2posts' => $ip2posts,
  'hash2posts' => $hash2posts,
  'postScores' => $postScores,
), 200, '', array('board' => $boardUri));

?>
