<?php

// https://a.4cdn.org/boards.json
$boards = listBoards();
if (getQueryField('prettyPrint')) {
  echo '<pre>', json_encode($boards, JSON_PRETTY_PRINT), "</pre>\n";
} else {
  echo json_encode($boards);
}
