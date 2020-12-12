<?php

function getNav($navItems, $replaces, $selected = '', $list = true) {
  $nav_html = '';
  if ($list) $nav_html = '<ul>';
  foreach($navItems as $label => $urlTemplate) {
    foreach($replaces as $s => $r) {
      $url = str_replace('{{' . $s . '}}', $r, $urlTemplate);
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

/*
$portal = array(
  'header'=>array(
    'file => '',
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
    'file => '',
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
