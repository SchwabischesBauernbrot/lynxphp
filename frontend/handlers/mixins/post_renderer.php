<?php

// the 4chan format is p. shitty
// missing threadid and boardUri...
function renderPost($boardUri, $p, $options = false) {
  global $pipelines;

  $templates = loadTemplates('mixins/post_detail');
  $checkable_template = $templates['loop0'];
  $file_template      = $templates['loop1'];
  $replies_template   = $templates['loop2'];
  $reply_template     = $templates['loop3'];

  $checkable = '';
  if ($options['checkable']) {
    $checkable = $checkable_template;
    $tmp = str_replace('{{name}}', htmlspecialchars($p['name']), $tmp);
    $tmp = str_replace('{{no}}', $p['no'], $tmp);
    $tmp = str_replace('{{jstime}}', date('c', $p['created_at']), $tmp);
    $tmp = str_replace('{{human_created_at}}', date('n/j/Y H:i:s', $p['created_at']), $tmp);
  }

  $tmp = $templates['header'];
  $tmp = str_replace('{{subject}}', htmlspecialchars($p['sub']),  $tmp);
  $tmp = str_replace('{{message}}', htmlspecialchars($p['com']),  $tmp);
  $tmp = str_replace('{{name}}',    htmlspecialchars($p['name']), $tmp);
  $tmp = str_replace('{{no}}',      $p['no'],   $tmp);
  $tmp = str_replace('{{uri}}', $boardUri, $tmp);
  $tmp = str_replace('{{threadNum}}', $p['threadid'] ? $p['threadid'] : $p['no'], $tmp);
  $tmp = str_replace('{{jstime}}', date('c', $p['created_at']), $tmp);
  $tmp = str_replace('{{human_created_at}}', date('n/j/Y H:i:s', $p['created_at']), $tmp);
  $tmp = str_replace('{{checkable}}', $checkable, $tmp);

  $files_html = '';
  foreach($p['files'] as $file) {
    $ftmpl = $file_template;
    // disbale images until we can mod...
    //$ftmpl = str_replace('{{path}}', 'backend/' . $file['path'], $ftmpl);
    $files_html .= $ftmpl;
  }
  $tmp = str_replace('{{files}}', $files_html, $tmp);

  $replies_html = '';
  $tmp = str_replace('{{replies}}', $replies_html, $tmp);

  return $tmp;
}

?>
