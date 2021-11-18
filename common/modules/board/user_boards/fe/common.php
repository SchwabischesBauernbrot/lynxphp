<?php

function getCreateBoardForm() {
  $formFields = array(
    'uri' => array('type' => 'text', 'label' => 'Board URI'),
    'title' => array('type' => 'text', 'label' => 'Board title'),
    'description' => array('type' => 'textarea', 'label' => 'Board description'),
  );
  // FIXME: pipeline
  // FIXME get named route
  global $BASE_HREF;
  return simpleForm($BASE_HREF . 'create_board.php', $formFields, 'Create board');
}

return array();

?>