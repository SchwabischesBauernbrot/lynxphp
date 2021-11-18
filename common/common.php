<?php

// should we require lib.loader.php
// and then ldr_require('lib.http.server.php')
// here?

// used by lib.packages
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
// what if the option name needs to be stored in a different variable
function ensureOptions($rules, $data) {
  $res = array();
  if (!$data || !is_array($data)) $data = array();
  foreach($rules as $f => $dv) {
    $res[$f] = isset($data[$f]) ? $data[$f] : $dv;
  }
  return $res;
}