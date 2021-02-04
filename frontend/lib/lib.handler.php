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

function wrapContent($content, $siteSettings = '') {
  // how do we hook in our admin group?
  // the data is only there if we asked for it...
  // could be a: global, pipeline or ??
  if ($siteSettings === '') {
    global $packages;
    // this can cause an infinite loop if backend has an error...
    $siteSettings = $packages['base']->useResource('settings', false, array('inWrapContent'=>true));
  }
  $enableJs = true;

  $templates = loadTemplates('header');
  // how and when does this change?
  // FIXME: cacheable...
  $tags = array(
    'nav' => '',
    'basehref' => BASE_HREF,
    'title' => $siteSettings['siteName'],
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
  if (DEV_MODE) {
    global $now;
    $diff = (microtime(true) - $now) * 1000;
    echo "took $diff ms<br>\n";
    curl_log_report();
  }
}

?>