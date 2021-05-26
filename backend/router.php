<?php

include '../common/router.php';

class BackendRouter extends Router {
  function __construct() {
    parent::__construct();
    $this->defaultContentType = 'application/json';
  }
  function fromResource($name, $res, $moduleDir) {
    if (!isset($res['handlerFile'])) {
      return 'handlerFile is not set';
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

    $func = function($request) use ($res, $moduleDir) {
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

      if (is_readable($moduleDir . 'shared.php')) {
        $shared = include $moduleDir . 'shared.php';
      }
      if (is_readable($moduleDir . 'be/common.php')) {
        $common = include $moduleDir . 'be/common.php';
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
      $get = function() use ($user_id, $ip, $sendResponse) {
        // request?
        return array(
          'sendResponse' => $sendResponse,
          'userid' => $user_id,
          'ip' => $ip,
        );
      };
      // we could global $db, $models here too
      $intFunc = include $res['handlerFile'];
    };

    if (!empty($res['cacheSettings'])) {
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
      case 'POST':
        $this->methods['POST'][$cond] = $func;
      break;
      case 'GET':
      default:
        $this->methods['GET'][$cond] = $func;
      break;
    }
    return true;
  }
}

return new BackendRouter;
?>