<?php

function getChangeUserPassForm() {
  // set up form
  $formFields = array(
    'username' => array('type' => 'text', 'label' => 'New Username'),
    'password' => array('type' => 'password', 'label' => 'New Password (Minimum 16 chars, we recommend using a pass phrase)'),
  );
  global $BASE_HREF;
  // FIXME: pipeline
  // FIXME get named route
  return simpleForm($BASE_HREF . 'account/change_userpass', $formFields, 'Migrate account');
}

return array();

?>