<?php

function getBoards() {
  $json   = curlHelper(BACKEND_BASE_URL . '4chan/boards.json');
  $boards = json_decode($json, true);
  return $boards;
}

function getBoard($boardUri) {
  $json      = curlHelper(BACKEND_BASE_URL . '4chan/' . $boardUri . '.json');
  $boardData = json_decode($json, true);
  return $boardData;
}

function getBoardPage($boardUri, $page = 1) {
  $json  = curlHelper(BACKEND_BASE_URL . '4chan/' . $boardUri . '/' . $page . '.json');
  $page1 = json_decode($json, true);
  return $page1;
}

function getBoardThread($boardUri, $threadNum) {
  $json  = curlHelper(BACKEND_BASE_URL . '4chan/' . $boardUri . '/thread/' . $threadNum . '.json');
  $result = json_decode($json, true);
  return $result['posts'];
}

?>
