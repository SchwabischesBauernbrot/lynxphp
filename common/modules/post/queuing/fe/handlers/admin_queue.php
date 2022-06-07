<?php

$params = $getHandler();

$data = $pkg->useResource('queue_list');

$str = '';

$str .= 'posts: ' . count($data['queue_posts']) . "<br>\n";

$str .= '<table width=100%>';
// could but a check all here...
$str .= '<tr><th><th>uri<th>thread<th>type<th>ip<th>post<th>links';
foreach($data['queue_posts'] as $s) {
  $uri = $s['board_uri'];
  $d = json_decode($s['data'], true);
  $str .= '<tr>';
  $str .= '<td><input type=checkbox name="list[]" value="' . $s['queueid'] . '">';
  $str .= '<th><a href="/' . $uri . '" target=_blank>' . $uri;
  $str .='<td>' . (!$s['thread_id'] ? 'new' : ('<a href="' . $uri . '/thread/' . $s['thread_id'] . '.html" target=_blank>' . $s['thread_id'] . '</a>'));
  $str .= '<td>' . $s['type'];
  $str .= '<td>' . $s['ip'] . '<td>' . renderPost($uri, $s['post']);
  //$str .= '<td><pre>' . print_r($s, 1) . "</pre>\n";
}
$str .= '</table>';

$str .= "<br><hr><br>\n";

$str .= '<table>';
$str .= '<tr><th>uri<th>setting';
foreach($data['boards'] as $b => $s) {
  $str .= '<tr><th>' . $b . '<td>' . print_r($s, 1) . "\n";
}
$str .= '</table>';

wrapContent($str);

?>
