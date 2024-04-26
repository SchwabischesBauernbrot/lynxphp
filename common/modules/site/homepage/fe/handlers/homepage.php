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

$posts_html = '';
if (is_array($homepage['newPosts'])) {
  foreach($homepage['newPosts'] as $i => $p) {
    $tno = $p['threadid'] ? $p['threadid'] : $p['no'];
    $url = '/' . $p['boardUri'] . '/thread/' . $tno . '.html#' . $p['no'];
    $posts_html .= '<tr><td><a href="' . $url . '">' . $p['com'] . '</a>';
    if ($i === 5) break;
  }
}

$images_html = '';
if (is_array($homepage['newFiles'])) {
  $icnt = 0;
  foreach($homepage['newFiles'] as $p) {
    if (!isset($p['thumbnail_path'])) continue;
    $tno = $p['tno'] ? $p['tno'] : $p['pno'];
    $url = '/' . $p['uri'] . '/thread/' . $tno . '.html#' . $p['pno'];
    $images_html .= '<td><a href="' . $url . '"><img height=100 src="' . BACKEND_PUBLIC_URL . $p['thumbnail_path'] . '"></a>';
    $icnt++; if ($icnt === 5) break;
  }
}

$showShortlist = !empty($settings['site']['shortlistMode']);
$shortlist_html = '';
if ($showShortlist) {
  if (!empty($settings['site']['customBoardShortlistList'])) {
    $uris = preg_split('/, ?/', $settings['site']['customBoardShortlistList']);
    $sllu = $homepage['shortlist']; // short list look up
    $list = array();
    foreach($uris as $buri) {
      $data = $sllu[$buri];
      // escape quotes out of the description
      $list[]= '<a href="/' . $buri .'/" title="' . addslashes($data['description']) . '">' . $data['title'] . '</a>';
      // maybe go wide or square?
      // wide with dots as seperators, let wrap if bigger than screen
    }
    $shortlist_html = '<tr><td>' . join(" &bull; ", $list);
  }
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
  // shortlistMode = 0 don't show
  'showShortlist' => $showShortlist ? '' : ' style="display: none"',
  //'showShortlist' => empty($settings['site']['showShortlist']) ? ' style="display: none"' : '',
  'showRecentImages' => empty($settings['site']['showRecentImages']) ? ' style="display: none"' : '',
  'showRecentPosts' => empty($settings['site']['showRecentPosts']) ? ' style="display: none"' : '',
  'showRecent' => empty($settings['site']['showRecent']) ? ' style="display: none"' : '',
  'shortlist' => $shortlist_html,
  'images' => $images_html,
  'posts' => $posts_html,
);

$content = replace_tags($templates['header'], $tags);
if (count($boards) > 10) {
  $content .= $moreBoards;
}
$content .= $templates['loop2'];

// , array('settings' => array('user'=>$settings, 'site'=>))
wrapContent($content, array('settings' => $settings));

?>
