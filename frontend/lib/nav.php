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

?>
