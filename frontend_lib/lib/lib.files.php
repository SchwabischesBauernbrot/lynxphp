<?php

/** send $_FILES to BE and get handles */
function processPostFiles() {
  $postSpoilers = getOptionalPostField('spoilers');
  $spoil = array();
  if (is_array($postSpoilers)) {
    foreach($postSpoilers as $h) {
      $spoil[$h] = true;
    }
  }
  $postStrips = getOptionalPostField('strip_filenames');
  $strip = array();
  if (is_array($postStrips)) {
    foreach($postStrips as $h) {
      $strip[$h] = true;
    }
  }
  $result = processFilesVar(array('files'));
  $handles = array();
  $hasErrors = false;
  foreach($result as $field => $files) {
    $handles[$field] = array();
    foreach($files as $f) {
      if (empty($f['error'])) {
        // send to BE
        $res = sendFile($f['tmp_name'], $f['type'], $f['name']);
        // check for error
        if ($res['meta']['code'] === 200 && !empty($res['data']['hash'])) {
          // type, name, size, hash
          // js can calculate the sha256...
          // if it was passed to us, we could verify it
          $h = $res['data']['hash'];
          if (!empty($spoil[$h])) {
            // would prefer
            //$res['data']['spoil'] = 1;
            // but lynxchan api
            $res['data']['spoiler'] = true;
          }
          if (!empty($strip[$h])) {
            // get need to get the extension
            $ext = pathinfo($res['data']['name'], PATHINFO_EXTENSION);
            global $now;
            $res['data']['name'] = str_replace('.', '', $now) . '.' . $ext;
            // hash could be md5 or sha1?
            // matching on size / mime is more important
          }
          $handles[$field][] = $res['data'];
        } else {
          $hasErrors = true;
          // res has to be a string, not array
          $handles[$field][] = array('error' => $res['meta']['error']);
        }
      } else {
        $hasErrors = true;
        $handles[$field][] = $f; // just passthru error, debug
      }
    }
  }
  return array(
    'hasErrors' => $hasErrors,
    'handles' => $handles,
    'debug' => array(
      'postSpoilers' => $postSpoilers,
      'postStrips' => $postStrips,
      'strips' => $strip,
      'spoils' => $spoil,
    )
  );
}

function getFileType($file) {
  $type = isset($file['type']) ? $file['type'] : 'image';
  if ($type === 'audio') {
    $isPlayable = $file['mime_type'] === 'audio/mpeg' || $file['mime_type'] === 'audio/wav' || $file['mime_type'] === 'audio/ogg'  || $file['mime_type'] === 'audio/flac';
    if (!$isPlayable) {
      $type = 'file';
    }
  }
  if ($type === 'video') {
    // browsers affect this
    $isPlayable = $file['mime_type'] === 'video/mp4' || $file['mime_type'] === 'video/webm' || $file['mime_type'] === 'video/ogg' || $file['mime_type'] === 'video/quicktime' || $file['mime_type'] === 'video/x-flv';
    if (!$isPlayable) {
      $type = 'image';
    }
  }
  // normalized
  if ($type === 'file' || $type === 'image') $type = 'img';
  return $type;
}

function getThumbnailWidth($file, $options = false) {
  extract(ensureOptions(array(
     // only should be used when we know we're opening a ton of requests in parallel
    'maxW' => 0,
    'type' => false,
  ), $options));
  if (!$type) $type = getFileType($file);

  // thumbnailable?
  if ($type === 'img' || $type === 'audio' || $type === 'video') {
    if (isset($file['thumbnail_path'])) {
      $type = 'img';
    }
  }

  if ($type !== 'img') {
    // no thumbnail yet for video/audio
    $file['tn_w'] = 209;
    $file['tn_h'] = 64;
    $type = 'img';
  }

  // figure out thumb size
  if (empty($file['tn_w']) || empty($file['tn_h'])) {
    $w = $file['w'];
    $h = $file['h'];
    while($h > 240) {
      $w *= 0.9;
      $h *= 0.9;
    }
  } else {
    $w = $file['tn_w'];
    $h = $file['tn_h'];
  }

  if ($maxW !== 0) {
    $w = $file['w'];
    $h = $file['h'];
    // audio/video won't have these set yet... but thumbnail will be
    if (empty($file['w']) || empty($file['h'])) {
      $w = $file['tn_w'];
      $h = $file['tn_h'];
    }
    while($w > $maxW) {
      $w *= 0.9;
      $h *= 0.9;
    }
  }
  if (!$w || !$h) {
    $w = 240;
    $h = 240;
  }
  $w = (int)$w;
  $h = (int)$h;
  return $w;
}

