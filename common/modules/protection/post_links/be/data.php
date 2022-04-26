<?php

$bePkgs = array(
  array(
    'models' => array(
      /*
      array(
        'name'   => 'tor_ip',
        'fields' => array(
          'ip'       => array('type' => 'str'),
        ),
      ),
      */
    ),
    'modules' => array(
      // register tag
      array('pipeline' => PIPELINE_POSTTAG_REGISTER, 'module' => 'register'),
      // determine if post needs a tag
      array('pipeline' => PIPELINE_NEWPOST_TAG, 'module' => 'newpost_tag'),
    ),
  ),
);
return $bePkgs;

?>
