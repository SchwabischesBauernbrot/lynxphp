<?php

$params = $getHandler();

global $packages;
$webroot = realpath($_SERVER['DOCUMENT_ROOT'] . '/..');
$content = '<ul>';

global $portalResources;
foreach($portalResources as $pname => $opts) {
  // define BEs since they're skipped in the FE
  definePipeline('PIPELINE_BE_' . strtoupper($opts['snakeName']) . '_PORTAL');
}

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
  // though info on install modules is always good too
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
wrapContent($content);