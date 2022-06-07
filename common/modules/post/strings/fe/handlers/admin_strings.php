<?php

$params = $getHandler();

$data = $pkg->useResource('string_list');

$lookup = $shared['admin_fields']['action']['options'];

$str = '';
$str .= '<a href="admin/strings/add.html">Add strings</a><br>';
$str .= '<table>';
$str .= '<tr><th>string<th>action';
foreach($data['strings'] as $s) {
  $str .= '<tr><td>' . $s['string'] . '<td><nobr>' . $lookup[$s['action']] . '</nobr>' . "\n";
}
$str .= '</table>';

wrapContent($str);

?>
