<?php

function redirectTo($url) {
  echo '<head>';
  //global $BASE_HREF;
  //echo '<base href="', $BASE_HREF, '">';
  echo '<meta http-equiv="refresh" content="0; url=', $url,'">';
  echo '</head>';
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
         ($req_path === '/forms/login' && $req_method === 'POST') ||
         strpos($req_path, 'user/settings/themedemo/') !== false ||
         strpos($req_path, '/preview/') !== false ||
         $req_path === '/logout'
      ) && strpos($req_path, '/.youtube') === false) {
    // make sure first lines of output are see-able
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
    'noWork' => false,
    'settings' => false,
  ), $options));

  $enableJs = true;
  if (empty($settings)) {
    // this can cause an infinite loop if backend has an error...
    // FIXME: caching
    $settings = $packages['base']->useResource('settings', false, array('inWrapContent'=>true));
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
  );
}

function wrapContentHeader($row) {
  global $pipelines;

  $siteSettings = $row['siteSettings'];
  $userSettings = $row['userSettings'];
  $enableJs = $row['enableJs'];

  $io = array(
    'siteSettings' => $siteSettings,
    'userSettings' => $userSettings,
    'head_html' => '',
  );
  $pipelines[PIPELINE_SITE_HEAD]->execute($io);
  $head_html = $io['head_html'] . "\n" . '<script>
    const BACKEND_PUBLIC_URL = \'' . BACKEND_PUBLIC_URL . '\'
  </script>';

  $templates = loadTemplates('header');
  // how and when does this change?
  // FIXME: cacheable...
  global $BASE_HREF;
  $tags = array(
    'nav' => '',
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
    'end' => $io['end_html'],
  );
  $footer = loadTemplates('footer');
  echo replace_tags($footer['header'], $tags);
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

function wrapContent($content, $options = '') {
  $row = wrapContentData($options);
  wrapContentHeader($row);
  echo $content;
  wrapContentFooter($row);
}

?>