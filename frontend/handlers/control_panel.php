<?php

function getControlPanel() {
  $account = backendLynxAccount();
  // you only get meta if error
  if (!$account || (!empty($account['meta']) && $account['meta']['code'] === 401)) {
    // FIXME get named route
    redirectTo(BASE_HREF . 'forms/login');
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
      $admin_html = str_replace('{{admin}}', BASE_HREF . 'admin', $admin_tmpl);
    }
    if ($isGlobal) {
      $global_html = str_replace('{{global}}', BASE_HREF . 'global', $global_tmpl);
    }
  }

  $boards_html = '';
  if (isset($account['ownedBoards']) && is_array($account['ownedBoards'])) {
    foreach($account['ownedBoards'] as $board) {
      $tmp = $board_html;
      $tmp = str_replace('{{uri}}', $board['uri'], $tmp);
      $boards_html .= $tmp;
    }
  }
  $tags = array(
    'login' => $account['login'],
    // FIXME get named route
    'account' => BASE_HREF . 'account',
    'create_board' => BASE_HREF . 'create_board',
    'logout' => BASE_HREF . 'logout',
    'admin' => $isAdmin ? $admin_html : '',
    'global' => $isGlobal ? $global_html : '',
    'ownedBoards' => $boards_html,
  );
  wrapContent(replace_tags($tmpl, $tags));
}

function getCreateBoardForm() {
  $formFields = array(
    'uri' => array('type' => 'text', 'label' => 'Board URI'),
    'title' => array('type' => 'text', 'label' => 'Board title'),
    'description' => array('type' => 'textarea', 'label' => 'Board description'),
  );
  // FIXME: pipeline
  // FIXME get named route
  return simpleForm(BASE_HREF . 'create_board', $formFields, 'Create board');
}

function getCreateBoard() {
  wrapContent(getCreateBoardForm());
}

function postCreateBoard() {
  $result = backendCreateBoard();
  if ($result['data'] === 'ok') {
    // maybe not display this?
    //wrapContent('Board created!');
    // FIXME get named route
    redirectTo(BASE_HREF . 'control_panel');
    /*
    $uri = $_POST['uri'];
    redirectTo($uri . '/settings');
    */
    return;
  }
  $tmpl = "Error: Board creation error: " . $result['meta']['err'] . "<br>\n";
  wrapContent($tmpl . getCreateBoardForm());
}

function getAccountPortalNav() {
  // FIXME get named route
  $navItems = array(
    'Change username/password' => BASE_HREF . 'account/change_userpass',
    'Change recovery email' => BASE_HREF . 'account/change_email',
  );
  // FIXME: pipeline
  return getNav2($navItems);
}

function getAccountPortal($options = false) {
  return array(
    'header' => '' . getAccountPortalNav(),
    'footer' => ''
  );
}

function getChangeUserPassForm() {
  // set up form
  $formFields = array(
    'username' => array('type' => 'text', 'label' => 'New Username'),
    'password' => array('type' => 'password', 'label' => 'New Password (Minimum 16 chars, we recommend using a pass phrase)'),
  );
  // FIXME: pipeline
  // FIXME get named route
  return simpleForm(BASE_HREF . 'account/change_userpass', $formFields, 'Migrate account');
}

function getChangeEmailForm() {
  // set up form
  $formFields = array(
    'email' => array('type' => 'email', 'label' => 'Recovery Email (we suggest using a burner/temp one)'),
  );
  // FIXME: pipeline
  // FIXME get named route
  return simpleForm(BASE_HREF . 'account/change_email', $formFields, 'Change recovery email');
}

function getChangeUserPass() {
  $accountPortal = getAccountPortal();
  wrapContent($accountPortal['header'] . getChangeUserPassForm() . $accountPortal['footer']);
}

function postChangeUserPass() {
  $eKp = getEdKeypair($user, $pass);
  $res = backendMigrateAccount($eKp['pk']);
  if (!empty($res['data'])) {
    // FIXME get named route
    redirectTo(BASE_HREF . 'account?message=' . urlencode('Account migrated'));
  } else {
    wrapContent('Error: ' . print_r($res) . getChangeEmailForm());
  }
}

function getChangeEmail() {
  $accountPortal = getAccountPortal();
  wrapContent($accountPortal['header'] . getChangeEmailForm() . $accountPortal['footer']);
}

function postChangeEmail() {
  $res = backendChangeEmail($_POST['email']);
  if (!empty($res['data'])) {
    // FIXME get named route
    redirectTo(BASE_HREF . 'account?message=' . urlencode('Recovery email changed'));
  } else {
    wrapContent('Error: ' . print_r($res) .  getChangeEmailForm());
  }
}

function getAccountSettings() {
  $msg = getQueryField('message');
  wrapContent($msg . getAccountPortalNav());
}

?>