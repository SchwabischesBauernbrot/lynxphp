<?php

// FIXME: pass in template...
function getNav($navItems, $replaces, $selected = '', $list = true) {
  $nav_html = '';
  if ($list) $nav_html = '<ul>';
  foreach($navItems as $label => $urlTemplate) {
    $url = $urlTemplate;
    foreach($replaces as $s => $r) {
      $url = str_replace('{{' . $s . '}}', $r, $url);
    }
    if ($list) $nav_html .= '<li>';
    $class = '';
    if ($selected === $label) {
      $class = ' class="bold"';
    }
    $nav_html .= '<a' . $class . ' href="' . $url . '">' . $label . '</a>' . "\n";
  }
  if ($list) $nav_html .= '</ul>';
  return $nav_html;
}

function getNav2($navItems, $options = array()) {
  $list = isset($options['list']) ? $options['list'] : false;
  $selected = isset($options['selected']) ? $options['selected'] :'';
  $selectedURL = isset($options['selectedURL']) ? $options['selectedURL'] : false;
  $replaces = isset($options['replaces']) ? $options['replaces'] : array();
  $prelabel = isset($options['prelabel']) ? $options['prelabel'] :'';
  $postlabel = isset($options['postlabel']) ? $options['postlabel'] :'';

  $nav_html = '';
  if ($list) $nav_html = '<ul>';
  foreach($navItems as $label => $urlTemplate) {
    $url = $urlTemplate;
    foreach($replaces as $s => $r) {
      $url = str_replace('{{' . $s . '}}', $r, $url);
    }
    if ($list) $nav_html .= '<li>';
    $class = '';
    //echo "selectedURL[$selectedURL] url[$url]<br>\n";
    if ($selectedURL && $selectedURL === $url) {
      $class = ' class="bold"';
    }
    if ($selected === $label) {
      $class = ' class="bold"';
    }
    $nav_html .= '<a' . $class . ' href="' . $url . '">' . $prelabel . $label . $postlabel . '</a>' . "\n";
  }
  if ($list) $nav_html .= '</ul>';
  return $nav_html;
}

/*
$portal = array(
  'header'=>array(
    'file' => '',
    // tag => code/constant
    'replaces' => array(),
    'nav' => array(
      'items' => array(),
      'replaces' => array(),
      'selected' => '',
      'displayOpts' => array(
        'list' => true
      )
    )
  ),
  'footer'=>array(
    'file' => '',
    'replaces' => array(),
  ),
);
*/

/*
function renderPortal($portal) {
  foreach($portal as $name => $section) {
    renderSection($section);
  }
}

function renderSection($section) {
  $templates = loadTemplatesFile($section['file']);
}
*/

// name is a lookup and part of what we're stored in (we store these objects in)
class portal {
  // little touch
  function __construct($header_template, $footer_template, $nav) {
    // even though we're sure we need to read both of these files
    // 2 files to keep the memory footprint down
    $this->headerTemplateFile = $header_template;
    $this->footerTemplateFile = $footer_template;
  }

  // if we stay as a class
  // it's a safe assumption that a header's going to have a footer
  // but we may only want to have one in memory at a time
  function renderHeader() {
    $templates = loadTemplatesFile($wrapper_template);
    $template = $templates['header'];
    // process template
    echo $template;
    unset($template); // free memory

    $navTemplate = $templates['loop0'];
    // process
    echo $navTemplate;
  }

  function renderFooter() {
    $template = file_get_contents($this->footerTemplateFile);
    // process template
    echo $template;
  }
}

?>
