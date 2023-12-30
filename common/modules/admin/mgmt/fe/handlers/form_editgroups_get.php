<?php

$params = $getHandler();

// ensure admin...

// get a list of users from backend
$users = $pkg->useResource('list');
$userid = $request['params']['id'];
if (!$userid) {
  return wrapContent($adminPortalHdr . 'please pass in an userid');
}
$users = array_filter($users, function($u) use ($userid) {
  return $userid === $u['userid'];
});
if (!$users) {
  return wrapContent($adminPortalHdr . 'userid not found');
}
$keys = array_keys($users);
$user = $users[$keys[0]];

// userid, username, email, created_at, updated_at
// get a list of available groups...
$groups = $pkg->useResource('listgroups');

$tmpl = '<form action="' . $params['action'] . '" method="POST">';
$tmpl .= '<input type=hidden name=userid value="' . $user['userid'] . '">';
$tmpl .= 'Public Key: ' . $user['publickey'] . "<br>\n";
$tmpl .= 'Groups:<ul>';
//echo "<pre>[", print_r($user['groupnames'], 1), "]</pre>\n";
// most users are without groups and are null
if ($user['groupnames'] === null) $user['groupnames'] = '';
$user_groups = explode(',', $user['groupnames']);
foreach($groups as $g) {
  $value = in_array($g['name'], $user_groups) ? ' CHECKED' : '';
  $tmpl .= '<li><label><input type=checkbox name=groups[] value="'.$g['groupid'].'"' . $value . '> ' . $g['name'] . ' </label>';
}
$tmpl .= '</ul>';
$tmpl .= '<input type=submit value=save>';
$tmpl .= '</form>';

wrapContent($tmpl);

?>
