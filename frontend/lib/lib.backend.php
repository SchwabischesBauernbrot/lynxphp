<?php

// is this file going to get huge
// should we specialize or tie to handler?

function getExpectJson($endpoint) {
  $json = curlHelper(BACKEND_BASE_URL . $endpoint);
  $obj = json_decode($json, true);
  if (!$obj) {
    wrapContent('Backend error: ' .  $endpoint . ': ' . $json);
    return;
  }
  return $obj;
}

function getBoards() {
  $boards = getExpectJson('4chan/boards.json');
  return $boards;
}

function getBoard($boardUri) {
  $boardData = getExpectJson('opt/' . $boardUri . '.json');
  return $boardData;
}

function backendGetBoardThreadListing($boardUri, $pageNum = 1) {
  $threadListing = getExpectJson('opt/boards/' . $boardUri . '/' . $pageNum);
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
  $result = getExpectJson('4chan/' . $boardUri . '/thread/' . $threadNum . '.json');
  return $result['posts'];
}

function sendFile($tmpfile, $type, $filename) {
  $json  = curlHelper(BACKEND_BASE_URL . 'lynx/files', array(
    'files' => curl_file_create($tmpfile, $type, $filename)
  ), '', '', '', 'POST');
  $result = json_decode($json, true);
  return $result['data'];
}

// authed functions

function backendAuthedGet($endpoint) {
  if (!isset($_COOKIE['session'])) {
    return json_encode(array('meta'=>array('code'=>401)));
  }
  $json = curlHelper(BACKEND_BASE_URL . $endpoint, '',
    array('sid' => $_COOKIE['session']));
  return $json;
}

function checkSession() {
  $json = backendAuthedGet('opt/session');
  $ses = json_decode($json, true);
  return $ses;
}

function backendLogin() {
  $user = $_POST['username'];
  $pass = $_POST['password'];
  // login, password, email
  $data = curlHelper(BACKEND_BASE_URL . 'lynx/login', array(
    'login'    => $user,
    'password' => $pass,
  ), array('HTTP_X_FORWARDED_FOR' => getip()));
  echo "data[$data]<br>\n";
  $res = json_decode($data, true);
  if (isset($res['data']['session']) && $res['data']['session']) {
    setcookie('session', $res['data']['session'], $res['data']['ttl'], '/');
    //redirectTo('control_panel.php');
    return true;
  }
  return $res['meta'];
}

function backendCreateBoard() {
  $data = curlHelper(BACKEND_BASE_URL . 'lynx/createBoard', array(
    'boardUri'         => $_POST['uri'],
    'boardName'        => $_POST['title'],
    'boardDescription' => $_POST['description'],
    // captcha?
  ), array('sid' => $_COOKIE['session']));
  return $data;
}

function backendLynxAccount() {
  $json = backendAuthedGet('lynx/account');
  //echo "json[$json]<br>\n";
  $account = json_decode($json, true);
  //print_r($account);
  return $account;
}


?>
