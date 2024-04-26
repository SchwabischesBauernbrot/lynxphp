<?php

function snippet($text, $size = 10, $tail='...') {
  $text = stripslashes($text);
  if (isset($text[$size + 1])) {
    $arr = explode('_$3pR_', wordwrap($text, $size, '_$3pR_', true));
    $text = array_shift($arr) . $tail;
  }
  return $text;
}

// $boardSettings, $userSettings are in $options
// but aren't required like they are here...
// but aren't really required because we can add getters
function getMediaTags($file, $boardUri, $options) {
  // unpack options (these really aren't options though...)
  extract(ensureOptions(array(
    'isOP' => false,
    'where' => '',
    'noActions' => false,
    'boardSettings' => false,
    'userSettings' => false,
    'spoiler' => '',
  ), $options));

  // we use array() for mock boards (theme demo)
  if ($boardSettings === false) {
    if (DEV_MODE) {
      echo "getMediaTags - boardSettings not passed<br>\n";
    }
    $boardSettings = getter_getBoardSettings($boardUri);
  }
  if ($userSettings === false) {
    if (DEV_MODE) {
      echo "getMediaTags - userSettings not passed<br>\n";
    }
    $userSettings = getUserSettings();
  }
  // filename, path, thumbnail_path, mime_type, type, size, w, h
  // tn_w, tn_h
  //echo "<pre>file[", print_r($file, 1), "]</pre>\n";
  //echo "file type[", $file['type'], "]<br>\n";

  $path = $file['path'];
  // filename is the original filename
  $ext = fileExtension($file['filename']); //  (from router)
  $noext = str_replace('.'.$ext, '', $file['filename']);
  $downloadPath = 'download/' . $path;
  //$watchLink = 'watch/' . $path;

  // if not an absolute URL
  if (strpos($path, '://') === false) {
    $path = BACKEND_PUBLIC_URL . $path;
    $downloadPath = BACKEND_PUBLIC_URL . $downloadPath;
    //$watchLink = BACKEND_PUBLIC_URL . $watchLink;
  }
  $majorMimeType = getFileType($file);
  //echo "majorMimeType[", $majorMimeType, "]<br>\n";
  $fileSha256 = 'f' . uniqid();
  $thumbUrl = $file['path'];
  if (isset($file['thumbnail_path'])) {
    $thumbUrl = $file['thumbnail_path'];
  }
  if (strpos($thumbUrl, '://') === false) {
    $thumbUrl = BACKEND_PUBLIC_URL . $thumbUrl;
  }

  $thumb = getThumbnail($file, array(
    'type' => $majorMimeType, 'alt' => 'thumbnail of ' . $file['filename'],
    // FIXME: db falsish check (isFalsish)
    'spoiler' => (empty($file['spoiler']) || $file['spoiler'] === 'f') ? false : $spoiler,
    // if a list of threads, any way to tell if this is the first?
    // && $firstThread
    'noLazyLoad' => $isOP,
  ));

  $mute = empty($userSettings['mute']) ? false : true;
  $loop = false;
  //echo "majorMimeType[$majorMimeType]<br>\n";
  if ($majorMimeType === 'video') {
    $loop = empty($userSettings['videoloop']) ? false : true;
  } else
  if ($majorMimeType === 'audio') {
    $loop = empty($userSettings['audioloop']) ? false : true;
  }
  $avmedia = getAudioVideo($file, array(
    'type' => $majorMimeType,
    'mute' => $mute,
    'loop' => $loop,
  ));
  // first clause was a hard fought for war... 2nd is just backup
  if ($file['type'] === 'glb' || strpos($file['path'], '.glb') !== false) {
    $avmedia = '';
    // I think if we just make the t_xxx_xxx.avif or .webp
    // we need the backend to send this
    echo "thumbnail_path[", isset($file['thumbnail_path']) ? $file['thumbnail_path'] : '', "]<br>\n";
    $thumb = getThumbnail(array(
      'path' => $file['path'],
      'type' => 'image',
      'thumbnail_path' => 'https://www.flowkit.app/s/demo/r/rh:-45,rv:15,s:255,var:street/u/' . urlencode(BACKEND_PUBLIC_URL . $file['path']),
      'tn_w' => 255,
      'tn_h' => 255,
    ), array(
      // can't pass in glb, expecting nothing but img basically
      'type' => 'img', 'alt' => 'thumbnail of ' . $file['filename'],
      // FIXME: db falsish check (isFalsish)
      'spoiler' => (empty($file['spoiler']) || $file['spoiler'] === 'f') ? false : $spoiler,
      // if a list of threads, any way to tell if this is the first?
      // && $firstThread
      'noLazyLoad' => $isOP,
    ));
    //echo "<pre>", htmlspecialchars(print_r($thumb, 1)), "</pre>\n";
    // only needed for the backwards compat it seems
    // we need to set this, for the js
    $majorMimeType = 'glb';
  }

  $shortenSize = 10;
  if (!empty($file['tn_w'])) {
    //$shortenSize = max($shortenSize, (int)($file['tn_w'] / 8));
    // we can make some estimate off the aspect ratio...
    $w = getThumbnailWidth($file, array('type' => $majorMimeType));
    //echo "w[$w]<Br>\n";
    // 154 / 8 = 20190419-_DSF2773.j... ~20 chars
    $shortenSize = max($shortenSize, (int)($w / 8) - 10);
  }
  //echo "shortenSize[$shortenSize] noext[$noext]<br>\n";
  $tn_w = empty($file['tn_w']) ? 0 : $file['tn_w'];
  $tn_h = empty($file['tn_h']) ? 0 : $file['tn_h'];

  $hover = empty($userSettings['hover']) ? false : true;
  $nojs  = empty($userSettings['nojs'])  ? false : true;

  if (strpos($file['path'], '.glb') !== false) {
    $hover = false;
  }

  /*
  // non-display attributes
  $fTags = array(
    // path/filename/size/w/h/codec should be in f
    //'path' => $file['path'],
    'url'  => $path,
    'downloadUrl' => $downloadPath,
    //'watchUrl' => $watchLink,
    'id' => $fileSha256, // kinda of display but interactivity for js comms
    'majorMimeType' => $majorMimeType,
    // shortfilename? tn_*? seems like a pure display thing
  );
  */

  $media_actions_html = '';
  if (!$noActions) {
    global $pipelines;

    // storage/BOARDURI/THREADNUM/POSTNUM_MEDIANUM.ext
    // maybe we parse it
    $noStorage = str_replace('storage/boards/', '', $file['path']);
    $parts = explode('/', $noStorage);
    $bp = $parts[0];
    $threadnum = empty($parts[1]) ? '' : $parts[1];
    $filename  = empty($parts[2]) ? '' : $parts[2];
    $parts   = explode('.', $filename);
    $ext2    = array_pop($parts);
    $rest    = explode('_', implode('.', $parts));
    //echo "<pre>rest[", print_r($rest, 1), "]</pre>\n";
    $postno  = $rest[0];
    // can't use empty because 0 will be considered empty
    $mediano = isset($rest[1]) ? $rest[1] : '';


    $initialActions = action_getLevels();
    $action_io = array(
      'f' => $file,
      'path' => array(
        'boardUri'  => $bp,
        'threadNum' => $threadnum,
        'filename'  => $filename,
        'ext'       => $ext,
        'postNum'   => $postno,
        'mediaNum'  => $mediano,
      ),
      'url' => $path,
      'downloadUrl' => $downloadPath,
      //'watchUrl' => $watchLink,
      'id' => $fileSha256,
      'majorMimeType' => $majorMimeType,
      //'p' => $p,
      'boardUri' => $boardUri,
      'actions'  => $initialActions,
      // what uses this and what data does it need?
      // probably to see if things like reacts are enabled...
      'boardSettings' => $boardSettings,
      'userSettings'  => $userSettings,
    );
    $pipelines[PIPELINE_MEDIA_ACTIONS]->execute($action_io);
    $media_actions_html = action_getExpandHtml($action_io['actions'], array(
      'boardUri' => $boardUri, 'where' => $where, 'nojs' => $nojs));
  }

  $containerId = 'container_' . $fileSha256;
  $expanderOptions = array(
    //'thumbUrl' => $thumbUrl, // didn't use
    'hover' => $hover,
    'parentContainerId' => $containerId, // needed for hover
    'majorMimeType' => $majorMimeType,
    'classes' => array('postFile', $majorMimeType),
    'tn_sz' => array($tn_w, $tn_h),
    'sz' => array($file['w'], $file['h']),
    'labelId' => 'details_' . $fileSha256,
    'styleContentUrl' => $path,
    'nojs' => $nojs,
  );
  //echo "hover[$hover]<br>\n";
  $fTags = array(
    'path' => $path,
    // but matter if audio/video, we can handle that in css
    'postFileClasses' => $hover ? ' useViewer ' : '',
    'downloadLink' => $downloadPath,
    //'watchLink' => $watchLink,
    'expanderCss' => getExpander_css($thumb, $avmedia, $expanderOptions),
    'expander' => getExpander_html($thumb, $avmedia, $expanderOptions),
    'fileid' => $fileSha256,
    'fileId' => ' id="' . $containerId . '"',
    'filename' => $file['filename'],
    'majorMimeType' => $majorMimeType,
    'shortfilename' => snippet($noext, $shortenSize) . ' ' . $ext,
    'size' => empty($file['size']) ? 'Unknown' : formatBytes($file['size']),
    'width' => $file['w'],
    'height' => $file['h'],
    'thumb' => $thumb,
    //'viewer' => getViewer($file, array('type' => $majorMimeType)),
    // not currently used but we'll include it in case they want to do something different
    'avmedia' => $avmedia,
    'codec' => empty($file['codec']) ? '' : $file['codec'],
    'codecSpace' => empty($file['codec']) ? '' : ' ',
    'tn_w' => $tn_w,
    'tn_h' => $tn_h,
    'actions' => $media_actions_html,
  );
  return $fTags;
}

