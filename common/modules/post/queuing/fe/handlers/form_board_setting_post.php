<?php

$params = $getHandler();

// do we own this board?
$boardUri = boardOwnerMiddleware($request);
if (!$boardUri) return;

// do header
$row = wrapContentData();
wrapContentHeader($row);

echo "Please wait...";

$res = $pkg->useResource('save_settings', array('boardUri' => $boardUri),
  array('addPostFields' => $_POST)
);

if ($res && $res['success'] && $res['success'] !== 'false') {
  // maybe a js alert?
  echo "Success<br>\n";
  //wrapContentFooter($row);
  // redirect dev mode does it's own weird header thing...
  redirectTo('/' . $boardUri . '/settings/queueing.html', array('header' => false));
} else {
  //wrapContent();
  echo 'Something went wrong...' , print_r($res, 1);
  wrapContentFooter($row);
}

?>