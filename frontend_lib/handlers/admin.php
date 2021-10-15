<?php

function getAdminPage() {

  $portal = array(
    'header'=>array(
      'file' => '',
      // tag => code/constant
      'replaces' => array(),
      'nav' => array(
        'items' => array(
        ),
        'replaces' => array(),
        'selected' => '',
        'displayOpts' => array(
          'list' => true
        )
      )
    ),
    'footer'=>array(
      'file' => '',
      'replaces' => array(),
    ),
  );

  $boardnav_html = renderAdminPortal();

  $content = $boardnav_html;
  $content .= <<< EOB
You're an admin, what do you want a cookie?
EOB;
  wrapContent($content);
}

function getAdminFERoutesPage() {
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
  wrapContent(renderAdminPortal() . $content);
}

function getAdminModulesPage() {
  global $packages;
  $webroot = realpath($_SERVER['DOCUMENT_ROOT'] . '/..');
  $content = '<ul>';
  // FIXME: path...
  include '../backend/pipelines.php'; // set up BE pipelines
  foreach($packages as $name => $pkg) {
    // load in backend packages
    $pkg->buildBackendRoutes();
    $content .= '<li>' . $name;
    $content .= '<ul>';
    $content .= '<li>Ver: ' . $pkg->ver;
    $dir = str_replace($webroot . '/common/modules/', '', $pkg->dir);
    $content .= '<li>Module directory: ' . $dir;
    if (is_array($pkg->resources)) {
      $content .= '<li>Resources: '. count($pkg->resources);
      $content .= '<table><tr><th>Name<th>Method<th>Route';
      foreach($pkg->resources as $rname => $rsrc) {
        // endpoint, unwrapData, requires(array), params (formData, querystring), handlerFile
        $method = empty($rsrc['method']) ? 'GET' : $rsrc['method'];
        $content .= '<tr><td>' . $rname . '<td>' . $method . '<td>' . $rsrc['endpoint'];
      }
      $content .= '</table>';
    }
    if (is_array($pkg->frontend_packages)) {
      $content .= '<li>Frontend Packages: '. count($pkg->frontend_packages);
      $content .= '<ul>';
      foreach($pkg->frontend_packages as $fepkg) {
        $content .= '<li>' . $fepkg->toString();
      }
      $content .= '</ul>';
    }
    // FIXME: we can't trust the backend is here
    // tho infor on install modules is always good too
    /*
    if (is_array($pkg->backend_packages)) {
      $content .= '<li>Backend Packages: '. count($pkg->backend_packages);
      $content .= '<ul>';
      foreach($pkg->backend_packages as $i=>$bepkg) {
        $content .= '<li>#' . ($i + 1) . ' ' . $bepkg->toString();
      }
      $content .= '</ul>';
    }
    */

    $content .= '</ul>';
    //$content .= '<pre>' . print_r($pkg, 1) . '</pre>';
    $content .= "\n";
  }
  $content .= '</li>';
  wrapContent(renderAdminPortal() . $content);
}

function getAdminInstallPage() {
  wrapContent(renderAdminPortal() . '<iframe src="install.php" frameborder=0 width=100%></iframe>');
}

?>
