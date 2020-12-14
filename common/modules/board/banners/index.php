<?php

$package = new package('board_banners', 1, __dir__);

// Lynx:
// bannerManagement
// createBanners
// deleteBanner
// randomBanner

// frontend usually routes wrap around these...
// so we can't just add more frontend resources
// we need to attach a frontend to it?
// and we don't need frontend attachments here...
// there could be some benefits of documenting the frontend routes here...

$package->addResource('random', array(
  'endpoint' => 'lynx/randomBanner',
  'unwrapData' => true,
  // can't set type like this
  // we need to be able to set types of non-required fields...
  'requires' => array(
    'boardUri' => 'querystring',
  ),
));

$package->addResource('list', array(
  'endpoint' => 'lynx/bannerManagement',
  'unwrapData' => true,
  'requires' => array(
    'boardUri'
  ),
));

$package->addResource('add', array(
  'endpoint'    => 'lynx/createBanners',
  'method'      => 'POST',
  'sendSession' => true,
  'unwrapData'  => true,
  'requires'    => array(
    'boardUri'
  ),
));

$package->addResource('del', array(
  'endpoint'    => 'lynx/deleteBanner',
  'method'      => 'POST',
  'sendSession' => true,
  'unwrapData'  => true,
  'requires'    => array(
    'bannerId'
  ),
));

return $package;

?>
