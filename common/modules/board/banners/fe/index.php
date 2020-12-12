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

$fePkg = new frontend_package();

//$fePkg->addBackendResource('', $beRsrc);
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


// plug into boardSettingsTmpl

global $router, $pipelines;
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

$router->get('/:uri/banners', function($request) {
  $boardUri = $request['params']['uri'];
  global $beRrsc_list;
  $call = $beRrsc_list;
  // FIXME:
  $call['endpoint'] .= '?boardUri=' . $boardUri;
  $banners = consume_beRsrc($call, array('boardUri' => $boardUri));

  $templates = moduleLoadTemplates('banner_listing', __DIR__);

  $tmpl = $templates['header'];
  $banner_tmpl = $templates['loop0'];
  $boardData = getBoard($boardUri);

  $boardHeader_html = renderBoardHeader($boardData);
  $boardNav_html = renderBoardNav($boardUri, $boardData['pageCount'], '[Banners]');
  $tmpl = $boardHeader_html . $boardNav_html . $tmpl;

  $banners_html = '';
  foreach($banners as $banner) {
    $tmp = $banner_tmpl;
    $tmp = str_replace('{{backend}}', 'backend', $tmp);
    $tmp = str_replace('{{uri}}', $boardUri, $tmp);
    $tmp = str_replace('{{id}}', $banner['bannerid'], $tmp);
    $tmp = str_replace('{{image}}', $banner['image'], $tmp);
    $banners_html .= $tmp;
  }
  $tmpl = str_replace('{{uri}}', $boardUri, $tmpl);
  $tmpl = str_replace('{{banners}}', $banners_html, $tmpl);
  wrapContent($tmpl);

});

$router->get('/:uri/settings/banners', function($request) {
  // do we own this board?
  $boardUri = boardOwnerMiddleware($request);
  if (!$boardUri) return;
  // get a list of banners from backend

  global $beRrsc_list;
  $call = $beRrsc_list;
  // FIXME:
  $call['endpoint'] .= '?boardUri=' . $boardUri;
  $banners = consume_beRsrc($call, array('boardUri' => $boardUri));

  $templates = moduleLoadTemplates('banner_listing', __DIR__);

  // FIXME: include board header...
  // FIXME: include paged board nav...

  $header = $templates['header'];
  $banner_tmpl = $templates['loop1'];
  $tmpl = str_replace('{{banners}}', $header, $templates['loop2']);
  // add link
  // list
  $banners_html = '';
  foreach($banners as $banner) {
    $tmp = $banner_tmpl;
    $tmp = str_replace('{{backend}}', 'backend', $tmp);
    $tmp = str_replace('{{uri}}', $boardUri, $tmp);
    $tmp = str_replace('{{id}}', $banner['bannerid'], $tmp);
    $tmp = str_replace('{{image}}', $banner['image'], $tmp);
    $banners_html .= $tmp;
  }
  $tmpl = str_replace('{{uri}}', $boardUri, $tmpl);
  $tmpl = str_replace('{{banners}}', $banners_html, $tmpl);
  wrapContent($tmpl);
});

$router->get('/:uri/settings/banners/add', function($request) {
  // do we own this board?
  $boardUri = boardOwnerMiddleware($request);
  if (!$boardUri) return;
  $templates = moduleLoadTemplates('banner_detail', __DIR__);
  $tmpl = $templates['header'];

  // wrap form
  $tmpl = '<form method="POST" action="' . $boardUri . '/settings/banners/add" enctype="multipart/form-data">' . $tmpl . '
    <input type=submit>
  </form>';
  // pop up fields...
  $tmpl = str_replace('{{image}}', '<input type=file name=image>', $tmpl);

  wrapContent($tmpl);
});

$router->post('/:uri/settings/banners/add', function($request) {
  // do we own this board?
  $boardUri = boardOwnerMiddleware($request);
  if (!$boardUri) return;
  // validate data
  // handle file uploads...
  $fileField = 'image';
  if (!isset($_FILES) || !isset($_FILES[$fileField])) {
    // reload form?
    return wrapContent('An image is required');
  }
  if (is_array($_FILES[$fileField]['tmp_name'])) {
    return wrapContent("Write me!<br>\n");
  } else {
    // could run make_file($tmpfile, $type, $filename) here...
    $files = array(array($_FILES[$fileField]['tmp_name'], $_FILES[$fileField]['type'], $_FILES[$fileField]['name']));
  }
  global $beRrsc_add;
  $call = $beRrsc_add;
  $call['endpoint'] .= '?boardUri=' . $boardUri;
  $call['formData'] = array(
    'files' => make_file($files[0][0], $files[0][1], $files[0][2])
  );
  $result = consume_beRsrc($call, array('boardUri' => $boardUri));
  if (isset($result['id'])) {
    // success
    redirectTo(BASE_HREF . $boardUri . '/settings/banners');
  } else {
    wrapContent('Something went wrong...');
  }
});

$router->get('/:uri/settings/banners/:id/delete', function($request) {
  // do we own this board?
  $boardUri = boardOwnerMiddleware($request);
  if (!$boardUri) return;
  // maybe show banner...
  $yesAction = '/' . $boardUri . '/settings/banners/' . $request['params']['id'] . '/delete';
  $tmpl = <<< EOB
Are you sure?
<form method="POST" action="$yesAction">
  <input type=submit value="Yes">
</form>
EOB;
  wrapContent($tmpl);
});

$router->post('/:uri/settings/banners/:id/delete', function($request) {
  // do we own this board?
  $boardUri = boardOwnerMiddleware($request);
  if (!$boardUri) return;
  global $beRrsc_del;
  $call = $beRrsc_del;
  $call['formData'] = array('bannerId'=>$request['params']['id']);
  $result = consume_beRsrc($call);
  if ($result === '1') {
    // success
    redirectTo(BASE_HREF . $boardUri . '/settings/banners');
  } else {
    wrapContent('Something went wrong...');
  }
});

$bsn = new pipeline_module('board_banner');
$bsn->attach('boardSettingNav', function(&$navItems) {
  $navItems['banners'] = '{{uri}}/settings/banners';
});

// add {{banner}} tag to boardDetailsTmpl
$bannersTag = new pipeline_module('board_banner');
$bannersTag->attach('boardDetailsTmpl', function(&$p) {
  $boardUri = $p['boardUri'];
  global $beRrsc_random;
  $call = $beRrsc_random;
  $call['endpoint'] .= '?boardUri=' . $boardUri;
  $banner = consume_beRsrc($call, array('boardUri' => $boardUri));
  $p['tags']['banner'] = '<img src="'. BASE_HREF . 'backend/' . $banner['image']. '" width="'.$banner['w'].'" height="'.$banner['h'].'">';
});

$bannersTag = new pipeline_module('board_banner');
$bannersTag->attach('boardHeaderTmpl', function(&$p) {
  $boardUri = $p['boardUri'];
  global $beRrsc_random;
  $call = $beRrsc_random;
  $call['endpoint'] .= '?boardUri=' . $boardUri;
  $banner = consume_beRsrc($call, array('boardUri' => $boardUri));
  $p['tags']['banner'] = '<img src="'. BASE_HREF . 'backend/' . $banner['image']. '" width="'.$banner['w'].'" height="'.$banner['h'].'">';
});


// add [Banner] to board naviagtion
$boardNav = new pipeline_module('board_banner');
$boardNav->attach('boardNav', function(&$navItems) {
  $navItems['[Banners]'] = '{{uri}}/banners';
});

?>
