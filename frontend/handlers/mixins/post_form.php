<?php

// https://stackoverflow.com/a/22500394
function convertPHPSizeToBytes($sSize) {
  //
  $sSuffix = strtoupper(substr($sSize, -1));
  if (!in_array($sSuffix,array('P','T','G','M','K'))){
    return (int)$sSize;
  }
  $iValue = substr($sSize, 0, -1);
  switch ($sSuffix) {
    case 'P':
      $iValue *= 1024;
      // Fallthrough intended
    case 'T':
      $iValue *= 1024;
      // Fallthrough intended
    case 'G':
      $iValue *= 1024;
      // Fallthrough intended
    case 'M':
      $iValue *= 1024;
      // Fallthrough intended
    case 'K':
      $iValue *= 1024;
      break;
  }
  return (int)$iValue;
}

// https://stackoverflow.com/a/19570313
function asBytes($ini_v) {
  $ini_v = trim($ini_v);
  $s = [ 'g'=> 1<<30, 'm' => 1<<20, 'k' => 1<<10 ];
  return intval($ini_v) * ($s[strtolower(substr($ini_v,-1))] ?: 1);
}

// https://stackoverflow.com/a/49122313
function number_abbr($number) {
  $abbrevs = [12 => 'T', 9 => 'B', 6 => 'M', 3 => 'K', 0 => ''];

  foreach ($abbrevs as $exponent => $abbrev) {
    if (abs($number) >= pow(10, $exponent)) {
      $display = $number / pow(10, $exponent);
      $decimals = ($exponent >= 3 && round($display) < 100) ? 1 : 0;
      $number = number_format($display, $decimals).$abbrev;
      break;
    }
  }

  return $number;
}

function renderPostForm($boardUri, $url, $options = false) {
  global $pipelines;

  $templates = loadTemplates('mixins/post_form');

  $type = 'Thread';
  $tagThread = '';
  if ($options) {
    if (!empty($options['reply'])) {
      $type = 'Reply';
      $tagThread = '<input type="hidden" name="thread" value="' . $options['reply'] . '">';
    }
    // this is for posting...
    /*
    if ($options['page']) {
      $tagThread = '<input type="hidden" name="page" value="' . $options['page'] . '">';
    }
    */
  }

  $tmp = $templates['header'];
  // used to set the post action
  $tmp = str_replace('{{uri}}',       $boardUri, $tmp);
  $tmp = str_replace('{{type}}',      $type,     $tmp);
  // inject what it's a reply to
  $tmp = str_replace('{{tagThread}}', $tagThread, $tmp);
  $tmp = str_replace('{{action}}',    $url,       $tmp);
  // FIXME: we need to be able to override webserver...
  $tmp = str_replace('{{maxlength}}', '4096', $tmp);
  $maxfiles = convertPHPSizeToBytes(ini_get('max_file_uploads'));
  $tmp = str_replace('{{maxfiles}}', $maxfiles, $tmp);
  $maxfilesize = min(convertPHPSizeToBytes(ini_get('post_max_size')), convertPHPSizeToBytes(ini_get('upload_max_filesize')));
  $tmp = str_replace('{{maxfilesize}}', number_abbr($maxfilesize) . 'B',  $tmp);

  return $tmp;
}

?>
