<?php
chdir('..');

include '../common/lib.loader.php';
ldr_require('../common/common.php'); // ensureOptions
ldr_require('../common/lib.http.server.php');
// we don't need routes...
$req_method = getServerField('REQUEST_METHOD', 'GET');

//define('BASE_HREF', '/pages');

include 'setup.php';
foreach($packages as $pkg) {
  $pkg->frontendPrepare();
}

global $BASE_HREF;
// it's always auto-detected correctly
// only if it's manually hardcoded when it's wrong...
// well we should normalize
// we need BASE_HREF to be where the frontend webroot is...
$BASE_HREF = preg_replace('~pages/$~', '', $BASE_HREF);
//echo "BASE_HREF[$BASE_HREF]<br>\n";

// we need css path
// nad the base tag to be different...

global $_HeaderData;
if (!$_HeaderData) $_HeaderData = wrapContentData(array());
wrapContentHeader($_HeaderData);
// make sure first lines of output are see-able
sendBump('GET', 'pages/'); // deal with template's static nav

chdir(__DIR__);
?>