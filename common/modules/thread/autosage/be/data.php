<?php

// autosage/be

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
      // handle incoming posts (though we only need to be on replies)
      array('pipeline' => PIPELINE_NEWPOST_PROCESS, 'module' => 'newpost_process'),
    ),
  ),
);


?>
