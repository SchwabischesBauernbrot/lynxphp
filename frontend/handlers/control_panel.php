<?php

function getControlPanel() {
  $boards = getBoards();
  print_r($boards);
  $tmpl = file_get_contents('templates/accounts.tmpl');
  $boards_html = '';
  foreach($boards as $board) {
    $boards_html .= '<a href="' . $board['uri'] . '">' . $board['uri'] . '</a><br>' . "\n";
  }
  $tmpl .= $boards_html;
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
  $data = curlHelper(BACKEND_BASE_URL . 'lynx/createBoard', array(
    'boardUri'         => $_POST['uri'],
    'boardName'        => $_POST['title'],
    'boardDescription' => $_POST['description'],
    // captcha?
  ), array('sid' => $_COOKIE['session']));
  echo $data;
}

?>
