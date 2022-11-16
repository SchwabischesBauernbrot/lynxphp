<?php

function getControlPanel() {
  global $BASE_HREF;
  $account = backendLynxAccount();
  //echo "<pre>", print_r($account, 1), "</pre>\n";
  // you only get meta if error
  if (!$account || (!empty($account['meta']) && $account['meta']['code'] === 401)) {
    // FIXME get named route
    redirectTo($BASE_HREF . 'forms/login.html');
    return;
  }

  $templates = loadTemplates('account');
  $tmpl = $templates['header'];
  $board_html = $templates['loop0'];
  $admin_tmpl = $templates['loop1'];
  $global_tmpl = $templates['loop2'];
  $link_tmpl = $templates['loop3'];

  $isAdmin = false;
  $isGlobal = false;
  $admin_html = '';
  $global_html = '';
  if (isset($account['groups'])) {
    $isAdmin = in_array('admin', $account['groups']);
    $isGlobal = in_array('global', $account['groups']);
    // FIXME get named route
    if ($isAdmin) {
      $admin_html = str_replace('{{admin}}', $BASE_HREF . 'admin.php', $admin_tmpl);
    }
    if ($isGlobal) {
      $global_html = str_replace('{{global}}', $BASE_HREF . 'global.php', $global_tmpl);
    }
  }

  $boards_html = '';
  global $pipelines;
  if (isset($account['ownedBoards']) && is_array($account['ownedBoards'])) {
    foreach($account['ownedBoards'] as $board) {
      $tmp = $board_html;

      $boardUri = $board;
      $board_actions = action_getLevels();
      // we need to unify these defaults...
      $board_actions['all'][] = array('link' => '/' . $boardUri . '/', 'label' => 'View');
      $board_actions['bo'][] = array('link' => '/' . $boardUri . '/board_settings.php', 'label' => 'Settings');

      $action_io = array(
        'boardUri' => $boardUri,
        'b' => array(),
        'actions'  => $board_actions,
      );
      $pipelines[PIPELINE_BOARD_ACTIONS]->execute($action_io);
      // remap output over the top of the input
      $board_actions = $action_io['actions'];
      // FIXME: expander?
      $board_actions_html = action_getHtml($board_actions, array(
        'boardUri' => $boardUri, 'where' => 'boards', 'join' => " | \n",
      ));

      // ['uri'] lynxchan just lists the names, if you need this use an /opt
      $tmp = str_replace('{{uri}}', $board, $tmp);
      $tmp = str_replace('{{actions}}', $board_actions_html, $tmp);
      $boards_html .= $tmp;
    }
  }

  $links_io = array(
    //'template' => $link_tmpl, // href, label
    //'html' => '',
    'navItems' => array(),
  );
  $pipelines[PIPELINE_ACCOUNT_NAV]->execute($links_io);
  // $other_links_html = $links_io['html'];
  $other_links_html = getNav2($links_io['navItems'], array(
    'template' => $link_tmpl,
  ));

  $tags = array(
    'login' => $account['login'],
    // FIXME get named route
    'account' => $BASE_HREF . 'account.php',
    //'create_board' => $BASE_HREF . 'create_board.html',
    'other_links' => $other_links_html,
    'logout' => $BASE_HREF . 'logout.php',
    'admin' => $isAdmin ? $admin_html : '',
    'global' => $isGlobal ? $global_html : '',
    'ownedBoards' => $boards_html,
  );
  wrapContent(replace_tags($tmpl, $tags));
}

function getAccountSettingsHandler() {
  $msg = getQueryField('message');
  wrapContent($msg . getAccountPortalNav());
}

?>