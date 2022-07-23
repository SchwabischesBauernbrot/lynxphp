<?php
// fe
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

$templates = moduleLoadTemplates('index', __DIR__);
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

$logoURL = 'images/default_logo.png';
$logoW = 279;
$logoH = 180;
$logoAlt = 'Site logo';
if (!empty($settings['site']['logo'])) {
  $logo = $settings['site']['logo'];
  if (!empty($logo['url'])) {
    if (strpos($logo['url'], '://') === false) {
      $logoURL = BACKEND_PUBLIC_URL . $logo['url'];
    } else {
      $logoURL = $logo['url'];
    }
  }
  if (!empty($logo['w'])) $logoW = $logo['w'];
  if (!empty($logo['h'])) $logoH = $logo['h'];
  if (!empty($logo['alt'])) $logoAlt = $logo['alt'];
}

$tags = array(
  'siteName' => empty($settings['site']['siteName']) ? 'New PHPLynx Site' : $settings['site']['siteName'],
  'slogan' => empty($settings['site']['slogan']) ? 'Go into <a href="admin/settings.html">Account > Admin interface > Settings</a> to set Name/Slogan' : $settings['site']['slogan'],
  'logoURL' => $logoURL,
  'logoAlt' => $logoAlt,
  'logoW' => $logoW,
  'logoH' => $logoH,
  'boards' => $boards_html,
  // FIXME: make these take less bandwidth
  'showSiteName' => empty($settings['site']['showSiteName']) ? ' style="display: none"' : '',
  'showWelcome' => empty($settings['site']['showWelcome']) ? ' style="display: none"' : '',
  'showSlogan' => empty($settings['site']['showSlogan']) ? ' style="display: none"' : '',
  'showLogo' => empty($settings['site']['showLogo']) ? 'none' : 'block',
);

$content = replace_tags($templates['header'], $tags);
if (count($boards) > 10) {
  $content .= $moreBoards;
}

// , array('settings' => array('user'=>$settings, 'site'=>))
wrapContent($content, array('settings' => $settings));

?>
