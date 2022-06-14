<?php

ldr_require('lib.units.php');

// apache/nginx hack
if (!function_exists('getallheaders')) {
  function getallheaders() {
    $headers = array();
    foreach($_SERVER as $name => $value) {
      if (substr($name, 0, 5) == 'HTTP_') {
        $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
      }
    }
    return $headers;
  }
}

function isNginx() {
  return stripos(getServerField('SERVER_SOFTWARE'), 'nginx') !== false;
}

function ensureQuerystring() {
  // work around nginx weirdness with PHP and querystrings
  if (isNginx() && strpos($_SERVER['REQUEST_URI'], '?') !== false) {
    $parts = explode('?', $_SERVER['REQUEST_URI']);
    $querystring = $parts[1];
    $chunks = explode('&', $querystring);
    $qs = array();
    foreach($chunks as $c) {
      if (!$c) continue; // skip empties
      //if (strpos('=', $c) !== false) {
      list($k, $v) = explode('=', $c);
      $qs[$k] = $v;
      if (empty($_GET[$k])) {
        $_GET[$k] = $v;
        // could stomp POST values...
        $_REQUEST[$k] = $v;
      }
      //}
    }
    //print_r($qs);
  }
}

function isPostTooBig($req_method) {
  // upload_max_filesize breakage will have _POST set...
  $max_length = min(convertPHPSizeToBytes(ini_get('post_max_size')), convertPHPSizeToBytes(ini_get('upload_max_filesize')));
  // nginx always sets CONTENT_LENGTH, apache only passes when browser sets it
  // router ignore _POST _FILES count
  if (!count($_POST) && !count($_FILES) && $req_method === 'POST') {
    //echo "GET", print_r($_GET, 1), "<br>\n";
    //echo "POST", print_r($_POST, 1), "<br>\n";
    //echo "SERVER", print_r($_SERVER, 1), "<br>\n";
    if ($_SERVER['CONTENT_LENGTH'] > $max_length) {
      return true;
    }
  }
  // echo "max_length[", number_format($max_length), "] vs content_length[", number_format($_SERVER['CONTENT_LENGTH']), "]<br>\n";
  return false;
}

function getCookie($field, $default = '') {
  return empty($_COOKIE[$field]) ? $default : $_COOKIE[$field];
}

function getServerField($field, $default = '') {
  return empty($_SERVER[$field]) ? $default : $_SERVER[$field];
}

// if '', '' is passed, so no issue.
// FIXME: should be getOptionalGetField so this are a pair
// or getOptionalPostField => getPostField
function getQueryField($field) {
  return empty($_GET[$field]) ? '' : $_GET[$field];
}

// is it optional if I don't want the PHP warning?
function getOptionalPostField($field) {
  return empty($_POST[$field]) ? '' : $_POST[$field];
}

function getip() {
  $ip = getServerField('REMOTE_ADDR', '127.0.0.1');
  // FIXME: don't trust HTTP_X_FORWARDED_FOR from any one...
  // cloudflare support and frontend will tuck it here
  $ip = getServerField('HTTP_X_FORWARDED_FOR', $ip);
  if (strpos($ip, ',') !== false) {
    $parts = explode(',', $ip);
    // first is supposed to be real ip
    $ip = array_shift($parts);
  }
  return $ip;
}

function hasPostVars($fields) {
  foreach($fields as $field) {
    if (empty($_POST[$field])) {
      wrapContent('Detected POST field "' . $field . '" is missing; ' . join(',', $fields) . ' are required');
      return false;
    }
  }
  return true;
}

function hasGetVars($fields) {
  foreach($fields as $field) {
    if (empty($_GET[$field])) {
      wrapContent('Detected GET field "' . $field . '" is missing; ' . join(',', $fields) . ' are required');
      return false;
    }
  }
  return true;
}

?>