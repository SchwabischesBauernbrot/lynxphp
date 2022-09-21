<?php

function hasThumbnail($mediaPath) {
  // original file doesn't matter
  $path = parsePath($mediaPath);
  $thumb = $path['thumb'];
  //echo "thumb[$thumb]<br>\n";
  return file_exists($thumb);
}

function fileDBtoAPI(&$row, $boardUri) {
  // expect file_ fields and strip the file_
  $row = key_map(function($v) { return substr($v, 5); }, array_filter($row, function($v, $k) {
    $f5 = substr($k, 0, 5);
    return $f5 ==='file_';
  }, ARRAY_FILTER_USE_BOTH));
  //echo "<pre>[", print_r($row, 1), "]</pre>\n";

  // if file exists
  if (file_exists($row['path']) && filesize($row['path'])) {
    // fix size
    if (empty($row['size']) && $row['fileid']) {
      global $db;
      $post_files_model = getPostFilesModel($boardUri);
      $size = filesize($row['path']);
      $urow = array('size' => $size);
      $db->update($post_files_model, $urow, array('criteria' =>  array('fileid' => $row['fileid'])));
      $row['size'] = $size;
    }
    // fix mime_type since it drives thumbnailing
    if (empty($row['mime_type']) && $row['fileid']) {
      global $db;
      $post_files_model = getPostFilesModel($boardUri);

      $finfo = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
      // php 5.3+ has this by default...
      $mime = finfo_file($finfo, $row['path']);
      $urow = array('mime_type' => $mime);
      $db->update($post_files_model, $urow, array('criteria' =>  array('fileid' => $row['fileid'])));
      $row['mime_type'] = $mime;
    }
    $m6 = substr($row['mime_type'], 0, 6);
    $isImage = $m6 === 'image/';
    // fix image size
    if ((empty($row['w']) || empty($row['h'])) && $row['fileid'] && $isImage) {
      global $db;
      $post_files_model = getPostFilesModel($boardUri);
      $sizes = getimagesize($row['path']);
      $urow = array('w' => $sizes[0], 'h' => $sizes[1]);
      $db->update($post_files_model, $urow, array('criteria' =>  array('fileid' => $row['fileid'])));
      $row['w'] = $sizes[0];
      $row['h'] = $sizes[1];
    }
  }

  $path = parsePath($row['path']);
  $thumb = $path['thumb'];
  $fp = getcwd() . '/' .  $thumb;

  //echo "path[$thumb] [", getcwd(), "] fp[$fp]<br>\n";
  // if thumb exits
  if (file_exists($fp) && filesize($fp)) {
    //echo "[$thumb] exists<br>\n";
    $row['thumbnail_path'] = $thumb;
    // size fix up
    if ($row['fileid'] && (empty($row['tn_w']) || empty($row['tn_h']))) {
      //echo "Updating thumbnail size on [", $row['fileid'], "][",$row['tn_w'],"]x[",$row['tn_h'],"]<Br>\n";
      global $db;
      $post_files_model = getPostFilesModel($boardUri);
      if (0) {
        $sizes = getimagesize($fp);
        $row['w'] = $sizes[0];
        $row['h'] = $sizes[1];
      }
      $sizes = getThumbnailSize($row);
      $urow = array('tn_w' => $sizes[0], 'tn_h' => $sizes[1]);
      //echo "<pre>", print_r($urow, 1), "</pre>\n";
      //echo "<pre>", print_r($row, 1), "</pre>\n";
      $db->update($post_files_model, $urow, array('criteria' =>  array('fileid' => $row['fileid'])));
      $row['tn_w'] = $sizes[0];
      $row['tn_h'] = $sizes[1];
    }
  } else {
    // request generation
    //echo "Requesting generation of [", $row['path'], "]<br>\n";
    global $workqueue;
    $row['boardUri'] = $boardUri;
    $workqueue->addWork(PIPELINE_FILE, $row);
    // but how do we not loop?
  }

  unset($row['fileid']);
  unset($row['postid']);
  unset($row['json']);
}

// FIXME: apply this
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

  // ensure jpg thumbnail output
  $parts = explode('.', $filename);
  $ext = array_pop($parts);
  $filename = join('.', $parts) . '.jpg';

  return array(
    'file' => $filename,
    'thumb' => $path . '/t_' . $filename,
    'path' => $path,
  );
}

function getPostFiles($boardURI, $post_id) {
  $post_files_model = getPostFilesModel($boardURI);
  if (!$post_files_model) {
    return false;
  }
  global $db;
  $res = $db->find($post_files_model, array('criteria' => array('postid' => $post_id)));
  return $db->toArray($res);
}

function getThumbnailSize($row) {
  $m6 = substr($row['mime_type'], 0, 6);

  $isImage = $m6 === 'image/';
  $isVideo = $m6 === 'video/';
  $isAudio = $m6 === 'audio/';

  $urow = array();
  //echo "mime[", $row['mime_type'], "] isImage[$isImage]<br>\n";
  if ($isImage || $isVideo) {
    list($urow['tn_w'], $urow['tn_h']) = scaleSize($row['w'], $row['h'], 240);
    $urow['tn_w'] = (int)$urow['tn_w'];
    $urow['tn_h'] = (int)$urow['tn_h'];
  } else {
    // audio doesn't have a size
    $urow['tn_w'] = 240;
    $urow['tn_h'] = 240;
  }
  return array($urow['tn_w'], $urow['tn_h']);
}

