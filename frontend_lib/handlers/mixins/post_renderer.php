<?php

// the 4chan format is p. shitty
// missing threadid and boardUri...
function renderPost($boardUri, $p, $options = false) {
  global $pipelines;

  $templates = loadTemplates('mixins/post_detail');
  $checkable_template = $templates['loop0'];
  $posticons_template = $templates['loop1'];
  $icon_template      = $templates['loop2'];
  $file_template      = $templates['loop3'];
  $replies_template   = $templates['loop4'];
  $reply_template     = $templates['loop5'];

  $postmeta = '';
  if ($options && $options['checkable']) {
    $postmeta .= replace_tags($checkable_template, array('no' => $p['no']));
  }
  // FIXME: pipeline...
  $icons = array();
  if (!empty($p['sticky']) && $p['sticky'] !== 'f') {
    $icons[] = 'sticky';
  }
  if (!empty($p['cyclic']) && $p['sticky'] !== 'f') {
    $icons[] = 'cyclic';
  }
  if (count($icons)) {
    $icons_html = '';
    foreach($icons as $file) {
      $tags = array(
        'file' => $file,
        'title' => $file,
      );
      $icons_html .= replace_tags($icon_template, $tags);
    }
    $tmp = $posticons_template;
    $tmp = str_replace('{{icons}}', $icons_html, $tmp);
    $postmeta .= $tmp;
  }
  // FIXME: this wrappers need to be controlled...
  // why was this subject? the field is sub...
  if (!empty($p['sub'])) {
    // needs trailing space to let name breathe on it's own
    $postmeta .= '<span class="post-subject">' . htmlspecialchars($p['sub']) . '</span> ';
  }
  if (!empty($p['name'])) {
    $postmeta .= '<span class="post-name">' . htmlspecialchars($p['name']) . '</span>';
  }
  if (!empty($p['flag'])) {
    $flag = addslashes(htmlspecialchars($p['flag']));
    $postmeta .= '<span class="flag flag-'.$p['flag_cc'].'" title="'.$flag.'" alt="'.$flag.'"></span>';
  }
  if (!empty($p['post-capcode'])) {
    $postmeta .= '<span class="post-capcode">' . htmlspecialchars($p['post-capcode']) . '</span>';
  }
  if (!empty($p['user-id'])) {
    $postmeta .= '<span class="user-id">' . htmlspecialchars($p['user-id']) . '</span>';
  }

  if ($postmeta !== '' && $options['checkable']) {
    $postmeta = '      <label>' . "\n" . $postmeta . '      </label>';
  }

  // tn_w, tn_h aren't enabled yet
  $files_html = '';
  foreach($p['files'] as $file) {
    $ftmpl = $file_template;
    // disbale images until we can mod...
    //$ftmpl = str_replace('{{path}}', 'backend/' . $file['path'], $ftmpl);

    // disbale images until we can mod...
    $ftmpl = str_replace('{{path}}', 'backend/' . $file['path'], $ftmpl);
    $ftmpl = str_replace('{{filename}}', $file['filename'], $ftmpl);
    if (isset($file['size'])) {
      $ftmpl = str_replace('{{size}}', $file['size'], $ftmpl);
    }
    //$ftmpl = str_replace('{{size}}', $file['size'], $ftmpl);
    $ftmpl = str_replace('{{width}}', $file['w'], $ftmpl);
    $ftmpl = str_replace('{{height}}', $file['h'], $ftmpl);
    $thumb = getThumbnail($file);
    $ftmpl = str_replace('{{thumb}}', $thumb, $ftmpl);
    $files_html .= $ftmpl;
  }

  $replies_html = '';

  global $pipelines;
  // pass in p and get it back modified
  $p['safeCom'] = htmlspecialchars($p['com']);
  $p['boardUri'] = $boardUri; // communicate what board we're on
  $pipelines[PIPELINE_POST_TEXT_FORMATTING]->execute($p);

  $threadid = $p['threadid'] ? $p['threadid'] : $p['no'];

  $links_html = '';
  // are we a BO? is this our post?

  $tags = array(
    'op'        => $threadid === $p['no'] ? 'op': '',
    'uri'       => $boardUri,
    'threadNum' => $threadid,
    'no'        => $p['no'],
    'subject'   => htmlspecialchars($p['sub']),
    'message'   => $p['safeCom'],
    'name'      => htmlspecialchars($p['name']),
    'postmeta'  => $postmeta,
    'files'     => $files_html,
    'replies'   => $replies_html,
    'jstime'    => gmdate('Y-m-d', $p['created_at']) . 'T' . gmdate('H:i:s.v', $p['created_at']) . 'Z',
    'human_created_at' => gmdate('n/j/Y H:i:s', $p['created_at']),
    'links'     => $links_html,
  );
  $tmp = replace_tags($templates['header'], $tags);

  return $tmp;
}

?>
