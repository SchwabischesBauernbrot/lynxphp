<?php

// used by router
// https://stackoverflow.com/a/22500394
// converts ini_set xY to integer
function convertPHPSizeToBytes($sSize) {
  $sSuffix = strtoupper(substr($sSize, -1));
  if (!in_array($sSuffix, array('P','T','G','M','K'))) {
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
// not sure if even used... better code? than above though...
// requires newer php though...
function asBytes($ini_v) {
  $ini_v = trim($ini_v);
  $s = [ 't' => 1<<40, 'g'=> 1<<30, 'm' => 1<<20, 'k' => 1<<10 ];
  return intval($ini_v) * ($s[strtolower(substr($ini_v,-1))] ?: 1);
}

// used by setup & post_form
// returns a string as "xx.xx YB"
// rename numberAbbrev
function formatBytes($bytes, $precision = 2) {
  $units = array('B', 'KB', 'MB', 'GB', 'TB');

  $bytes = max($bytes, 0);
  $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
  $pow = min($pow, count($units) - 1);

  // Uncomment one of the following alternatives
  //$bytes /= pow(1024, $pow);
  $bytes /= (1 << (10 * $pow));

  return round($bytes, $precision) . ' ' . $units[$pow];
}

// https://stackoverflow.com/a/49122313
// returns a string as "x,xxx Y"
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