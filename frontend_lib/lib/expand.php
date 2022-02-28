<?php

/*
<details id="boardsNav">
  <summary class="nav-item">Boards</a></summary>
  <!-- 50% is weird, maybe 1/3 or just go full screen... ? -->
  <div id="boardsSubpage" style="position: absolute; top: 32px; z-index: 1; padding: 5px; background-color: var(--background-rest);">
    <!-- class="nojsonly-block" -->
    <iframe title="boards list subframe" id="boardsSubpageFrame" frameborder=0 name="boardFrame" style="width: 100vw; height: 50vh;" srcdoc='<a style="display: block; line-height: 100vh; text-align: center; width: 100vw; height: 100vh;" target="boardFrame" href="/boards_inline.html">Please click to load all the html for full board list</a>'></iframe>
  </div>
</details>
<details class="threadExpander">
  <summary>{{replies_omitted}} replies omitted. <!-- a href="{{uri}}/thread/{{threadNum}}.html">View the full thread</a --> Click to expand viewer</summary>
  <div>
    <!-- meta http-equiv="refresh" content="0;URL={{threadUrl}}" / -->
    <iframe title="expanded thread viwer" name="thread{{threadNum}}View" width=700 style="height: 50vh;" srcdoc='<a id="link" style="display: block; line-height: 100vh; text-align: center; width: 700px; height: 100vh;" target="thread{{threadNum}}View" href="{{threadUrl}}">Please click to load all the html for full thread #{{threadNum}}</a>'>
    </iframe>
  </div>
</details>
*/
// iframeId*
// label is basically thumbnail tag (getThumbnail)
function getExpander($label, $content, $options = array()) {
  extract(ensureOptions(array(
    'type' => 'media', // media/iframe
    'detailsClass' => false,
    'detailsId' => false,
    'summaryClass' => false,
    'divId' => false,
    // use proper css...
    //'divStyle' => false,
    'iframeId' => false,
    'iframeTitle' => '', // accessibility score
    'iframeBorder' => true,
    // I think this can be always hard coded
    //'aId' = false,
    'target' => false, // has to be unique per page...
    'aLabel' => 'Please click to load all the full html for this section',
    'aHref'  => '/', // almost required
    'tn_sz' => array(),
    'sz'    => array(),

    'classes' => array(),
    'labelId' => false,
      'styleContentUrl' => false,
  ), $options));

  $style = '';
  if ($labelId && $styleContentUrl) {
/*
    $style = '<style>
details[open].img#' . $labelId . ' > summary::after {
  content: url(' . $styleContentUrl . ');
}
</style>';
*/
$r = 1;
if ($sz[1]) {
  if ($sz[1] < $sz[0]) {
    $r = ($sz[1] / $sz[0]) * 100;
  } else {
    $r = ($sz[0] / $sz[1]) * 100;
  }
}
// ' . $sz[1] . 'px

// only after click will collapse it
// background-image: url(' . $styleContentUrl . ');
    $style = '<style>
details[open].img#' . $labelId . ' > summary::after {
  content: \'\';
  background-color: #00000000;
  background-size: ' . $sz[0] . 'px ' . $sz[1]. 'px;
  display: inline-block;
  width: ' . $sz[0] . 'px;
  height: ' . $sz[1] . 'px;
  position: absolute;
  top: 0;
}
details.img#' . $labelId . ' .contentarea {
  width: 95vw;
  padding-bottom: ' . $r . '%;
}
details[open].img#' . $labelId . ' .contentarea {
  background: url(' . $styleContentUrl . ');
  background-size: contain;
  background-repeat: no-repeat;
}
</style>';
  }
  // latest chrome wont let you slip it under
/*
  position: absolute;
  top: 0;
  z-index: -1;
*/
  $id = $labelId !== false ? ' id="' . $labelId . '"' : '';
  $classes[] = 'nojsonly-block';
  $class = count($classes) ? ' class="' . join(' ', $classes) . '"' : '';

  // nojs
  $html = $style;
  $html .= '<details' . $id . $class . '>' . "\n";
  $html .= '<summary>' . $label . '</summary>' . "\n";
  $html .= $content . "\n";
  $html .= '<div class="contentarea"></div></details>' . "\n";
  // for js
  // we need to reserve the space to avoid relayout
  // we can't just have JS insert these later...
  // or manipulate the html above...
  $html .= '<a class="jsonly" href="' . $styleContentUrl . '">' . $label . '</a>';
  return $html;
}

?>
