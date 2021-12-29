<?php

function redirectTo($url, $options = false) {
  if (DEV_MODE) {
    wrapContent('DEV_MODE is enabled, <a href="' . $url . '">click here to continue</a>', $options);
  } else {
    echo '<head>';
    //global $BASE_HREF;
    //echo '<base href="', $BASE_HREF, '">';
    echo '<meta http-equiv="refresh" content="0; url=', $url,'">';
    echo '</head>';
  }
  /*
  echo '<script>';
  echo 'window.location = "', $url, '"';
  echo '</script>';
  */
}

// or maybe don't have a static div...
// use js to change it if X condition are met?

// not POSTING to this page or this page or ANY this page
// and reqpath does not have .youtube
$sentBump = false;
function sendBump($req_method, $req_path) {
  global $sentBump;
  if (
      !(
         ($req_path === '/signup' && $req_method === 'POST') ||
         ($req_path === '/signup.php' && $req_method === 'POST') ||
         ($req_path === '/forms/login' && $req_method === 'POST') ||
         ($req_path === '/forms/login.php' && $req_method === 'POST') ||
         strpos($req_path, 'user/settings/themedemo/') !== false ||
         strpos($req_path, '_inline.html') !== false ||
         strpos($req_path, '/preview/') !== false ||
         strpos($req_path, '/refresh') !== false ||
         $req_path === '/logout' ||
         $req_path === '/logout.php'
      ) && strpos($req_path, '/.youtube') === false) {
    // make sure first lines of output are see-able
    //if (DEV_MODE)
    echo '<!-- lib.handler::sendBump [', $req_method, '][', $req_path, '] -->';
    echo '<div style="height: 40px;"></div>', "\n"; flush();
    $sentBump = true;
  }
}

function wrapContentData($options = false) {
  global $packages;
  // how do we hook in our admin group?
  // the data is only there if we asked for it...
  // could be a: global, pipeline or ??

  extract(ensureOptions(array(
     // only should be used when we know we're opening a ton of requests in parallel
    'noWork'      => false,
    'settings'    => false,
    'closeHeader' => true,
    'canonical'   => false,
  ), $options));

  $enableJs = true;
  if (empty($settings)) {
    // this can cause an infinite loop if backend has an error...
    // FIXME: caching
    //echo "packages[", print_r(array_keys($packages), 1), "]<br>\n";
    $settings = $packages['base_settings']->useResource('settings', false, array('inWrapContent'=>true));
  }
  //echo "<pre>", print_r($settings, 1), "</pre>\n";
  if (empty($settings) || !is_array($settings)) {
    $siteSettings = array();
    $userSettings = array();
  } else {
    $siteSettings = $settings['site'];
    $userSettings = $settings['user'];
  }
  if (DISABLE_WORK) {
    $noWork = true;
  }

  return array(
    'siteSettings' => $siteSettings,
    'userSettings' => $userSettings,
    'enableJs' => $enableJs,
    'doWork' => !$noWork,
    'closeHeader' => $closeHeader,
    'canonical' => $canonical,
  );
}

// inline use this direct...
/*
<!DOCTYPE html>
<html>
<head id="settings">
  <base href="$BASE_HREF" target="_parent">
  $head_html
</head>
<body id="top">
*/
// we'd have to pull all that structure out of the header
// how would we inject stuff into the head?
// {{header}} could insert all that..
// or just section it off like footer
// used for _inline.html pages
function wrapContentGetHeadHTML($row) {
  global $pipelines;
  $siteSettings = $row['siteSettings'];
  $userSettings = $row['userSettings'];
  $io = array(
    'siteSettings' => $siteSettings,
    'userSettings' => $userSettings,
    'head_html' => '',
  );
  $pipelines[PIPELINE_SITE_HEAD]->execute($io);
  $head_html = $io['head_html'] . "\n" . '<script>
    const BACKEND_PUBLIC_URL = \'' . BACKEND_PUBLIC_URL . '\'
    const DISABLE_JS = false
  </script>' . "\n";

  if (!empty($row['canonical'])) {
    $head_html .= '<link rel="canonical" href="' . $row['canonical'] . '" />';
  }
  return $head_html;
}

