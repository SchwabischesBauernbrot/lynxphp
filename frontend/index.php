<?php
require '../common/lib.loader.php';
ldr_require('../common/common.php');
ldr_require('../common/lib.http.server.php');
ldr_require('../frontend_lib/lib/lib.handler.php'); // sendBump() and output functions

// REQUEST_URI seems to be more accruate in NGINX
$req_path   = getServerField('PATH_INFO', getServerField('REQUEST_URI'));
$req_method = getServerField('REQUEST_METHOD', 'GET');

require 'setup.php';
require 'setup.router.php';
ldr_done(); // free memory

if ($router->sendHeaders($req_method, $req_path)) {
  return;
}

// this lives in lib.handler
sendBump($req_method, $req_path); // deal with template's static nav

// work around nginx weirdness with PHP and querystrings
ensureQuerystring();

$res = $router->exec($req_method, $req_path);
// work is called in wrapContent
if (!$res) {
  http_response_code(404);
  echo "404 Page not found<br>\n";
  if (DEV_MODE) {
    echo "method[$req_method] path[$req_path]<br>\n";
    //echo "<pre>method routes:", print_r($router->methods[$req_method], 1), "</pre>\n";
    /*
    foreach($router->methods[$req_method] as $r => $f) {
      echo "$r<br>\n";
    }
    */
    $debug = $router->debug($req_method);
    echo "<pre>", print_r($debug, 1), "</pre>\n";
  }
}
?>