<?php

// local server config
// has to be first if we use defines...
$localConfig = 'config_' . getServerField('HTTP_HOST') . '.php';
if (file_exists($localConfig)) {
  include($localConfig);
} else {
  echo "Local backend config file [$localConfig] not found in ", getcwd(), "<br>\n";
}

// site wide config
if (!defined('BACKEND_KEY') || BACKEND_KEY === '') {
  echo json_encode(array(
    'err' => 'BACKEND_KEY',
    'message' => 'BACKEND_KEY is not set in the backend configuration. Used to hash things in the database, needs to be set before users are created!')
  );
  exit;
}

// CORS configuration

//
// defaults
//

if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PWD')) define('DB_PWD', '');
if (!defined('DB_NAME')) define('DB_NAME', 'lynxphp');
if (!defined('DB_DRIVER')) define('DB_DRIVER', 'mysql');
if (!defined('IN_TEST')) define('IN_TEST', false);
// scratch file would need this
if (!defined('USER')) define('USER', 'www-data'); // debian
if (!defined('IN_GENERATE')) define('IN_GENERATE', false);

?>
