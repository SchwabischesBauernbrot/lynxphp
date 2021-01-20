<?php

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
