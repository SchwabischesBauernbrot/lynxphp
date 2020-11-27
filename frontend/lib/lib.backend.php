<?php

// is this file going to get huge
// should we specialize or tie to handler?

function getBoards() {
  $json   = curlHelper(BACKEND_BASE_URL . '4chan/boards.json');
  $boards = json_decode($json, true);
  return $boards;
}

function getBoard($boardUri) {
  $json      = curlHelper(BACKEND_BASE_URL . '4chan/' . $boardUri . '.json');
  //echo "getBoardJson[$json]<br>\n";
  $boardData = json_decode($json, true);
  return $boardData;
}

function backendGetBoardThreadListing($boardUri, $pageNum = 1) {
  $json = curlHelper(BACKEND_BASE_URL . 'opt/boards/' . $boardUri . '/' . $pageNum);
  //echo "getBoardThreadListing[$json]<br>\n";
  $threadListing = json_decode($json, true);
  //print_r($threadListing);
  return $threadListing;
}

function getBoardPage($boardUri, $page = 1) {
  $json  = curlHelper(BACKEND_BASE_URL . '4chan/' . $boardUri . '/' . $page . '.json');
  //echo "getBoardPageJson[$json]<br>\n";
  $page1 = json_decode($json, true);
  //print_r($page1);
  return $page1;
}

function getBoardCatalog($boardUri) {
  $json  = curlHelper(BACKEND_BASE_URL . '4chan/' . $boardUri . '/catalog.json');
  //echo "json[$json]<br>\n";
  $pages = json_decode($json, true);
  return $pages;
}

function getBoardThread($boardUri, $threadNum) {
  $json  = curlHelper(BACKEND_BASE_URL . '4chan/' . $boardUri . '/thread/' . $threadNum . '.json');
  $result = json_decode($json, true);
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
  $json   = curlHelper(BACKEND_BASE_URL . $endpoint, '',
    array('sid' => $_COOKIE['session']));
  return $json;
}

function checkSession() {
  $json = backendAuthedGet('opt/session');
  $ses = json_decode($json, true);
  return $ses;
}

function backendLogin() {
  $user  = $_POST['username'];
  $pass  = $_POST['password'];
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
  $json   = backendAuthedGet('lynx/account');
  $account = json_decode($json, true);
  return $account;
}


?>
