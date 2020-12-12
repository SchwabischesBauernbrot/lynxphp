<?php

// Lynx:
// bannerManagement
// createBanners
// deleteBanner
// randomBanner

// pipelines:
// - boardData

$module = new pipeline_module('board_banners');
$module->dependencies = array();
$module->preempt      = array();
$module->attach('boardData', function(&$row) {
});

// backend handlers...
global $routers;
$routers['lynx']->get('/randomBanner', function($request) {
  $boardData = boardMiddleware($request);
  global $db, $models;
  $res = $db->find($models['board_banner'], array('criteria' => array(
    array('board_id', '=', $boardData['boardid']),
  )));
  $banners = $db->toArray($res);
  shuffle($banners);
  sendResponse($banners[0]);
});

$routers['lynx']->get('/bannerManagement', function($request) {
  $boardData = boardMiddleware($request);
  global $db, $models;
  $res = $db->find($models['board_banner'], array('criteria' => array(
    array('board_id', '=', $boardData['boardid']),
  )));
  $banners = $db->toArray($res);
  sendResponse($banners);
});

$routers['lynx']->post('/createBanners', function($request) {
  $boardUri = boardOwnerMiddleware($request);
  global $db, $models;
  // FIXME: validation that there is an upload...
  // handle file uploads...
  $bannersDir = 'storage/boards/' . $boardUri . '/banners';
  if (!file_exists($bannersDir)) {
    mkdir($bannersDir);
  }
  $file = $bannersDir . '/' . basename($_FILES['files']['tmp_name']);
  move_uploaded_file($_FILES['files']['tmp_name'], $file);

  $boardData = getBoardByUri($boardUri);
  $sizes = getimagesize($file);

  $id = $db->insert($models['board_banner'], array(array(
    'board_id' => $boardData['boardid'],
    'image' => $file,
    'w' => $sizes[0],
    'h' => $sizes[1],
    'weight' => 1,
  )));

  sendResponse(array(
    'id'   => $id,
    'path' => $file,
  ));
});

$routers['lynx']->post('/deleteBanner', function($request) {
  if (!hasPostVars(array('bannerId'))) {
    return;
  }
  $bannerId = (int)$_POST['bannerId'];
  global $db, $models;
  $res = $db->delete($models['board_banner'],array('criteria'=>array(
    array('bannerid', '=', $bannerId),
  )));
  sendResponse($res);
});

?>
