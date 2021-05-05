<?php

function getGlobalPage() {

  $portal = array(
    'header'=>array(
      'file' => '',
      // tag => code/constant
      'replaces' => array(),
      'nav' => array(
        'items' => array(
        ),
        'replaces' => array(),
        'selected' => '',
        'displayOpts' => array(
          'list' => true
        )
      )
    ),
    'footer'=>array(
      'file' => '',
      'replaces' => array(),
    ),
  );

  $boardnav_html = renderGlobalPortal();

  $content = $boardnav_html;
  $content .= <<< EOB
You're an hotpocket, you do it for FREE!
EOB;
  wrapContent($content);
}

?>
