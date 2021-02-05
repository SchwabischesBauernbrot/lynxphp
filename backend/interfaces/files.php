<?php

function fileDBtoAPI(&$row) {
  // expect file_ fields and strip the file_
  $row = key_map(function($v) { return substr($v, 5); }, array_filter($row, function($v, $k) {
    $f5 = substr($k, 0, 5);
    return $f5 ==='file_';
  }, ARRAY_FILTER_USE_BOTH));
  $path = parsePath($row['path']);
  $thumb = $path['thumb'];
  $fp = getcwd() . '/' .  $thumb;
  //echo "path[$thumb] [", getcwd(), "] fp[$fp]<br>\n";
  if (file_exists($fp) && filesize($fp)) {
    //echo "[$thumb] exists<br>\n";
    $row['thumbnail_path'] = $thumb;
  }

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

function parsePath($filePath) {
  $parts = explode('/', $filePath);
  $filename = array_pop($parts);
  $path = join('/', $parts);
  return array(
    'file' => $filename,
    'thumb' => $path . '/t_' . $filename,
    'path' => $path,
  );
}

function make_thumbnail($filePath, $duration = 1) {
  $fileIn = escapeshellarg($filePath);

  $path = parsePath($filePath);

  $fileOut = escapeshellarg($path['thumb']);

  $width  = 320;
  $height = 200;
  //$ffmpeg

  $ffmpeg_out = array();
  $try = floor($duration / 2);
  $ffmpegPath = '/usr/bin/ffmpeg';
  //exec('$ffmpegPath -strict -2 -ss ' . $try . ' -i ' . $fileIn . ' -v quiet -an -vframes 1 -f mjpeg -vf scale=' . $width . ':' . $height .' ' . $fileOut . ' 2>&1', $ffmpeg_out, $ret);
  exec($ffmpegPath . ' -i ' . $fileIn . ' -vf scale=' . $width . ':' . $height .' ' . $fileOut . ' 2>&1', $ffmpeg_out, $ret);
  //echo "ret[$ret]<br>\n";
  if (!$ret) {
    print_r($ffmpeg_out);
  }
  /*
  // if duration fails
  if (!filesize($fileOut) && $try) {
    exec("$ffmpegPath -y -strict -2 -ss 0 -i $filename -v quiet -an -vframes 1 -f mjpeg -vf scale=$width:$height $thumbnailfc 2>&1", $ffmpeg_out, $ret);
    clearstatcache();
    if (!filesize($fileOut)) {
      return false;
    }
  }
  */
  return true;
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
    $num = (int) $num; // just be safe
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
    // FIXME: escape ext?
    $finalPath = $threadPath . '/' . $postid . '_' . $num . '.' . $ext;
    copy($srcPath, $finalPath);
    if (!file_exists($finalPath)) {
      $issues[] = $num . ' - can not copy to ' . $finalPath;
      continue;
    }
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

    $fileData = array(
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
    );

    // FIXME: thumbnail?
    global $workqueue;
    // farm it out
    $workqueue->addWork(PIPELINE_FILE, $fileData);

    $id = $db->insert($post_files_model, array($fileData));
    if (!$id) {
      $issues[] = $num . ' - '.$file['hash'] . ' database error';
    }
  }
  return $issues;
}

?>