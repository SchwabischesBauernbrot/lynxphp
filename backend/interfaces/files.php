<?php

function fileDBtoAPI(&$row) {
  // expect file_ fields and strip the file_
  $row = key_map(function($v) { return substr($v, 5); }, array_filter($row, function($v, $k) {
    $f5 = substr($k, 0, 5);
    return $f5 ==='file_';
  }, ARRAY_FILTER_USE_BOTH));
  unset($row['fileid']);
  unset($row['postid']);
  unset($row['json']);
}

// JPEG, TIFF, WAV
// https://stackoverflow.com/a/38862429
function removeExif($old, $new) {
  // Open the input file for binary reading
  $f1 = fopen($old, 'rb');
  // Open the output file for binary writing
  $f2 = fopen($new, 'wb');

  // Find EXIF marker
  while (($s = fread($f1, 2))) {
    $word = unpack('ni', $s)['i'];
    if ($word == 0xFFE1) {
      // Read length (includes the word used for the length)
      $s = fread($f1, 2);
      $len = unpack('ni', $s)['i'];
      // Skip the EXIF info
      fread($f1, $len - 2);
      break;
    } else {
      fwrite($f2, $s, 2);
    }
  }

  // Write the rest of the file
  while (($s = fread($f1, 4096))) {
    fwrite($f2, $s, strlen($s));
  }

  fclose($f1);
  fclose($f2);
}

function processFiles($boardUri, $files_json, $threadid, $postid) {
  $issues = array();
  $files = json_decode($files_json, true);
  if ($files === false) {
    $issues[] = 'json decode failure: ' . $files_json;
    return $issues;
  }
  $threadid = (int)$threadid; // prevent any .. tricks
  $postid = (int)$postid; // prevent any .. tricks

  if (!is_array($files)) {
    // ok not to have files
    //$issues[] = 'no files';
    return $issues;
  }
  global $db;
  $post_files_model = getPostFilesModel($boardUri);
  foreach($files as $num => $file) {
    if (!empty($file['meta'])) {
      $issues[] = $num . ' - no hash but got meta';
      continue;
    }
    if (empty($file['hash'])) {
      $issues[] = $num . ' - no hash';
      continue;
    }
    // move file into path
    $srcPath = 'storage/tmp/'.$file['hash'];
    if (!file_exists($srcPath)) {
      $issues[] = $num . ' - '.$file['hash'] . ' does not exist';
      continue;
    }
    $threadPath = 'storage/boards/' . $boardUri . '/' . $threadid;
    if (!file_exists($threadPath)) {
      if (!mkdir($threadPath)) {
        $issues[] = $num . ' - can not make ' . $threadPath;
        continue;
      }
    }
    $arr = explode('.', $file['name']);
    $ext = end($arr);
    $finalPath = $threadPath . '/' . $postid . '_' . $num . '.' . $ext;
    copy($srcPath, $finalPath);
    unlink($srcPath);

    $size = filesize($finalPath);
    $finfo = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
    // php 5.3+ has this by default...
    $mime = finfo_file($finfo, $finalPath);
    $m6 = substr($mime, 0, 6);

    $isImage = $m6 === 'image/';
    $isVideo = $m6 === 'video/';
    $isAudio = $m6 === 'audio/';

    $type = 'file';
    if ($isImage) $type = 'image';
    if ($isVideo) $type = 'video';
    if ($isAudio) $type = 'audio';

    $sizes = array(0, 0);
    if ($isImage) {
      $sizes = getimagesize($finalPath);
      // FIXME: strip exif from JPG
    }
    if ($isVideo) {
    }
    if ($isAudio) {
    }
    // FIXME: thumbnail?

    $id = $db->insert($post_files_model, array(array(
      'postid' => $postid,
      'sha256' => $file['hash'],
      'path'   => $finalPath,
      'ext'    => $ext,
      'browser_type' => $file['type'],
      'mime_type'    => $mime,
      'type'         => $type,
      'filename'     => $file['name'],
      'size' => $size,
      'w' => $sizes[0],
      'h' => $sizes[1],
      'filedeleted' => 0,
      'spoiler' => 0,
    )));
    if (!$id) {
      $issues[] = $num . ' - '.$file['hash'] . ' database error';
    }
  }
  return $issues;
}

?>
