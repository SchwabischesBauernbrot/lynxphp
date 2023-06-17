<?php

$params = $getHandler();

// ensure admin...

// get a list of boards from backend
$boards = $pkg->useResource('boards_list');
$boardid = $request['params']['id'];
if (!$boardid) {
  return wrapContent($adminPortalHdr . 'invalid boardid');
}
$boards = array_filter($boards, function($u) use ($boardid) {
  return $boardid === $u['boardid'];
});
if (!$boards) {
  return wrapContent($adminPortalHdr . 'userid not found');
}
$keys = array_keys($boards);
$board = $boards[$keys[0]];
//print_r($users);

// userid, username, email, created_at, updated_at
// get a list of available groups...
//$groups = $pkg->useResource('listgroups');

//print_r($board);

$tmpl = '<form action="' . $params['action'] . '" method="POST">';
$tmpl .= '<input type=hidden name=userid value="' . $board['boardid'] . '">';
$tmpl .= 'URI: ' . $board['uri'] . "<br>\n";
$tmpl .= 'Created: ' . date('Y-m-d H:i:s', $board['created_at']) . "<br>\n";
//$tmpl .= 'Public key: ' . $board['publickey'] . "<br>\n";
//$tmpl .= 'Groups: ' . $board['groupnames'] . "<br>\n";
/*
$tmpl .= 'Groups:<ul>';
$user_groups = explode(',', $user['groupnames']);
foreach($groups as $g) {
  $value = in_array($g['name'], $user_groups) ? ' CHECKED' : '';
  $tmpl .= '<li><label><input type=checkbox name=groups[] value="'.$g['groupid'].'"' . $value . '> ' . $g['name'] . ' </label>';
}
$tmpl .= '</ul>';
*/
$tmpl .= '<input type=submit value=delete>';
$tmpl .= '</form>';

wrapContent($tmpl);

?>
