<?php

//
// Optimized routes for lynxphp
//

$router = new router;

$router->get('/check', function($request) {
  // db check...
  global $db;
  sendResponse(array('check' => ($db->conn !== null) ? 'ok' : 'not ok'));
});

$router->get('/session', function($request) {
  $user_id = loggedIn();
  if (!$user_id) {
    return;
  }
  sendResponse(array('session' => 'ok'));
});

/*
function handleCFE($request) {
  global $db, $models;
  echo "Thank you for choosing PHPLynx<br>\n";
}

$router->get('/createFrontEnd', handleCFE); // official
$router->get('/createfrontend', handleCFE); // ux
*/

$router->get('/boards/:uri/:page', function($request) {
  global $tpp;

  $boardUri = $request['params']['uri'];
  $pageNum = $request['params']['page'] ? (int)$request['params']['page'] : 1;

  $boardData = getBoard($boardUri, array('jsonFields' => 'settings'));
  if (!$boardData) {
    return sendResponse(array(), 404, 'Board does not exist');
  }
  $threadCount = getBoardThreadCount($boardUri);
  $threads = boardPage($boardUri, $pageNum);
  sendResponse(array(
    'board' => $boardData,
    'page1' => $threads,
    'threadsPerPage'   => $tpp,
    'threadCount' => $threadCount,
    'pageCount' => ceil($threadCount/$tpp),
  ));
});

// board data + thread data
// would be good to include the banners data too
// need a pipeline for that..
$router->get('/:board/thread/:thread', function($request) {
  global $tpp;
  $boardUri = $request['params']['board'];
  $boardData = getBoard($boardUri, array('jsonFields' => 'settings'));
  if (!$boardData) {
    echo '[]';
    return;
  }
  $threadNum = (int)str_replace('.json', '', $request['params']['thread']);
  $boardData['threadCount'] = getBoardThreadCount($boardUri);
  $boardData['pageCount'] = ceil($boardData['threadCount']/$tpp);
  $boardData['posts'] = getThread($boardUri, $threadNum);
  sendResponse($boardData);
});

// https://a.4cdn.org/po/catalog.json
$router->get('/:board/catalog.json', function($request) {
  global $tpp;
  $boardUri = $request['params']['board'];
  $page = boardCatalog($boardUri);
  if (!is_array($page)) {
    // boardCatalog handles this
    return;
  }
  $boardData = getBoard($boardUri, array('jsonFields' => 'settings'));
  $pages = count($page);
  // FIXME: just return a list of threads...
  // also be able to page count?
  $res = array();
  for($i = 1; $i <= $pages; $i++) {
    $res[] = array(
      'page' => $i,
      'threads' => $page[$i],
    );
  }
  sendResponse(array(
    'pages' => $res,
    'board' => $boardData,
  ));
});

$router->get('/boards.json', function($request) {
  global $db;
  // default is popularity (desc)
  $search = empty($_GET['search']) ? '' : $_GET['search'];
  $sort = empty($_GET['sort']) ? 'activity' : $_GET['sort'];

  // updated_at isn't good enough, last
  $sortByField = $sort === 'popularity' ? 'posts' : 'last';

  $boards = listBoards($sort, $search);
  $res = array();
  $noLast = array();
  foreach($boards as $b) {
    // FIXME: N+1s... (yea page is almost at 1s for 40 boards)
    // include posts, threads, last_activity
    $b['threads'] = getBoardThreadCount($b['uri']); // 1 query
    $b['posts'] = getBoardPostCount($b['uri']); // 1 query

    if ($b['threads']) {
      $posts_model = getPostsModel($b['uri']);
      $newestThreadRes = $db->find($posts_model, array('criteria'=>array(
          array('threadid', '=', 0),
      ), 'limit' => '1', 'order'=>'updated_at desc')); // 1 query
      $newestThread = $db->toArray($newestThreadRes);
      $db->free($newestThreadRes);
      $b['last'] = $newestThread[0];
    }
    if ($sortByField === 'last') {
      if (isset($b[$sortByField])) {
        $res[$b[$sortByField]['updated_at']] = $b;
      } else {
        $noLast[] = $b;
      }
    } else {
      $res[$b[$sortByField]] = $b;
    }
  }
  ksort($res);
  $res = array_merge($noLast, $res);
  // FIXME: not very cacheable like this...
  sendResponse(array('settings' => getSettings(), 'boards' => array_values($res)));
});


