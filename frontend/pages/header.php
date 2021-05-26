<?php
chdir('..');
include '../common/post_vars.php';
// we don't need routes...
$req_method = getServerField('REQUEST_METHOD', 'GET');

//define('BASE_HREF', '/pages');

include 'setup.php';

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
chdir(__DIR__);
// make sure first lines of output are see-able
echo '<div style="height: 40px;"></div>', "\n";
?>