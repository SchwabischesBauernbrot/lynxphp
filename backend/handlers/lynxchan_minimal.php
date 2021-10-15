<?php

/*
$router->post('/registerAccount', function($request) {
  if (!hasPostVars(array('login', 'password', 'email'))) {
    // hasPostVars already outputs
    return; // sendResponse(array(), 400, 'Needs login, password, and email');
  }
  global $db, $models;
  $email = strtolower($_POST['email']);
  $login = strtolower($_POST['login']);
  $emRes = $db->find($models['user'], array('criteria' => array(
    array('email', '=', $email),
  )));
  if ($db->num_rows($emRes)) {
    return sendResponse(array(), 403, 'Already has account');
  }
  $res = $db->find($models['user'], array('criteria' => array(
    array('username', '=', $login),
  )));
  if ($db->num_rows($res)) {
    return sendResponse(array(), 403, 'Already Taken');
  }
  //echo "Creating<br>\n";
  $id = $db->insert($models['user'], array(array(
    'username' => $login,
    'email'    => $email,
    'password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
  )));
  $data = array('id'=>$id);
  sendResponse($data);
});

$router->post('/login', function($request) {
  global $db, $models;
  // login, password, remember
  if (!hasPostVars(array('login', 'password'))) {
    // hasPostVars already outputs
    return; // sendResponse(array(), 400, 'Requires login and password');
  }
  $res = $db->find($models['user'], array('criteria' => array(
    array('username', '=', $_POST['login']),
  )));
  if (!$db->num_rows($res)) {
    return sendResponse(array(), 401, 'Incorrect login - no username');
  }
  $row = $db->get_row($res);
  $db->free($res);
  // password check
  if (!password_verify($_POST['password'], $row['password'])) {
    return sendResponse(array(), 401, 'Incorrect login - bad pass');
  }

  // could upgrade to ensureSession but it only handle creation
  $sesRow = getSession();
  if ($sesRow) {
    if ($sesRow['userid']) {
      return sendResponse(array(), 500, 'Already logged in');
    } else {
      // upgrade session
      if (!sessionSetUserID($sesRow['session'], $row['userid'])) {
        return sendResponse(array(), 500, 'Could not upgrade session');
      }
      $ses['session'] = $sesRow['session'];
      $ses['ttl'] = $sesRow['expires'];
    }
  } else {
    // we should create a session token for this user
    $ses = createSession($row['userid']);
    if (!$ses) {
      return sendResponse(array(), 500, 'Could not create session');
    }
  }

  // and return it
  $data = array(
    'username' => $row['username'],
    'session'  => $ses['session'],
    'ttl'      => $ses['ttl'],
  );
  sendResponse($data);
});
*/

?>