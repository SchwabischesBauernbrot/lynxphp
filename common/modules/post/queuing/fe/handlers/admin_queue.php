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

global $pipelines;

$io = array(
  'addFields' => array()
);
$pipelines[PIPELINE_FE_ADMIN_QUEUE_ROW]->execute($io);
$addFields = $io['addFields'];

$str .= '<table width=100%>';
// could but a check all here...
$fields = array(
  '', 'uri', 'id', 'thread', 'type', 'ip', 'post', 'votes',
);
$str .= '<tr><th><nobr>' . join('</nobr><th><nobr>', $fields) . '</nobr>';
foreach($addFields as $f) {
  $str .= '<th>' . $f['label'];
}
$str .= '<th>Actions';
foreach($data['queue_posts'] as $s) {
  $uri = $s['board_uri'];
  $d = json_decode($s['data'], true);
  $str .= '<tr>';
  $str .= '<td><input type=checkbox name="list[]" value="' . $s['queueid'] . '">';
  $str .= '<th><a href="/' . $uri . '" target=_blank>' . $uri;
  $str .= '<td>' . $s['queueid'];
  $str .= '<td>' . (!$s['thread_id'] ? 'new' : ('<a href="' . $uri . '/thread/' . $s['thread_id'] . '.html" target=_blank>' . $s['thread_id'] . '</a>'));
  $str .= '<td>' . $s['type'];
  $str .= '<td>' . $s['ip'] . '<td>' . renderPost($uri, $s['post']);
  $str .= '<td>' . $s['votes'];
  foreach($addFields as $f) {
    $str .= '<td>' . $s[$f['field']];
  }
  // then actions?
  $dataActions = array(
    array('link' => 'admin/queue/' . $s['queueid'] . '/delete.html', 'label' => 'Delete'),
  );
  $htmlLinks = array();
  foreach($dataActions as $a) {
    $htmlLinks[] = action_getLinkHTML($a, array('where' => false));
  }
  $str .= '<td>' . join('<br>' > "\n", $htmlLinks);
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

wrapContent(renderAdminPortal() . $str);

?>
