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
    $b['lastCom'] = isset($last['com']) ? $last['com'] : 0;
    unset($b['last']); // can't pass an array value into replace_tags
    $boards_html .= replace_tags($board_template, $b) . "\n";
    if ($c > 10) break;
  }
}

$logo = 'images/default_logo.png';
if (!empty($settings['site']['logo'])) {
  if (strpos($settings['site']['logo'], '://') === false) {
    $logo = BACKEND_PUBLIC_URL . $settings['site']['logo'];
  } else {
    $logo = $settings['site']['logo'];
  }
}

$tags = array(
  'siteName' => empty($settings['site']['siteName']) ? 'New PHPLynx Site' : $settings['site']['siteName'],
  'slogan' => empty($settings['site']['slogan']) ? 'Go into <a href="admin/settings">Account > Admin interface > Settings</a> to set Name/Slogan' : $settings['site']['slogan'],
  'logoURL' => $logo,
  'boards' => $boards_html,
);

$content = replace_tags($templates['header'], $tags);
if (count($boards) > 10) {
  $content .= $moreBoards;
}

// , array('settings' => array('user'=>$settings, 'site'=>))
wrapContent($content, array('settings' => $settings));

?>
