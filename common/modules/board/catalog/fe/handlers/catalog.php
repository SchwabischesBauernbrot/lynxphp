<?php

$params = $getHandler();

// FIXME: do we own this board?
$boardUri = $request['params']['uri'];

// change to resource
//$data = getBoardCatalog($boardUri);
$data = $pkg->useResource('catalog', array('boardUri' => $boardUri));
$catalog = $data['pages'];
global $boardData;
$boardData = $data['board'];
if (!empty($catalog['meta']['err'])) {
  if ($catalog['meta']['err'] === 'Board not found') {
    wrapContent("Board not found");
  } else {
    wrapContent("Unknown board error");
  }
  return;
}
$templates = loadTemplates('catalog');

$tmpl = $templates['header'];

$boardnav_html  = $templates['loop0'];
$image_template = $templates['loop1'];
$tile_template  = $templates['loop2'];

$maxPage = 0;
$posts = array();
if (is_array($catalog)) {
  foreach($catalog as $i=>$obj) {
    if (isset($obj['page'])) {
      $maxPage = max($obj['page'], $maxPage);
    } else {
      echo "<pre>No page set in [", print_r($obj, 1), "]</pre>\n";
    }
    foreach($obj['threads'] as $j => $post) {
      $catalog[$i]['threads'][$j]['boardUri'] = $boardUri;
      preprocessPost($catalog[$i]['threads'][$j]);
      $posts[] = $post;
    }
  }
}

global $pipelines;
$data = array(
  'posts'    => $posts,
  'catalog'  => $catalog,
  'boardUri' => $boardUri,
);
$pipelines[PIPELINE_POST_POSTPREPROCESS]->execute($data);
unset($posts);

//$boardnav_html = renderBoardNav($boardUri, $maxPage, '[Catalog]');
$boardnav_html = '';

$tiles_html = '';
if (is_array($catalog)) {
  global $BASE_HREF;
  $tile_tags = array('uri' => $boardUri);
  foreach($catalog as $pageNum => $page) {
    foreach($page['threads'] as $thread) {
      /*
      $tile_image = '<a href="' . BASE_HREF . $boardUri . '/thread/' .
        $thread['no']. '.html#' . $thread['no'] .
        '"><img src="images/imagelessthread.png" width=209 height=64></a><br>';
      */
      //echo "<pre>thread[", print_r($thread, 1), "]</pre>\n";

      // update thread number
      $tile_tags['no'] = $thread['no'];
      //$tile_image = str_replace('{{file}}', 'backend/' . $thread['files'][0]['path'], $tile_image);
      // filename, size, w, h
      // thumb to be set
      if (isset($thread['files']) && count($thread['files'])) {
        $tile_tags['thumb'] = getThumbnail($thread['files'][0], array('maxW' => 209));
      } else {
        $tile_tags['thumb'] = '<img src="images/imagelessthread.png" width=209 height=64>';
      }
      // need $BASE_HREF..
      // do we? we have it in the base tag...
      //echo "page[$pageNum]<br>\n";
      $tags = array(
        'uri' => $boardUri,
        'subject' => empty($thread['sub']) ? '' : htmlspecialchars($thread['sub']),
        'message' => htmlspecialchars($thread['com']),
        'name' => htmlspecialchars($thread['name']),
        'no' => $thread['no'],
        'jstime' => gmdate('Y-m-d', $thread['created_at']) . 'T' . gmdate('H:i:s.v', $thread['created_at']) . 'Z',
        'human_created_at' => gmdate('n/j/Y H:i:s', $thread['created_at']),
        // why is this sometimes empty?
        'replies' => empty($thread['reply_count']) ? 0 : $thread['reply_count'],
        'files' => empty($thread['file_count']) ? 0 : $thread['file_count'],
        // starts at 0
        'page' => $pageNum + 1,
        'tile_image' => replace_tags($image_template, $tile_tags),
      );
      $tiles_html .= replace_tags($tile_template, $tags);
    }
  }
}
//$boardData = getBoard($boardUri);
//$boardData['pageCount'] = $boardThreads['pageCount'];
$boardData['pageCount'] = $maxPage;
// but no footer...
// why no footer?

//$boardHeader = renderBoardPortalHeader($boardUri, $boardData, array(
//  'isCatalog' => true,
//));

// this is for postactions
$io = array(
  'boardUri' => $boardUri,
  'beforeFormEndHtml' => '',
  'tags' => array()
);
$pipelines[PIPELINE_BOARD_FOOTER_TMPL]->execute($io);

$p = array(
  'boardUri' => $boardUri,
  'tags' => array(
    'uri' => $boardUri,
    'description' => htmlspecialchars($boardData['description']),
    'tiles' => $tiles_html,
    'boardNav' => $boardnav_html,
    // mixin
    //'postform' => renderPostForm($boardUri, $boardUri . '/catalog'),
    //'postactions' => renderPostActions($boardUri),
    // quick hack for now
    'postactions' => $io['beforeFormEndHtml'],
  ),
);
global $pipelines;
$pipelines[PIPELINE_BOARD_DETAILS_TMPL]->execute($p);
$tmpl = replace_tags($tmpl, $p['tags']);
wrapContent($tmpl);