function scaleSize($w, $h, $maxh = 240) {
  // calculate thumbnail size
  $tn_w = $w;
  $tn_h = $h;
  while($tn_w > $maxh) {
    $tn_w *= 0.9;
    $tn_h *= 0.9;
  }
  return array($tn_w, $tn_h);
}

function make_thumbnail($fileData, $duration = 1) {
  /*
  $w = $fileData['w'];
  $h = $fileData['h'];
  while($w > 240) {
    $w *= 0.9;
    $h *= 0.9;
  }
  */
  $m6 = substr($fileData['mime_type'], 0, 6);

  $isImage = $m6 === 'image/';
  $isVideo = $m6 === 'video/';
  $isAudio = $m6 === 'audio/';

  //echo "isImage[$isImage]<br>\n";
  //echo "<pre>", print_r($fileData, 1), "</pre>\n";

  $updateThumbSize = false;
  $sizes = getThumbnailSize($fileData);
  if (empty($fileData['tn_w']) || empty($fileData['tn_h'])) {
    $updateThumbSize = true;
  }
  $fileData['tn_w'] = $sizes[0];
  $fileData['tn_h'] = $sizes[1];
  if ($isImage) {
    if (!make_image_thumbnail_ffmpeg($fileData['path'], $fileData['tn_w'], $fileData['tn_h'], $duration)) {
      // fail
      echo "Failed<br>\n";
      return;
    }
  } elseif ($isAudio) {
    // the image will actually extract album artwork I think

    // FIXME: move this logic into make_audio_thumbnail_ffmpeg

    //echo "Making audio thumb<br>\n";
    if (!make_image_thumbnail_ffmpeg($fileData['path'], $fileData['tn_w'], $fileData['tn_h'], $duration)) {
      // fail
      //echo "no album artwork<br>\n";
      if (!make_audio_thumbnail_ffmpeg($fileData['path'], $fileData['tn_w'], $fileData['tn_h'], $duration)) {
        // fail
        echo "Failed<br>\n";
        return;
      }
    }
  } elseif ($isVideo) {
    // should work fine...
    if (!make_image_thumbnail_ffmpeg($fileData['path'], $fileData['tn_w'], $fileData['tn_h'], $duration)) {
      // fail
      echo "Failed<br>\n";
      return;
    }
  }
  // get final size?
  // may not save any writes at all if the size differes
  if ($updateThumbSize && $fileData['fileid']) {
    // write thumbnail size to db
    if (empty($fileData['boardUri'])) {
      echo "Would update thumbsize but no boardUri<br>\n";
      return;
    }
    global $db;
    $post_files_model = getPostFilesModel($fileData['boardUri']);
    $urow = array(
      'tn_w' => (int)$fileData['tn_w'],
      'tn_h' => (int)$fileData['tn_h'],
    );
    //echo "Updating[", $fileData['boardUri'], "] [", $fileData['fileid'], "] to [", print_r($urow, 1), "]<br>\n";
    $db->update($post_files_model, $urow, array('criteria' =>  array('fileid' => $fileData['fileid'])));
  }
}

// might be a big delay between upload and this call
function processFiles($boardUri, $files_json, $threadid, $postid) {
  $issues = array();
  $files = json_decode($files_json, true);
  if ($files === null) {
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

    $finfo = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
    // php 5.3+ has this by default...
    $mime = finfo_file($finfo, $srcPath);
    $m6 = substr($mime, 0, 6);

    $isImage = $m6 === 'image/';
    $isVideo = $m6 === 'video/';
    $isAudio = $m6 === 'audio/';

    // FIXME: escape ext?
    $finalPath = $threadPath . '/' . $postid . '_' . $num . '.' . $ext;

    if ($isImage) {
      removeExif($srcPath, $finalPath);
    } else {
      copy($srcPath, $finalPath);
    }
    if (!file_exists($finalPath)) {
      $issues[] = $num . ' - can not copy to ' . $finalPath;
      continue;
    }
    unlink($srcPath);

    $size = filesize($finalPath);
    $type = 'file';
    if ($isImage) $type = 'image';
    if ($isVideo) $type = 'video';
    if ($isAudio) $type = 'audio';

    $sizes = array(0, 0);
    if ($isImage) {
      $sizes = getimagesize($finalPath);
    }
    if ($isVideo) {
      $vr = getVideoResolution($finalPath);
      $sizes = array($vr['width'], $vr['height']);
    }
    if ($isAudio) {
      $sizes = array(240, 240);
    }

    // calculate thumbnail size
    list($tn_w, $tn_h) = scaleSize($sizes[0], $sizes[1], 240);

    // prepare database record
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
      'tn_w' => (int)$tn_w,
      'tn_h' => (int)$tn_h,
      'filedeleted' => 0,
      'spoiler' => 0,
    );

    // FIXME: thumbnail?

    $id = $db->insert($post_files_model, array($fileData));
    if (!$id) {
      $issues[] = $num . ' - '.$file['hash'] . ' database error';
      continue;
    }

    // farm out thumbnailing
    global $workqueue;
    $fileData['fileid'] = $id; // set fileid
    $fileData['boardUri'] = $boardUri;
    $workqueue->addWork(PIPELINE_FILE, $fileData);

  }
  return $issues;
}

?>