function getThumbnail($file, $options = false) {
  extract(ensureOptions(array(
     // only should be used when we know we're opening a ton of requests in parallel
    'maxW' => 0,
    'type' => false,
    'alt' => 'thumbnail',
    'spoiler' => false,
    'noLazyLoad' => false,
  ), $options));
  if (!$type) $type = getFileType($file);

  // set default, no thumb
  $thumb = $file['path'];

  // thumbnailable?
  if ($type === 'img' || $type === 'audio' || $type === 'video') {
    if (isset($file['thumbnail_path'])) {
      $thumb = $file['thumbnail_path'];
      $type = 'img';
    }
  }

  if (strpos($thumb, '://') === false) {
    $thumb = BACKEND_PUBLIC_URL . $thumb;
  }
  if (!empty($file['type']) && $file['type'] === 'file') {
    $thumb = 'images/imagelessthread.png';
    $file['tn_w'] = 209;
    $file['tn_h'] = 64;
    $type = 'img';
  }
  if ($type !== 'img') {
    // thumbnail maybe processing still
    $thumb = 'images/awaiting_thumbnail.png';
    $file['tn_w'] = 209;
    $file['tn_h'] = 64;
    $type = 'img';
  }

  if ($spoiler) {
    $thumb = $spoiler['url'];
    $file['tn_w'] = $spoiler['w'];
    $file['tn_h'] = $spoiler['h'];
  }

  // figure out thumb size
  if (empty($file['tn_w']) || empty($file['tn_h'])) {
    $w = $file['w'];
    $h = $file['h'];
    while($h > 240) {
      $w *= 0.9;
      $h *= 0.9;
    }
  } else {
    $w = $file['tn_w'];
    $h = $file['tn_h'];
  }

  if ($maxW !== 0) {
    $w = $file['w'];
    $h = $file['h'];
    // audio/video won't have these set yet... but thumbnail will be
    if (empty($file['w']) || empty($file['h'])) {
      if (!empty($file['tn_w']) && !empty($file['tn_h'])) {
        $w = $file['tn_w'];
        $h = $file['tn_h'];
      }
    }
    while($w > $maxW) {
      $w *= 0.9;
      $h *= 0.9;
    }
  }
  if (!$w || !$h) {
    $w = 240;
    $h = 240;
  }
  $w = (int)$w;
  $h = (int)$h;

  $loading = $noLazyLoad ? '' : 'loading="lazy"';
  //$spoilerClass = $spoiler ? ' spoilerimg' : '';
  return '<img class="file-thumb" src="' . $thumb . '" width="'.$w.'" height="'.$h.'" ' . $loading . ' alt="' . $alt . '" />';
}

function getAudioVideo($file, $options = false) {
  extract(ensureOptions(array(
     // only should be used when we know we're opening a ton of requests in parallel
    'maxW' => 0,
    'loop' => true,
    'mute' => false,
    'type' => false,
  ), $options));
  if (!$type) $type = getFileType($file);
  // no video/audio if an image
  if ($type === 'img') {
    return '';
  }
  // maybe don't loop audio?
  return getViewer($file, $options);
}

// anything use this besides getAudioVideo?
// this is the full player not the thumb
function getViewer($file, $options = false) {
  extract(ensureOptions(array(
     // only should be used when we know we're opening a ton of requests in parallel
    'maxW' => 0,
    'loop' => true,
    'mute' => false,
    'type' => false,
  ), $options));
  if (!$type) $type = getFileType($file);

  // set default, no thumb
  //$path = 'watch/' . $file['path'];
  $path = $file['path'];

  // no view if not viewable
  if (!($type === 'img' || $type === 'audio' || $type === 'video')) {
    return false;
  }

  // figure out size
  $w = $file['w'];
  $h = $file['h'];

  if ($maxW !== 0) {
    $w = $file['w'];
    $h = $file['h'];
    // audio/video won't have these set yet... but thumbnail will be
    if (empty($file['w']) || empty($file['h'])) {
      $w = $file['tn_w'];
      $h = $file['tn_h'];
    }
    while($w > $maxW) {
      $w *= 0.9;
      $h *= 0.9;
    }
  }
  if (!$w || !$h) {
    $w = 240;
    $h = 240;
  }
  $w = (int)$w;
  $h = (int)$h;

  if (strpos($path, '://') === false) {
    $path = BACKEND_PUBLIC_URL . $path;
  }
  // loop
  //echo "loop[$loop] mute[$mute]<br>\n";
  $loopAtt = $loop ? ' loop=true' : '';
  $muteAtt = $mute ? ' muted=true' : '';
  // poster (show this while downloading)
  // can't loop because of how we collapse
  // class="" ?
  // autoplay=true seemed to make chrome49 and icecat download and play all thumbs without clicking on them
  return '<' . $type . '  src="' . $path . '" width="'.$w.'" height="'.$h.'" loading="lazy" controls' . $loopAtt . $muteAtt . ' preload=none />';
}

?>