function wrapContentHeader($row) {
  global $pipelines;

  $siteSettings = $row['siteSettings'];
  $userSettings = $row['userSettings'];
  $enableJs = $row['enableJs'];

  /*
  $io = array(
    'siteSettings' => $siteSettings,
    'userSettings' => $userSettings,
    'head_html' => '',
  );
  $pipelines[PIPELINE_SITE_HEAD]->execute($io);
  $head_html = $io['head_html'] . "\n" . '<script>
    const BACKEND_PUBLIC_URL = \'' . BACKEND_PUBLIC_URL . '\'
    const DISABLE_JS = false
  </script>';
  */
  $head_html = wrapContentGetHeadHTML($row);

  $templates = loadTemplates('header');

  // iframe are immediately loaded
  // can use names and links to load content
  $boards_html = getexpander(
    '<a href="boards.php" target="boardView">Boards</a>',
    '<iframe name=boardView src="loaded_iframe" style="display: none"></iframe>', array(
      'classes' => array('nav-item')
    )
  );

  $boards_html = <<<EOB
EOB;

  // FIXME: select the right page...

  $leftNavItems = array(
    //'Home' => '.',
    //'Boards' => 'boards.html',
    //'Help' => 'help.html',
  );
  $leftNav_io = array(
    'navItems' => $leftNavItems,
  );
  $pipelines[PIPELINE_SITE_LEFTNAV]->execute($leftNav_io);

  $leftNav_html = getNav($leftNav_io['navItems'], array(
    'type' => 'none', 'baseClasses' => array('nav-item'),
    //'selected' => 'none of those',
    'selectedURL' => substr($_SERVER['REQUEST_URI'], 1),
  ));

  $rightNavItems = array(
    '' => 'user/settings.html',
    'Account' => array('control_panel.php', 'forms/login.html') ,
  );
  $rightNavItems2 = array(
    array('label' => '', 'alt' => 'Settings', 'destinations' => 'user/settings.html'),
    array('label' => 'Account', 'destinations' => array('control_panel.php', 'forms/login.html')),
  );
  $rightNav_io = array(
    'navItems' => $rightNavItems2,
  );
  $pipelines[PIPELINE_SITE_RIGHTNAV]->execute($rightNav_io);
  $rightNav_html = getNav2($rightNav_io['navItems'], array(
    'type' => 'none', 'baseClasses' => array('nav-item', 'right'),
    'ids' => array('' => 'settings'),
    'selected' => 'none of those', // has to be set for settings not to be highlighted
    'selectedURL' => substr($_SERVER['REQUEST_URI'], 1),
  ));

  // how and when does this change?
  // FIXME: cacheable...
  global $BASE_HREF;
  $tags = array(
    'leftNav'  => $leftNav_html,
    'rightNav' => $rightNav_html,
    'boards'   => $boards_html,
    'basehref' => $BASE_HREF,
    'title' => empty($siteSettings['siteName']) ? '': $siteSettings['siteName'],
    // maybe head insertions is better?
    'head' => $head_html,
    'jsenable' => $enableJs ? '' : '<!-- ',
    'jsenable2' => $enableJs ? '' : ' -->',
  );

  echo replace_tags($templates['header'], $tags);
  global $sentBump;
  if (!$sentBump) {
    // make sure first lines of output are see-able
    echo '<div style="height: 40px;"></div>', "\n"; flush();
  }
}

