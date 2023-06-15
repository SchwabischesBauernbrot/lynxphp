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
if (!defined('DB_NAME')) define('DB_NAME', 'doubleplus');
if (!defined('DB_DRIVER')) define('DB_DRIVER', 'mysql');
if (!defined('IN_TEST')) define('IN_TEST', false);
if (!defined('DISABLE_MODULES')) define('DISABLE_MODULES', array());

if (!defined('SCRATCH_DRIVER')) define('SCRATCH_DRIVER', 'db');
if (!defined('QUEUE_DRIVER')) define('QUEUE_DRIVER', 'db');

if (!defined('FRONTEND_BASE_URL')) define('FRONTEND_BASE_URL', 'https://' . getServerField('HTTP_HOST') . '/');

// scratch file would need this
if (!defined('USER')) define('USER', 'www-data'); // debian
if (!defined('IN_GENERATE')) define('IN_GENERATE', false);

// archive module would like to know our BASE_HOST and BASE_HREF

// only frontend needs to check this...
//define('BACKEND', true); // need to set this on the FE too
// we do this because the front may include us for a reason maybe?
if (!defined('BACKEND_HEAD_SUPPORT')) define('BACKEND_HEAD_SUPPORT', false);
?>
