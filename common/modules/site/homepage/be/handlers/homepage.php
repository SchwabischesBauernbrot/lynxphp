<?php

$params = $get();

$boards = listBoards();
$settings = getPublicSiteSettings();
// recent posts/images?
sendResponse(array(
  'boards' => $boards,
  'settings' => $settings,
));

?>
