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
  $io['tags']['banner'] = '<img src="'. BASE_HREF . 'backend/' . $banner['image']. '" width="'.$w.'" height="'.$h.'">';
} else {
  // array() just means no banners
  $io['tags']['banner'] = 'No banners, make a banners thread';
}

?>
