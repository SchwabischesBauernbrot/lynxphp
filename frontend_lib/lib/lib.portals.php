<?php

// support functions for portals

global $portalsConfig;
// this shouldn't stopm anything as long as it's loaded first
$portalsConfig = array();

// documentation around the portal idea should go here

// router ignorant of portals for the most part
// lib.packages manages a lot of the piping
// though we need an object we pass into modules
// and is passed into wrap functions
// wrapContent is called after BE call so it will have portals QS data
// but if they split the header/footer calls
// header won't have the data yet... unless they do the BE call before the header
// which is what's generally done now

// maybe some portalsConfig around which headers need data from BE
// and maybe we split header into 2 parts
// part that doesn't need BE data and part that does
// out header, inner header, footer (footer will always have the data)

// could just pass portalname to it instead of paramsCode
function portal_getParamsFromContext($paramsCode, $request) {
  $params = array();
  // so type checking is warranted here
  foreach($paramsCode as $k => $param) {
    if (!isset($param['type'])) {
      echo "<pre>getPortalBoard param is missing type[", print_r($param, 1), "]</pre>\n";
      continue;
    }
    if ($param['type'] === 'params') {
      $n = $param['name'];
      $v = isset($request['params'][$n]) ? $request['params'][$n] : '';
      $params[$k]= $v;
    }
  }
  return $params;
}

function portal_modifyBackend($portalName, &$rsrc) {
  global $portalsConfig;
  if (empty($portalsConfig[$portalName])) {
    if (DEV_MODE) {
      echo "lib.portal::portal_modifyBackend - [$portalName] isn't loaded or has config<Br>\n";
    }
    return;
  }
  $config = $portalsConfig[$portalName];
  // if we need SID, then apply sid on rsrc settings
}


// was in lib.backend, moved out to unify all the documentation/notes
// the lib.packages could prepare this stuff
// but handler still needs to call what it calls

/*
// was used in base/board/view/fe/commom but now dead
function getPortalsToUrl($q) {
  return join(',', $q['portals']);
}

// was used in base/board/view/fe/commom but now dead
function addPortalsToUrl($q, $url) {
  // some portal BE stuff will need IP and probably SID
  // so we're have to send those for all requests
  // isn't the end of the world but meh...
  //
  // portal registration will need to communicate what it needs
  // what parameters it needs as inputs
  // and what outputs to the BE
  // and what inputs from the BE
  // - though I think these will be neat placed in somewhere easy to find for portals
  //
  // if (!empty($options['sendIP'])) $headers['HTTP_X_FORWARDED_FOR'] = getip();
  return $url . '?portals=' . join(',', $q['portals']);
}
*/

?>
