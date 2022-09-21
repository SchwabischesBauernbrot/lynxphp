<?php

function getVideoRaw($video) {
  $command = 'ffmpeg  -i ' . escapeshellarg($video) . ' -vstats 2>&1';
  $output = shell_exec($command);
  return $output;
}

function getVideoFPS($video, $output = false) {
  if (!$output) {
    $output = getVideoRaw($video);
  }

  $regex_sizes = "/, ([0-9]{1,3}\.?[0-9]*) fps,/"; // or : $regex_sizes = "/Video: ([^\r\n]*), ([^,]*), ([0-9]{1,4})x([0-9]{1,4})/"; (code from @1owk3y)
  if (preg_match($regex_sizes, $output, $regs)) {
    //print_r($regs);
    return $regs [1] ? $regs [1] : false;
  }
  /*
  echo "Could not parse\n";
  echo $output;
  exit(1);
  */
  return false;
}

function getVideoResolution($video, $output = false) {
  if (!$output) {
    $command = 'ffmpeg  -i ' . escapeshellarg($video) . ' -vstats 2>&1';
    $output = shell_exec($command);
  }

  $regex_sizes = "/, ([0-9]{1,4})x([0-9]{1,4})[, ]\[?/"; // or : $regex_sizes = "/Video: ([^\r\n]*), ([^,]*), ([0-9]{1,4})x([0-9]{1,4})/"; (code from @1owk3y)
  if (preg_match($regex_sizes, $output, $regs)) {
    //print_r($regs);
    $width = $regs [1] ? $regs [1] : null;
    $height = $regs [2] ? $regs [2] : null;
    return array(
      'width'    => $width,
      'height'   => $height,
    );
  }
  /*
  echo "Could not parse\n";
  echo $output;
  exit(1);
  */
  return false;
}

function getVideoProperties($video) {

  $command = 'ffprobe -show_format -loglevel quiet ' . escapeshellarg($video);
  $output = shell_exec($command);
  //echo "output[$output]<br>\n";
  $lines = explode("\n", $output);
  $props = array();
  foreach($lines as $line) {
    if (strpos($line, '=') !== false) {
      list($k, $v) = explode('=', $line);
      if ($v != '') {
        $props[$k] = $v;
      } else {
        echo "[$k=$v]\n";
      }
    }
  }
  return $props;
}

function make_image_thumbnail_ffmpeg($filePath, $width, $height, $duration = 1) {
  $fileIn = $filePath;
  if (!$fileIn || !file_exists($fileIn)) {
    echo "Source file does not exists[$fileIn]<br>\n";
    return false;
  }
  $sFileIn = escapeshellarg($filePath);

  $path = parsePath($filePath);

  $fileOut = $path['thumb'];

  // clean up zero byte files to prevent prompt
  $outExists = file_exists($fileOut);
  if ($outExists && !filesize($fileOut)) {
    unlink($fileOut);
    $outExists = false;
  }
  if ($outExists) {
    echo "File[$fileOut] already exists<br>\n";
    return false;
  }

  $sFileOut = escapeshellarg($fileOut);
  $ffmpegPath = '/usr/bin/ffmpeg';

  $width = (int)$width;
  $height = (int)$height;

  $ffmpeg_out = array();
  $try = floor($duration / 2);
  //exec('$ffmpegPath -strict -2 -ss ' . $try . ' -i ' . $fileIn . ' -v quiet -an -vframes 1 -f mjpeg -vf scale=' . $width . ':' . $height .' ' . $fileOut . ' 2>&1', $ffmpeg_out, $ret);
  // webm may need -frames:v 1 or -update
  exec($ffmpegPath . ' -i ' . $sFileIn . ' -vf scale=' . $width . ':' . $height .' ' . $sFileOut . ' -frames:v 1 2>&1', $ffmpeg_out, $ret);
  echo "ret[$ret]<br>\n";
  // failure seems to be 1 (if the file already exists)
  // ret === 0 on success
  //if (!$ret) {
    echo "<pre>", print_r($ffmpeg_out, 1), "</pre>\n";
  //}
  // if duration fails
  if (!file_exists($fileOut) || !filesize($fileOut)) {
    echo "file does not exist or empty [$fileOut] ret[$ret]<br>\n";
    return false;
    //  && $trg
    /*
    exec("$ffmpegPath -y -strict -2 -ss 0 -i $filename -v quiet -an -vframes 1 -f mjpeg -vf scale=$width:$height $thumbnailfc 2>&1", $ffmpeg_out, $ret);
    clearstatcache();
    if (!filesize($fileOut)) {
      return false;
    }
    */
  }
  return true;
}

function make_audio_thumbnail_ffmpeg($filePath, $width, $height, $duration = 1) {
  $fileIn = $filePath;
  if (!$fileIn || !file_exists($fileIn)) {
    echo "Source file does not exists[$fileIn]<br>\n";
    return false;
  }
  $sFileIn = escapeshellarg($filePath);

  $path = parsePath($filePath);

  $fileOut = $path['thumb'];

  // clean up zero byte files to prevent prompt
  $outExists = file_exists($fileOut);
  if ($outExists && !filesize($fileOut)) {
    unlink($fileOut);
    $outExists = false;
  }
  if ($outExists) {
    echo "File[$fileOut] already exists<br>\n";
    return false;
  }

  $sFileOut = escapeshellarg($fileOut);
  $ffmpegPath = '/usr/bin/ffmpeg';

  $width = (int)$width;
  $height = (int)$height;

  $ffmpeg_out = array();
  $try = floor($duration / 2);
  //exec('$ffmpegPath -strict -2 -ss ' . $try . ' -i ' . $fileIn . ' -v quiet -an -vframes 1 -f mjpeg -vf scale=' . $width . ':' . $height .' ' . $fileOut . ' 2>&1', $ffmpeg_out, $ret);
  exec($ffmpegPath . ' -i ' . $sFileIn . ' -filter_complex "showwavespic=s='.$width.'x'.$height.':split_channels=1" -frames:v 1 ' . $sFileOut . ' 2>&1', $ffmpeg_out, $ret);
  echo "ret[$ret]<br>\n";
  // failure seems to be 1 (if the file already exists)
  // ret === 0 on success
  //if (!$ret) {
    echo "<pre>", print_r($ffmpeg_out, 1), "</pre>\n";
  //}
  // if duration fails
  if (!file_exists($fileOut) || !filesize($fileOut)) {
    echo "file does not exist or empty [$fileOut] ret[$ret]<br>\n";
    return false;
    //  && $trg
    /*
    exec("$ffmpegPath -y -strict -2 -ss 0 -i $filename -v quiet -an -vframes 1 -f mjpeg -vf scale=$width:$height $thumbnailfc 2>&1", $ffmpeg_out, $ret);
    clearstatcache();
    if (!filesize($fileOut)) {
      return false;
    }
    */
  }
  return true;
}

?>