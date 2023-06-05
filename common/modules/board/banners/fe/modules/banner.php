<?php

$params = $getModule();

// io is p
// we prepend to board_header_top

$boardUri = $io['boardUri'];

// if things are right this shouldn't be needed
// some modules can be called twice
// this one shouldn't

//if (!function_exists('setHeaderTopHTML')) {
  //echo "define", gettrace();
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
      // FIXME: allow templates to be able to style this tag better
      // well if we slap a class on it css can manage it
      $io['tags']['board_header_top'] = '<img class="board-banner" src="'. BACKEND_PUBLIC_URL . $banner['image']. '" width="'.$w.'" height="'.$h.'" alt="a random ' . $boardUri .' banner">' . $io['tags']['board_header_top'];
    } else {
      // array() just means no banners
      // maybe say nothing?
      $io['tags']['board_header_top'] = 'No banners, make a banners thread' . $io['tags']['board_header_top'];
    }
  }
//} else {
  //echo "twice", gettrace();
//}

global $portalData;
//echo "<pre>", print_r($portalData, 1), "</pre>\n";
if (isset($portalData['board']['banners'])) {
  if (is_array($portalData['board']['banners']) && count($portalData['board']['banners'])) {
    $banners = $portalData['board']['banners'];
    //print_r($banners);
    $key = array_rand($banners);
    $banner = $banners[$key];
    //print_r($banner);
    setHeaderTopHTML($io, $banner);
  } else {
    // was false
    setHeaderTopHTML($io, array());
  }
  return;
}

// get a random banner from backend
$banner = $pkg->useResource('random', array('boardUri' => $boardUri));
setHeaderTopHTML($io, $banner);
?>
