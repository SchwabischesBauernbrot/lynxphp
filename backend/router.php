<?php

include '../common/router.php';

class BackendRouter extends Router {
  function __construct() {
    parent::__construct();
    $this->defaultContentType = 'application/json';
  }
  // override defaults
  function import($routes, $module = 'backend', $dir = 'handlers') {
    return parent::import($routes, $module);
  }
  function fromResource($name, $res, $bePkg) {
    // DEV_MODE is not available on backend...
    if (!isset($res['handlerFile'])) {
      return 'handlerFile is not set';
    }
    if (!file_exists($res['handlerFile']) || !is_readable($res['handlerFile'])) {
      echo "BackendRouter::fromResource - handlerFile[", $res['handlerFile'], "] is not found or accessible<br>\n";
    }
    // router is stripped by this point
    // has no / in front
    $cond = $res['endpoint'];
    $method = empty($res['method']) ? 'GET' : $res['method'];
    if ($method === 'AUTO') {
      if ($res['formData']) {
        $method = 'POST';
      } else {
        $method = 'GET';
      }
    }

    $func = function($request) use ($res, $bePkg) {
      // get session
      $user_id = null;
      if (!empty($res['sendSession'])) {
        $user_id = getUserID();
      }
      if (!empty($res['requireSession'])) {
        $user_id = loggedIn();
        if (!$user_id) {
          return;
        }
      }
      // get ip
      $ip = null;
      if (!empty($res['sendIP'])) {
        $ip = getip();
      }

      $moduleDir = $bePkg->dir;
      $shared = $bePkg->shared;
      $module_path = $moduleDir;
      // coordinate with bePkg
      if (!$bePkg->ranOnce) {
        if (is_readable($module_path . 'be/common.php')) {
          // ref isn't defined...
          //$ref->common =
          $bePkg->common = include $module_path . 'be/common.php';
        } else {
          if (file_exists($module_path . 'be/common.php')) {
            echo "perms? [$module_path]be/common.php<br>\n";
          }
        }
        $bePkg->ranOnce = true;
        if (isset($bePkg->common)) {
          $common = $bePkg->common;
        }
      }

      // make pass a callback to handle response
      $sendResponse = function($request, $response, $next) use ($res) {
        $respText = responseToText($response);
        if ($res['unwrapData']) {
          sendResponse($respText);
        } else
        if ($res['expectJson']) {
          echo json_encode($respText);
        }
      };
      // create a single closure this file API can depend on
      $get = function() use ($user_id, $ip, $sendResponse, $request) {
        // request?
        // yea we need to get at params
        return array(
          'params' => $request['params'],
          'sendResponse' => $sendResponse,
          'userid' => $user_id,
          'ip' => $ip,
        );
      };
      // we could global $db, $models here too
      $intFunc = include $res['handlerFile'];
    };

    if (!empty($res['cacheSettings'])) {
      //echo "Setting cacheSettings for [$method]_[$cond]<br>\n";
      $this->routeOptions[$method . '_' . $cond]['cacheSettings'] = $res['cacheSettings'];
    }

    // frontend data...
    /*
    // styleSheets, headScripts, title
    // do we need scripts?
    // maybe footer scripts?
    // less sure about this because there's still could a temporal issue?
    // not really atm...
    if (!empty($res['styleSheets'])) {
      $this->routeOptions[$method . '_' . $cond] = $res['styleSheets'];
    }
    if (!empty($res['headScripts'])) {
      $this->routeOptions[$method . '_' . $cond] = $res['headScripts'];
    }
    if (!empty($res['title'])) {
      $this->routeOptions[$method . '_' . $cond] = $res['title'];
    }
    */

    //echo "Installing [$method][$cond]<br>\n";
    switch($method) {
      case 'GET':
        $this->methods['GET'][$cond] = $func;
      break;
      case 'POST':
        $this->methods['POST'][$cond] = $func;
      break;
      case 'HEAD':
        $this->methods['HEAD'][$cond] = $func;
      break;
      case 'PUT':
        $this->methods['PUT'][$cond] = $func;
      break;
      case 'DELETE':
        $this->methods['DELETE'][$cond] = $func;
      break;
      default:
        echo "Unknown method [$method]<br>\n";
      break;
    }
    return true;
  }
}

return new BackendRouter;
?>