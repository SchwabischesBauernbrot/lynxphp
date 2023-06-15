<?php
return array(
  'name' => 'protection_captcha_lynxim',
  'version' => 1,
  'settings' => array(
    array(
      'level' => 'bo', // constant?
      'location' => 'board', // /tab/group
      'addFields' => array(
        'captcha_mode' => array(
          'label' => 'CAPTCHA mode',
          'type'  => 'select',
          'options' => array(
            'no' => 'No captcha',
            'threads' => 'Only for threads',
            'posts' => 'For all posts',
          ),
        ),
      )
    ),
  ),
  'resources' => array(
  ),
);
?>