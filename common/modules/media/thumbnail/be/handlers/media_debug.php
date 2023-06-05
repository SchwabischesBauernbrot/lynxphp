<?php

$params = $get();

$uri = $params['params']['uri'];
$pid = $params['params']['pid'];

$files = getPostFiles($uri, $pid);

$res = array();
foreach($files as $i => $f) {
  $path = parsePath($f['path']);
  $res[$i] = array(
    'data' => $f,
    'paths' => $path,
    'source' => array(
      'path' => $f['path'],
      's' => filesize($path['path']),
      'm' => md5_file($path['path']),
      'w' => $f['w'],
      'h' => $f['h'],
    ),
    'thumb' => array(
      'path' => $path['thumb'],
      'e' => hasThumbnail($f['path']),
      's' => filesize($path['thumb']),
      'm' => md5_file($path['thumb']),
      'w' => $f['tn_w'],
      'h' => $f['tn_h'],
    ),
  );
  // isVideo?
  if (strpos($f['mime_type'], 'video/') !== false || strpos($f['browser_type'], 'video/') !== false) {
    $prop = getVideoProperties($f['path']);
    $ffout = getVideoRaw($f['path']);
    $reso = getVideoResolution(false, $ffout);
    $fps = getVideoFPS(false, $ffout);
    $res[$i]['video'] = array(
      'prop' => $prop,
      'reso' => $reso,
      'fps' => $fps,
    );
  }
}
foreach($files as $i => $f) {
  fileDBtoAPI($f, $uri); // cue thumb building if needed
}

// sendResponse2: code, err, mtime, meta
sendResponse2($res);

?>