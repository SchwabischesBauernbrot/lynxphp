<?php

function getBannersByUri($boardUri) {
  $row = getBoardRaw($boardUri);
  if (!$row) return false;
  return getBanners($row['boardid']);
}

function getBanners($boardid) {
  global $db, $models;
  $res = $db->find($models['board_banner'], array('criteria' => array(
    array('board_id', '=', $boardid),
  )));
  $banners = $db->toArray($res);
  return $banners;
}
// $banners = getBanners($io['boardid']);

return true;

?>
