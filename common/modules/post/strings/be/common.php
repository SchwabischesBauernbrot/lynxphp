<?php

// strings/be

function post_strings_getCount($text) {
  global $db, $models;
  $res = $db->find($models['post_string']);

  $cnt = 0;
  while($s = $db->get_row($res)) {
    if (strpos($s['string'], $text) !== false) {
      // contains $s['string']
      $cnt++;
    }
  }
  $db->free($res);
  return $cnt;
}

function post_strings_getAction($text) {
  global $db, $models;
  $res = $db->find($models['post_string']);

  $action = 0;
  while($s = $db->get_row($res)) {
    if (strpos($s['string'], $text) !== false) {
      // contains $s['string']
      // maybe we go over all of them?
      $action = $s['action'];
      break;
    }
  }
  $db->free($res);
  return $action;
}

return array(
);

?>
