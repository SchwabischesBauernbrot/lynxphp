<?php
require '../common/lib.loader.php';
ldr_require('../common/lib.http.server.php');
ldr_require('../frontend_lib/lib/lib.handler.php'); // sendBump() and output functions

// anything not SEO related...

// REQUEST_URI seems to be more accruate in NGINX
$req_path   = getServerField('PATH_INFO', getServerField('REQUEST_URI'));
$req_method = getServerField('REQUEST_METHOD', 'GET');

/*
// we are routerless
if (isPostTooBig(getServerField('REQUEST_METHOD', 'GET'))) {
  echo '<div style="height: 40px;"></div>', "\n";
  echo "This POST request has sent too much data for this server, try sending less data.<br>\n";
  return;
}
*/

sendBump($req_method, $req_path); // deal with template's static nav

// work around nginx weirdness with PHP and querystrings
ensureQuerystring();

require 'setup.php';
require 'setup.router.php';
/*
foreach($packages as $pkg) {
  $pkg->frontendPrepare();
}
*/

$action = getQueryField('action');
$boardUri = getQueryField('boardUri');
$id = getQueryField('id');

echo "action[$action] [$req_path]<br>\n";

// how do we decode routes...
// map an action to a handler or form...
// we'd have to know the module and then the handler file...
// also we didn't do a router here... I guess we should...
$methods = $router->methods[$req_method];
foreach($methods as $cond => $func) {
  echo "cond[$cond]<br>\n";
}

?>