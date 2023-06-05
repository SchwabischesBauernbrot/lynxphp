<?php

$params = $getHandler();

// FIXME: add required/optional querystring/body
// to be nice to group them by module...
global $packages;
// FIXME: path...
$be_path = '../backend/';
include $be_path . 'pipelines.php'; // set up BE pipelines
$routes = array();
// how do I get the base routers?
$beRouterData = array();
$beRouterData['4chan'] = include $be_path . 'routes/4chan.php'; // set up BE pipelines
$beRouterData['lynxchan_minimal'] = include $be_path . 'routes/lynxchan_minimal.php'; // set up BE pipelines
$beRouterData['opt'] = include $be_path . 'routes/opt.php'; // set up BE pipelines
// doubleplus? I don't think it has it's own router tbh
foreach($beRouterData as $n => $data) {
  foreach($data[$n]['routes'] as $name => $p) {
    //echo $name, print_r($p, 1), "<br>\n";
    $routes[] = array(
      'method'   => (empty($p['method']) ? 'GET' : $p['method']),
      'endpoint' => $p['route'],
      'module'   => 'core::' . $n,
      'address'  => $data[$n]['dir'] . '/' . $p['file'],
    );
  }
}

global $portalResources;
foreach($portalResources as $pname => $opts) {
  // define BEs since they're skipped in the FE
  definePipeline('PIPELINE_BE_' . strtoupper($opts['snakeName']) . '_PORTAL');
}

foreach($packages as $pname => $pkg) {
  // load in backend packages
  $pkg->buildBackendRoutes();
  if (is_array($pkg->resources)) {
    foreach($pkg->resources as $name => $data) {
      // endpoint
      // method, sendSession, unwrapData, requires, params, handlerFile
      $cacheSettings = $pkg->resourcesCache[$name];
      //echo "test[", print_r($cacheSettings, 1), "]<br>\n";
      $routes[] = array(
        'method'   => (empty($data['method']) ? 'GET' : $data['method']),
        'endpoint' => $data['endpoint'],
        'module'   => $pname,
        'address'  => $data['handlerFile'],
        'form'     => '',
        'auth'     => '',
        'cache'    => $cacheSettings ? 'Y' : '',
      );
    }
    //echo '<pre>', htmlspecialchars(print_r($pkg->resources, 1)), "</pre>\n";
  }
}
$content = '';
$content .= 'There are ' . count($routes) . ' backend resources';
$content .= '<table><tr><th>Method<th>Route<th>Module<th>[Func@]File<th>Form<th>Auth<th>Cacheable';
$after = '';
foreach($routes as $row) {
  $line = '<tr><td>' . join('</td><td>', $row) . "</td>\n";
  // move frontend up to the top
  if ($row['module'] === 'frontend') {
    $content .= $line;
  } else {
    $after .= $line;
  }
}
$content .= $after . '</table>';
wrapContent($content);