<?php
require '../common/lib.loader.php'; // lib.http needs ldr_require
ldr_require('../common/lib.http.server.php'); // config needs getServerField
include 'config.php'; // get BACKEND_PUBLIC_URL
$path = BACKEND_PUBLIC_URL . 'storage/site/logo.png';
header('Content-type: image/png');
readfile($path);

?>