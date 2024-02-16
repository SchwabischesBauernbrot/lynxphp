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
//   because then the frontend would have to download it every time it's called
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
      /*
      $strs = array();
      foreach($options['querystring'] as $k => $v) {
        $strs[] = $k . '=' . $v;
      }
      */
      $strs = paramsToQuerystringGroups($options['querystring']);
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
  $etag = '';
  $ts = 0;
  $key = '';
  $check = array(
    'ts' => '',
    'etag' => '',
  );
  // not sure this check makes much sense
  // well options is special because it's a module resource
  // the one case the FE gets route info from the BE
  // but not usable because of the bridge
  // if etag is present, use that to save bandwidth
  // the bridge's caches will fill up tho, caching everyload
  // luckily it's keyed, so only one key stored at a time
  // key per page per session though
  // so we need an expiration system...

  // not it's just localhost to the bridge
  // a reduction in bandwidth doesn't help much
  // and the extra HEAD request on a miss has a cost 9-477ms

  // cacheSettings matter because lynx/randomBanner can't HEAD at all
  //if (isset($options['cacheSettings'])) {
  // likely cacheable
  // we have the endpoint and params...

  // GET vs POST
  // get can be the URL
  // POST, well... should these be cache?

  $feCachable = empty($options['method']) && empty($options['dontCache']);
  if ($feCachable) {
    // what's our caching key?
    $hckey = $options['endpoint'] . $querystring;
    $key = BACKEND_BASE_URL . $hckey;
    // uhm this defeats passing SID to backend
    if (!empty($headers['sid'])) {
      // maybe it should be prefixed...
      $key .= '_' . $headers['sid'];
    }
    // should we check our cache first?
    global $scratch;
    $check = $scratch->get('consume_beRsrc_' . $key);
    // still need to see if backend is newer
    //echo "<pre>check [$key]", htmlspecialchars(print_r($check, 1)), "</pre>\n";
    if ($check && is_array($check)) {
      global $_HEAD_CACHE;
      //echo "<pre>_HEAD_CACHE", htmlspecialchars(print_r($_HEAD_CACHE, 1)), "</pre>\n";
      // $_HEAD_CACHE &&  needed?
      if (isset($_HEAD_CACHE[$hckey])) {
        //if (DEV_MODE) echo "<pre>Using head cache key[$hckey] [", print_r($_HEAD_CACHE[$hckey], 1), "]</pre>\n";
        if (doWeHaveHeader($_HEAD_CACHE[$hckey], $check)) {
          //if (DEV_MODE) echo "WeHaveHeader<br>\n";
          return postProcessJson($check['res'], $options);
        }
        if (DEV_MODE) echo "<pre>WeDontHaveHeader key[$hckey] [", print_r($_HEAD_CACHE[$hckey], 1), "]</pre>\n";
      } else {
        // FIXME: should be contains
        if (empty($_SERVER['HTTP_CACHE_CONTROL']) || $_SERVER['HTTP_CACHE_CONTROL'] !== 'no-cache') {
          // no _HEAD_CACHE
          //$headers['consume-head'] = true; // we don't need this
          if (!empty($check['ts'])) {
            if (!$headers || !is_array($headers)) $headers = array();
            $headers['If-Modified-Since'] = gmdate('D, d M Y H:i:s', $check['ts']) . ' GMT';
            //$headers['consume-ts'] = $check['ts']; // for debug reasoning
          }
          // etag can also be used with POST to make sure another user didn't edit
          if (!empty($check['etag'])) {
            if (!$headers || !is_array($headers)) $headers = array();
            $headers['If-None-Match'] = $check['etag'];
          }
        }
      }
    }
  }
  //}

  //echo "feCachable[$feCachable] header[", print_r($headers, 1), "]<br>\n";

  // post login/IP
  //echo "lib.backend::consume_beRsrc [", BACKEND_BASE_URL . $options['endpoint'] . $querystring, "]<br>\n";
  $requestOptions = array(
    'url'    => BACKEND_BASE_URL . $options['endpoint'] . $querystring,
    'method' => empty($options['method']) ? 'AUTO' : $options['method'],
    'headers' => $headers,
    'body' => $postData,
  );
  if (DEV_MODE) {
    $requestOptions['devData'] = array(
      'resourceCacheSettings' => empty($options['cacheSettings']) ? 'none' : $options['cacheSettings'],
      'key' => $key,
      'cacheTs' => empty($check['ts']) ? '' : $check['ts'],
      'cacheEtag' => empty($check['etag']) ? '' : $check['etag'],
      'serverTs' => $ts,
      'serverEtag' => $etag,
      'headCache' => empty($_HEAD_CACHE[$options['endpoint'] . $querystring]) ? '' : $_HEAD_CACHE[$options['endpoint'] . $querystring],
      'headers' => $headers,
      //'saveCache' => $saveCache,
    );
  }
  $responseText = request($requestOptions);
  //echo "<pre>respHeader[", print_r($respHeaders, 1), "]</pre>\n";

  //$responseText = curlHelper(BACKEND_BASE_URL . $options['endpoint'] . $querystring,
  //  $postData, $headers, '', '', empty($options['method']) ? 'AUTO' : $options['method']);
  //echo "<pre>responseText[", print_r($responseText, 1), "]</pre>\n";

  if ($feCachable) {
    $rawHeaders = request_getLastHeader();
    //echo "rawHeaders[", print_r($rawHeaders, 1), "]<br>\n";
    $is304 = strpos($rawHeaders, '304 Not Modified') !== false;

    // only need to save if we don't already have it

    //$log = request_getLastLog();
    //echo "<pre>log[", print_r($log, 1), "]</pre>\n";

    // if 304, on apache there's no headers beyond date
    //echo "respHeaders[", print_r($respHeaders, 1), "]<br>\n";

    // handle 304 response
    // has to be before, so we can re-run expectJson
    //doWeHaveHeader($respHeaders, $check)
    if ($is304) {
      // basically a 304
      return postProcessJson($check['res'], $options);
    } else {
      $respHeaders = parseHeaders($rawHeaders);
      // actually have content?
      $outTs = isset($respHeaders['last-modified']) ? strtotime($respHeaders['last-modified']) : $ts;
      $outEtag = isset($respHeaders['etag']) ? $respHeaders['etag'] : $etag;
      // how do we get headers from this...
      //echo "saving[$key]<br>\n";
      // we should require ($outTs || $outEtag)
      // we need to expire this data too, we can't have eTag stick around forever
      $scratch->set('consume_beRsrc_' . $key, array(
        'ts'   => $outTs,
        'etag' => $outEtag,
        'res'  => $responseText
      ));
    }
  }
  return postProcessJson($responseText, $options);
}

