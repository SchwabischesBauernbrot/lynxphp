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
// we don't need $params at all
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
  // having it for sessions would be good for scoping
  if (!empty($options['sendIP'])) $headers['X-FORWARDED-FOR'] = getip();
  if (!count($headers)) $headers = '';

  /*
  // files
  $json  = curlHelper(BACKEND_BASE_URL . $options['endpoint'], array(
    'files' => curl_file_create($tmpfile, $type, $filename)
  ), '', '', '', 'POST');
  */
  $querystring = '';
  if (!empty($options['querystring'])) {
    //print_r($options['querystring']);
    // endpoint shouldn't have a querystring yet
    if (is_array($options['querystring'])) {
      $strs = array();
      foreach($options['querystring'] as $k => $v) {
        $strs[] = $k . '=' . $v;
      }
      $querystring = '?' . join('&', $strs);
    } else {
      $querystring = '?' . $options['querystring'];
    }
  }
  //echo "querystring[$querystring]<br>\n";

  // while it should be in the dev report at the bottom, that's not always available
  // this is very handy
  //echo "URL[", BACKEND_BASE_URL . $options['endpoint'] . $querystring, "]<br>\n";
  //if ($postData) echo "POST[", print_r($postData, 1), "]<br>\n";
  //if (isset($options['method'])) echo "method[", $options['method'], "]<br>\n";

  // do we make a HEAD call and check our local scratch?
  // could always just check the cache
  $saveCache = false;
  $etag = '';
  $ts = 0;
  if (isset($options['cacheSettings'])) {
    // likely cacheable
    // we have the endpoint and params...

    // GET vs POST
    // get can be the URL
    // POST, well... should these be cache?
    if (empty($options['method'])) {
      // what's our caching key?
      $key = BACKEND_BASE_URL . $options['endpoint'] . $querystring;
      // should we check our cache first?
      global $scratch;
      $check = $scratch->get('consume_beRsrc_' . $key);
      // still need to see if backend is newer
      //echo "<pre>check", htmlspecialchars(print_r($check, 1)), "</pre>\n";

      // we don't need to bother with 304 becaues it's a HEAD already
      /*
      $sendHeaders = $headers;
      if (!empty($check['ts'])) {
        $sendHeaders['ts']
      }
      */
      global $_HEAD_CACHE;
      //echo "<pre>_HEAD_CACHE", htmlspecialchars(print_r($_HEAD_CACHE, 1)), "</pre>\n";
      if (isset($_HEAD_CACHE[$options['endpoint'] . $querystring])) {
        /*
        if (DEV_MODE) {
          echo "Using head cache<br>\n";
        }
        */
        $headRes = $_HEAD_CACHE[$options['endpoint'] . $querystring];
      } else {
        $result = request(array(
          //'url' => 'http://localhost/backend/' . str_replace(array_keys($params), array_values($params), $be['route']),
          'url'    => BACKEND_BASE_URL . $options['endpoint'] . $querystring,
          //
          'headers' => $headers,
          'method' => 'HEAD',
        ));
        $headRes = parseHeaders($result);
      }
      // unmarshall headRes
      if (isset($headRes['etag'])) {
        //echo "lib.backend::consume_beRsrc - etag cache write me!";
        $etag = $headRes['etag'];
      }
      if (isset($headRes['last-modified'])) {
        $ts = strtotime($headRes['last-modified']);
      }
      if ($etag || $ts) {
        $saveCache = true;
      }
      //echo "etag[$etag] ts[$ts] save[$saveCache]<br>\n";

      if ($etag && !empty($check['etag'])) {
        if (DEV_MODE) {
          echo "compare [$etag]vs[", $check['etag'], "]<br>\n";
        }
        // if valid
        // return $check['res'];
      }
      if ($ts && !empty($check['ts'])) {
        //echo "compare [$ts]vs[", $check['ts'], "]<br>\n";
        // if valid
        if ($ts <= $check['ts']) {
          // this breaks /user/settings/theme.php
          // key: http://localhost/backend/opt/settings@1658889353
          /*
          if (DEV_MODE) {
            echo "Using scratch cache [$key@", $check['ts'], "] vs live[$ts]<br>\n";
          }
          */
          return $check['res'];
        }
        // if newer, refresh it
      }

      // if $check is valid, return it's data
    }
  }


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
  $retval = $responseText;
  if (!empty($options['expectJson']) || !empty($options['unwrapData'])) {
    $obj = expectJson($responseText, $options['endpoint'], $options);
    if ($obj) {
      if (!empty($options['unwrapData'])) {
        if (isset($obj['data'])) {
          $retval = $obj['data'];
        } else {
          // likely backend problem..
          $retval = false;
        }
      } else {
        $retval = $obj;
      }
    } else {
      $retval = $obj;
    }
  }
  if ($saveCache) {
    //echo "saving[$key]<br>\n";
    $scratch->set('consume_beRsrc_' . $key, array(
      'ts'   => $ts,
      'etag' => $etag,
      'res'  => $retval
    ));
  }

  return $retval;
}

