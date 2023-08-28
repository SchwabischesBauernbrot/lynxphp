<?php

// action
//   link
//   label
//   includeWhere
function action_getLevels() {
  $actions = array(
    'all'    => array(),
    'user'   => array(),
    'bo'     => array(),
    'global' => array(),
    'admin'  => array(),
  );
  return $actions;
}

function action_redirectToWhere($options = false) {
  global $BASE_HREF;

  // get from querystring
  $where = getQueryField('from');
  // boardUri? could get it from options
  // decode where into link
  $link = urldecode($where);
  switch($where) {
    case 'boards':
      $link = 'boards.html';
    break;
  }

  // BASE_HREF has trailing slash
  redirectTo($BASE_HREF . $link);
}

function action_getLinkHTML($a, $options) {
  $link = $a['link'];
  if (!empty($a['includeWhere'])) {
    if (isset($options['where'])) {
      // is a separator needed
      $lastChar = $link[strlen($link) - 1];
      if ($lastChar !== '?' && $lastChar !== '&') {
        // which separator is apt
        $missingQ = strpos($link, '?') === false;
        $link .= $missingQ ? '?' : '&';
      }
      $link .= 'from=' . urlencode($options['where']);
    }
  }
  // but some actions we want them to be indexed
  // FIXME: make option to index the action...
  // or I wonder if we could query the router about this link...
  $index = 'rel=noindex ';
  if (!empty($a['index'])) {
    $index = '';
  }
  return '<a ' . $index . 'href="' . $link . '">' . $a['label'] . '</a>';
}

// decode permissions
function action_decodePerms($actions, $options = false) {
  // unpack options
  extract(ensureOptions(array(
    'boardUri' => false, // for BO permission context
  ), $options));

  $permitted = array();
  if (count($actions['all'])) {
    foreach($actions['all'] as &$a) {
      $permitted[] = $a;
    }
    unset($a);
  }
  if (count($actions['user']) && isLoggedIn()) {
    foreach($actions['user'] as &$a) {
      $permitted[] = $a;
    }
    unset($a);
  }
  if ($boardUri) {
    if (count($actions['bo']) && perms_isBO($boardUri)) {
      foreach($actions['bo'] as &$a) {
        $permitted[] = $a;
      }
      unset($a);
    }
  }
  if (perms_inGroups(array('global'))) {
    foreach($actions['global'] as &$a) {
      $permitted[] = $a;
    }
    unset($a);
  }
  if (perms_inGroups(array('admin'))) {
    foreach($actions['admin'] as &$a) {
      $permitted[] = $a;
    }
    unset($a);
  }
  return $permitted;
}

function action_permittedToHtml($permitted, $options = false) {
  // unpack join
  extract(ensureOptions(array(
    'join'     => '<br>' . "\n",
  ), $options));

  $actions_html_parts = array();
  foreach($permitted as &$a) {
    /*
    $post_actions_html_parts[] = '<a href="dynamic.php?boardUri=' . urlencode($boardUri) .
      '&action=' . urlencode($a). '&id=' . $p['no']. '">' . $l . '</a>';
    */
    //$actions_html_parts[] = '<a href="' . $a['link'] . '">' . $a['label'] . '</a>';
    $actions_html_parts[] = action_getLinkHTML($a, $options);
  }
  unset($a);

  return join($join, $actions_html_parts);
}

// used for multiple levels of access
/*
Options
- boardUri: for BO permission context
- join: how to join all the actions
- where: parameter to set if includeWhere is set on the link
*/
function action_getHtml($actions, $options = false) {
  $permitted = action_decodePerms($actions, $options);
  return action_permittedToHtml($permitted, $options);
}

// we should move the expander in here
// so we can hide if no actions
// but we have to deal with the css background color issue which is context based...

// the expand isn't always ideal because you can have more than one open at a time
// but js could fix that...
function action_getExpandHtml($actions, $options = false) {
  extract(ensureOptions(array(
    'label' => 'Actions',
    'float' => true,
    // boardUri used in action_decodePerms for BO checks
    // where used in action_getLinkHTML
    'nojs' => false,
  ), $options));
  $permitted = action_decodePerms($actions, $options);
  $cnt = count($permitted);
  if ($cnt) {
    if ($cnt === 1) {
      return action_permittedToHtml($permitted, $options);
    } else {
      $inner = action_permittedToHtml($permitted, $options);
      //
      /*
      <details style="display: inline;">
        <summary>...</summary>
        <div style="position: relative; z-index: 1; background-color: var(--post-color); padding: 5px;">
        {{actions}}
        </div>
      </details>
      */
      /*
      <details style="display: inline; position: relative;">
        <summary>Actions</summary>
        <nav class="doubleplus-actions">
          {{ actions }}
        </nav>
      </details>
      */
      // we can set an id or a class
      $classes = array('doubleplus-actions-dropdown');
      if ($float) {
        $classes []= 'float';
      } else {
        $classes []= 'non-float';
      }
      $wrap = '<nav class="doubleplus-actions">' .  $inner . '</nav>';
      return getExpander($label, $wrap, array('classes' => $classes, 'nojs' => $nojs));
    }
  }
}


?>
