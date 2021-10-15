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
  if (isset($account['ownedBoards']) && is_array($account['ownedBoards'])) {
    foreach($account['ownedBoards'] as $board) {
      $tmp = $board_html;
      // ['uri'] lynxchan just lists the names, if you need this use an /opt
      $tmp = str_replace('{{uri}}', $board, $tmp);
      $boards_html .= $tmp;
    }
  }
  $tags = array(
    'login' => $account['login'],
    // FIXME get named route
    'account' => $BASE_HREF . 'account.php',
    'create_board' => $BASE_HREF . 'create_board.html',
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