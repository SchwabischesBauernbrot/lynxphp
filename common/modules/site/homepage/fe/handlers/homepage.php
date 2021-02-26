<?php

$params = $getHandler();

$homepage = $pkg->useResource('homepage');

if (!$homepage || !is_array($homepage)) {
  $boards = array();
  $settings = array(
    'siteName' => '',
    'slogan' => '',
  );
} else {
  $boards = $homepage['boards'];
  $settings = $homepage['settings'];
}

$templates = loadTemplates('index');
$board_template = $templates['loop0'];
$moreBoards = $templates['loop1'];

$boards_html = '';
if (is_array($boards)) {
  foreach($boards as $c => $b) {
    $last = $b['last'];
    $b['lastCom'] = $last['com'];
    unset($b['last']); // can't pass an array value into replace_tags
    $boards_html .= replace_tags($board_template, $b) . "\n";
    if ($c > 10) break;
  }
}

$logo = 'images/default_logo.png';
if (!empty($settings['logo'])) {
  // FIXME: BACKEND_BASE_URL
  $logo = 'backend/' . $settings['logo'];
}

$tags = array(
  'siteName' => empty($settings['siteName']) ? 'New PHPLynx Site' : $settings['siteName'],
  'slogan' => empty($settings['slogan']) ? 'Go into <a href="admin/settings">Account > Admin interface > Settings</a> to set Name/Slogan' : $settings['slogan'],
  'logoURL' => $logo,
  'boards' => $boards_html,
);

$content = replace_tags($templates['header'], $tags);
if (count($boards) > 10) {
  $content .= $moreBoards;
}

wrapContent($content);

?>