// the 4chan format is p. shitty
// missing threadid and boardUri...
// would be handy to have this in JS for dynamic content
// js can't hook into the pipeline system, so it can't render the same
// well JS can use the ajax endpoints to pull it
// see preview stuff...

// no is optional...
// - we need to turn off actions
// - turn off "no" and "0"
// - remove "[Reply]"
// but we'll need OC tags on templates... or sections...
function renderPost($boardUri, $p, $options = false) {
  global $pipelines;

  /*
  // FIXME: should be a pipeline
  if ($p['type'] === 'doubleplus.post.repost') {
    // this works for the board page
    // but how can we tell which page we're on
    $origPost = $p;
    $p = $p['repostOf'];
    $p['no'] = $origPost['threadid'];
  }
  */

  //echo "<pre>", print_r($p['files'], 1), "</pre>\n";
  //echo "<pre>", print_r($p, 1), "</pre>\n";

  // unpack options
  extract(ensureOptions(array(
    'checkable' => false,
    'postCount' => false, // for omit & pipeline (actions)
    'noOmit'    => false,
    //'topReply' => false, // related to noOmit
    //'inMixedBoards' => false, // ?
    // maybe convert to lazyLoad?
    'firstThread' => false, // for adjusting loading=lazy
    'where' => '', // what is in this? for lib.actions for from query
    'userSettings'  => false,
    'boardSettings' => false,
    'noActions' => false, // for themes to reduce BE calls
  ), $options));

  //$isBO = perms_isBO($boardUri);
  if (DEV_MODE) {
    if (!isset($p['threadid'])) {
      // well I see no thread but posts...
      echo "<pre>threadid missing[", print_r($p, 1), "]</pre>\n";
    }
  }
  $threadId = $p['threadid'] ? $p['threadid'] : $p['no'];
  $isOP = $threadId === $p['no'];

  /*
  if ($postCount !== false) {
    echo "postCount[$postCount] [$threadId]<br>\n";
  } else {
    echo "no postCount<br>\n" . gettrace();
  }
  */

  if ($userSettings === false) {
    if (DEV_MODE) {
      echo "No userSettings passed to renderPost [", gettrace(), "]<Br>\n";
    }
    $userSettings = getUserSettings();
  }
  //echo "<pre>userSettings:", print_r($userSettings, 1), "</pre>\n";

  // this makes the page more dynamic
  // but if we can explicit now to omit js, we can save bandwidth
  // can we explicit omit nojs?
  // customization makes it hard to cache though...
  $nojs = empty($userSettings['nojs']) ? false : true;

  $spoiler = array(
    'url' => 'images/img/spoiler.png',
    'w' => 200,
    'h' => 200,
  );
  if ($boardSettings === false) {
    if (DEV_MODE) {
      echo "No boardSettings passed to renderPost [", gettrace(), "]<Br>\n";
    }
    $boardData = getBoard($boardUri);
    if (isset($boardData['settings'])) {
      $boardSettings = $boardData['settings'];
    }
    //print_r($boardSettings);
  }

  // override default spoiler
  if (isset($boardSettings['customSpoiler'])) {
    $spoiler = $boardSettings['customSpoiler'];
  }
  // upgrade to option for getMediaTags
  $options['spoiler'] = $spoiler;

  $post_actions = action_getLevels();
  // FIXME: post/actions should provide this
  $post_actions['all'][] = array('link' => '/' . $boardUri . '/report/' . $p['no'], 'label' => 'report');
  // hide
  // filter ID/name
  // moderate
  if (!$p['no']) {
    $post_actions['all'] = array(); // remove report
  }
  global $pipelines;
  // pretext processing...
  $action_io = array(
    'boardUri' => $boardUri,
    'p' => $p,
    'actions'  => $post_actions,
    // disable because?
    // use because?
    //'postCount' => $postCount, // # of posts in thread, pass it if we have it
    // what uses this and what data does it need?
    // probably to see if things like reacts are enabled...
    'boardSettings' => $boardSettings,
  );
  if ($postCount !== false) {
    $action_io['postCount'] = $postCount;
  }
  if ($isOP && !$noActions) {
    $pipelines[PIPELINE_THREAD_ACTIONS]->execute($action_io);
  }
  // can't noactions this because of the pretext?
  $pipelines[PIPELINE_POST_ACTIONS]->execute($action_io);
  $post_actions = $action_io['actions']; // pipeline output
  //echo "<pre>", print_r($post_actions, 1), "</pre>\n";

  // when is includeWhere not needed?
  // seems like any thread or posts can appear on the thread listing, details or overboard
  // so it's seemingly should always be on
  // it's only needed on links that need to return
  // so items that actually navigate away
  // and don't intend to return
  // either way it should be opt-out and we can add that when we actually need it
  /*
  foreach($post_actions as $type=>$actions) {
    foreach($actions as $i => $a) {
      // opt-out? add when needed
      $actions[$i]['includeWhere'] = true;
    }
  }
  */

  // $where is an option above, good to document that more...
  // what does nojs do? it's a usersetting that reduces the amount of HTML produced
  $post_actions_html = action_getExpandHtml($post_actions, array(
    'boardUri' => $boardUri, 'where' => $where, 'nojs' => $nojs));

  // should we have pre and post links around the no #123 part?
  $post_links_html = '';
  $links_io = array(
    'boardUri' => $boardUri,
    'p' => $p,
    // FIXME: usersettings and boardsettings
    'nojs' => $nojs,
    'links' => array(
      //array('label' => '[Reply]', 'link' => '/' . $boardUri . '/thread/' . $threadId. '.html#postform')
    ),
  );
  $pipelines[PIPELINE_POST_LINKS]->execute($links_io);
  $post_link_html_parts = array();
  if (count($links_io['links'])) {
    foreach($links_io['links'] as &$a) {
      // FIXME: bracket styling doesn't belong here
      // definitely doesn't belong inside PIPELINE_POST_LINKS modules
      $post_link_html_parts[] = '[<a href="' . $a['link'] . '">' . $a['label'] . '</a>]';
    }
  }
  $post_links_html = join(' ' . "\n", $post_link_html_parts);

  // os disk cache will handle caching
  // but not the parsing tbh
  static $templates; // this will use more memory over the request lifetime
                     // but only if it's called once..
  if (!is_array($templates)) {
    $templates = loadTemplates('mixins/post_detail');
  }
  //$checkable_template = $templates['loop0'];
  $posticons_template = $templates['loop0'];
  // icon, title
  $icon_template      = $templates['loop1'];
  $file_template      = $templates['loop2'];
  $replies_template   = $templates['loop3'];
  $reply_template     = $templates['loop4'];
  $omitted_template   = $templates['loop5'];

  $postmeta = '';

  // add icons to postmeta
  $icon_io = array(
    'boardUri' => $boardUri,
    'p' => $p,
    // FIXME: usersettings and boardsettings
    'nojs' => $nojs,
    'icons' => array(),
  );
  if ($isOP) {
    // sticky, bumplocked, locked, cyclic
    $pipelines[PIPELINE_THREAD_ICONS]->execute($icon_io);
  }
  // jschan doesn't have any post icons, only thread ones
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
    // could put a label tag inside or around...
    $postmeta .= '<span class="post-subject">' . htmlspecialchars($p['sub']) . '</span> ';
  }
  $defaultName = false;
  if (empty($p['name'])) {
    $defaultName = true;
    $p['name'] = 'anonymous';
  }
  // jschan goes icons, subject, email/name, flag, trip, cap, datetime, userId, links
  // less bytes than a small tag and give BO/theme/custom css better control over look

  // consider https://developers.google.com/search/docs/appearance/structured-data/article
  // only turn on for seobot though...
  $defaultClass = $defaultName ? ' default-name' : '';
  if (!empty($p['email']) && !empty($p['name'])) {
      $postmeta .= '<a href="mailto:' . $p['email'] . '"  rel="author" class="author post-name' . $defaultClass . '">' . htmlspecialchars($p['name']) . '</a>';
  } else  {
    // al la cart
    if (!empty($p['email'])) {
      // https://stackoverflow.com/questions/7290504/which-html5-tag-should-i-use-to-mark-up-an-author-s-name
      // class author is a microformat thingy
      $postmeta .= '<a rel="author" class="author post-name' . $defaultClass . '">' . htmlspecialchars($p['name']) . '</address>';
    } else
    if (!empty($p['name'])) {
      // moved style="display: inline-block" into lynxphp.css
      // could put a label tag inside
      $postmeta .= '<address class="author post-name' . $defaultClass . '">' . htmlspecialchars($p['name']) . '</address>';
    }
  }
  //echo "<pre>", print_r($p['flag_cc'], 1), "</pre>\n";
  // lynxchan doesn't need flag to set flag_cc / flagName
  if (!empty($p['flag_cc'])) {
    // country flag
    $flagTitle = empty($p['flagName']) ? '' : $p['flagName'];
    $postmeta .= ' <span class="flag flag-'.$p['flag_cc'].'" title="' . $flagTitle . '"></span>';
  } else // only one flag
  if (!empty($p['flag'])) {
    $flag = addslashes(htmlspecialchars($p['flag']));
    // non-country flag
    //$postmeta .= ' <span title="'.$p['flagName'].'">';
    // FIXME: flag width and height
    // 19x12 for IGA
    // and 16x16 for Nuro+ https://endchan.wrongthink.net:8443/ausneets/flags/5e4b58dfe571bd1c7b890205
    $flagTitle = empty($p['flagName']) ? '' : $p['flagName'];
    $postmeta .= ' <img src="' . BACKEND_PUBLIC_URL . $p['flag'] . '" alt="'.$flagTitle.'">';
  }

  // Hook processing for $postmeta
  $meta_io = array(
    'uri' => $boardUri,
    'threadNum' => $threadId,
    'p' => $p,
    'meta' => $postmeta,
    // FIXME: usersettings and boardsettings
    'nojs' => $nojs,
    // temp, remove later
    'checkable' => $checkable,
  );
  $pipelines[PIPELINE_POST_META_PROCESS]->execute($meta_io);
  $postmeta = $meta_io['meta'];

  // this (user-id) is bad, needs to be changed
  // can't dereference in JS
  $userid_html = '';
  if (!empty($p['user-id'])) {
    // min dom check: passed
    $userid_html .= '<span class="user-id" style="background-color: #' . $p['user-id'] . '">' . htmlspecialchars($p['user-id']) . '</span>';
  }

  $omitted_html = '';
  if ($isOP) {
    //echo "threadId[$threadId] postCount[$postCount]<br>\n";
    // reply_count
    // file_count
    //echo "<pre>thread[", print_r($p, 1), "]</pre>\n";

    // warning?
    if ($postCount === false) $postCount = 0;

    // total - rpp
    $rOmitted = $postCount - 5;
    if ($rOmitted < 0) $rOmitted = 0;
    if (!$noOmit && $rOmitted) {

      //$lastPost = $posts[count($posts) - 1];
      $add_html = '';
      $threadUrl = '/' . $boardUri . '/thread/' . $threadId . '_inline.html';
      $expandUrl = $threadUrl;
      //if ($topReply) {
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
      //}

      $omit_tags = array(
        'replies_omitted' => $rOmitted ? $rOmitted : '',
        'uri'       => $boardUri,
        'threadNum' => $threadId,
        'threadUrl' => $expandUrl,
      );
      $omitted_html = $add_html . replace_tags($omitted_template, $omit_tags);
    }
  }

  //echo "<pre>userSettings:", print_r($userSettings, 1), "</pre>\n";

  //$files_style = '';
  $files_html = '';
  if (isset($p['files']) && is_array($p['files'])) {
    foreach($p['files'] as $file) {
      $ftmpl = $file_template;
      // check cache that might have been set in the top loop
      $mediaTags = isset($file['mediaTags']) ? $file['mediaTags'] : getMediaTags($file, $boardUri, $options);
      //$files_style .= $mediaTags['expanderCss'];
      $files_html .= replace_tags($file_template, $mediaTags);
    }
  }

  $replies_html = '';

  // pass in p and get it back modified
  // com isn't required if there's a file
  if (!isset($p['com'])) $p['com'] = ''; // might be safer for pipeline
  $p['safeCom'] = htmlspecialchars($p['com']);
  $p['boardUri'] = $boardUri; // communicate what board we're on
  // we'll we communicate what board this post is on
  // but we don't communicate what type of page
  // we need to know if this is mix board context like overboard

  // if mixed context, just always show it?
  // no, it has nothing to do where it's from
  // it has to do where it's pointing...
  //$p['inMixedBoards'] = $inMixedBoards; // just means don't strip anything
  // well from that we can assume to strip $boardUri
  $pipelines[PIPELINE_POST_TEXT_FORMATTING]->execute($p);

  $links_html = '';
  // are we a BO? is this our post?
  $io = array(
    'uri' => $boardUri,
    'p' => $p,
    'html' => '',
  );
  $pipelines[PIPELINE_POST_ROW_APPEND]->execute($io);
  $links_html = $io['html'];

  $tags = array(
    'op'        => $isOP ? 'op': '',
    'uri'       => $boardUri,
    'threadNum' => $threadId,
    'no'        => $p['no'],
    'subject'   => empty($p['sub']) ? '' : htmlspecialchars($p['sub']),
    'message'   => $p['safeCom'],
    'name'      => empty($p['name']) ? '' : htmlspecialchars($p['name']),
    'postmeta'  => $postmeta,
    'userid'    => $userid_html,
    //'fileStyles' => $files_style,
    'files'     => $files_html,
    'replies'   => $replies_html,
    // for actions details/summary
    //'backgroundColorCSS' => $isOP ? 'var(--background-rest)' : 'var(--post-color)',
    'jstime'    => gmdate('Y-m-d', $p['created_at']) . 'T' . gmdate('H:i:s.v', $p['created_at']) . 'Z',
    'human_created_at' => gmdate('n/j/Y H:i:s', $p['created_at']),
    'links'     => $links_html,
    'actions'   => $post_actions_html,
    'postlinks' => $post_links_html,
    'omitted'   => $omitted_html,
    'threadOpen' => $isOP && !$noOmit ? '[<span style="text-decoration: underline">Open</span>]' : '',
  );
  $tmp = replace_tags($templates['header'], $tags);

  return $tmp;
}

?>