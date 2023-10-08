<?php

$params = $getHandler();

// drop down for menus

// list items for active menus

// get a list of users from backend
$pages = $pkg->useResource('list');

$menus = array();

//print_r($users);
// static URL, page, board, thread, post or ??

$html = '<table>';
$html .= '<tr><td>title<td>actions' . "\n";
foreach($menus as $p) {
  //print_r($p);
  // pageid, title, created_at, updated_at
  $html .= '<tr><td>' . $p['title'] .'<td><a href="admin/pages/' . $p['pageid'] . '/edit">edit</a> | <a href="admin/pages/' . $p['pageid'] . '/remove">remove</a>';
}
$html .= '</table>';

//$templates = moduleLoadTemplates('listing', __DIR__);

//$header = $templates['header'];
//$user_tmpl = $templates['loop1'];
//$tmpl = str_replace('{{users}}', $header, $templates['loop2']);

/*
$tags = array(
  'searchForm' => simpleForm('admin/users', $formFields, 'search'),
  'users' => $users_html,
);
*/

// replace_tags($tmpl, $tags)

// $adminPortalHdr .
wrapContent('
<h2>Edit Menu</h2>
' . $html);

?>
