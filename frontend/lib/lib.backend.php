<?php

// is this file going to get huge
// should we specialize or tie to handler?

/**
 * consume backend resource
 * options
 *   general
 *     endpoint (lynx/bob) REQUIRED
 *     method (GET, POST, AUTO, etc)
 *     querystring is an array
 *   post data
 *     requires - validation
 *     formData - associative array of key/values
 *   headers
 *     sendSession
 *     sendIP
 *   response
 *     expectJson
 *     unwrapData
 */
// should there be a validation levels for call params...
// these should not be generated by the backend side
//   because then the frontend would have to download it everytime it's called
//   though you could cache it locally
// I don't think we need $params at all
function consume_beRsrc($options, $params = '') {
  if (!isset($options['endpoint'])) return;
  $postData = '';
  if (!empty($options['formData'])) {
    $postData = $options['formData'];
  }
  $headers = array();
  if (!empty($options['sendSession'])) {
    if (isset($_COOKIE['session'])) {
      $headers['sid'] = $_COOKIE['session'];
    }
  }
  if (!empty($options['requireSession'])) {
    if (!isset($_COOKIE['session'])) {
      return false;
    }
    $headers['sid'] = $_COOKIE['session'];
  }
  // when shouldn't we send this?
  if (!empty($options['sendIP'])) $headers['HTTP_X_FORWARDED_FOR'] = getip();
  if (!count($headers)) $headers = '';

  /*
  // files
  $json  = curlHelper(BACKEND_BASE_URL . $options['endpoint'], array(
    'files' => curl_file_create($tmpfile, $type, $filename)
  ), '', '', '', 'POST');
  */
  $querystring = '';
  if (!empty($options['querystring'])) {
    $querystring = '?' . join('&', $options['querystring']);
  }

  // post login/IP
  $responseText = curlHelper(BACKEND_BASE_URL . $options['endpoint'] . $querystring,
    $postData, $headers, '', '', empty($options['method']) ? 'AUTO' : $options['method']);
  //echo "<pre>responseText[$responseText]</pre>\n";
  if (!empty($options['expectJson']) || !empty($options['unwrapData'])) {
    $obj = json_decode($responseText, true);
    if ($obj === NULL) {
      if ($options['inWrapContent']) {
        echo 'Backend error (consume_beRsrc): ' .  $options['endpoint'] . ': ' . $responseText, "\n";
      } else {
        wrapContent('Backend error (consume_beRsrc): ' .  $options['endpoint'] . ': ' . $responseText);
      }
      return;
    }
    // let's just handle 401s globally here
    if (!empty($obj['meta']) && $obj['meta']['code'] === 401) {
      //echo "<hr><hr><hr>\n";
      //echo "<pre>Got a 401 [$responseText] for [", $options['endpoint'], ']via[', $options['method'],"]</pre>\n";
      return redirectTo('/login.php');
    }
    // this hides 401s... we need to handle and pass back problems better...
    if (!empty($options['unwrapData'])) return $obj['data'];
    return $obj;
  }
  return $responseText;
}

function expectJson($json, $endpoint = '') {
  $obj = json_decode($json, true);
  if ($obj === NULL) {
    wrapContent('Backend JSON parsing error: ' .  $endpoint . ': ' . $json);
    return;
  }
  return $obj;
}

function getExpectJson($endpoint) {
  $json = curlHelper(BACKEND_BASE_URL . $endpoint);
  return expectJson($json, $endpoint);
}

function getBoards() {
  //$boards = getExpectJson('4chan/boards.json');
  $boards = getExpectJson('opt/boards.json');
  return $boards;
}

function getBoard($boardUri) {
  $boardData = getExpectJson('opt/' . $boardUri . '.json');
  return $boardData['data'];
}

function backendGetBoardThreadListing($boardUri, $pageNum = 1) {
  $threadListing = getExpectJson('opt/boards/' . $boardUri . '/' . $pageNum);
  if ($threadListing === null) return;
  return $threadListing['data'];
}

function getBoardPage($boardUri, $page = 1) {
  $page1 = getExpectJson('4chan/' . $boardUri . '/' . $page . '.json');
  return $page1;
}

function getBoardCatalog($boardUri) {
  $pages = getExpectJson('4chan/' . $boardUri . '/catalog.json');
  return $pages;
}

function getBoardThread($boardUri, $threadNum) {
  $result = getExpectJson('opt/' . $boardUri . '/thread/' . $threadNum . '.json');
  return $result['data'];
}

function sendFile($tmpfile, $type, $filename) {
  $json  = curlHelper(BACKEND_BASE_URL . 'lynx/files', array(
    'files' => make_file($tmpfile, $type, $filename)
  ), '', '', '', 'POST');
  return expectJson($json, 'lynx/files');
}

// authed functions

function backendAuthedGet($endpoint) {
  //echo "<pre>", print_r($_COOKIE, 1), "</pre>\n";
  if (!isset($_COOKIE['session'])) {
    return json_encode(array('meta'=>array('code'=>401)));
  }
  $json = curlHelper(BACKEND_BASE_URL . $endpoint, '',
    array('sid' => $_COOKIE['session']));
  return $json;
}

function checkSession() {
  $json = backendAuthedGet('opt/session');
  return expectJson($json, 'opt/session');
}

function backendLogin($user, $pass) {
  // login, password, email
  $json = curlHelper(BACKEND_BASE_URL . 'lynx/login', array(
    'login'    => $user,
    'password' => $pass,
  ), array('HTTP_X_FORWARDED_FOR' => getip()));
  $res = expectJson($json, 'lynx/login');
  if (!empty($res['data']['session'])) {
    setcookie('session', $res['data']['session'], $res['data']['ttl'], '/');
    //redirectTo('control_panel.php');
    return true;
  }
  return $res['meta'];
}

function backendCreateBoard() {
  $json = curlHelper(BACKEND_BASE_URL . 'lynx/createBoard', array(
    'boardUri'         => $_POST['uri'],
    'boardName'        => $_POST['title'],
    'boardDescription' => $_POST['description'],
    // captcha?
  ), array('sid' => $_COOKIE['session']));
  return expectJson($json, 'lynx/createBoard');
}

function backendLynxAccount() {
  $json = backendAuthedGet('lynx/account');
  return expectJson($json, 'lynx/account');
}


?>
