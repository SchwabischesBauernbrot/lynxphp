<?php

// FIXME: we need access to package
$params = $getHandler();

$boardUri = $request['params']['uri'];

// Array ( [checkedposts] => 79 [postpassword] => [report] => 1 [report_reason] => )
//wrapContent(print_r($_POST, 1));

// FIXME: files (delete, spoil)
// FIXME: captcha
// FIXME: HTML + ban UI
// ip deletion?
// purge files vs remove from post

if ($_POST['delete']) {
  // FIXME: could be a ban-delete too
  $result = $pkg->useResource('content_actions',
    array('action' => 'delete', 'password' => $_POST['postpassword']),
    array('addPostFields' => array( $boardUri . '-ThreadNum-'.$_POST['checkedposts'] => true))
  );
}

if ($_POST['report']) {
  // send report request to BE
  // is reason required?
  $result = $pkg->useResource('content_actions',
    array('action' => 'report'),
    array('addPostFields' => array( $boardUri . '-ThreadNum-'.$_POST['checkedposts'] => true))
  );
}
if ($_POST['global_report']) {
  // send report request to BE
  // is reason required?
  $result = $pkg->useResource('content_actions',
    array('action' => 'report', 'globalReport'=>1),
    array('addPostFields' => array( $boardUri . '-ThreadNum-'.$_POST['checkedposts'] => true))
  );
}

wrapContent('<pre>'.print_r($result, 1).'</pre>');

?>