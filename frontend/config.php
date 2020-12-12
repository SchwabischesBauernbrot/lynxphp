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


// local server config

// backend_url
// needs to be HTTPS if the backend is not on the same server
// must have trailing slash
if (!defined('BACKEND_BASE_URL')) define('BACKEND_BASE_URL', 'http://localhost/backend/');

// what request path is the site design to run under
if (!defined('BASE_HREF')) define('BASE_HREF', '/');
?>
