<?php

// local server config
// has to be first if we use defines...
$localConfig = 'config_'.$_SERVER['SERVER_NAME'].'.php';
if (file_exists($localConfig)) {
  include($localConfig);
} else {
  echo "Local config file [$localConfig] not found<br>\n";
}

// site wide config

// CORS configuration

// defaults
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PWD')) define('DB_PWD', '');
if (!defined('DB_NAME')) define('DB_NAME', 'lynxphp');

?>
