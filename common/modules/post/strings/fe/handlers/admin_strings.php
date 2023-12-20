<?php

$params = $getHandler();

$data = $pkg->useResource('string_list');

$str = '';

if ($data) {
  $lookup = $shared['admin_fields']['action']['options'];

  $str .= '<a href="admin/strings/add.html">Add strings</a><br>';
  // FIXME: add test post text function
  $str .= '<table>';
  $str .= '<tr><th>string<th>action';
  foreach($data['strings'] as $s) {
    $str .= '<tr><td>' . $s['string'] . '<td><nobr>' . $lookup[$s['action']] . '</nobr>' . "\n";
  }
  $str .= '</table>';
}

wrapContent($str);

?>
