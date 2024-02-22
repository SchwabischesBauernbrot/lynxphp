<?php

$params = $get();

$uri = $params['params']['uri'];
$pid = $params['params']['pid'];

$files = getPostFiles($uri, $pid);

$res = array();
foreach($files as $i => $f) {
  //
  $path = parsePath($f['path']);
  $res[$i] = array(
    //'p' => $path['thumb'],
    'e' => hasThumbnail($f['path']),
    'w' => $f['tn_w'],
    'h' => $f['tn_h'],
  );
}

// sendResponse2: code, err, mtime, meta
sendJson($res);

?>