// returns
// - false = error
// - array = decoded data
// - string = decoded data
function expectJson($json, $endpoint = '', $options = array()) {
  extract(ensureOptions(array(
    'inWrapContent' => false,
    'method' => 'AUTO',
    'redirect' => true,
  ), $options));

  $obj = json_decode($json, true);
  if ($obj === NULL) {
    if (!empty($inWrapContent)) {
      echo 'Backend error (consume_beRsrc): ' .  $endpoint . ': ' . $json, "\n";
    } else {
      http_response_code(500);
      wrapContent('Backend JSON parsing error: ' .  $endpoint . ': ' . $json . "\n");
    }
    return false;
  }
  if (!empty($obj['err']) && $obj['err'] === 'BACKEND_KEY') {
    if (!empty($inWrapContent)) {
      echo 'Backend configuration error: ' .  $obj['message'], "\n";
    } else {
      http_response_code(500);
      wrapContent('Backend configuration error: ' .  $obj['message'] . "\n");
    }
    exit(1);
    return false;
  }
  // meta processing
  if (!empty($obj['meta'])) {
    if (isset($obj['meta']['portals'])) {
      // a wiring harness would be better than a global
      global $portalData;
      $portalData = $obj['meta']['portals'];
    }
    // singular
    if (isset($obj['meta']['board'])) {
      global $boardData;
      $boardData = $obj['meta']['board'];
      if (isset($obj['meta']['board']['settings'])) {
        global $board_settings, $boards_settings;
        $board_settings = $obj['meta']['board']['settings'];
        $uri = $obj['meta']['board']['uri'];
        $boards_settings[$uri] = $obj['meta']['board']['settings'];
      }
    }
    // multiple for overboards like pages
    if (isset($obj['meta']['boardSettings'])) {
      global $boards_settings;
      //$boards_settings = $obj['meta']['boardSettings'];
      // merge don't replace
      foreach($obj['meta']['boardSettings'] as $b => $s) {
        $boards_settings[$b] = $s;
      }
    }
    if (isset($obj['meta']['setCookie'])) {
      setcookie($obj['meta']['setCookie']['name'], $obj['meta']['setCookie']['value'], $obj['meta']['setCookie']['ttl'], '/');
    }
    if (DEV_MODE) {
      if ($obj['meta']['code'] === 404) {
        if (DEV_MODE) {
          echo "<pre>BE gave 404 [", print_r($obj['data'], 1), "]</pre>\n";
        }
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
        echo "<pre>Got a 401 [$json] for [", $endpoint, ']via[', ($method ? $method : 'AUTO') ,"]</pre>\n";
      } else {
        // FIXME get named route
        if ($redirect) {
          global $BASE_HREF;
          return redirectTo($BASE_HREF . 'forms/login.html');
        } else {
          // can't get perms
        }
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

  // we're always passing SID if we have it now...
  // even if the route doesn't need it...
  $headers = array();
  if (isset($_COOKIE['session'])) {
    $headers = array('sid' => $_COOKIE['session']);
  }
  $json = request(array('url' => BACKEND_BASE_URL . $endpoint, 'headers' => $headers));
  return expectJson($json, $endpoint);
}

/*
function postExpectJson($endpoint, $postData) {
  $json = curlHelper(BACKEND_BASE_URL . $endpoint, $postData, '', '', '', 'POST');
  return expectJson($json, $endpoint);
}
*/

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

function addPortalsToUrl($q, $url) {
  // some portal BE stuff will need IP and probably SID
  // so we're have to send those for all requests
  // isn't the end of the world but meh...
  // if (!empty($options['sendIP'])) $headers['HTTP_X_FORWARDED_FOR'] = getip();
  return $url . '?portals=' . join(',', $q['portals']);
}
function backendGetBoardThreadListing($q, $boardUri, $pageNum = 1) {
  $threadListing = getExpectJson(addPortalsToUrl($q, 'opt/boards/' . $boardUri . '/' . $pageNum));
  //echo "type[", gettype($threadListing), "][$threadListing]\n";
  if (!$threadListing) return;
  if (isset($threadListing['data']['board']['settings'])) {
    global $board_settings;
    $board_settings = $threadListing['data']['board']['settings'];
  }
  return $threadListing['data'];
}

// calls that eventually call getboardportal needs a optional flag
// so we can pull things we need to pull
// maybe a cb system (split control/request plane and process/response plane)
// we could define in the route potentially usage
function getBoardPage($boardUri, $page = 1) {
  $page1 = getExpectJson('4chan/' . $boardUri . '/' . $page . '.json');
  if (isset($result['data']['board']['settings'])) {
    global $board_settings;
    $board_settings = $result['data']['board']['settings'];
  }
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
  if ($result === false) {
    // 404
    return $result;
  }
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
  // FIXME: meta.setCookie
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
    // FIXME: meta.setCookie
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
    // FIXME: meta.setCookie
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

function backendLynxAccount($redirect = true) {
  $json = backendAuthedGet('lynx/account');
  // means not logged in...
  if (!$json) return false;
  return expectJson($json, 'lynx/account', array('redirect' => $redirect));
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

function preprocessPost(&$p) {
  global $pipelines;
  $pipelines[PIPELINE_POST_PREPROCESS]->execute($p);
}

?>
