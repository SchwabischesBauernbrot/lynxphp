<?php

$params = $getHandler();

// Array ( [checkedposts] => 79 [postpassword] => [report] => 1 [report_reason] => )

// FIXME: files (delete, spoil)
// FIXME: captcha
// FIXME: HTML + ban UI
// ip deletion?
// purge files vs remove from post

// not bad, it gets hidden
// but if there's an error, it's left on the screen...
//echo "Please wait...<br>\n"; flush();

$postFields = array();

// normalizing checkedposts to ensure an array
if (!is_array($_POST['checkedposts'])) {
  if (strpos($_POST['checkedposts'], ',') === false) {
    // just upgrade this single into an array
    $_POST['checkedposts'] = array($_POST['checkedposts']);
  } else {
    $_POST['checkedposts'] = explode(',', $_POST['checkedposts']);
  }
}

// should also be optional in a way...
$boardUri = $request['params']['uri'];

// process array
if (is_array($_POST['checkedposts'])) {
  // we may or may not have thread? why?
  // well if we're using the - format, we don't need it
  $threadNum = getOptionalPostField('thread') ? getOptionalPostField('thread') : 'ThreadNum';
  $postFields = array();
  foreach($_POST['checkedposts'] as $in) {
    // overboard will need - format
    $parts = explode('-', $in);
    if (count($parts) === 3) {
      $postFields[$parts[0] . '-' . $parts[1] . '-' . $parts[2]] = true;
    } else {
      $postFields[$boardUri . '-' . $threadNum . '-' . $in] = true;
    }
  }
} else {
  // probably empty or invalid
  echo "checkedpost is not an array, write me!<br>\n";
}

// can only prepare one action...
$action = $_POST['action'];
switch($action) {
  case 'delete':
    if (count($postFields)) {
      // FIXME: could be a ban-delete too
      $result = $pkg->useResource('content_actions',
        array('action' => 'delete', 'password' => getOptionalPostField('postpassword')),
        array('addPostFields' => $postFields)
      );
      if (is_array($result)) {
        // guard around these
        $removedPosts = empty($result['removedPosts']) ? 0 : $result['removedPosts'];
        $removedThreads = empty($result['removedThreads']) ? 0 : $result['removedThreads'];
        if ($removedPosts + $removedThreads === count($postFields)) {
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
      }
    } else {
      wrapContent('no posts given to delete');
      return;
    }
  break;
  case 'report':
    // send report request to BE
    // is reason required?
    //$threadNum = getOptionalPostField('thread') ? getOptionalPostField('thread') : 'ThreadNum';
    // print_r($_POST);

    //echo "<pre>", print_r($postFields, 1), "</pre>\n";
    //echo "level[", $_POST['level'], "]<br>\n";
    // "" => not sure
    // cancer
    // spam
    // flood
    // board
    // global
    // copyright
    // illegal
    // other
    $global_enable = array('global', 'copyright', 'illegal', 'spam', 'flood');
    // captcha passthru for DIRECT?
    $result = $pkg->useResource('content_actions',
      array(
        'action' => 'report',
        'reason' => $_POST['report_reason'],
        'global' => in_array($_POST['level'], $global_enable),
        'captcha' => getOptionalPostField('captcha'), // optional for now
        //$_POST['level'],
      ),
      array('addPostFields' => $postFields) // which posts
    );
  break;
  case 'ban':
    print_r($_POST);
    $result = $pkg->useResource('content_actions',
      array(
        'action' => 'ban',
        'reason' => $_POST['ban_message'],
        //'global' => in_array($_POST['level'], $global_enable),
        'captcha' => getOptionalPostField('captcha'), // optional for now
        //$_POST['level'],
      ),
      array('addPostFields' => $postFields) // which posts
    );
  break;
  default:
    wrapContent('Error: unknown action [' . $action . ']');
    return;
  break;
}

/*
// additional report
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

// offical result
//echo "<pre>result", print_r($result, 1), "</pre>\n";
// we need to expect auth, status and data
// auth seems to always be null atm
// and then we can have a meta and/or debug added if we want...

if ($result['status'] === 'ok') {
  // how can overboard hook this
  // without hardcoding overboard here
  // maybe it's something we can pass in
  // but those other forms don't know about overboard and shouldn't...
  // overboard page will be '0'
  if (!empty($_POST['page'])) {
    redirectTo('/' . $boardUri . '/page/' . $_POST['page'] . '.html');
  } else
  if ($result['request'][0]['threadid'] !== 'ThreadNum') {
    redirectTo('/'. $boardUri . '/thread/' . $result['request'][0]['threadid'] . '.html');
  } else {
    redirectTo('/'. $boardUri . '/');
  }
  return;
}
if ($result['status'] === 'error') {
  // could be a 400 or 500
  http_response_code(410); // for gone (since it'll like be expired captcha)
  // would be nice to valid the captcha before going back
  // well that's a JS thing
  // for nojs, we just need to re-present the posts and form
  // or just ask for another captcha...
  //wrapContent('ERROR: ' . print_r($result, 1));
  wrapContent('ERROR: ' . $result['data']);
  return;
}

http_response_code(410); // for gone (since it'll like be expired captcha)
wrapContent('RESULT ERROR: ' . print_r($result, 1));

/*
if (is_array($result) && count($result['issues'])) {
  wrapContent(print_r($result['issues'], 1)."<br>\n<pre>".print_r($result, 1)."</pre>\n");
} else {
  $boardUri = false;
  if (is_array($result)) {
    $boardUri = $result['request'][0]['board'];
  }

  // confirm the number matches...
  $ok = true;
  if (is_array($_POST['checkedposts'])) {
    $ok = false;
    if (is_array($result)) {
      if (count($result['request']) === count($_POST['checkedposts'])) {
        $ok = true;
      }
    }
  }

  // how do we know they were added?
  if (getOptionalPostField('report')) {
    $ok = false;
    if ($result['reportsAdded'] === count($_POST['checkedposts'])) {
      $ok = true;
    }
  }

  if ($ok && $boardUri) {
    if (!empty($_POST['page'])) {
      redirectTo('/' . $boardUri . '/page/' . $_POST['page'] . '.html');
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
*/



?>