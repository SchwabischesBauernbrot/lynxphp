<?php

$params = $getHandler();

// admin check...
if (!perms_inGroups(array('admin'))) {
  wrapContent('access denied<br>' . "\n");
  return;
}

$data = $pkg->useResource('workq_summary');

$str = '';

$str .= 'tasks: ' . number_format($data['count']) . "<br>\n";

$str .= '<table>';
$str .= '<tr><th>pipeline<th>count';
foreach($data['analyics'] as $p => $c) {
  $str .= '<tr><td>' . $p . '<td>' . $c;
}
$str .= '</table>';

wrapContent($str);

?>
