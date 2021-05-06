<?php

function redirectTo($url) {
  echo '<head>';
  //echo '<base href="', BASE_HREF, '">';
  echo '<meta http-equiv="refresh" content="0; url=', $url,'">';
  echo '</head>';
  /*
  echo '<script>';
  echo 'window.location = "', $url, '"';
  echo '</script>';
  */
}

function tagify($tag) {
  return '{{' . $tag . '}}';
}

function replace_tags($template, $tags) {
  if (!is_string($template)) {
    echo "replace_tags - Template isn't an string\n";
    return $template;
  }
  if (!is_array($tags)) {
    echo "replace_tags - Tags isn't an array\n";
    return $template;
  }
  return str_replace(array_map('tagify', array_keys($tags)), array_values($tags), $template);
}

function loadTemplates($template) {
  return loadTemplatesFile('templates/' . $template . '.tmpl');
}

function moduleLoadTemplates($template, $dir) {
  // this will be called from the frontend_handlers dir
  return loadTemplatesFile($dir . '/../views/' . $template . '.tmpl');
}

function loadTemplatesFile($path) {
  $lines = @file($path);
  if (!is_array($lines)) {
    echo "lib.handler::loadTemplatesFile - Can't read [$path]<br>\n";
    return array();
  }
  $section = 'header';
  $loop = -1;
  $templates = array('header' => '');
  foreach($lines as $line) {
    $tline = trim($line);
    if ($tline === '<!-- loop -->') {
      $loop++;
      $section = 'loop' . $loop;
      $templates[$section] = '';
      continue;
    } else if ($tline === '<!-- end -->') {
      $section = 'header';
      continue;
    }
    $templates[$section] .= $line;
  }
  return $templates;
}

function loadTemplatesFile2($path) {
  $section = 'header';
  $templates = array($section => '');
  $lines = file($path);
  foreach($lines as $line) {
    $tline = trim($line);
    // starts with <!-- section[
    if (substr(0, 13, $tline) === '<!-- section[') {
      //echo "Found new layout format<Br>\n";
      // ends with ] -->
      $end = strpos($tline, '] -->', 12);
      $section = substr($tline, 12, $end);
      if (empty($templates[$section])) $templates[$section]='';
      continue; // don't include line
    }
    if (strtoupper($tline) === '<!-- END -->') {
      // only count loops, so that loop0 is the first loop
      $section = 'footer';
      if (empty($templates[$section])) $templates[$section]='';
      continue; // don't include line
    }
    $templates[$section] .= $line;
  }
  return $templates;
}

function wrapContent($content, $options = '') {
  global $pipelines, $packages;
  // how do we hook in our admin group?
  // the data is only there if we asked for it...
  // could be a: global, pipeline or ??
  if (empty($options['settings'])) {
    // this can cause an infinite loop if backend has an error...
    $settings = $packages['base']->useResource('settings', false, array('inWrapContent'=>true));
  } else {
    $settings = $options['settings'];
    //echo "<pre>", print_r($settings, 1), "</pre>\n";
  }
  if (empty($settings) || !is_array($settings)) {
    $siteSettings = array();
    $userSettings = array();
  } else {
    $siteSettings = $settings['site'];
    $userSettings = $settings['user'];
  }
  $enableJs = true;
  $doWork = true;
  // only should be used when we know we're opening a ton of requests in parallel
  if (!empty($options['noWork'])) {
    $doWork = false;
  }

  $themes = array('yotsuba-b', 'yotsuba', 'amoled', 'army-green', 'cancer', 'chaos', 'choc', 'darkblue', 'gurochan', 'lain', 'miku', 'mushroom', 'navy', 'pink', 'rei-zero', 'solarized-dark', 'solarized-light', 'tempus-cozette', 'tomorrow', 'tomorrow2', 'vapor', 'win95', 'snerx');
  if (empty($userSettings['current_theme']) || $userSettings['current_theme'] === 'default') $userSettings['current_theme'] = $themes[0];

  $themesHtml = '';
  foreach($themes as $theme) {
    if ($userSettings['current_theme'] === $theme) {
      $themesHtml .= '<link id="theme" rel="stylesheet" data-theme="' . $theme . '" href="css/themes/' . $theme . '.css">';
    } else {
      // these are always downloaded in chrome... ugh
      //$themesHtml .= '<link rel="alternate stylesheet" type="text/css" data-theme="' . $theme . '" title="' . $theme . '" href="css/themes/' . $theme . '.css">';
    }
  }

  $templates = loadTemplates('header');
  // how and when does this change?
  // FIXME: cacheable...
  $tags = array(
    'nav' => '',
    'basehref' => BASE_HREF,
    'title' => empty($siteSettings['siteName']) ? '': $siteSettings['siteName'],
    // maybe head insertions is better?
    'themes' => $themesHtml,
    'jsenable' => $enableJs ? '' : '<!-- ',
    'jsenable2' => $enableJs ? '' : ' -->',
  );
  echo replace_tags($templates['header'], $tags), $content;
  unset($templates);
  $tags = array(
    'jsenable' => $enableJs ? '' : '<!-- ',
    'jsenable2' => $enableJs ? '' : ' -->',
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
    echo '<iframe style="display: none" src="backend/opt/work"></iframe>', "\n";
    //$result = $packages['base']->useResource('work', false, array('inWrapContent' => true));
    //
    if (DEV_MODE) {
      $start = microtime(true);
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
    //echo "SERVER", print_r($_SERVER, 1), "<br>\n";
  }
}

?>