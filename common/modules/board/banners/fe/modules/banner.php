<?php

$params = $getModule();

// io is p
// we prepend to board_header_top

$boardUri = $io['boardUri'];

// add {{banner}} tag
function setHeaderTopHTML(&$io, $banner) {
  if (is_array($banner) && count($banner)) {
    $w = $banner['w'];
    $h = $banner['h'];
    while($w > 640) {
      $h *= 0.9;
      $w *= 0.9;
    }
    while($h > 240) {
      $h *= 0.9;
      $w *= 0.9;
    }
    $ih = (int)$h;
    $iw = (int)$w;
    $boardUri = $io['boardUri'];
    $io['tags']['board_header_top'] = '<img src="'. BACKEND_PUBLIC_URL . $banner['image']. '" width="'.$w.'" height="'.$h.'" alt="a random ' . $boardUri .' banner">' . $io['tags']['board_header_top'];
  } else {
    // array() just means no banners
    // maybe say nothing?
    $io['tags']['board_header_top'] = 'No banners, make a banners thread' . $io['tags']['board_header_top'];
  }
}

global $portalData;
if (isset($portalData['board']['banners'])) {
  if (count($portalData['board']['banners'])) {
    $banners = $portalData['board']['banners'];
    //print_r($banners);
    $key = array_rand($banners);
    $banner = $banners[$key];
    //print_r($banner);
    setHeaderTopHTML($io, $banner);
  } else {
    setHeaderTopHTML($io, array());
  }
  return;
}

// get a random banner from backend
$banner = $pkg->useResource('random', array('boardUri' => $boardUri));
setHeaderTopHTML($io, $banner);
?>
