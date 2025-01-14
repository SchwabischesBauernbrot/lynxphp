<?php

return array(
  'user_mgmt' => array(
    'handlers' => array(
    ),
    'forms' => array(
      array(
        'route' => '/signup',
        'handler' => 'signup',
        'options' => array(
          'get_options'=> array(
            'cacheSettings' => array(
              'files' => array(
                'templates/header.tmpl', // wrapContent
                'templates/footer.tmpl', // wrapContent
              ),
            ),
          ),
        ),
      ),
    ),
    'modules' => array(
      /*
      // add [users] to admin nav
      array(
        'pipeline' => 'PIPELINE_ADMIN_NAV',
        'module'   => 'nav',
      ),
      */
    ),
    'pipelines' => array(
      /*
      array(
        'name' => 'PIPELINE_MODULE_USER_SETTINGS_GENERAL',
      ),
      */
    ),
  ),
);

?>