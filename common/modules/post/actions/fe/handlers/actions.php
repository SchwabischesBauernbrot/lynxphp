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

// not bad, it gets hidden
echo "Please wait...<br>\n"; flush();

// can only prepare one action...
$action = $_POST['action'];
switch($action) {
  case 'delete':
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
        if (!empty($_POST['page'])) {
          return redirectTo('/' . $boardUri . '/page/' . $_POST['page']);
        } else
        if ($threadNum === 'ThreadNum') {
          return redirectTo('/' . $boardUri . '/');
        } else {
          return redirectTo('/' . $boardUri . '/thread/' . $threadNum . '.html');
        }
      }
    } else {
      echo "write me!<br>\n";
    }
  break;
  case 'report':
    // send report request to BE
    // is reason required?
    $postFields = array();
    $threadNum = getOptionalPostField('thread') ? getOptionalPostField('thread') : 'ThreadNum';
    if (is_array($_POST['checkedposts'])) {
      foreach($_POST['checkedposts'] as $postNum) {
        $postFields[$boardUri . '-' . $threadNum . '-' . $postNum] = true;
      }
    } else {
      $postFields[$boardUri . '-' . $threadNum . '-' . $_POST['checkedposts']] = true;
    }
    //echo "<pre>", print_r($postFields, 1), "</pre>\n";
    $result = $pkg->useResource('content_actions',
      array('action' => 'report'),
      array('addPostFields' => $postFields)
    );
  break;
  default:
    wrapContent('Error: unknown action [' . $action . ']');
    return;
  break;
}

/*
if (getOptionalPostField('global_report')) {
  // send report request to BE
  // is reason required?
  $postFields = array();
  $threadNum = getOptionalPostField('thread') ? getOptionalPostField('thread') : 'ThreadNum';
  if (is_array($_POST['checkedposts'])) {
    foreach($_POST['checkedposts'] as $postNum) {
      $postFields[$boardUri . '-' . $threadNum . '-' . $postNum] = true;
    }
  } else {
    $postFields[$boardUri . '-' . $threadNum . '-' . $_POST['checkedposts']] = true;
  }
  $result = $pkg->useResource('content_actions',
    array('action' => 'report', 'globalReport'=>1),
    array('addPostFields' => $postFields)
  );
}
*/

if (count($result['issues'])) {
  wrapContent(print_r($result['issues'], 1)."<br>\n<pre>".print_r($result, 1)."</pre>\n");
} else {
  $boardUri = $result['request'][0]['board'];

  // confirm the number matches...
  $ok = true;
  if (is_array($_POST['checkedposts'])) {
    $ok = false;
    if (count($result['request']) === count($_POST['checkedposts'])) {
      $ok = true;
    }
  }

  // how do we know they were added?
  if (getOptionalPostField('report')) {
    $ok = false;
    if ($result['reportsAdded'] === count($_POST['checkedposts'])) {
      $ok = true;
    }
  }

  if ($ok) {
    if (!empty($_POST['page'])) {
      redirectTo('/' . $boardUri . '/page/' . $_POST['page']);
    } else
    if ($result['request'][0]['threadid'] !== 'ThreadNum') {
      redirectTo('/'. $boardUri . '/thread/' . $result['request'][0]['threadid'] . '.html');
    } else {
      redirectTo('/'. $boardUri . '/');
    }
  } else {
    wrapContent('<pre>' . print_r($result, 1) . '</pre>');
  }
}



?>