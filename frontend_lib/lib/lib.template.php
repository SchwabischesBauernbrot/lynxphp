<?php

//
// template engine
//

function tagify($tag) {
  return '{{' . $tag . '}}';
}

function replace_tags($template, $tags) {
  if (!is_string($template)) {
    // we need to be able to inform what file...
    echo "lib.template::replace_tags - Template isn't an string\n";
    return $template;
  }
  if (!is_array($tags)) {
    echo "lib.template::replace_tags - Tags isn't an array\n";
    return $template;
  }
  // check to make sure all tags values are strings?
  return str_replace(array_map('tagify', array_keys($tags)), array_values($tags), $template);
}

// os disk cache will handle caching
function loadTemplatesFile($path, $options = false) {
  $lines = @file($path);
  if (!is_array($lines)) {
    echo "lib.template::loadTemplatesFile - Can't read [$path]<br>\n";
    return array();
  }
  $section = 'header';
  $loop = -1;
  $templates = array('header' => '');
  if (DEV_MODE && $options && !$options['noDev']) {
    $templates = array('header' => '<!-- DEV_MODE: included from ' . $path . ' -->' . "\n");
  }
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
  if (DEV_MODE) {
    $templates = array('header' => '<!-- DEV_MODE: included from ' . $path . ' -->' . "\n");
  }
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

function loadTemplates($template) {
  return loadTemplatesFile('templates/' . $template . '.tmpl');
}

function moduleLoadTemplates($template, $dir, $options = false) {
  // this will be called from the frontend_handlers dir
  return loadTemplatesFile($dir . '/../views/' . $template . '.tmpl', $options);
}