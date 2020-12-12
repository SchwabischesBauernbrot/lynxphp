<?php

function getControlPanel() {
  $check = checkSession();
  if (isset($check['meta']) && $check['meta']['code'] === 401) {
    redirectTo(BASE_HREF . 'login.php');
    return;
  }
  $account = backendLynxAccount();
  //print_r($account);
  $templates = loadTemplates('account');
  $tmpl = $templates['header'];
  $board_html = $templates['loop0'];

  $boards_html = '';
  foreach($account['ownedBoards'] as $board) {
    $tmp = $board_html;
    $tmp = str_replace('{{uri}}', $board['uri'], $tmp);
    $boards_html .= $tmp;
  }
  $tmpl = str_replace('{{ownedBoards}}', $boards_html, $tmpl);
  wrapContent($tmpl);
}

function getCreateBoard() {

  $content = <<< EOB
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
  wrapContent($content);
}

function postCreateBoard() {
  $result = backendCreateBoard();
  //print_r($result);
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
  $tmpl = "Error: Sign up error: " . $result['meta']['err'] . "<br>\n";
  wrapContent($tmpl . getSignupForm());
}

?>