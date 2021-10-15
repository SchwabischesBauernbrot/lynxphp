<?php

function processFiles($filter_fields = false) {
  $fields = $filter_fields;
  if ($fields === false) {
    // just auto-detect them
    $fields = array_keys($_FILES);
  }
  // normalized fields as an array
  if (!is_array($fields)) $fields = array($filter_fields);

  $files = array();
  if (isset($_FILES)) {
    $phpFileUploadErrors = array(
        0 => 'There is no error, the file uploaded with success',
        1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
        2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
        3 => 'The uploaded file was only partially uploaded',
        4 => 'No file was uploaded',
        6 => 'Missing a temporary folder',
        7 => 'Failed to write file to disk.',
        8 => 'A PHP extension stopped the file upload.',
    );
    //print_r($_FILES);
    foreach($fields as $field) {
      // each field could have multiple file support...
      $files[$field] = array();
      if (is_array($_FILES[$field]['tmp_name'])) {
        foreach($_FILES[$field]['tmp_name'] as $i=>$path) {
          if (!$path) {
            if (isset($_FILES[$field]['error'][$i])) {
              // usually means no file upload...
              if ($_FILES[$field]['error'][$i] !== 4) {
                echo "File upload error: ", $phpFileUploadErrors[$_FILES[$field]['error'][$i]], "<br>\n";
                echo "<pre>empty file[", print_r($_FILES[$field], 1), "</pre>\n";
              }
            } else {
              echo "<pre>empty file[", print_r($_FILES[$field], 1), "</pre>\n";
            }
            continue;
          }
          $res = sendFile($path, $_FILES[$field]['type'][$i], $_FILES[$field]['name'][$i]);
          // check for error
          if (empty($res['data']['hash'])) {
            echo "multifile - file error[", print_r($res, 1), "]<br>\n";
            return;
          }
          $files[$field][] = $res['data'];
        }
      } else {
        if ($_FILES[$field]['error'] && $_FILES[$field]['error'] !== 4) {
          echo "file PHP file upload error[", $phpFileUploadErrors[$_FILES[$field]['error']], "](", $_FILES[$field]['error'], ")<br>\n";
          return;
        }
        // make sure there is a file upload...
        if ($_FILES[$field]['error'] !== 4) {
          $res = sendFile($_FILES[$field]['tmp_name'], $_FILES[$field]['type'], $_FILES[$field]['name']);
          // check for error
          if ($res['meta']['code'] !== 200) {
            echo "fe::::lib.files:::processFiles - code[", $res['meta']['code'], "] file error[", print_r($res, 1), "]<br>\n";
            return;
          }
          if (empty($res['data']['hash'])) {
            echo "fe::::lib.files:::processFiles - no hash, file error[", print_r($res, 1), "]<br>\n";
            return;
          }
          $files[$field][] = $res['data'];
        }
      }
    }
  }
  return array(
    'errors' => array(),
    'handles' => $files,
  );
}

function getFileType($file) {
  $type = isset($file['type']) ? $file['type'] : 'image';
  if ($type === 'audio') {
    $isPlayable = $file['mime_type'] === 'audio/mpeg' || $file['mime_type'] === 'audio/wav' || $file['mime_type'] === 'audio/ogg';
    if (!$isPlayable) {
      $type = 'file';
    }
  }
  if ($type === 'video') {
    $isPlayable = $file['mime_type'] === 'video/mp4' || $file['mime_type'] === 'video/webm' || $file['mime_type'] === 'video/ogg';
    if (!$isPlayable) {
      $type = 'image';
    }
  }
  // normalized
  if ($type === 'file' || $type === 'image') $type = 'img';
  return $type;
}

function getThumbnail($file, $options = false) {
  extract(ensureOptions(array(
     // only should be used when we know we're opening a ton of requests in parallel
    'maxW' => 0,
    'type' => false,
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

  if ($type !== 'img') {
    // no thumbnail yet for video/audio
    $thumb = 'images/awaiting_thumbnail.png';
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

  if (strpos($thumb, '://') === false) {
    $thumb = BACKEND_PUBLIC_URL . $thumb;
  }
  return '<' . $type . ' class="file-thumb" src="' . $thumb . '" width="'.$w.'" height="'.$h.'" loading="lazy" controls loop preload=no />';
}

function getAudioVideo($file, $options = false) {
  extract(ensureOptions(array(
     // only should be used when we know we're opening a ton of requests in parallel
    'maxW' => 0,
    'type' => false,
  ), $options));
  if (!$type) $type = getFileType($file);
  // no view if not viewable
  if ($type === 'img') {
    return '';
  }
  // maybe don't loop audio?
  return getViewer($file, $options);
}

function getViewer($file, $options = false) {
  extract(ensureOptions(array(
     // only should be used when we know we're opening a ton of requests in parallel
    'maxW' => 0,
    'type' => false,
  ), $options));
  if (!$type) $type = getFileType($file);

  // set default, no thumb
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
  // can't loop because of how we collapse
  return '<' . $type . ' class="" src="' . $path . '" width="'.$w.'" height="'.$h.'" loading="lazy" controls  preload=none />';
}

?>