function postProcessJson($responseText, $options) {
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
      //echo "<pre>uploaded portal data", print_r($obj['meta']['portals'], 1), "]</pre>\n";
      $portalData = $obj['meta']['portals'];
      // need to know uri to make this work

      // may need to call these even if meta.portals isn't set... (banners)
      $portals = array_keys($portalData);
      global $_PortalPipelines, $portalResources, $pipelines;
      foreach($portals as $p) {
        //echo "running[$p]<br>\n";
        $portal_io = array(
          'name' => $p,
          'resp' => $obj,
          'ep' => $endpoint,
          'portalData' => $portalData[$p],
        );
        $r = false;
        if (isset($portalResources[$p])) {
          // so it's documented
          $r = $portalResources[$p];
          $portal_io['portalOptions'] = $r;
        }
        //echo "<pre>portal options", print_r($portal_io, 1), "]</pre>\n";
        if (isset($_PortalPipelines[$p])) {
          // main (non-extensible) portal definition pipeline
          $_PortalPipelines[$p]->execute($portal_io);
        } else if (DEV_MODE) {
          echo "lib.backend::expectJson - portal pipeline[$p] is missing from _PortalPipelines<br>\n";
        }
        if ($r) {
          // portal extensions pipeline
          $pipelines[$r['pipeline']]->execute($portal_io);
        }
        // we should communicate something back
        // but expectjson can't communicate it...
        // could put this in the router to avoid the global...
        // maybe pkg can be the anchor
        // pkg injects function into router
        // pkg->useResource
        // pkg->wrapContent
        global $_portalData;
        $_portalData[$p] = $portalData[$p];
      }
      /*
      if (isset($portalData['boardSettings']['settings'])) {
        // I have no clue which board this is...
        global $boards_setting;
      }
      */
    }
    // FIXME: shouldn't have this branch every
    // there should be a pipeline here
    // mapable...
    // maybe better in the route/module definition
    if (isset($obj['meta']['menus'])) {
      global $menusData;
      $menusData = $obj['meta']['menus'];
    }
    // singular
    if (isset($obj['meta']['board'])) {
      global $boardData;
      // might just be the board name
      // don't want to step on it
      if (is_array($obj['meta']['board'])) {
        $boardData = $obj['meta']['board'];
        if (isset($obj['meta']['board']['settings'])) {
          global $board_settings, $boards_settings;
          $board_settings = $obj['meta']['board']['settings'];
          $uri = $obj['meta']['board']['uri'];
          $boards_settings[$uri] = $obj['meta']['board']['settings'];
        }
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
    if (isset($obj['meta']['session'])) {
      global $loggedIn;
      if (!$loggedIn) {
        // if there's a pk, we're logged in and have a valid session (if we sent one...)
        if (!empty($obj['meta']['session']['pk'])) {
          // consider session checked
          $loggedIn = 'true';
        }
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
  // probably should find the pkg and use the resource
  // so we get 304 and portal systems...
  $boardData = getExpectJson('opt/' . $boardUri . '.json');
  if (!isset($boardData['data'])) {
    return false;
  }
  // getExpectJson only processes meta information
  //echo "<pre>", htmlspecialchars(print_r($boardData['data']['settings'], 1)), "</pre>\n";
  if (isset($boardData['data']['settings'])) {
    global $boards_settings;
    //echo "setting[$boardUri]<br>\n";
    $boards_settings[$boardUri] = $boardData['data']['settings'];
  }
  return $boardData['data'];
}

// calls that eventually call getboardportal needs a optional flag
// so we can pull things we need to pull
// maybe a cb system (split control/request plane and process/response plane)
// we could define in the route potentially usage
function getBoardPage($boardUri, $page = 1) {
  $result = getExpectJson('4chan/' . $boardUri . '/' . $page . '.json');
  if (isset($result['data']['board']['settings'])) {
    global $board_settings;
    $board_settings = $result['data']['board']['settings'];
  }
  return $result;
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
  // the rss case doesn't need the portal
  $result = getExpectJson('opt/' . $boardUri . '/thread/' . $threadNum . '.json?portals=board,posts');
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
  /*
  $json  = curlHelper(BACKEND_BASE_URL . 'lynx/files', array(
    'files' => make_file($tmpfile, $type, $filename)
  ), '', '', '', 'POST');
  */
  $json = request(array(
    'url' => BACKEND_BASE_URL . 'lynx/files',
    'method' => 'POST',
    'body' => array(
      'files' => make_file($tmpfile, $type, $filename)
    ),
    'multipart' => true,
    //'headers' => array('Content-Type' => 'multipart/form-data'),
  ));
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
  // ok an array of body data makes multipart
  $json = request(array(
    'url' => BACKEND_BASE_URL . 'opt/verifyAccount',
    'body' => array('u' => $user, 'p' => $pass),
    'headers' => array('HTTP_X_FORWARDED_FOR' => getip()),
    'multipart' => false,
  ));
  /*
  $json = curlHelper(BACKEND_BASE_URL . 'opt/verifyAccount', array(
    'u' => $user, 'p' => $pass,
  ), array('HTTP_X_FORWARDED_FOR' => getip()));
  */
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
  $sid = empty($_COOKIE['session']) ? '' : $_COOKIE['session'];
  $retval = expectJson($json, 'lynx/account', array('redirect' => $redirect));
  if (isset($retval['meta']['setCookie'])) {
    $sid = $retval['meta']['setCookie']['value'];
  }
  // update cache
  $key = 'user_session' . $sid;
  global $now, $persist_scratch;
  $user = array(
    'account' => $retval,
    'account_ts' => $now,
  );
  $persist_scratch->set($key, $user);

  return $retval;
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
