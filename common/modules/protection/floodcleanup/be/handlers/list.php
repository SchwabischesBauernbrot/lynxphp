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

// we could give the raw data and let the frontend crunch
// but a lot less if we just send what they need...

$posts_model = getPostsModel($boardUri);
if ($posts_model === false) {
  // this board does not exist
  sendResponse(array(), 404, 'Board not found');
  return;
}
$priv_model = getPrivatePostsModel($boardUri);
$post_files_model = getPostFilesModel($boardUri);

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


// messages
$res = $db->find($posts_model);
$messages = array();
$com2posts = array();

$posts = array();
while($row = $db->get_row($res)) {
  $pid = $row['postid'];

  if (!isset($posts[$pid])) {
    // take the sting out of xss injections
    $com = htmlspecialchars($row['com']);
    if (empty($messages[$com])) $messages[$com] = 0;
    if (empty($com2posts[$com])) $com2posts[$com] = array();
    $messages[$com]++;
    $com2posts[$com][] = $row['postid'];
    $prow = $row;
    if (!$row['threadid']) {
      threadDBtoAPI($prow, $boardUri);
    } else {
      postDBtoAPI($prow, $boardUri);
    }
    $posts[$pid]= $prow;
    $posts[$pid]['files'] = array();
  }
  if (!empty($row['file_fileid'])) {
    $fid = $row['file_fileid'];
    if (!isset($posts[$pid]['files'][$fid])) {
      $frow = $row;
      fileDBtoAPI($frow, $boardUri);
      $posts[$pid]['files'][$fid] = $frow;
    }
  }
}
$db->free($res);

foreach($posts as $pk => $p) {
  $posts[$pk]['files'] = array_values($posts[$pk]['files']);
}
// we'll leave the post ids in


// ips
$res = $db->find($priv_model);
$ips = array();
$ip2posts = array();
// last, first, list of post ids?
while($row = $db->get_row($res)) {
  $ip = md5(BACKEND_KEY . $boardUri . $row['ip']); // could hash for BOs
  if (empty($ips[$ip])) $ips[$ip] = 0;
  if (empty($ip2posts[$ip])) $ip2posts[$ip] = array();
  $ips[$ip]++;
  $ip2posts[$ip][] = $row['postid'];
}
$db->free($res);

// sha
$res = $db->find($post_files_model, array(
  // order it chronologically
  'order' => 'created_at',
));
$hashes = array();
$hash2posts = array();

// find range of posts where the same file is upload with the same text
$sinceLast = 30; // within 30 seconds
$lastRow = false;
$lastSha = false;
$rangeStart = false;
$ranges = array();
$samples = array();
while($row=$db->get_row($res)) {
  if (empty($hashes[$row['sha256']])) $hashes[$row['sha256']] = 0;
  if (empty($hash2posts[$row['sha256']])) $hash2posts[$row['sha256']] = array();
  $hashes[$row['sha256']]++;
  $hash2posts[$row['sha256']][] = $row['postid'];

  $samples[$row['sha256']] = $row['path'];

  if ($lastSha !== false) {
    if ($row['sha256'] === $lastSha) {
      // found a streak
      if ($rangeStart === false) {
        // start a streak
        $rangeStart = $lastRow['postid'];
      } else {
        // we're in a streak
        // it's the same, nothing to do
      }
    } else {
      // FIXME: they could spam a group of images...
      if ($rangeStart !== false) {
        // streak broken
        $ranges []= array(
          'start' => $rangeStart,
          'end'   => $lastRow['postid'],
          'sha'   => $lastSha,
        );
        $rangeStart = false;
      }
    }
  }
  $lastSha = $row['sha256'];
  $lastRow = $row;
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
// we can do this on the frontend tbh
//$interestingIps = array_filter($ips, "filterJustOnce");

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

// do we need hashes when we have hash2posts
// interestingHashes are filtered down where hash2posts is complete...
// but we could do the filtering on the fe side
sendResponse(array(
  'hashes' => $interestingHashes,
  'ranges' => $ranges,
  'messages' => $interestingMessages,
  //'usedOnceIps' => $interestingIps,
  'ips' => $ips,
  'com2posts' => $com2posts,
  'ip2posts' => $ip2posts,
  'hash2posts' => $hash2posts,
  'postScores' => $postScores,
  'samples' => $samples,
  'posts' => $posts,
), 200, '', array('board' => $boardUri));

?>
