<?php

$host = getenv('USE_CONFIG');
// argument overrides environment
if (isset($GLOBALS['argv'][3])) {
  $host = $GLOBALS['argv'][3];
}

function deleteBoard($boardUri) {
  global $db, $models;

  // delete files table
  $fm = getPostFilesModel($boardUri);
  $db->delete($fm, array());

  // delete posts table
  $pm = getPostsModel($boardUri);
  $db->delete($pm, array());

  // delete board row
  $db->delete($models['board'], array('criteria'=>array('uri' => $boardUri)));
}

if ($host) {
  $_SERVER['HTTP_HOST'] = $host;
}

chdir('frontend');
include '../common/post_vars.php';
// how do we get the correct server name?
// there is only one config on the frontend side...
include 'config.php';
include 'lib/lib.http.php';
include 'lib/lib.backend.php';
chdir('..');

function wrapContent($content) {
  echo "wrapContent called[$content]\n";
}

function usesSendResponse($t, $res) {
  $t->assertIsArray($res);
  $t->assertArrayHasKey('meta', $res);
  $t->assertIsArray($res['meta']);
  $t->assertArrayHasKey('code', $res['meta']);
  $t->assertArrayHasKey('data', $res);
  $t->assertIsArray($res['data']);
}
