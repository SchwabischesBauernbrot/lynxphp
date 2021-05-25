<?php

// FIXME: pass in template...
function getNav($navItems, $options = array()) {
  $list = isset($options['list']) ? $options['list'] : true;
  $selected = isset($options['selected']) ? $options['selected'] :'';
  $selectedURL = isset($options['selectedURL']) ? $options['selectedURL'] : false;
  $replaces = isset($options['replaces']) ? $options['replaces'] : array();
  $prelabel = isset($options['prelabel']) ? $options['prelabel'] :'';
  $postlabel = isset($options['postlabel']) ? $options['postlabel'] :'';

  $nav_html = '';
  if ($list) $nav_html = '<ul>';
  foreach($navItems as $label => $urlTemplate) {
    $url = replace_tags($urlTemplate, $replaces);
    if ($list) $nav_html .= '<li>';
    $class = '';
    //echo "selectedURL[$selectedURL] url[$url]<br>\n";
    if ($selectedURL && $selectedURL === $url) {
      $class = ' class="bold"';
    }
    if ($selected === $label) {
      $class = ' class="bold"';
    }
    $nav_html .= '<a' . $class . ' href="' . $url . '">' . $prelabel . $label . $postlabel . '</a>' . "\n";
  }
  if ($list) $nav_html .= '</ul>';
  return $nav_html;
}

/*
  $portalOptions = array(
    'headerPipeline' => PIPELINE_USER_HEADER_TMPL,
    'footerPipeline' => PIPELINE_USER_FOOTER_TMPL,
    'navPipeline'    => PIPELINE_USER_NAV,
    'navItems' => array(
      'general' => 'user/settings/general',
      'theme' => 'user/settings/theme',
    ),
    // nav ideas: replaces? selected? displayOpts (list)
    'useNavFirstItem' => true/false,
    // do we want this? like header tags?
    'replaces' => array()
  );
*/


/*
$portal = array(
  'header'=>array(
    'file' => '',
    // tag => code/constant
    'replaces' => array(),
    'nav' => array(
      'items' => array(),
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
*/


function renderPortalHeader($type, $options = false) {
  global $pipelines;

  extract(ensureOptions(array(
     // PIPELINE_type_HEADER_TMPL
    'headerPipeline' => false,
     // PIPELINE_type_NAV
    'navPipeline' => false,
      'useNavFirstItem' => false,
    'navItems'  => array(),
    'prelabel'  => '[',
    'postlabel' => ']',
  ), $options));

  $templates = loadTemplates('mixins/' . $type . '_header');
  $selectedURL = false;

  if ($navPipeline && isset($pipelines[$navPipeline])) {
    $pipelines[$navPipeline]->execute($navItems);
    if ($useNavFirstItem) {
      $keys = array_keys($navItems);
      $selectedURL = $navItems[$keys[0]];
    }
  }
  $navOptions = array(
    'selectedURL' => $selectedURL ?: substr($_SERVER['REQUEST_URI'], 1),
    'prelabel' => $prelabel,
    'postlabel' => $postlabel,
  );
  $nav_html = getNav($navItems, $navOptions);

  $p = array(
    'tags' => array(
      'nav' => $nav_html,
    )
  );
  if ($headerPipeline && isset($pipelines[$headerPipeline])) {
    $pipelines[$headerPipeline]->execute($p);
  }
  return replace_tags($templates['header'], $p['tags']);
}

function renderPortalFooter($type, $options = false) {
  global $pipelines;

  extract(ensureOptions(array(
     // PIPELINE_type_FOOTER_TMPL
    'footerPipeline' => false,
  ), $options));

  $templates = loadTemplates('mixins/' . $type . '_footer');
  $p = array(
    'tags' => array()
  );

  if ($footerPipeline && isset($pipelines[$footerPipeline])) {
    $pipelines[$footerPipeline]->execute($p);
  }
  return replace_tags($templates['header'], $p['tags']);
}

/*
$unit = array(
  'name' => 'unit name',
  'cacheSettings' => array(),
  'workFunction' => 'function name',
)
*/

/*
// name is a lookup and part of what we're stored in (we store these objects in)
class portal {
  // little touch
  function __construct($header_template, $footer_template, $nav) {
    // even though we're sure we need to read both of these files
    // 2 files to keep the memory footprint down
    $this->headerTemplateFile = $header_template;
    $this->footerTemplateFile = $footer_template;
  }

  // common data?

  // if we stay as a class
  // it's a safe assumption that a header's going to have a footer
  // but we may only want to have one in memory at a time
  function renderHeader() {
    $templates = loadTemplatesFile($wrapper_template);
    $template = $templates['header'];
    // process template
    echo $template;
    unset($template); // free memory

    $navTemplate = $templates['loop0'];
    // process
    echo $navTemplate;
  }

  function renderFooter() {
    $template = file_get_contents($this->footerTemplateFile);
    // process template
    echo $template;
  }
}
*/

?>
