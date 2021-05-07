<?php

$params = $getHandler();

//echo "<pre>", print_r($params, 1), "</pre>\n";

$theme = $params['request']['params']['theme'];

if (file_exists('css/themes/' . $theme . '.css')) {
  $mtime = filemtime('css/themes/' . $theme . '.css');
  // we'd have to generate it to get the length
  if (checkCacheHeaders($mtime, array('contentType' => 'text/html'))) {
    exit();
  }
}

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

// can't use getBoardPortal because we need to pick a board
// and be consistent...
$board = getBoardPortal($boardUri, array(
  'pageCount' => 15,
  'title' => 'random',
  'description' => 'this is an imaginary board for purposes of demostration',
), array(
  // no banner
  'pagenum' => $pagenum,
  'noBoardHeaderTmpl' => true,
));

// how do we fake contents?

  $templates = loadTemplates('thread_listing');
  //echo join(',', array_keys($templates));

  $page_template = $templates['loop0'];
  $boardnav_html = $templates['loop1'];
  $file_template = $templates['loop2'];
  $threadhdr_template = $templates['loop3'];
  $threadftr_template = $templates['loop4'];
  $thread_template = $templates['loop5'];

  //echo "test[", htmlspecialchars(print_r($templates, 1)),"]<br>\n";

  // FIXME: register/push a portal with wrapContent
  // so it can fast out efficiently
  // also should wrapContent be split into header/footer for efficiency? yes
  // and we need keying too, something like ESI
  $boardnav_html = '';

  // used to look at text, so we can queue up another backend query if needed
  // FIXME: check count of PIPELINE_POST_PREPROCESS
  /*
  $nPosts = array();
  global $pipelines;
  $data = array(
    'posts' => 99,
    'boardThreads' => 15 * 10,
    'pagenum' => 1
  );
  $pipelines[PIPELINE_POST_POSTPREPROCESS]->execute($data);
  unset($nPosts);
  */
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
            ),
            array(
              'path' => 'https://www.placecage.com/g/300/200',
              'filename' => 'noimageset.png',
              'w' => 300,
              'h' => 200,
              'type' => 'image',
            ),
            array(
              'path' => 'https://www.placecage.com/c/256/256',
              'filename' => 'noimageset.png',
              'w' => 256,
              'h' => 256,
              'type' => 'image',
            ),
            array(
              'path' => 'https://www.placecage.com/gif/200/300',
              'filename' => 'noimageset.png',
              'w' => 200,
              'h' => 300,
              'type' => 'image',
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
    )
  );

  $threads_html = '';
  foreach($pageData as $thread) {
    if (!isset($thread['posts'])) continue;
    $posts = $thread['posts'];
    //echo "count[", count($posts), "]<br>\n";
    $threads_html .= $threadhdr_template;
    foreach($posts as $i => $post) {
      $threads_html .= renderPost($boardUri, $post, array('checkable' => true));
    }
    $threads_html .= $threadftr_template;
  }

  $tmpl = $templates['header'];
  $p = array(
    'boardUri' => $boardUri,
    'tags' => array()
  );
  global $pipelines;
  $pipelines[PIPELINE_BOARD_DETAILS_TMPL]->execute($p);
  $tmpl = replace_tags($tmpl, $p['tags']);
  /*
  foreach($p['tags'] as $s => $r) {
    $tmpl = str_replace('{{' . $s . '}}', $r, $tmpl);
  }
  */

  // need this for form actions
  $tmpl = str_replace('{{uri}}', $boardUri, $tmpl);
  //$tmpl = str_replace('{{title}}', htmlspecialchars($boardData['title']), $tmpl);
  //$tmpl = str_replace('{{description}}', htmlspecialchars($boardData['description']), $tmpl);
  $tmpl = str_replace('{{threads}}', $threads_html, $tmpl);
  $tmpl = str_replace('{{boardNav}}', $boardnav_html, $tmpl);
  $tmpl = str_replace('{{pagenum}}', $pagenum, $tmpl);
  // mixin
  //$tmpl = str_replace('{{postform}}', renderPostForm($boardUri, $boardUri . '/'), $tmpl);
  $tmpl = str_replace('{{postactions}}', renderPostActions($boardUri), $tmpl);


wrapContent(
  //$content .
  $board['header'] .
  $tmpl .
  $board['footer'],
  // stop settings http request
  array(
    'settings' => array(
      'user' => array(
        'current_theme' => $theme
      ),
      'site' => array(
      ),
    ),
    'noWork' => true,
  )
);

?>