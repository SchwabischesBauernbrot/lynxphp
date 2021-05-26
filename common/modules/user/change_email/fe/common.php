<?php

function getChangeEmailForm() {
  // set up form
  $formFields = array(
    'email' => array('type' => 'email', 'label' => 'Recovery Email (we suggest using a burner/temp one)'),
  );
  global $BASE_HREF;
  // FIXME: pipeline
  // FIXME get named route
  return simpleForm($BASE_HREF . 'account/change_email', $formFields, 'Change recovery email');
}

return array();

?>