function wrapContentFooter($row) {
  global $pipelines;
  $enableJs = $row['enableJs'];
  $doWork = $row['doWork'];
  $closeHeader = $row['closeHeader'];

  // a script could be
  // - a relative url
  // - a file in a module
  // ordering of the script can be important too
  // so a single list is preferred
  // however that doesn't let us group load a module...
  // so we have scenarios where we want to combine
  // and others we don't
  // we'd have to compile it...
  // well for now, we'll add an option
  // and deal with it when we have a need for ordering...
  $io = array(
    'scripts' => array(
      // lynxphp and jschan both use this
      'js/url.js',
      // jschan
      // quote requires localstorage
      'js/localstorage.js',
      // click to reply
      'js/jschan/quote.js',
      // preview quotes on hover
      'js/jschan/hover.js',
      // make top form draggable
      'js/jschan/dragable.js',
      // post form message character counter
      // you need to set message limits
      //'js/jschan/counter.js',
      // lynxphp
      'js/lynxphp/embed.js',
      'js/lynxphp/refresh.js',
      'js/lynxphp/expander.js',
    ),
  );
  // THINK: how do we let JS live in module directories
  // but be efficently servered by web server?
  // so that we don't have to fire up php each time
  // make the static generation engine can copy them
  // and then we have PHP fallback
  $pipelines[PIPELINE_SITE_END_SCRIPTS]->execute($io);
  $scripts = $io['scripts'];

  // THINK: how to use a pipeline to override this behavior?
  // maybe fallback if pipeline has no hooks
  $scripts_html = '';
  foreach($scripts as $p) {
    if (is_array($p)) {
      // can add a type/version key later
      // FIXME: support multiple scripts on one module
      // FIXME: generate support to drop the need for php call
      // make all the scripts local to webroot
      $p = 'js.php?module=' .$p['module'] . '&scripts=' . $p['script'];
    }
    $scripts_html .= '<script src="' . $p . '"></script>' . "\n";
  }

  $io = array(
    'siteSettings' => $row['siteSettings'],
    'userSettings' => $row['userSettings'],
    'end_html' => '',
  );
  $pipelines[PIPELINE_SITE_END_HTML]->execute($io);
  $tags = array(
    'jsenable' => $enableJs ? '' : '<!-- ',
    'jsenable2' => $enableJs ? '' : ' -->',
    'footer_header' => '',
    'footer_nav' => '',
    'footer_footer' => '',
    'end' => $scripts_html . $io['end_html'],
  );
  $footer = loadTemplates('footer');
  if ($closeHeader) {
    echo $footer['header'];
  }
  echo replace_tags($footer['loop0'], $tags);
  flush();
  // lets put this before the report, so we can profile it
  // call backend worker
  if (DEV_MODE) {
    global $now;
    $diff = (microtime(true) - $now) * 1000;
    echo "took $diff ms<br>\n";
    curl_log_report();
    curl_log_clear();
  }
  if ($doWork) {
    // FIXME: if DEV_MODE use JS to report how long it took to load
    // use an iframe...
    // X-Frame-Options could block this...
    $workUrl = BACKEND_PUBLIC_URL . 'opt/work';
    // https://stackoverflow.com/questions/57467159/how-to-make-allow-scripts-and-allow-same-origin-coexist-in-iframe
    // https://www.w3schools.com/tags/att_iframe_sandbox.asp
    // in a frame because it set 'X-Frame-Options' to 'SAMEORIGIN'.
    // sandbox="allow-same-origin"
    if (DEV_MODE) {
      $start = microtime(true);
      echo '<iframe width=99% onload="this.style.height = (this.contentWindow.document.body.scrollHeight)+\'px\'" src="' . $workUrl . '"></iframe>', "\n";
      //global $packages;
      // add 200ms to script time
      //$result = $packages['base']->useResource('work', false, array('inWrapContent' => true));
    } else {
      echo '<iframe style="display: none" src="' . $workUrl . '"></iframe>', "\n";
      $result = '';
    }
    // expirations happen here...
    $pipelines[PIPELINE_AFTER_WORK]->execute($result);

    if (DEV_MODE) {
      $diff = (microtime(true) - $start) * 1000;
      curl_log_report();
      if ($result) {
        echo "<pre>worker result [$result]</pre>\n";
      }
      // only show if it takes longer than 10ms
      if ($diff > 10) {
        echo 'PIPELINE_AFTER_WORK took ', $diff, "ms <br>\n";
      }
    }
  }
  if (DEV_MODE) {
    echo "<h4>input</h4>";
    if (count($_GET)) echo "GET", print_r($_GET, 1), "<br>\n";
    if (count($_POST)) echo "POST", print_r($_POST, 1), "<br>\n";
    //if (count($_REQUEST)) echo "POST", print_r($_REQUEST, 1), "<br>\n";
    //echo "SERVER", print_r($_SERVER, 1), "<br>\n";
  }
}

function wrapContent($content, $options = false) {
  extract(ensureOptions(array(
    'header' => true
  ), $options));
  $row = wrapContentData($options);
  if ($header) {
    wrapContentHeader($row);
  }
  echo $content;
  wrapContentFooter($row);
}

?>