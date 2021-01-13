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

wrapContent('Please wait...');

if (getOptionalPostField('delete')) {
  // FIXME: could be a ban-delete too
  if (is_array($_POST['checkedposts'])) {
    $threadNum = getOptionalPostField('thread') ? getOptionalPostField('thread') : 'ThreadNum';
    $postFields = array();
    foreach($_POST['checkedposts'] as $postNum) {
      $postFields[$boardUri . '-' . $threadNum . '-' . $postNum] = true;
    }
    // how is multiple handled?
    $result = $pkg->useResource('content_actions',
      array('action' => 'delete', 'password' => getOptionalPostField('password')),
      array('addPostFields' => $postFields)
    );
    if ($result['removedPosts'] + $result['removedThreads'] === count($postFields)) {
      echo "Successful!<bR>\n"; flush();
      if ($threadNum === 'ThreadNum') {
        return redirectTo('/' . $boardUri);
      } else {
        return redirectTo('/' . $boardUri . '/thread/' . $threadNum . '.html');
      }
    }
  } else {
    echo "write me!<br>\n";
  }
}

if (getOptionalPostField('report')) {
  // send report request to BE
  // is reason required?
  $result = $pkg->useResource('content_actions',
    array('action' => 'report'),
    array('addPostFields' => array( $boardUri . '-ThreadNum-'.$_POST['checkedposts'] => true))
  );
}
if (getOptionalPostField('global_report')) {
  // send report request to BE
  // is reason required?
  $result = $pkg->useResource('content_actions',
    array('action' => 'report', 'globalReport'=>1),
    array('addPostFields' => array( $boardUri . '-ThreadNum-'.$_POST['checkedposts'] => true))
  );
}

// echo '<pre>'.print_r($result, 1).'</pre>', "\n";

?>