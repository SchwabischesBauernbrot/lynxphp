<?php

// set up frontend specific code (handlers, forms, modules)

// $this is the package
$fePkg = $this->makeFrontend();

// add frontend handlers and forms
$fePkg->addHandler('GET', '/:uri/banners', 'public_list');
$fePkg->addHandler('GET', '/:uri/settings/banners', 'settings_list');
$fePkg->addForm('/:uri/settings/banners/add', 'add');
$fePkg->addForm('/:uri/settings/banners/:id/delete', 'delete');

// disabled because
// we can't embed the correct width/height this way to prevent bounce...
//$fePkg->addHandler('GET', '/:uri/banners/random', 'random_banner');
/*
$router->get('/:uri/banners/random', function($request) {
  $boardUri = $request['params']['uri'];

  // get a random banner from backend
  $banner = $pkg->useResource('random', array('boardUri' => $boardUri));

  // redirect to banner
  header('Location: ' . BASE_HREF . 'backend/'.$banner['image']);
});
*/

// add frontend pipeline modules
// add [Banner] to board naviagtion
$fePkg->addModule(PIPELINE_BOARD_NAV,          'nav');
// add {{banner}} tag to board_header_tmpl
$fePkg->addModule(PIPELINE_BOARD_HEADER_TMPL,  'banner');
// add {{banner}} tag to board_details_tmpl
$fePkg->addModule(PIPELINE_BOARD_DETAILS_TMPL, 'banner');
// adds banners to nav settings
$fePkg->addModule(PIPELINE_BOARD_SETTING_NAV,  'nav_settings');

?>
