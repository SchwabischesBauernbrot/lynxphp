<?php

$params = $getHandler();

global $packages, $router;
// all nonGET won't be loaded because of how buildRoutes is loaded per method
foreach($router->methods as $method => $r) {
  //echo "method[$method]<br>\n";
  if ($method === $_SERVER['REQUEST_METHOD']) continue;
  foreach($packages as $pName => $pkg) {
    // would cause multiple loadings...
    //$pkg->buildFrontendRoutes($router, $method);
    foreach($pkg->frontend_packages as $fe_pkg) {
      $fe_pkg->buildRoutes($router, $method);
    }
  }
}
// generated flag?
// dontGen flag?
$routes = array();
foreach($router->methods as $m => $r) {
  foreach($r as $c=>$f) {
    $ro = $router->routeOptions[$m . '_' . $c];
    $routes[] = array(
      'method'  => $m,
      'route'   => $c,
      'module'  => $ro['module'],
      'address' => $ro['address'],
      'form'          => (empty($ro['form'])          ? '' : 'Y'),
      'loggedIn'      => (empty($ro['loggedIn'])      ? '' : 'Y'),
      'cacheSettings' => (empty($ro['cacheSettings']) ? '' :
        '<span title="' . print_r($ro['cacheSettings'], 1) . '">Y</a>'),
    );
  }
}
$content = '';
$content .= 'There are ' . count($routes) . ' frontend routes';
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
