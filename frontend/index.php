<?php
include '../common/post_vars.php';

// REQUEST_URI seems to be more accruate in NGINX
$req_path   = getServerField('PATH_INFO', getServerField('REQUEST_URI'));
$req_method = getServerField('REQUEST_METHOD', 'GET');

/*
// upload_max_filesize breakage will have _POST set...
$max_length = min(convertPHPSizeToBytes(ini_get('post_max_size')), convertPHPSizeToBytes(ini_get('upload_max_filesize')));
if (!count($_POST) && !count($_FILES) && $req_method === 'POST') {
  //echo "GET", print_r($_GET, 1), "<br>\n";
  //echo "POST", print_r($_POST, 1), "<br>\n";
  //echo "SERVER", print_r($_SERVER, 1), "<br>\n";
  if ($_SERVER['CONTENT_LENGTH'] > $max_length) {
    echo '<div style="height: 40px;"></div>', "\n";
    echo "This POST has too much data for this server, try sending less data.<br>\n";
    return;
  }
}
echo "max_length[", number_format($max_length), "] vs content_length[", number_format($_SERVER['CONTENT_LENGTH']), "]<br>\n";
*/

// or maybe don't have a static div...
// use js to change it if X condition are met?

// not POSTING to this page or this page or ANY this page
// and reqpath does not have .youtube
$sentBump = false;
if (
    !(
       ($req_path === '/signup' && $req_method === 'POST') ||
       ($req_path === '/forms/login' && $req_method === 'POST') ||
       strpos($req_path, 'user/settings/themedemo/') !== false ||
       $req_path === '/logout'
    ) && strpos($req_path, '/.youtube') === false) {
  // make sure first lines of output are see-able
  echo '<div style="height: 40px;"></div>', "\n"; flush();
  $sentBump = true;
}

include 'setup.php';

// work around nginx weirdness with PHP and querystrings
$isNginx = stripos(getServerField('SERVER_SOFTWARE'), 'nginx') !== false;
if ($isNginx && strpos($_SERVER['REQUEST_URI'], '?') !== false) {
  $parts = explode('?', $_SERVER['REQUEST_URI']);
  $querystring = $parts[1];
  $chunks = explode('&', $querystring);
  $qs = array();
  foreach($chunks as $c) {
    list($k, $v) = explode('=', $c);
    $qs[$k] = $v;
    if (empty($_GET[$k])) {
      $_GET[$k] = $v;
      // could stomp POST values...
      $_REQUEST[$k] = $v;
    }
  }
  //print_r($qs);
}

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