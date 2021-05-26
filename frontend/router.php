<?php

include '../common/router.php';

class FrontendRouter extends Router {
  function __construct() {
    parent::__construct();
    //$this->defaultContentType = 'text/html';
    $this->included = array();
  }
  /*
  function fromResource($name, $res, $moduleDir) {
    // front doesn't need to set up backend routes
  }
  */
  function import($routes) {
    foreach($routes as $group => $groupData) {
      foreach($groupData['routes'] as $routeData) {
        $method = empty($routeData['method']) ? 'GET' : $routeData['method'];
        $this->methods[$method][$routeData['route']] = function($request) use ($routeData, $groupData) {
          if (!empty($routeData['file']) && empty($this->included[$routeData['file']])) {
            include '../frontend_lib/handlers/' . $routeData['file'] . '.php';
            $this->included[$routeData['file']] = true;
          }
          if ($routeData['func']) {
            $func = $routeData['func'];
            $func($request);
          } else {
            echo "No function defined<br>\n";
          }
        };
        if (isset($routeData['options'])) {
          $this->routeOptions[$method . '_' . $routeData['route']] = $routeData['options'];
        }
        $this->frontendData[$method . '_' . $routeData['route']] = array(
          'group' => $groupData,
          'route' => $routeData,
        );
      }
    }
  }
}

return new FrontendRouter;
?>