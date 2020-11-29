<?php

function renderBoardHeader($boardData) {
  global $pipelines;
  $templates = loadTemplates('mixins/board_header');
  $tmpl = $templates['header'];

  $p = array(
    'boardUri' => $boardData['uri'],
    'tags' => array()
  );
  $pipelines['boardHeaderTmpl']->execute($p);
  foreach($p['tags'] as $s => $r) {
    $tmpl = str_replace('{{' . $s . '}}', $r, $tmpl);
  }

  $tmpl = str_replace('{{uri}}', $boardData['uri'], $tmpl);
  $tmpl = str_replace('{{url}}', $_SERVER['REQUEST_URI'], $tmpl);
  $tmpl = str_replace('{{title}}', htmlspecialchars($boardData['title']), $tmpl);
  $tmpl = str_replace('{{description}}', htmlspecialchars($boardData['description']), $tmpl);

  return $tmpl;
}

?>
