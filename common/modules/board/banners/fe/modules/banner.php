<?php

$params = $getModule();

// io is p
$boardUri = $io['boardUri'];

// get a random banner from backend
$banner = $pkg->useResource('random', array('boardUri' => $boardUri));

// add {{banner}} tag
if (is_array($banner) && count($banner)) {
  $io['tags']['banner'] = '<img src="'. BASE_HREF . 'backend/' . $banner['image']. '" width="'.$banner['w'].'" height="'.$banner['h'].'">';
} else {
  // array() just means no banners
  $io['tags']['banner'] = 'No banners, make a banners thread';
}

?>
