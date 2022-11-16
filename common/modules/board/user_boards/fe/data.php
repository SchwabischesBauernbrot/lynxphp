<?php

return array(
  'user_boards' => array(
    'handlers' => array(
    ),
    'forms' => array(
      array(
        //'portal' => 'user',
        'route' => '/create_board',
        'handler' => 'create_board',
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
      // add create boards to admin nav
      array(
        'pipeline' => 'PIPELINE_ACCOUNT_NAV',
        'module'   => 'account_nav',
      ),
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