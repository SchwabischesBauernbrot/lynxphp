<?php

function snippet($text, $size = 10, $tail='...') {
  $text = stripslashes($text);
  if (isset($text[$size + 1])) {
    $arr = explode('_$3pR_', wordwrap($text, $size, '_$3pR_', true));
    $text = array_shift($arr) . $tail;
  }
  return $text;
}


// the 4chan format is p. shitty
// missing threadid and boardUri...
// would be handy to have this in JS for dynamic content
// js can't hook into the pipeline system, so it can't render the same
// well JS can use the ajax endpoints to pull it
function renderPost($boardUri, $p, $options = false) {
  global $pipelines;

  //echo "<pre>", print_r($p['files'], 1), "</pre>\n";

  // unpack options
  extract(ensureOptions(array(
    'checkable'  => false,
    'postCount'  => false,
    'topReply' => false,
    'noOmit'   => false,
  ), $options));

  //$isBO = perms_isBO($boardUri);
  $threadId = $p['threadid'] ? $p['threadid'] : $p['no'];
  $isOP = $threadId === $p['no'];

  $post_actions = array(
    'all'    => array(
      // FIXME: post/actions should provide this
      array('link' => '/' . $boardUri . '/report/' . $p['no'], 'label' => 'report'),
      // hide
      // filter ID/name
      // moderate
    ),
    'user'   => array(),
    'bo'     => array(),
    'global' => array(),
    'admin'  => array(),
  );

  /*
  if ($postCount !== false) {
    echo "postCount[$postCount] [$threadId]<br>\n";
  } else {
    echo "no postCount<br>\n" . gettrace();
  }
  */

  global $pipelines;
  // pretext processing...
  $action_io = array(
    'boardUri' => $boardUri,
    'p' => $p,
    'actions'  => $post_actions,
    // FIXME: pass post count...
  );
  if ($postCount !== false) {
    $action_io['postCount'] = $postCount;
  }
  if ($isOP) {
    $pipelines[PIPELINE_THREAD_ACTIONS]->execute($action_io);
  }
  $pipelines[PIPELINE_POST_ACTIONS]->execute($action_io);
  // remap output over the top of the input
  $post_actions = $action_io['actions'];

  //

  $post_actions_html_parts = array();
  if (count($post_actions['all'])) {
    foreach($post_actions['all'] as &$a) {
      /*
      $post_actions_html_parts[] = '<a href="dynamic.php?boardUri=' . urlencode($boardUri) .
        '&action=' . urlencode($a). '&id=' . $p['no']. '">' . $l . '</a>';
      */
      $post_actions_html_parts[] = '<a href="' . $a['link'] . '">' . $a['label'] . '</a>';
    }
  }
  if (count($post_actions['bo']) && perms_isBO($boardUri)) {
    foreach($post_actions['bo'] as &$a) {
      $post_actions_html_parts[] = '<a href="' . $a['link'] . '">' . $a['label'] . '</a>';
    }
  }
  unset($a);
  $post_actions_html = join('<br>' . "\n", $post_actions_html_parts);

  // should we have pre and post links around the no #123 part?
  $post_links_html = '';
  $links_io = array(
    'boardUri' => $boardUri,
    'p' => $p,
    'links' => array(),
  );
  $pipelines[PIPELINE_POST_LINKS]->execute($links_io);
  $post_link_html_parts = array();
  if (count($links_io['links'])) {
    foreach($links_io['links'] as &$a) {
      $post_link_html_parts[] = '<a href="' . $a['link'] . '">' . $a['label'] . '</a>';
    }
  }
  $post_links_html = join('<br>' . "\n", $post_link_html_parts);

  // os disk cache will handle caching
  $templates = loadTemplates('mixins/post_detail');
  $checkable_template = $templates['loop0'];
  $posticons_template = $templates['loop1'];
  $icon_template      = $templates['loop2'];
  $file_template      = $templates['loop3'];
  $replies_template   = $templates['loop4'];
  $reply_template     = $templates['loop5'];
  $omitted_template   = $templates['loop6'];

  $postmeta = '';
  if ($checkable) {
    $postmeta .= replace_tags($checkable_template, array('no' => $p['no']));
  }

  // add icons to postmeta
  $icon_io = array(
    'boardUri' => $boardUri,
    'p' => $p,
    'icons' => array(),
  );
  if ($isOP) {
    $pipelines[PIPELINE_THREAD_ICONS]->execute($icon_io);
  }
  $pipelines[PIPELINE_POST_ICONS]->execute($icon_io);
  $icons = $icon_io['icons'];
  if (count($icons)) {
    $icons_html = '';
    foreach($icons as $i) {
      $icons_html .= replace_tags($icon_template, $i);
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
    $postmeta .= ' <span class="flag flag-'.$p['flag_cc'].'" title="'.$p['flagName'].'" alt="'.$p['flagName'].'"><img src="' . BACKEND_PUBLIC_URL . $p['flag'] . '"></span>';
  }
  // post-
  if (!empty($p['capcode'])) {
    $postmeta .= ' <span class="post-capcode">' . htmlspecialchars($p['capcode']) . '</span>';
  }
  if (!empty($p['user-id'])) {
    $postmeta .= '<span class="user-id">' . htmlspecialchars($p['user-id']) . '</span>';
  }

  if ($postmeta !== '' && $checkable) {
    $postmeta = '      <label>' . "\n" . $postmeta . '      </label>';
  }

  $omitted_html = '';
  if ($isOP) {
    //echo "threadId[$threadId] postCount[$postCount]<br>\n";
    // reply_count
    // file_count
    //echo "<pre>thread[", print_r($p, 1), "]</pre>\n";

    // total - rpp
    $rOmitted = $postCount - 5;
    if ($rOmitted < 0) $rOmitted = 0;
    if (!$noOmit && $rOmitted) {

      //$lastPost = $posts[count($posts) - 1];
      $add_html = '';
      $threadUrl = '/' . $boardUri . '/thread/' . $threadId . '_inline.html';
      $expandUrl = $threadUrl;
      if ($topReply) {
        //while we can, we shouldn't...
        //$expandUrl .= '#' . $topReply;
        // didn't work because the anchor is only set on the iframe
        /*
        $add_html = '
        <style>
          #'.$topReply.':target ~ #threadExpander' . $threadId . ' {
            display: block;
          }
        </style>
        ';
        */
      }

      $omit_tags = array(
        'replies_omitted' => $rOmitted ? $rOmitted : '',
        'uri'       => $boardUri,
        'threadNum' => $threadId,
        'threadUrl' => $expandUrl,
      );
      $omitted_html = $add_html . replace_tags($omitted_template, $omit_tags);
    }
  }
  // tn_w, tn_h aren't enabled yet
  $files_html = '';
  foreach($p['files'] as $file) {
    //echo "<pre>file[", print_r($file, 1), "]</pre>\n";
    $ftmpl = $file_template;
    // disbale images until we can mod...
    //$ftmpl = str_replace('{{path}}', 'backend/' . $file['path'], $ftmpl);

    // disbale images until we can mod...
    $path = $file['path'];
    // if not an absolute URL
    if (strpos($path, '://') === false) {
      $path = BACKEND_PUBLIC_URL . $path;
    }
    $majorMimeType = getFileType($file);
    $fileSha256 = 'f' . uniqid();
    $thumb   = getThumbnail($file, array('type' => $majorMimeType));
    $avmedia = getAudioVideo($file, array('type' => $majorMimeType));
    $shortenSize = 10;
    if (!empty($file['tn_w'])) {
      //$shortenSize = max($shortenSize, (int)($file['tn_w'] / 8));
    } else {
      // we can make some estimate off the aspect ratio...
      $w = getThumbnailWidth($file, array('type' => $majorMimeType));
      //echo "w[$w]<Br>\n";
      // 154 / 8 = 20190419-_DSF2773.j... ~20 chars
      $shortenSize = max($shortenSize, (int)($w / 8) - 10);
    }
    $fTags = array(
      'path' => $path,
      'expander' => getExpander($thumb, $avmedia, array(
        'classes' => array('postFile', $majorMimeType),
        'labelId' => $fileSha256,
        'styleContentUrl' => $path,
      )),
      'fileid' => $fileSha256,
      'filename' => $file['filename'],
      'shortfilename' => snippet($file['filename'], $shortenSize),
      'size' => empty($file['size']) ? 'Unknown' : formatBytes($file['size']),
      'width' => $file['w'],
      'height' => $file['h'],
      'majorMimeType' => $majorMimeType,
      'thumb' => $thumb,
      //'viewer' => getViewer($file, array('type' => $majorMimeType)),
      'avmedia' => $avmedia,
      'path' => $path,
      'tn_w' => empty($file['tn_w']) ? 0 : $file['tn_w'],
      'tn_h' => empty($file['tn_h']) ? 0 : $file['tn_h'],
    );

    $ftmpl = replace_tags($file_template, $fTags);
    $files_html .= $ftmpl;
  }

  $replies_html = '';

  // pass in p and get it back modified
  $p['safeCom'] = htmlspecialchars($p['com']);
  $p['boardUri'] = $boardUri; // communicate what board we're on
  $pipelines[PIPELINE_POST_TEXT_FORMATTING]->execute($p);

  $links_html = '';
  // are we a BO? is this our post?

  $tags = array(
    'op'        => $isOP ? 'op': '',
    'uri'       => $boardUri,
    'threadNum' => $threadId,
    'no'        => $p['no'],
    'subject'   => htmlspecialchars($p['sub']),
    'message'   => $p['safeCom'],
    'name'      => htmlspecialchars($p['name']),
    'postmeta'  => $postmeta,
    'files'     => $files_html,
    'replies'   => $replies_html,
    // for actions details/summary
    'backgroundColorCSS' => $isOP ? 'var(--background-rest)' : 'var(--post-color)',
    'jstime'    => gmdate('Y-m-d', $p['created_at']) . 'T' . gmdate('H:i:s.v', $p['created_at']) . 'Z',
    'human_created_at' => gmdate('n/j/Y H:i:s', $p['created_at']),
    'links'     => $links_html,
    'actions'   => $post_actions_html,
    'postlinks' => $post_links_html,
    'omitted'   => $omitted_html,
  );
  $tmp = replace_tags($templates['header'], $tags);

  return $tmp;
}

?>
