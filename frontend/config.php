<?php

// should index set the defaults?

// local server config
// has to be first if we use defines...
$localConfig = 'config_' . getServerField('HTTP_HOST', getServerField('SERVER_NAME')) . '.php';
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

// what request path is the site design to run under
// cannot include protocol
if (!defined('BASE_HREF')) {
  // nginx: /index.php
  // maybe a different var would be better...
  // REQUEST_URI (but what does this look like in not /)
  // without protocol
  define('BASE_HREF', rtrim(dirname(getServerField('SCRIPT_NAME', __FILE__)), '/') . '/');
}

// includes :PORT if needed
if (!defined('BASE_HOST')) { define('BASE_HOST', getServerField('HTTP_HOST')); }
// BASE_PATH is basically BASE_HREF
if (!defined('DEV_MODE')) { define('DEV_MODE', false); }
?>
