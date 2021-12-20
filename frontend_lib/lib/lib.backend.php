<?php

// is this file going to get huge
// should we specialize or tie to handler?

// FIXME: option for should only work over TLS unless same ip/localhost

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
// we don't need to define optional params
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
  //echo "querystring[$querystring]<br>\n";

  // while it should be in the dev report at the bottom, that's not always available
  // this is very handy
  //echo "URL[", BACKEND_BASE_URL . $options['endpoint'] . $querystring, "]<br>\n";
  //if ($postData) echo "POST[", print_r($postData, 1), "]<br>\n";
  //if (isset($options['method'])) echo "method[", $options['method'], "]<br>\n";

  // post login/IP
  $responseText = request(array(
    'url'    => BACKEND_BASE_URL . $options['endpoint'] . $querystring,
    'method' => empty($options['method']) ? 'AUTO' : $options['method'],
    'headers' => $headers,
    'body' => $postData,
  ));
  //$responseText = curlHelper(BACKEND_BASE_URL . $options['endpoint'] . $querystring,
  //  $postData, $headers, '', '', empty($options['method']) ? 'AUTO' : $options['method']);
  //echo "<pre>responseText[$responseText]</pre>\n";
  if (!empty($options['expectJson']) || !empty($options['unwrapData'])) {
    $obj = expectJson($responseText, $options['endpoint'], $options);
    if ($obj) {
      if (!empty($options['unwrapData'])) {
        if (isset($obj['data'])) {
          return $obj['data'];
        } else {
          // likely backend problem..
          return false;
        }
      }
    }
    return $obj;
  }
  return $responseText;
}

// returns
// - false = error
// - array = decoded data
// - string = decoded data
function expectJson($json, $endpoint = '', $options = array()) {
  $obj = json_decode($json, true);
  if ($obj === NULL) {
    if (!empty($options['inWrapContent'])) {
      echo 'Backend error (consume_beRsrc): ' .  $endpoint . ': ' . $json, "\n";
    } else {
      wrapContent('Backend JSON parsing error: ' .  $endpoint . ': ' . $json . "\n");
    }
    return false;
  }
  if (!empty($obj['err']) && $obj['err'] === 'BACKEND_KEY') {
    if (!empty($options['inWrapContent'])) {
      echo 'Backend configuration error: ' .  $obj['message'], "\n";
    } else {
      wrapContent('Backend configuration error: ' .  $obj['message'] . "\n");
    }
    exit(1);
    return false;
  }
  // meta processing?
  if (!empty($obj['meta'])) {
    if (isset($obj['meta']['board'])) {
      global $boardData;
      $boardData = $obj['meta']['board'];
    }
    if (isset($obj['meta']['board']['settings'])) {
      global $board_settings;
      $board_settings = $obj['meta']['board']['settings'];
    }
    if (DEV_MODE) {
      if ($obj['meta']['code'] === 404) {
        echo "<pre>BE gave 404 [", print_r($obj['data'], 1), "]</pre>\n";
        return false;
      }
    }
    // let's just handle 401s globally here
    if ($obj['meta']['code'] === 401) {
      //echo "<hr><hr><hr>\n";
      if (IN_TEST) {
        return $obj;
      } else
      if (DEV_MODE) {
        echo "<pre>Got a 401 [$json] for [", $endpoint, ']via[', isset($options['method']) ? $options['method'] : 'AUTO' ,"]</pre>\n";
      } else {
        // FIXME get named route
        global $BASE_HREF;
        return redirectTo($BASE_HREF . 'forms/login');
      }
    }
  }
  /*
  if ($obj === false) {
    echo "json[$json] parsed to false<br>\n";
  }
  */
  return $obj;
}

function getExpectJson($endpoint) {
  //$json = curlHelper(BACKEND_BASE_URL . $endpoint);
  $json = request(array('url' => BACKEND_BASE_URL . $endpoint));
  return expectJson($json, $endpoint);
}

/*
function postExpectJson($endpoint, $postData) {
  $json = curlHelper(BACKEND_BASE_URL . $endpoint, $postData, '', '', '', 'POST');
  return expectJson($json, $endpoint);
}
*/

function getBoards($params = false) {
  //$boards = getExpectJson('4chan/boards.json');
  /*
  $qstr = '';
  if ($params) {
    $qstr = '?';
    foreach($params as $k => $v) {
      $qstr .= $k . '=' . $v . '&';
    }
  }
  $boards = getExpectJson('opt/boards.json'.$qstr);
  */
  $qs = array();
  if ($params) {
    foreach($params as $k => $v) {
      $qs[] = $k . '=' . urlencode($v);
    }
  }
  //echo "qs[", join('&', $qs), "]<br>\n";
  $boards = consume_beRsrc(array(
    'endpoint'    => 'opt/boards.json',
    'querystring' => $qs,
    'sendSession' => true,
    'expectJson'  => true,
    //'unwrapData'  => true,
  ));
  return $boards;
}

function getBoard($boardUri) {
  $boardData = getExpectJson('opt/' . $boardUri . '.json');
  if (!isset($boardData['data'])) {
    return false;
  }
  //echo "<pre>", print_r($boardData, 1), "</pre>\n";
  if (isset($boardData['data']['settings'])) {
    global $board_settings;
    $board_settings = $boardData['data']['settings'];
  }
  return $boardData['data'];
}

