<?php

return array(
  array(
    'models' => array(
      /*
      array(
        'name'   => 'board_banner',
        'fields' => array(
          'board_id' => array('type' => 'int'),
          'image'    => array('type' => 'str'),
          'w'        => array('type' => 'int'),
          'h'        => array('type' => 'int'),
          'weight'   => array('type' => 'int'),
        ),
      ),
      */
    ),
    'modules' => array(
      // allow post deletions
      array('pipeline' => 'PIPELINE_BE_CONTENTACTIONS_DELETE', 'module' => 'passcheck',),
    ),
  ),
);

?>
