<?php

// are we installed?
// what's considered installed?

echo <<<EOB
<html>
<head>
  <link rel="stylesheet" href="css/style.css">
<body>
<h1>PHPLynx Installation Support</h1>
EOB;

// detect webserver
$isApache = strpos($_SERVER["SERVER_SOFTWARE"], 'Apache') !== false;
$isNginx = stripos($_SERVER["SERVER_SOFTWARE"], 'nginx') !== false;
$hasASupportedWebserver = $isApache || $isNginx;
// detect php version
$phpVer = phpversion();
// mysql
$hasMysql = function_exists('mysqli_connect');
$hasPgsql = function_exists('pg_connect');
$hasASupportedDB = $hasMysql || $hasPgsql;
// curl
$hasCurl = function_exists('curl_version');

$ok = true;

if (PHP_MAJOR_VERSION < 5) {
  echo "We require at least PHP 5.x<br>\n";
  $ok = false;
}

if (!$hasASupportedWebserver) {
  echo "You currently don't have a supported webserver.<br>\n";
  $ok = false;
}
if (!$hasASupportedDB) {
  echo "You currently don't have a supported database server driver installed (mysqli module).<br>\n";
  $ok = false;
}
if (!$hasCurl) {
  echo "You current don't have the PHP Curl extensions installed. This is required<br>\n";
  $ok = false;
}

if (!$ok) {
  echo "You must fix the previous items above to continue installations<br>\n";
  exit(1);
}
echo "Your PHP installation looks good<Br>\n";

// FIXME: mod_rewrite

if (!file_exists('../common') || !is_dir('../common')) {
  echo "Your common directory can not be found<br>\n";
  exit(1);
}
include '../common/post_vars.php';

// assuming ran by php/webserver
if (function_exists('posix_getpwuid')) {
  $arr = posix_getpwuid(posix_geteuid());
  //print_r($user);
  $user = $arr['name'];
} else {
  $user = getenv("username");
}

// detect settings override...
if (file_exists('backend') && is_dir('backend')) {
  // local backend tests

  // check backend override...
  $old = getcwd();
  chdir('backend/');
  include 'backend/config.php';

  $db_driver = DB_DRIVER;
  include 'backend/lib/database_drivers/'.$db_driver.'.php';
  $driver_name = DB_DRIVER . '_driver';
  $db = new $driver_name;

  if (!$db->connect_db(DB_HOST, DB_USER, DB_PWD, DB_NAME)) {
    echo "Can not connect to the configured database, did you make the database and grant access to the user configured?<br>\n";
    exit(1);
  }
  // uploads configuration
  if (!file_exists('storage') || !is_dir('storage') || !is_writeable('storage')) {
    echo "Backend's storage directory is not yet made OR not writeable by ", $user, ", please create<br>\n";
    exit(1);
  }

  chdir($old);

} else {
  echo "Does not look like you followed the default installation instructions all the way or have special backend configuration, skipping some tests<br>\n";
}

// remote backend tests

// backend detection
include 'config.php';
echo "Backend configured URL: ", BACKEND_BASE_URL, "<br>\n";
include 'lib/lib.http.php';
$json = curlHelper(BACKEND_BASE_URL);
$result = json_decode($json, true);
if ($result === false) {
  echo "Something seems to be wrong with your backend or backend URL<br>\n";
  echo "It not return JSON<br>\n";
  exit(1);
}
//echo "<pre>", htmlspecialchars($json), "</pre>\n";

// database connection
$json = curlHelper(BACKEND_BASE_URL . 'check');
$result = json_decode($json, true);
if ($result === false) {
  echo "Something seems to be wrong with your backend or backend URL /check<br>\n";
  echo "It not return JSON<br>\n";
  exit(1);
}

$json = curlHelper(BACKEND_BASE_URL . '/opt/boards.json');
$result = json_decode($json, true);
//echo "<pre>", print_r($result, 1), "</pre>";
echo '<ul>';
foreach($result['data']['boards'] as $b) {
  // threads, posts, udpated_at
  echo '<li>', $b['uri'], ' owned by ', $b['owner_id'], "\n";
  $path = 'backend/storage/boards/' . $b['uri'];
  if (!file_exists($path) || !is_dir($path) || !is_writeable($path)) {
    echo "Exist: ", file_exists($path) ? 'yes' : 'no', "<br>\n";
    echo "IsDirectory: ", is_dir($path) ? 'yes' : 'no', "<br>\n";
    echo "IsWritable: ", is_writeable($path) ? 'yes' : 'no', "<br>\n";
    echo $b['uri'] . "'s storage directory [$path] is not yet made OR not writeable by ", $user, ", please create<br>\n";
    exit(1);
  }
}
echo '</ul>';

echo "Everything seems a-ok<br>\n";

?>