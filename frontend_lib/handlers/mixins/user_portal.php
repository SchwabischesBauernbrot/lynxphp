<?php

function renderUserPortalHeader() {
  $test = substr($_SERVER['REQUEST_URI'], 1);
  $portalOptions = array(
    'headerPipeline' => PIPELINE_USER_HEADER_TMPL,
    'navPipeline'    => PIPELINE_USER_NAV,
    'navItems' => array(
      'general' => 'user/settings/general.html',
      'theme' => 'user/settings/theme.html',
    ),
  );
  if ($test === 'user/settings') {
    $portalOptions['useNavFirstItem'] = true;
  }
  return renderPortalHeader('user', $portalOptions);
}

// was happy in control_panel
// but now modules need to be able to call it too
function getAccountPortalNav() {
  global $BASE_HREF;
  // FIXME get named route
  $navItems = array(
    'Change username/password' => $BASE_HREF . 'account/change_userpass',
    'Change recovery email' => $BASE_HREF . 'account/change_email',
  );
  // FIXME: pipeline
  return getNav($navItems);
}

function getAccountPortal($options = false) {
  return array(
    'header' => '' . getAccountPortalNav(),
    'footer' => ''
  );
}

?>
