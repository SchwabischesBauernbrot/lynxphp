<?php

// old navItems format:
//   label => urlTemplate(s)
// new items format:
// [] => array(label, alt, destinations)
// destinations can be an array if multiple URLs point to the same content
// FIXME: subnavs

// label is design and we can now do that in the template
// problems with template
// - speed because of tags (pre, url, mid, label, after)
// - mixing non-template options like pre/post label?
// - classes maybe set in the template, they might not be...
//   - can use multiple types of tags (cpu)
//   - can add oc tags (complexity/makes the template ugly)

// default template: <a class="{{classes}}" {{id}} href="{{url}}" {{alt}}>{{label}}</a>\n
// are we a utility that builds lists of links
// or join htmls?
// well one goal is to have some defined
function getNav2($navItems, $options = array()) {
  extract(ensureOptions(array(
    'type' => 'list', // none, list, nav
    'list' => true,
    'selected' => '',
    'selectedURL' => false,
    'replaces' => array(),
    // template makes more sense, because we can pass through the wishes
    // of the template designers and the coders shouldn't be inserting
    // post/pre stuff
    //'prelink' => '',
    //'postlink' => '',
    'prelabel' => '',
    'postlabel'  => '',
    'baseClasses' => array(),
    // or we could just let something outside of this handle it...
    'listClass' => false,
    'targets' => false, // can be a string or array of specific targets
    // label => id
    'ids' => array(),
    // {{tags}}: url, label, classes, id
    'template' => false,
    'selected_template' => false,
  ), $options));

  // backwards compat
  if ($list === false) $type = 'none';
  // control the style in the template
  if ($template) {
    $type = 'none';
  }

  $nav_html = '';
  // maybe a look up is better?
  $listClassAdd = '';
  if ($listClass) {
    $listClassAdd = ' class="' . $listClass. '"';
  }
  switch($type) {
    case 'none':
    break;
    case 'nav':
      $nav_html = '<nav' . $listClassAdd . '>';
    break;
    case 'list':
      $nav_html = '<ul' . $listClassAdd . '>';
    break;
    /*
    case 'nav':
      $nav_html = '<nav class="navbar">';
    break;
    */
  }
  // maybe target option?
  foreach($navItems as $data) {
    $label = empty($data['label']) ? '' : $data['label'];
    if (empty($data['html_override'])) {
      $alt = empty($data['alt']) ? '' : ' aria-label="' . $data['alt'] . '"';
      // dev error if $data['destinations'] isn't set...
      if (isset($data['destinations'])) {
        $urlTemplate = $data['destinations'];
        if (is_array($urlTemplate)) {
          // always link to first one
          $url = replace_tags($urlTemplate[0], $replaces);
          if ($selectedURL !== false) {
            // maybe like pageURLs?
            $checkUrl = array();
            foreach($urlTemplate as $tmpl) {
              $checkUrl[] = replace_tags($tmpl, $replaces);
            }
          //} else {
            //$checkUrl = $url;
          }
        } else {
          $url = replace_tags($urlTemplate, $replaces);
          $checkUrl = $url;
        }
      } else {
        $checkUrl = '';
        $url = '';
      }
    }
    switch($type) {
      case 'none':
      //case 'nav':
      break;
      case 'list':
        $nav_html .= '<li>';
      break;
    }
    $classes = $baseClasses;
    // is it selected?
    $itemSelected = false;
    if ($selectedURL !== false) {
      if (is_array($checkUrl)) {
        if (in_array($selectedURL, $checkUrl)) {
          $itemSelected = true;
        }
      } else {
        if ($selectedURL === $url) {
          $itemSelected = true;
        } else
        if ($selectedURL === '' && $url === '.') {
          $itemSelected = true;
        }
      }
    }
    if ($selected === $label) {
      $itemSelected = true;
    }
    //
    $use_template = $template;
    if ($itemSelected) {
      if ($selected_template) {
        $use_template = $selected_template;
      } else {
        // default mode...
        $classes['active'] = 'active'; // was bold
      }
    }
    $id = isset($ids[$label]) ? ' id="' . $ids[$label] . '"' : '';

    if ($use_template) {
      $class = count($classes) ? ' ' . join(' ', $classes) : '';
      $tags = array(
        'id'  => $id,
        'url' => $url,
        'alt' => $alt,
        'label'   => $prelabel . $label . $postlabel,
        'classes' => $class,
      );
      $nav_html .= replace_tags($use_template, $tags);
    } else {
      $class = count($classes) ? ' class="' . join(' ', $classes) . '"' : '';

      if (!empty($data['html_override'])) {
        $nav_html .= $data['html_override'];
      } else {
        // $prelink . $postlink
        $linkAdd = '';
        if ($targets) {
          if (is_array($targets)) {
            if (DEV_MODE) {
              echo "getnav2 - targets are an array - write me!<br>\n";
            }
          } else {
            $linkAdd .= ' target=_top';
          }
        }
        if (isset($data['destinations'])) {
          $nav_html .= '<a' . $class . $id . ' href="' . $url . '"' . $linkAdd . $alt . '>';
        }
        $nav_html .= $prelabel . $label . $postlabel;
        if (isset($data['destinations'])) {
          $nav_html .= '</a>' . "\n";
        }
      }
    }
    if (isset($data['subItems'])) {
      $nav_html .= getNav2($data['subItems'], $options);
    }
  }
  switch($type) {
    case 'none':
    break;
    case 'nav':
      $nav_html .= '</nav>';
    break;
    case 'list':
      $nav_html .= '</ul>';
    break;
    /*
    case 'nav':
      $nav_html .= '</nav>';
    break;
    */
  }
  return $nav_html;
}

function getNav($navItems, $options = array()) {
  $newFormat = array();
  foreach($navItems as $label => $urlTemplate) {
    $newFormat[] = array(
      'label' => $label,
      'destinations' => $urlTemplate,
    );
  }
  $nav_html = getNav2($newFormat, $options);
  return $nav_html;
}

/*
maybe components can be scoped to a specific portal
ie can't exist outside one...
*/

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
    'navItems2'  => array(),
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
  $nav_html = '';
  if (count($navItems2)) {
    $nav_html .= getNav2($navItems2, $navOptions);
  }
  if (count($navItems)) {
    // the concat seems to work for now
    // could just inject into navItems2...
    $nav_html .= getNav($navItems, $navOptions);
  }

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
