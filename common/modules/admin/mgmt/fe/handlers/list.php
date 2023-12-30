<?php

// FIXME: we need access to package
$params = $getHandler();

if (isset($_POST['publickey']) || isset($_POST['email'])) {
  //echo "Searching<br>\n";
  $users = $pkg->useResource('list_search', array(
    'publickey' => getOptionalPostField('publickey'),
    'email'     => getOptionalPostField('email'),
  ));
  //echo "users[", gettype($users), "][", print_r($users, 1), "]<br>\n";
} else {
  // get a list of users from backend
  $users = $pkg->useResource('list');
}
//print_r($users);
// userid, username, email, created_at, updated_at

//$adminPortalHdr = renderAdminPortal();

$templates = moduleLoadTemplates('listing', __DIR__);

// FIXME: include board header...
// FIXME: include paged board nav...

$header = $templates['header'];
$user_tmpl = $templates['loop1'];
$tmpl = str_replace('{{users}}', $header, $templates['loop2']);
// add link
// list
$users_html = '';
$formFields = array();
if (is_array($users)) {
  foreach($users as $user) {
    if ($user['groupnames'] === null) $user['groupnames'] = '';
    $tmp = $user_tmpl;
    $tmp = str_replace('{{id}}',         $user['userid'],     $tmp);
    $tmp = str_replace('{{publickey}}',  $user['publickey'] ? $user['publickey'] : 'not migrated yet',  $tmp);
    $tmp = str_replace('{{groups}}',     $user['groupnames'], $tmp);
    $tmp = str_replace('{{created_at}}', $user['created_at'], $tmp);
    $tmp = str_replace('{{updated_at}}', $user['updated_at'], $tmp);
    $users_html .= $tmp;
  }
  $formFields = array(
    'publickey' => array('type' => 'text', 'label' => 'Public Key'),
    'email'     => array('type' => 'text', 'label' => 'Recovery Email'),
  );
}

$tags = array(
  'searchForm' => simpleForm('admin/users', $formFields, 'search'),
  'users' => $users_html,
);
// $adminPortalHdr .
wrapContent(replace_tags($tmpl, $tags));

?>
