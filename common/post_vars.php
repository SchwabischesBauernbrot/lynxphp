<?php

function hasPostVars($fields) {
  foreach($fields as $field) {
    if (empty($_POST[$field])) {
      wrapContent('Field "' . $field . '" required');
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

$pipelines = array();
function definePipeline($constant, $str) {
  global $pipelines;
  define($constant, $str);
  $pipelines[$str] = new pipeline_registry;
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
    if ($i > 2) break;
    $trace .= ' <- ' . $call['file'] . ':' . $call['line'];
  }
  return $trace;
}

?>
