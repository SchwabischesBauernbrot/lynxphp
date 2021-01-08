<?php

// FIXME: we need access to package
$params = $getHandler();

// get a list of users from backend
$users = $pkg->useResource('list');
//print_r($users);
// userid, username, email, created_at, updated_at

$adminPortalHdr = renderAdminPortal();

$templates = moduleLoadTemplates('listing', __DIR__);

// FIXME: include board header...
// FIXME: include paged board nav...

$header = $templates['header'];
$user_tmpl = $templates['loop1'];
$tmpl = str_replace('{{users}}', $header, $templates['loop2']);
// add link
// list
$users_html = '';
foreach($users as $user) {
  $tmp = $user_tmpl;
  $tmp = str_replace('{{id}}',         $user['userid'],     $tmp);
  $tmp = str_replace('{{username}}',   $user['username'],   $tmp);
  $tmp = str_replace('{{email}}',      $user['email'],      $tmp);
  $tmp = str_replace('{{groups}}',     $user['groupnames'],     $tmp);
  $tmp = str_replace('{{created_at}}', $user['created_at'], $tmp);
  $tmp = str_replace('{{updated_at}}', $user['updated_at'], $tmp);
  $users_html .= $tmp;
}
//$tmpl = str_replace('{{uri}}', $boardUri, $tmpl);
$tmpl = str_replace('{{users}}', $users_html, $tmpl);
wrapContent($adminPortalHdr . $tmpl);

?>
