<?php

function loadTemplates($template) {
  return loadTemplatesFile('templates/' . $template . '.tmpl');
}

function moduleLoadTemplates($template, $dir) {
  return loadTemplatesFile($dir . '/' . $template . '.tmpl');
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

function wrapContent($content) {
  // could be readfile but probably going to need tags
  $templates = loadTemplates('header');
  $hdrTmpl = $templates['header'];
  // how and when does this change?
  $hdrTmpl = str_replace('{{nav}}', '', $hdrTmpl);
  // FIXME: cacheable...
  $hdrTmpl = str_replace('{{basehref}}', BASE_HREF, $hdrTmpl);
  echo $hdrTmpl;

  echo $content;

  $ftrTmpl = file_get_contents('templates/footer.tmpl');
  echo $ftrTmpl;
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
