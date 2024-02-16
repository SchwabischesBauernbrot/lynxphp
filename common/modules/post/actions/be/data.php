<?php

$bePkgs = array(
  array(
    'models' => array(
    ),
    'modules' => array(
      array('pipeline' => PIPELINE_BE_BOARD_SETTINGS_PORTAL, 'module' => 'board_settings_portal_data'),
    ),
    'pipelines' => array(
      array('name' => 'PIPELINE_BE_CONTENTACTIONS_DELETE'),
    ),
  ),
);
return $bePkgs;

?>