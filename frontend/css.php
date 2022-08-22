<?php
include '../common/lib.loader.php';
ldr_require('../common/lib.http.server.php');
ldr_require('../common/lib.http.response.php');
ldr_require('../common/common.php');

// work around nginx weirdness with PHP and querystrings
ensureQuerystring();

// we need FE packages
include 'setup.php';
foreach($packages as $pkg) {
  $pkg->frontendPrepare(false, 'GET', array(
    'loadHandlers'  => false,
    'loadForms'     => false,
    'loadJs'        => true,
    'loadModules'   => false,
    'loadPipelines' => false,
  ));
}

if (empty($_GET['module'])) {
  echo "alert('module is not passed to js.php')";
  return;
}

// which module?
$module = $_GET['module'];
//echo "module[$module]<br>\n";
if (!isset($packages[$module])) {
  echo "alert('js.php - [$module] module is not found')";
  return;
}

$scripts = array();
if (isset($_GET['scripts'])) {
  $scripts = explode(',', $_GET['scripts']);
}


$pkg = $packages[$module];
// can only be a fe folder
$dir = $pkg->dir . 'fe/js/';

$paths = array();
$max = 0;
$size = 0;
foreach($pkg->frontend_packages as $fe_pkg) {
  foreach($fe_pkg->js as $j) {
    // could do generated JS here too tbh
    // but need to see how that would work with generate.php
    if (count($scripts)) {
      //echo "checking[", getcwd(), '/', $dir . $j['file'], "][", realpath(getcwd() . '/' . $dir . $j['file']), "]<br>\n";
      // data.php js.file needs to match script in js_add_script
      // also the _GET['scripts']
      //echo "file[", basename($j['file']), "] scripts[", print_r($scripts, 1), "]<br>\n";
      if (!in_array(basename($j['file']), $scripts)) {
        continue;
      }
    }
    //echo "dir[$dir][", print_r($j, 1), "]<br>\n";
    $path = $dir . $j['file'];
    if (!empty($j['module'])) {
      if (empty($packages[$j['module']])) {
        echo '// module [', $j['module'], '] does not exist', "\n";
      }
      $otherPkg = $packages[$j['module']];
      $path = $otherPkg->dir . 'fe/js/' . $j['file'];
      //echo "module updated path to [$path]<br>\n";
    }
    $max = max($max, filemtime($path));
    $size += filesize($path);
    $paths[] = $path;
  }
}

if (checkCacheHeaders($max, array('contentType' => 'text/javascript', 'fileSize' => $size))) return;

// generate content
foreach($paths as $p) {
  echo "// $p\n";
  readfile($p);
}

?>