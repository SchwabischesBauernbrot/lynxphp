<?php

$params = $getHandler();

$fields = $shared['community_moderate_fields']; // imported from shared.php

$boardUri = $request['params']['uri'];

/*
// handle hooks for additionl settings
global $pipelines;
$pipelines[]->execute($fields);
*/

// just pass all the _POST data to save_settings...
// maybe we could do some validation...
// or filter the params through the pipeline

// FIXME: get from formdata...
// this is a problem is something goes wrong...
$row = wrapContentData();
wrapContentHeader($row);

echo "Please wait...";
$res = $pkg->useResource('vote_pending_post', array('boardUri' => $boardUri),
  array('addPostFields' => $_POST)
);

// only place in the system where the boardUri changes
//$boardUri = $_POST['uri'];

if ($res && $res['success'] && $res['success'] !== 'false') {
  // maybe a js alert?
  echo "Success<br>\n";
  //wrapContentFooter($row);
  // redirect dev mode does it's own weird header thing...
  redirectTo('/' . $boardUri . '/moderate.html', array('header' => false));
} else {
  //wrapContent();
  echo 'Something went wrong...' , print_r($res, 1);
  wrapContentFooter($row);
}

?>