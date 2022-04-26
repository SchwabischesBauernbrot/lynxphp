<?php

$params = $getHandler();

//echo "<pre>", print_r($params, 1), "</pre>\n";

$theme = $params['request']['params']['theme'];
$theme = str_replace('.html', '', $theme);

/*
if (file_exists('css/themes/' . $theme . '.css')) {
  $mtime = filemtime('css/themes/' . $theme . '.css');
  // doesn't seem to help with CF or browser
  //cachePageContentsForever($mtime);
  // we'd have to generate it to get the length
  if (checkCacheHeaders($mtime, array('contentType' => 'text/html'))) {
    exit();
  }
*/
/*
} else {
  cachePageContentsForever(); */
//}
echo '<div style="height: 40px;"></div>', "\n";

/*
$templates = moduleLoadTemplates('demo', __DIR__);
$tmpl = $templates['header'];

$tags = array(
  'theme' => $theme,
  'base' => BASE_HREF,
);

echo replace_tags($tmpl, $tags);
*/
//$content = '<link rel="stylesheet" type="text/css" href="css/themes/' . $theme . '.css">';

$boardUri = 'b';
$pagenum = 1;
// avoid loading board settings from the backend
// so selector doesn't hammer it
global $board_settings;
$board_settings = array(
);

// how do we fake contents?
global $now;
$pageData = array(
  'page1' => array(
    'posts' => array(
      array(
        'no' => 1,
        'threadid' => 1,
        'sub' => 'subject',
        'name' => 'anon',
        'created_at' => $now,
        'com' => 'We were on the track ahead as the nightmare plastic column of foetid black
          iridescence oozed tightly onward through its fifteen-foot sinus;',
        'files' => array(
          array(
            'path' => 'https://placekitten.com/200/300',
            'filename' => 'noimageset.png',
            'w' => 200,
            'h' => 300,
            'type' => 'image',
          ),
          // 2nd image forces the text to drop down
          /*
          array(
            'path' => 'https://www.placecage.com/g/300/200',
            'filename' => 'noimageset.png',
            'w' => 300,
            'h' => 200,
            'type' => 'image',
          ),
          */
        ),
      ),
      array(
        'no' => 2,
        'threadid' => 1,
        'sub' => 'subject',
        'name' => 'anon',
        'created_at' => $now,
        'com' => 'gathering
          unholy speed and driving before it a spiral, re-thickening cloud of the pallid
          abyss-vapour.',
        'files' => array(),
      ),
    ),
    'thread_reply_count' => 1,
  ),
  array(
    'posts' => array(
      array(
        'no' => 3,
        'threadid' => 3,
        'sub' => 'subject',
        'name' => 'anon',
        'created_at' => $now,
        'com' => 'Quickly empower backward-compatible process improvements through client-focused applications. Dynamically extend collaborative results and next-generation platforms. Intrinsicly redefine holistic vortals before enabled technologies. Globally innovate resource sucking leadership via 24/7 "outside the box" thinking. Objectively brand team building methodologies through world-class "outside the box" thinking.',
        'files' => array(
          array(
            'path' => 'https://www.placecage.com/200/300',
            'filename' => 'noimageset.png',
            'w' => 200,
            'h' => 300,
            'type' => 'image',
            'size' => 123456,
          ),
          array(
            'path' => 'https://www.placecage.com/g/300/200',
            'filename' => 'noimageset.png',
            'w' => 300,
            'h' => 200,
            'type' => 'image',
            'size' => 123456,
          ),
          array(
            'path' => 'https://www.placecage.com/c/256/256',
            'filename' => 'noimageset.png',
            'w' => 256,
            'h' => 256,
            'type' => 'image',
            'size' => 123456,
          ),
          array(
            'path' => 'https://www.placecage.com/gif/200/300',
            'filename' => 'noimageset.png',
            'w' => 200,
            'h' => 300,
            'type' => 'image',
            'size' => 123456,
          ),
        ),
      ),
      array(
        'no' => 4,
        'threadid' => 3,
        'sub' => 'subject',
        'name' => 'anon',
        'created_at' => $now,
        'com' => 'Intrinsicly underwhelm diverse outsourcing before user friendly users. Efficiently network covalent value and market positioning users. Seamlessly drive proactive infrastructures for empowered infrastructures. Completely conceptualize dynamic paradigms for distributed e-business.',
        'files' => array(),
      ),
    ),
    'thread_reply_count' => 3,
  )
);

$boardThreads = array(
  'page1' => $pageData,
  'pageCount' => 15,
  'board' => array(
    'title' => 'random',
    'description' => 'this is an imaginary board for purposes of demostration',
    // needs to be set to avoid asking for the backend for boardSettings for NAV
    'settings' => array(),
  )
);

if (!function_exists('secondsToTime')) {
  // this smells bad...
  include '../frontend_lib/handlers/boards.php';
  // prevent generation's fe router from including this group again
  global $router;
  $router->included['boards'] = true;
}

getBoardThreadListingRender($boardUri, $boardThreads, 1, array(
  'noBoardHeaderTmpl' => true,
  // stop settings http request
  'settings' => array(
    'user' => array(
      'current_theme' => $theme
    ),
    'site' => array(
    ),
  ),
  'noWork' => true,
));

?>