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

echo "Please wait...<br>\n"; flush();

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
        return redirectTo('/' . $boardUri . '/');
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

if (count($result['issues'])) {
  wrapContent(print_r($result['issues'], 1)."<br>\n<pre>".print_r($result, 1)."</pre>\n");
} else {
  $boardUri = $result['request'][0]['board'];
  //wrapContent('<pre>' . print_r($result, 1) . '</pre>');
  if ($result['request'][0]['threadid'] !== 'ThreadNum') {
    redirectTo('/'. $boardUri . '/thread/' . $result['request'][0]['threadid'] . '.html');
  } else {
    redirectTo('/'. $boardUri . '/');
  }
}



?>