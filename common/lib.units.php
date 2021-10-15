<?php

// used by router
// https://stackoverflow.com/a/22500394
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

// used by setup
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