<?php

$params = $getHandler();

// admin check...
if (!perms_inGroups(array('admin'))) {
  wrapContent('access denied<br>' . "\n");
  return;
}

$data = $pkg->useResource('queue_list');

// votes? list of IDs that voted
// logs?

$str = '';

$str .= 'posts: ' . count($data['queue_posts']) . "<br>\n";

// FIXME: add a link to clear all with (updated) strings?
$str .= '<a href="admin/queue/strings.html">delete all with refused strings</a>';

$str .= post_queue_display($data['queue_posts']);

$str .= "<br><hr><br>\n";

$str .= '<table>';
$str .= '<tr><th>uri<th>setting';
foreach($data['boards'] as $b => $s) {
  $str .= '<tr><th>' . $b . '<td>' . print_r($s, 1) . "\n";
}
$str .= '</table>';

wrapContent(renderAdminPortal() . $str);

?>
