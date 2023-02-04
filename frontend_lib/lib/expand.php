<?php

function makeIframeContents($aHref, $aLabel) {
  // no single quotes allowed (because we're quoting with single-quotes)
  $html = '<a class="nojsonly-block" style="line-height: 100vh; text-align: center; width: 100vw; height: 100vh;" target="boardFrame" href="' . $aHref .'">' . $aLabel . '</a>';
  return $html;
}

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
// too maybe ids and targets, should have one key drive them all
function getExpander($label, $content, $options = array()) {
  extract(ensureOptions(array(
    'type' => 'media', // media/iframe
    'hover' => false,
    'parentContainerId' => '', // needed for hover
    'majorMimeType' => 'unknown',
    // dedundant with classes
    // when do we need more than one
    'detailsClass' => false,
    'detailsId' => false,
    'summaryClass' => false,
    'divId' => false,
    // use proper css...
    //'divStyle' => false,
    'iframeId' => false,
    'iframeName' => '', // js? targeting?
    'iframeTitle' => '', // accessibility score
    // probably could leave this off and let css style it
    'iframeBorder' => true, // maybe 0 or 1?
    // I think this can be always hard coded
    //'aId' = false,
    // when would this not be iframeName?
    'target' => false, // has to be unique per page...
    // these two could be moved into $content tbh
    'aLabel' => 'Please click to load all the full html for this section',
    'aHref'  => '/', // almost required
    // sizes are requied to position / size correctly
    'tn_sz' => array(),
    'sz'    => array(),

    'classes' => array(),
    'labelId' => false, // similar to detailsId
      'styleContentUrl' => false,
  ), $options));

  if ($detailsClass) $classes []= $detailsClass;

  $style = '';
  if ($labelId && $styleContentUrl && $type !== 'iframe') {
    $r = 1;
    if ($sz[1]) {
      if ($sz[1] < $sz[0]) {
        $r = ($sz[1] / $sz[0]) * 100;
      } else {
        $r = ($sz[0] / $sz[1]) * 100;
      }
    }
    // ' . $sz[1] . 'px

    if ($hover) {
      if ($majorMimeType === 'img') {
        $style = '<style>
#'.$parentContainerId.':hover ~ .viewer {
  background: url("' . $styleContentUrl . '") no-repeat;
  background-size: contain;
  width:  ' . $sz[0] . 'px;
  height: ' . $sz[1] . 'px;
  max-width: 90vw;
  max-height: 90vh;
}

</style>
        ';
      }
    } else {
      // does content let you display videos? no
      /*
          $style = '<style>
      details[open].img#' . $labelId . ' > summary::after {
        content: url(' . $styleContentUrl . ');
      }
      </style>';
      */

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
  }
  // latest chrome wont let you slip it under
/*
  position: absolute;
  top: 0;
  z-index: -1;
*/
  $id = $labelId !== false ? ' id="' . $labelId . '"' : '';

  if ($type === 'iframe') {
    // iframe type
    /*
    meta tag refresh loads the content onload, instead of waiting for it to be used
    iframe title="boards list subframe" id="boardsSubpageFrame" frameborder=0 name="boardFrame" style="width: 100vw; height: 50vh;" src="/boards_cacheable.html"></iframe
    what so bad if it's always loading from the cache?
    #div is a js loading destination zone
    #boardsSubpage 50% is weird, maybe 1/3 or just go full screen... ?
    #boardsSubpageFrame class="nojsonly-block"  display: block;
    */
    // FIXME: base class
    // FIXME: class for details/div/iframe (remove style)
    $html = '
    <details id="' . $detailsId . '" class="detailsClass" style="display: inline-block">
      <summary class="' . $summaryClass . '">' . $label . '</summary>
      <div id="' . $divId . '" style="position: absolute; top: 32px; left: 0; z-index: 1; padding: 5px; background-color: var(--background-rest);">
        <iframe title="' . $iframeTitle . '" id="' . $iframeId . '" frameborder=0 name="' . $iframeName . '" style="width: 100vw; height: 50vh;" srcdoc=\'<a class="nojsonly-block" style="line-height: 100vh; text-align: center; width: 100vw; height: 100vh;" target="' . $target . '" href="' . $aHref .'">' . $aLabel . '</a>\'></iframe>
      </div>
    </details>
    ';
  } else {
    // media

    if ($hover) {
      $class = count($classes) ? ' class="' . join(' ', $classes) . '"' : '';
      if ($majorMimeType === 'img') {
        $html = $style;
        // id is need or the auto-generated css above to put the content in
        $html .= '<span' . $id . $class . '><span>' . $label . '</span>';
        $html .= '</span>';
      } else {
        // audio / video
        // css can't play these...
        $html = $style;
        $html .= $label;
        /*
        $html .= '<span' . $class . '><span>' . $label . '</span></span>';
        // autoplay loading=lazy poster="' . $thumbUrl . '"
        // <source src="' . $styleContentUrl . '" type="video/webm" loading=lazy>
        $html .= '<span class="videoPlayer">
        </span>
        ';
        */
        // <video src="' . $styleContentUrl . '"></video>
      }
    } else {
      // nojs
      if ($styleContentUrl) {
        $classes[] = 'nojsonly-block';
      }
      $class = count($classes) ? ' class="' . join(' ', $classes) . '"' : '';
      $html = $style;
      $html .= '<details' . $id . $class . '>' . "\n";
      $html .= '<summary>' . $label . '</summary>' . "\n";
      // why don't we just use an img tag here?
      $html .= $content . "\n";
      $html .= '<div class="contentarea"></div></details>' . "\n";

      // for js
      // we need to reserve the space to avoid relayout
      // we can't just have JS insert these later...
      // or manipulate the html above...
      if ($styleContentUrl) {
        $html .= '<a class="jsonly" href="' . $styleContentUrl . '">' . $label . '</a>';
      }
    }
  }
  return $html;
}