function backendGetBoardThreadListing($boardUri, $pageNum = 1) {
  $threadListing = getExpectJson('opt/boards/' . $boardUri . '/' . $pageNum);
  //echo "type[", gettype($threadListing), "][$threadListing]\n";
  if (!$threadListing) return;
  if (isset($threadListing['data']['board']['settings'])) {
    global $board_settings;
    $board_settings = $threadListing['data']['board']['settings'];
  }
  return $threadListing['data'];
}

function getBoardPage($boardUri, $page = 1) {
  $page1 = getExpectJson('4chan/' . $boardUri . '/' . $page . '.json');
  return $page1;
}

function getBoardCatalog($boardUri) {
  $result = getExpectJson('opt/' . $boardUri . '/catalog.json');
  if (isset($result['data']['board']['settings'])) {
    global $board_settings;
    $board_settings = $result['data']['board']['settings'];
  }
  return $result['data'];
}

function getBoardThread($boardUri, $threadNum) {
  $result = getExpectJson('opt/' . $boardUri . '/thread/' . $threadNum . '.json');
  if (isset($result['data']['settings'])) {
    global $board_settings;
    $board_settings = $result['data']['settings'];
  }
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
  if (!isLoggedIn()) {
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

function getChallenge($pk) {
  $json = curlHelper(BACKEND_BASE_URL . 'opt/getChallenge', array(
    'i' => base64_encode($pk),
  ), array('HTTP_X_FORWARDED_FOR' => getip()));
  //echo "<pre>getChallenge[$json]</pre>\n";
  $res = expectJson($json, 'opt/getChallenge');
  if (empty($res['data']['cipherText64']) || empty($res['data']['serverPubkey64'])) {
    return false;
  }
  return $res['data'];
}

function backendRegister($chal, $sig, $email = '') {
  // chal and sig. optional: email
  $json = curlHelper(BACKEND_BASE_URL . 'opt/registerAccount', array(
    'chal' => $chal, 'sig' => $sig, 'email' => $email
  ));
  //echo "json[$json]<br>\n";
  $res = expectJson($json, 'opt/registerAccount');
  //echo "<pre>backendRegister", print_r($res, 1), "</pre>\n";
  if ($res === false) {
    // couldn't parse json
    return;
  }
  // session/ttl/upgradedAccount
  if (!empty($res['data']['session'])) {
    setcookie('session', $res['data']['session'], $res['data']['ttl'], '/');
    return true;
  }
  // error
  return $res['meta'];
}

function backendLogin($user, $pass) {
  $json = curlHelper(BACKEND_BASE_URL . 'opt/verifyAccount', array(
    'u' => $user, 'p' => $pass,
  ), array('HTTP_X_FORWARDED_FOR' => getip()));
  $res = expectJson($json, 'opt/verifyAccount');
  //echo "<pre>backendLogin", print_r($res, 1), "</pre>\n";
  if ($res === false) {
    // couldn't parse json
    return;
  }
  // session
  if (!empty($res['data']['session'])) {
    // FIXME: ttl?
    // looks like 1 hour, supposed to renew every minute...
    //if (isset($res['data']['ttl'])) {
      setcookie('session', $res['data']['session'], $res['data']['ttl'], '/');
    /*
    } else {
      // lynx bridge hack
      global $now;
      setcookie('session', $res['data']['session'], $now + 3600, '/');
    }
    */
    return true;
  }
  // error
  return $res['meta'];
}

function backendVerify($chal, $sig, $user = '', $pass = '') {
  // chal and sig. during migration phase: u, p
  $json = curlHelper(BACKEND_BASE_URL . 'opt/verifyAccount', array(
    'chal' => $chal, 'sig' => $sig, 'u' => $user, 'p' => $pass,
  ), array('HTTP_X_FORWARDED_FOR' => getip()));
  $res = expectJson($json, 'opt/verifyAccount');
  //echo "<pre>backendVerify", print_r($res, 1), "</pre>\n";
  if ($res === false) {
    // couldn't parse json
    return;
  }
  // session/ttl/upgradedAccount
  if (!empty($res['data']['session'])) {
    setcookie('session', $res['data']['session'], $res['data']['ttl'], '/');
    //redirectTo('control_panel.php');
  }
  if (!empty($res['data']['upgradedAccount'])) {
    echo "Account upgraded!<br>\n";
    // ask about clearing out the email field?
    echo "Your keyphrase is: ";
  }
  if (!empty($res['data']['session'])) {
    return true;
  }
  // error
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
  // means not logged in...
  if (!$json) return false;
  return expectJson($json, 'lynx/account');
}

function backendOptMyBoards() {
  $json = backendAuthedGet('opt/myBoards');
  // means not logged in...
  if (!$json) return false;
  return expectJson($json, 'opt/myBoards');
}

function backendGetPerm($perm, $target = false) {
  $options = array(
    'endpoint'    => 'opt/perms/' . $perm,
    //'method'      => 'POST',
    'sendSession' => true,
    'sendIP'      => true,
    'unwrapData'  => true,
  );
  if ($target) {
    $options['querystring'] = array('target'=>$target);
  }
  $res = consume_beRsrc($options);
  return $res;
}

?>
