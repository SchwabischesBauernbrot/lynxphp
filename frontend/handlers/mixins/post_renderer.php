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
    $tmp = $checkable_template;
    $tmp = str_replace('{{name}}', htmlspecialchars($p['name']), $tmp);
    $tmp = str_replace('{{no}}', $p['no'], $tmp);
    $tmp = str_replace('{{jstime}}', date('c', $p['created_at']), $tmp);
    $tmp = str_replace('{{human_created_at}}', date('n/j/Y H:i:s', $p['created_at']), $tmp);
    $checkable = $tmp;
  }

  $tmp = $templates['header'];
  // $tmp = str_replace('{{op}}',      $i === 0 ? 'op' : '', $tmp);
  $tmp = str_replace('{{op}}',  $p['threadid'] ? '' : 'op', $tmp);
  $tmp = str_replace('{{subject}}', htmlspecialchars($p['sub']),  $tmp);
  $tmp = str_replace('{{message}}', htmlspecialchars($p['com']),  $tmp);
  $tmp = str_replace('{{name}}',    htmlspecialchars($p['name']), $tmp);
  $tmp = str_replace('{{no}}',  $p['no'], $tmp);
  $tmp = str_replace('{{uri}}', $boardUri, $tmp);
  $tmp = str_replace('{{threadNum}}', $p['threadid'] ? $p['threadid'] : $p['no'], $tmp);
  $tmp = str_replace('{{jstime}}', date('c', $p['created_at']), $tmp);
  $tmp = str_replace('{{human_created_at}}', date('n/j/Y H:i:s', $p['created_at']), $tmp);
  $tmp = str_replace('{{checkable}}', $checkable, $tmp);

  // tn_w, tn_h aren't enabled yet
  $files_html = '';
  foreach($p['files'] as $file) {
    $ftmpl = $file_template;
    // disbale images until we can mod...
    //$ftmpl = str_replace('{{path}}', 'backend/' . $file['path'], $ftmpl);

    $type = $file['type'] ? $file['type'] : 'image';
    if ($type === 'audio') {
      $isPlayable = $file['mime_type'] === 'audio/mpeg' || $file['mime_type'] === 'audio/wav' || $file['mime_type'] === 'audio/ogg';
      if (!$isPlayable) {
        $type = 'file';
      }
    }
    if ($type === 'video') {
      $isPlayable = $file['mime_type'] === 'video/mp4' || $file['mime_type'] === 'video/webm' || $file['mime_type'] === 'video/ogg';
      if (!$isPlayable) {
        $type = 'image';
      }
    }
    if ($type === 'file' || $type === 'image') $type = 'img';
    //print_r($file);
    // disbale images until we can mod...
    $ftmpl = str_replace('{{path}}', 'backend/' . $file['path'], $ftmpl);
    $ftmpl = str_replace('{{filename}}', $file['filename'], $ftmpl);
    if (isset($file['size'])) {
      $ftmpl = str_replace('{{size}}', $file['size'], $ftmpl);
    }
    //$ftmpl = str_replace('{{size}}', $file['size'], $ftmpl);
    $w = $file['w'];
    $h = $file['h'];
    while($w > 240) {
      $w *= 0.9;
      $h *= 0.9;
    }
    $ftmpl = str_replace('{{width}}', $file['w'], $ftmpl);
    $ftmpl = str_replace('{{height}}', $file['h'], $ftmpl);
    $ftmpl = str_replace('{{thumb}}', '<' . $type . ' class="file-thumb" src="backend/'.$file['path'].'" width="'.$w.'" height="'.$h.'" loading="lazy" controls loop preload=no />', $ftmpl);
    $files_html .= $ftmpl;
  }
  $tmp = str_replace('{{files}}', $files_html, $tmp);

  $replies_html = '';
  $tmp = str_replace('{{replies}}', $replies_html, $tmp);

  return $tmp;
}

?>
