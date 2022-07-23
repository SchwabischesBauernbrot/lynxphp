<?php

// FIXME: we need access to package
$params = $getHandler();

/*
if (isset($_POST['publickey']) || isset($_POST['email'])) {
  //echo "Searching<br>\n";
  $users = $pkg->useResource('list_search', array(
    'publickey' => getOptionalPostField('publickey'),
    'email'     => getOptionalPostField('email'),
  ));
  //echo "users[", gettype($users), "][", print_r($users, 1), "]<br>\n";
} else {
*/
  // get a list of users from backend
  $boards = $pkg->useResource('boards_list');
//}
//print_r($boards);
// boardid, uri, owner_id, title, description, json, created_at, updated_at
// posts, last_thread, last_post

$adminPortalHdr = renderAdminPortal();

$templates = moduleLoadTemplates('boards_listing', __DIR__);

//echo "<pre>", htmlspecialchars(print_r($templates, 1)), "</pre>\n";

// FIXME: include board header...
// FIXME: include paged board nav...

$header = $templates['header'];
$board_html = $templates['loop0'];
$footer = $templates['loop1'];
// put loop1 into header
//$tmpl = str_replace('{{boards}}', $header, $templates['loop1']);
// add link
// list
$boards_html = '';
$formFields = array();
if (is_array($boards)) {
  foreach($boards as $b) {
    // what do we need to filter out?
    // we should just filter on the backend
    // we need to transform
    $tags = array(
      'id' => $b['boardid'],
      'uri' => $b['uri'],
      'title' => $b['title'],
      // this might be not set...
      'posts' => empty($b['posts']) ? array() : $b['posts'],
      'created_at' => date('Y-m-d H:i:s', $b['created_at']),
      'updated_at' => date('Y-m-d H:i:s', $b['updated_at']),
    );
    $boards_html .= replace_tags($board_html, $tags);
  }
  $formFields = array(
    'uri' => array('type' => 'text', 'label' => 'URI'),
    'email'     => array('type' => 'text', 'label' => 'Recovery Email'),
  );
}

$tags = array(
  'searchForm' => '', //simpleForm('admin/boards', $formFields, 'search'),
  'boards' => $boards_html,
);
// no footer?
wrapContent($adminPortalHdr . replace_tags($header, $tags) . $footer);

?>
