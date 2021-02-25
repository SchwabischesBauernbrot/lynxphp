<?php

function getControlPanel() {
  $account = backendLynxAccount();
  // you only get meta if error
  if (!$account || (!empty($account['meta']) && $account['meta']['code'] === 401)) {
    redirectTo(BASE_HREF . 'login.php');
    return;
  }

  $templates = loadTemplates('account');
  $tmpl = $templates['header'];
  $board_html = $templates['loop0'];
  $admin_html = $templates['loop1'];
  $global_html = $templates['loop2'];

  $isAdmin = false;
  $isGlobal = false;
  if (isset($account['groups'])) {
    $isAdmin = in_array('admin', $account['groups']);
    $isGlobal = in_array('global', $account['groups']);
  }
  $tmpl = str_replace('{{admin}}', $isAdmin ? $admin_html : '', $tmpl);
  $tmpl = str_replace('{{global}}', $isGlobal ? $global_html : '', $tmpl);

  $boards_html = '';
  if (isset($account['ownedBoards']) && is_array($account['ownedBoards'])) {
    foreach($account['ownedBoards'] as $board) {
      $tmp = $board_html;
      $tmp = str_replace('{{uri}}', $board['uri'], $tmp);
      $boards_html .= $tmp;
    }
  }
  $tmpl = str_replace('{{ownedBoards}}', $boards_html, $tmpl);
  wrapContent($tmpl);
}

function getCreateBoardFrom() {
  return <<< EOB
<form action="create_board.php" method="POST">
  <dl>
    <dt>URI
    <dd><input type=text name="uri" placeholder="Board URI">
    <dt>Title
    <dd><input type=text name="title" placeholder="Board title">
    <dt>Description
    <dd><textarea name="description" placeholder="Board description"></textarea>
  <input type=submit value="create">
</form>
EOB;
}

function getCreateBoard() {
  wrapContent(getCreateBoardFrom());
}

function postCreateBoard() {
  $result = backendCreateBoard();
  if ($result['data'] === 'ok') {
    // maybe not display this?
    //wrapContent('Board created!');
    redirectTo('control_panel.php');
    /*
    $uri = $_POST['uri'];
    redirectTo($uri . '/settings');
    */
    return;
  }
  $tmpl = "Error: Board creation error: " . $result['meta']['err'] . "<br>\n";
  wrapContent($tmpl . getCreateBoardFrom());
}

?>
