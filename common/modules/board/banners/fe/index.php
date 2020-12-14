<?php

// pipelines:

// frontend handlers...
// boardAdminNav
// page tmpl
// thread tmpl
// catalog tmpl

// this could be a template
// which we copy and set the params

// could attach to a package handle if we had one here...

// FIXME: pass in $package...
// or return this, so we can set it...
//$fePkg = new frontend_package($this);
$fePkg = $this->makeFrontend();

//$fePkg->addBackendResource('', $beRsrc);
global $beRrsc_random, $beRrsc_list, $beRrsc_add, $beRrsc_del;
$beRrsc_random = array(
  'endpoint' => 'lynx/randomBanner',
  'unwrapData' => true,
  'requires' => array(
    'boardUri'
  ),
);

$beRrsc_list = array(
  'endpoint' => 'lynx/bannerManagement',
  'unwrapData' => true,
  'requires' => array(
    'boardUri'
  ),
);

$beRrsc_add = array(
  'endpoint'    => 'lynx/createBanners',
  'method'      => 'POST',
  'sendSession' => true,
  'unwrapData'  => true,
  'requires'    => array(
    'boardUri'
  ),
);

$beRrsc_del = array(
  'endpoint'    => 'lynx/deleteBanner',
  'method'      => 'POST',
  'sendSession' => true,
  'unwrapData'  => true,
  'requires'    => array(
    'bannerId'
  ),
);

$fePkg->addHandler('GET', '/:uri/banners', 'public_list');
$fePkg->addHandler('GET', '/:uri/settings/banners', 'settings_list');
$fePkg->addForm('/:uri/settings/banners/add', 'add');
$fePkg->addForm('/:uri/settings/banners/:id/delete', 'delete');

// yea, we can't embed the correct width/height this way to prevent bounce...
/*
$router->get('/:uri/banners/random', function($request) {
  $boardUri = $request['params']['uri'];
  global $beRrsc_random;
  $call = $beRrsc_random;
  $call['endpoint'] .= '?boardUri=' . $boardUri;
  $banner = consume_beRsrc($call, array('boardUri' => $boardUri));
  header('Location: ' . BASE_HREF . 'backend/'.$banner['image']);
});
*/

// add [Banner] to board naviagtion
$fePkg->addModule(PIPELINE_BOARD_NAV,          'nav');
// add {{banner}} tag to board_header_tmpl
$fePkg->addModule(PIPELINE_BOARD_HEADER_TMPL,  'banner');
// add {{banner}} tag to board_details_tmpl
$fePkg->addModule(PIPELINE_BOARD_DETAILS_TMPL, 'banner');
// adds banners to nav settings
$fePkg->addModule(PIPELINE_BOARD_SETTING_NAV,  'nav_settings');

?>
