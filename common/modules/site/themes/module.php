<?php

return array(
  'name'    => 'site_themes',
  'version' => 1,
  'settings' => array(
    array(
      'level' => 'admin', // constant?
      'location' => 'site', // /tab/group
      'addFields' => array(
        'default_theme' => array(
          'label' => 'Default Theme',
          'type'  => 'select',
          'options' => $shared['themes'],
          //'optionsExec' => 'getThemes',
        ),
      )
    ),
  ),
);

?>