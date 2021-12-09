<?php

$params = $getModule();

// io is p
$boardUri = $io['boardUri'];

// get a random banner from backend
$banner = $pkg->useResource('random', array('boardUri' => $boardUri));

// add {{banner}} tag
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
  $io['tags']['board_header_top'] = '<img src="'. BACKEND_PUBLIC_URL . $banner['image']. '" width="'.$w.'" height="'.$h.'">' . $io['tags']['board_header_top'];
} else {
  // array() just means no banners
  // maybe say nothing?
  $io['tags']['board_header_top'] = 'No banners, make a banners thread' . $io['tags']['board_header_top'];
}

?>