$router->get('/myBoards', function($request) {
  $user_id = loggedIn();
  if (!$user_id) {
    return;
  }
  $boards = userBoards($user_id);
  sendResponse($boards);
});

// does this user have this perms
// on optional object?
$router->get('/perms/:perm', function($request) {
  $user_id = loggedIn();
  if (!$user_id) {
    // well if you anon you don't get EXTRA permissions
    return; // already sends something...
  }
  $access = isUserPermitted($user_id, $request['params']['perm'], $request['target']);
  sendResponse(array(
    'access' => $access,
    'user_id' => $user_id,
  ));
});

$router->post('/getChallenge', function($request) {
  if (!hasPostVars(array('i'))) {
    // hasPostVars already outputs
    return;
  }
  include '../common/sodium/autoload.php';

  // so you claim to have this identity, prove it
  $edSrvKp = \Sodium\crypto_box_keypair();
  $edSrvSk = \Sodium\crypto_box_secretkey($edSrvKp);
  $edSrvPk = \Sodium\crypto_box_publickey($edSrvKp);
  $token =  md5(uniqid());
  $destEdPk = base64_decode($_POST['i']); // edPk stored as b64
  $destXPkBin = \Sodium\crypto_sign_ed25519_pk_to_curve25519($destEdPk);

  $symKey = \Sodium\crypto_box_keypair_from_secretkey_and_publickey(
    $edSrvSk,
    $destXPkBin
  );
  $iv = \Sodium\randombytes_buf(\Sodium\CRYPTO_BOX_NONCEBYTES);
  $cipherText = \Sodium\crypto_box($token, $iv, $symKey);
  global $db, $models, $now;
  $db->insert($models['auth_challenge'], array(array(
    'challenge' => $token,
    'publickey' => $_POST['i'], // edPk stored as b64
    'expires'   => (int)$now,
    'ip'        => getip(),
  )));

  // also could send a server public key to encrypt the verify payload
  // but the TLS transport should take care of that if needed
  // generate id
  $data = array(
    'cipherText64' => base64_encode($iv . $cipherText),
    'serverPubkey64' => base64_encode($edSrvPk),
  );
  // storage it temporarily w/expiration
  // return it
  sendResponse($data);
});

function verifyChallengedSignatureHandler() {
  if (!hasPostVars(array('chal', 'sig'))) {
    // hasPostVars already outputs
    return;
  }
  $chal = $_POST['chal'];
  $sig  = $_POST['sig'];
  include '../common/sodium/autoload.php';
  // validate chal is one we issued? why?
  // so we can't reuse an old chal
  // well at least
  // FIXME: make sure it's not expired
  global $db, $models;
  $res = $db->find($models['auth_challenge'], array('criteria' =>
    array('challenge' => $chal)
  ));
  if (!$db->num_rows($res)) {
    $db->free($res);
    return sendResponse(array(), 401, 'challenge not found');
  }
  $row = $db->get_row($res);
  $db->free($res);
  // make sure no one can replay
  $db->deleteById($models['auth_challenge'], $row['challengeid']);
  $edPkBin = base64_decode($row['publickey']); // it's ed signing key in b64

  // prove payload was from user and not just a guessed challenge
  if (!\Sodium\crypto_sign_verify_detached($sig, $chal, $edPkBin)) {
    return sendResponse(array(), 401, 'signature verification failed');
  }
  return $edPkBin;
}

function loginResponseMaker($user_id, $upgradedAccount = false) {
  if (!$user_id) {
    return sendResponse(array(), 500, 'logging in as no user');
  }
  $sesrow = ensureSession($user_id);
  if (!isset($sesrow['created']) && $sesrow['userid'] != $user_id) {
    // there's already a session
    return sendResponse(array(), 400, 'You passed an active session');
  }
  // and return it
  $data = array(
    'session' => $sesrow['session'],
    'ttl'     => $sesrow['expires'],
    'upgradedAccount' => $upgradedAccount,
  );
  sendResponse($data);
}

