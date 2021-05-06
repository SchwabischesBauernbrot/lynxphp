<?php

// apache/nginx hack
if (!function_exists('getallheaders')) {
  function getallheaders() {
    $headers = '';
    foreach ($_SERVER as $name => $value) {
      if (substr($name, 0, 5) == 'HTTP_') {
        $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
      }
    }
    return $headers;
  }
}

// private
function _doHeaders($mtime, $options = false) {
  global $now;

  $contentType = '';
  $fileSize = 0;
  $lastMod = '';
  if ($options) {
    if (isset($options['contentType'])) $contentType = $options['contentType'];
    if (isset($options['lastMod']))     $lastMod = $options['lastMod'];
    if (isset($options['fileSize']))    $fileSize = $options['fileSize'];
  }

  // why is this empty?
  // being empty in chrome makes html page not render as html in nginx/php-fpm
  header('Content-Type: ' . $contentType);

  if ($mtime === $now) {
    // don't cache
    header ('Expires: ' . gmdate('D M d H:i:s Y', 1)); // old
    header ('Proxy-Connection: keep-alive');
    header ('Cache-Control: no-store, must-revalidate, post-check=1, pre-check=2');
  } else {
    // cache this
    if (!$lastMod) {
      $lastMod = gmdate('D, d M Y H:i:s', $mtime) . ' GMT';
    }
    header('Last-Modified: ' . $lastMod);
    if ($fileSize) {
      header('Content-Length: ' . $fileSize);
    }
    $ssl = false;
    if (isset($_SERVER['HTTPS'])) {
      $ssl = $_SERVER['HTTPS'] === 'on';
    }
    $public='';
    if (isset($_SERVER['PHP_AUTH_USER']) || $ssl) {
      // public says to cache through SSL & httpauth
      // otherwise just cached in memory only
      $public = 'public, ';
    }
    header('Cache-Control: ' . $public . 'must-revalidate');
    header('Vary: Accept-Encoding');
    if ($fileSize) { // don't generate if not needed
      $etag = dechex($mtime) . '-' . dechex($fileSize);
      header('ETag: "' . $etag . '"');
    }
    // CF is also injecting this without checking for it...
    //header('X-Frame-Options: SAMEORIGIN');
  }
}

function checkCacheHeaders($mtime, $options = false) {
  $contentType = '';
  $fileSize = 0;
  if ($options) {
    if (isset($options['contentType'])) $contentType = $options['contentType'];
    if (isset($options['fileSize']))    $fileSize = $options['fileSize'];
  }

  // polyfill should handle this...
  /*
  $headers=array();
  if (function_exists('getallheaders')) {
  */
  $headers = getallheaders();
  //}
  // we don't always know the size
  $etag = false;
  if ($fileSize) { // don't generate if not needed
    $etag = dechex($mtime) . '-' . dechex($fileSize);
  }
  $lastmod = gmdate('D, d M Y H:i:s', $mtime) . ' GMT';
  if ((!isset($headers['Cache-Control']) || (isset($headers['Cache-Control']) && $headers['Cache-Control'] !== 'no-cache'))
      && (
        ($etag && !empty($headers['If-None-Match'])
           && strpos($headers['If-None-Match'], $etag) !== false) ||
        (!empty($headers['If-Modified-Since'])
           && $lastmod == $headers['If-Modified-Since']))
      ) {
    header('HTTP/1.1 304 Not Modified');
    _doHeaders($mtime, array(
      'contentType' => $contentType, 'lastMod' => $lastmod));
    // maybe just exit?
    return true;
  }
  _doHeaders($mtime, array('contentType' => $contentType, 'lastMod' => $lastmod));
  // maybe return etag so it doesn't have to be generated?
  return false;
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

function getCookie($field, $default = '') {
  return empty($_COOKIE[$field]) ? $default : $_COOKIE[$field];
}

function getServerField($field, $default = '') {
  return empty($_SERVER[$field]) ? $default : $_SERVER[$field];
}

// if '', '' is passed, so no issue.
function getQueryField($field) {
  return empty($_GET[$field]) ? '' : $_GET[$field];
}

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
    $ip = array_shift($parts);
  }
  return $ip;
}

function modelToString($model) {
  $s = '<ul><li>Name: ' . $model['name'];
  if (isset($model['fields'])) {
    $s .= '<li><table><tr><th>Field<th>Type';
    foreach($model['fields'] as $k => $v) {
      // type, length
      $s .= '<tr><td>' . $k . '<td>' . $v['type'];
    }
    $s .= '</table>';
  }
  if (isset($model['seed'])) {
    $s .= '<li><table><tr><th>Field<th>Value';
    foreach($model['seed'] as $k => $v) {
      // type, length
      $s .= '<tr><td>' . $k . '<td>' . print_r($v, 1);
    }
    $s .= '</table>';
  }
  $s .= '</ul>';
  return $s;
}

function key_map($func, $arr) {
  $nArr = array();
  foreach($arr as $k => $v) {
    $nK = $func($k);
    $nArr[$nK] = $v;
  }
  return $nArr;
}

function gettrace() {
  $calls = debug_backtrace();
  array_shift($calls); // remove the call to self
  $trace = '';
  foreach($calls as $i => $call) {
    if ($i > 3) break;
    $trace .= ' <- ' . $call['file'] . ':' . $call['line'];
  }
  return $trace;
}

// ensure all values are set in res
function ensureOptions($rules, $data) {
  $res = array();
  if (!$data || !is_array($data)) $data = array();
  foreach($rules as $f => $dv) {
    $res[$f] = isset($data[$f]) ? $data[$f] : $dv;
  }
  return $res;
}

?>
