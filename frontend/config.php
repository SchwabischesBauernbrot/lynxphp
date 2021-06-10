<?php

// should index set the defaults?

// local server config
// has to be first if we use defines...
$HTTP_HOST = getServerField('HTTP_HOST', getServerField('SERVER_NAME'));
$localConfig = 'config_' . $HTTP_HOST . '.php';
if (file_exists($localConfig)) {
  include($localConfig);
} else {
  echo "Local frontend config file [$localConfig] not found in ", getcwd(), "<br>\n";
}

// site wide config
// most of this should be in the db or on the backend

//
// defaults
//

// backend_url
// needs to be HTTPS if the backend is not on the same server
// must have trailing slash
if (!defined('BACKEND_BASE_URL')) define('BACKEND_BASE_URL', 'http://localhost/backend/');
if (!defined('BACKEND_PUBLIC_URL')) define('BACKEND_PUBLIC_URL', 'https://' . $HTTP_HOST . '/backend/');

// what request path is the site design to run under
// cannot include protocol
if (!defined('BASE_HREF')) {
  // nginx: /index.php
  // maybe a different var would be better...
  // REQUEST_URI (but what does this look like in not /)
  // without protocol
  // and when we run in CLI mode?
  define('BASE_HREF', rtrim(dirname(getServerField('SCRIPT_NAME', __FILE__)), '/') . '/');
}

// includes :PORT if needed
if (!defined('BASE_HOST')) define('BASE_HOST', getServerField('HTTP_HOST'));
// BASE_PATH is basically BASE_HREF
if (!defined('DEV_MODE')) define('DEV_MODE', false);
if (!defined('SCRATCH_DRIVER')) define('SCRATCH_DRIVER', 'auto');
if (!defined('FILE_SCRATCH_DIRECTORY')) define('FILE_SCRATCH_DIRECTORY', '../frontend_storage/');

if (!defined('REDIS_HOST')) define('REDIS_HOST', 'localhost');
if (!defined('REDIS_PORT')) define('REDIS_PORT', '127.0.0.1');
if (!defined('REDIS_SOCKET')) define('REDIS_SOCKET', '/tmp/redis.sock');
if (!defined('REDIS_FORCE_HOST')) define('REDIS_FORCE_HOST', false);
if (!defined('IN_TEST')) define('IN_TEST', false);
if (!defined('IN_GENERATE')) define('IN_GENERATE', false);
if (!defined('USER')) define('USER', 'www-data'); // debian

?>
