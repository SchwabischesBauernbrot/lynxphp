<?php

function component_listing($template, $addAction, $name, $fields, $options = false) {
  extract(ensureOptions(array(
    'top_actions' => array(),
  ), $options));
  if (!count($top_actions)) {
    $top_actions = array(
      array(
        //
      ),
    );
  }
  $html = '';
  $addLink = '<a href="">add ' . $name . '</a>';
  $html = $template['header'] . $addLink. $template['footer'];
  return $html;
}

?>