function getHoverExpander($label, $content, $options = array()) {
  extract(ensureOptions(array(
    'type' => 'media', // media/iframe
    'detailsClass' => false,
    'detailsId' => false,
    'summaryClass' => false,
    'divId' => false,
    'majorMimeType' => 'unknown',
    'thumbUrl' => false,
    // use proper css...
    //'divStyle' => false,
    'iframeId' => false,
    'iframeName' => '', // js? targeting?
    'iframeTitle' => '', // accessibility score
    // probably could leave this off and let css style it
    'iframeBorder' => true, // maybe 0 or 1?
    // I think this can be always hard coded
    //'aId' = false,
    // when would this not be iframeName?
    'target' => false, // has to be unique per page...
    // these two could be moved into $content tbh
    'aLabel' => 'Please click to load all the full html for this section',
    'aHref'  => '/', // almost required
    // sizes are requied to position / size correctly
    'tn_sz' => array(),
    'sz'    => array(),

    'classes' => array(),
    'labelId' => false, // similar to detailsId
      'styleContentUrl' => false,
  ), $options));

  $style = '';
  if ($labelId && $styleContentUrl) {
    if ($majorMimeType === 'img') {
    $r = 1;
    if ($sz[1]) {
      if ($sz[1] < $sz[0]) {
        $r = ($sz[1] / $sz[0]) * 100;
      } else {
        $r = ($sz[0] / $sz[1]) * 100;
      }
    }
    // ' . $sz[1] . 'px
// responsive but we're not sure how many on a row...
// width=255 style="max-height: 255px; object-fit: contain"
// this does break aspect ratio (object-fit can help)

// so we can't have them in a row
// if we want to take the entire row...
    //$classes[] = 'useViewer';
      $style = '<style>
#'.$labelId.':hover ~ .viewer {
  background: url("' . $styleContentUrl . '") no-repeat;
  background-size: contain;
  width:  ' . $sz[0] . 'px;
  height: ' . $sz[1] . 'px;
  max-width: 90vw;
  max-height: 90vh;
}

</style>
      ';
/*
#'.$labelId.':hover ~ .viewerSizer {
  width: 90vw;
  height: 90vh;
}
      <!-- pushes the posts down, I don't think we need it -->
      <!-- div class="viewerSizer"></div -->

*/
// the size pushed on the items to the right and pushes them to the next line
// including our preview
    } else {
      $style = '<style>
.videoPlayer {
  visibility: hidden;
  display: none;
}
</style>
      ';
    }
  }

  $id = $labelId !== false ? ' id="' . $labelId . '"' : '';

  // nojs
  if ($styleContentUrl) {
    //$classes[] = 'nojsonly-block';
  }
  $class = count($classes) ? ' class="' . join(' ', $classes) . '"' : '';

  if ($majorMimeType === 'img') {
    $html = $style;
    $html .= '<span' . $class . '><span>' . $label . '</span>';
    $html .= '</span>';
  } else {
    // audio / video
    // css can't play these...
    $html = $style;
    $html .= '<span' . $class . '><span>' . $label . '</span></span>';
    // autoplay loading=lazy poster="' . $thumbUrl . '"
    // <source src="' . $styleContentUrl . '" type="video/webm" loading=lazy>
    $html .= '<span class="videoPlayer">
    </span>
    ';
    // <video src="' . $styleContentUrl . '"></video>
  }
  return $html;
}

?>