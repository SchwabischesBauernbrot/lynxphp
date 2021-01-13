<?php

function loadTemplates($template) {
  return loadTemplatesFile('templates/' . $template . '.tmpl');
}

function moduleLoadTemplates($template, $dir) {
  // this will be called from the frontend_handlers dir
  return loadTemplatesFile($dir . '/../views/' . $template . '.tmpl');
}

function loadTemplatesFile($path) {
  $lines = file($path);
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

function wrapContent($content) {
  // could be readfile but probably going to need tags
  $templates = loadTemplates('header');
  $hdrTmpl = $templates['header'];
  // how and when does this change?
  $hdrTmpl = str_replace('{{nav}}', '', $hdrTmpl);
  // FIXME: cacheable...
  $hdrTmpl = str_replace('{{basehref}}', BASE_HREF, $hdrTmpl);
  echo $hdrTmpl, $content;
  $ftrTmpl = file_get_contents('templates/footer.tmpl');
  echo $ftrTmpl;
  flush();
}

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

?>