// should only work over TLS unless same ip/localhost
$router->post('/verifyAccount', function($request) {
  $edPkBin = verifyChallengedSignatureHandler();
  if (!$edPkBin) {
    return;
  }
  global $db, $models;

  // process account upgrades, remove code later
  $upgradedAccount = false;
  if (1) {
    $u = strtolower(getOptionalPostField('u'));
    //echo "u[$u]<br>\n";
    if ($u && isset($_POST['p'])) {
      $p = $_POST['p'];
      //echo "Trying to locate user [$u]<br>\n";
      $res = $db->find($models['user'], array('criteria' => array(
        array('username', '=', $u),
      )));
      $row = $db->get_row($res);
      $db->free($res);
      //echo "id[", $row['userid'], "] pk[", $row['publickey'], "] p[$p]<br>\n";
      if ($row && $row['userid'] && !$row['publickey'] && password_verify($p, $row['password']) && strpos($row['email'], '@') !== false) {
        // convert users - ONLY DO THIS ONCE
        // Should we clear out the email?
        $db->updateById($models['user'], $row['userid'], array('username' => '', 'password' => '', 'publickey' => bin2hex($edPkBin), 'email' => hash('sha512', BACKEND_KEY . $row['email'] . BACKEND_KEY)));
        $upgradedAccount = true;
      }
    }
  }

  $res = $db->find($models['user'], array('criteria' => array(
    array('publickey', '=', bin2hex($edPkBin)),
  )));
  if (!$db->num_rows($res)) {
    $db->free($res);
    return sendResponse(array(), 401, 'Incorrect login - key is not registered, please sign up');
  }
  $row = $db->get_row($res);
  $db->free($res);
  $id = $row['userid'];
  loginResponseMaker($id, $upgradedAccount);
});

$router->post('/registerAccount', function($request) {
  $edPkBin = verifyChallengedSignatureHandler();
  if (!$edPkBin) {
    return;
  }
  global $db, $models;

  $res = $db->find($models['user'], array('criteria' => array(
    array('publickey', '=', bin2hex($edPkBin)),
  )));
  if ($db->num_rows($res)) {
    // FIXME: should we just log in, they proved their key...
    return sendResponse(array(), 403, 'Already registered');
  }

  //echo "Creating<br>\n";
  $row = array('publickey' => bin2hex($edPkBin));
  $em = getOptionalPostField('email');
  if ($em) $row['email'] = hash('sha512', BACKEND_KEY . $em . BACKEND_KEY);
  $id = $db->insert($models['user'], array($row));
  loginResponseMaker($id);
});

$router->post('/migrateAccount', function($request) {
  if (!hasPostVars(array('pk'))) {
    // hasPostVars already outputs
    return;
  }
  // require being logged in
  $user_id = loggedIn();
  if (!$user_id) {
    return;
  }
  global $db, $models;
  // pass it in as hex, so you can't easily correlate it with challenge request
  $row = array('publickey' => $_POST['pk']);
  $res = $db->updateById($models['user'], $user_id, $row);
  sendResponse($res);
});

$router->post('/changeEmail', function($request) {
  if (!hasPostVars(array('em'))) {
    // hasPostVars already outputs
    return;
  }
  $em = $_POST['em'];
  // require being logged in
  $user_id = loggedIn();
  if (!$user_id) {
    return;
  }
  global $db, $models;
  $row = array('email' => hash('sha512', BACKEND_KEY . $em . BACKEND_KEY));
  $res = $db->updateById($models['user'], $user_id, $row);
  sendResponse($res);
});

// has to be last...
// non-standard 4chan api - lets disable for now
// /opt should have replaced this
$router->get('/:board', function($request) {
  global $db, $models, $tpp;
  $boardUri = str_replace('.json', '', $request['params']['board']);
  $boardData = getBoard($boardUri, array('jsonFields' => 'settings'));
  if (!$boardData) {
    echo '[]';
    return;
  }
  $boardData['threadCount'] = getBoardThreadCount($boardUri);
  $boardData['pageCount'] = ceil($boardData['threadCount']/$tpp);
  sendResponse($boardData);
});

return $router;

?>