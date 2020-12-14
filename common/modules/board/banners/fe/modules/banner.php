<?php

$params = $getModule();

// io is p
$boardUri = $io['boardUri'];
$banner = $pkg->useResource('random', array('boardUri' => $boardUri));
$io['tags']['banner'] = '<img src="'. BASE_HREF . 'backend/' . $banner['image']. '" width="'.$banner['w'].'" height="'.$banner['h'].'">';

?>
