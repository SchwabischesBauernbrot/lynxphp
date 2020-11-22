<?php

function homepage() {
  $boards = getBoards();
  $templates = loadTemplates('index');
  //echo "<pre>", print_r($templates, 1), "</pre>\n";
  $boards_html = '';
  foreach($boards as $c=>$b) {
    $tmp = $templates['loop0'];
    $tmp = str_replace('{{uri}}', $b['uri'], $tmp);
    $tmp = str_replace('{{title}}', $b['title'], $tmp);
    $tmp = str_replace('{{description}}', $b['description'], $tmp);
    $boards_html .= $tmp . "\n";
    if ($c > 10) break;
  }

  $content = $templates['header'];
  $content = str_replace('{{boards}}', $boards_html, $content);

  wrapContent($content);
}

?>
