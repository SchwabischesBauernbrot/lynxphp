<?php

$fePkgs = array(
  array(
    'handlers' => array(
      array(
        'method'  => 'GET',
        'route'   => '/',
        'handler' => 'homepage',
        'cacheSettings' => array(
          'resource' => array(
            'homepage',
          ),
          'files' => array(
            // theme is also would affect this caching
            'templates/header.tmpl', // wrapContent
            '../common/modules/site/homepage/fe/views/index.tmpl', // homepage
            'templates/footer.tmpl', // wrapContent
          ),
          'config' => array(
            'BACKEND_PUBLIC_URL',
          ),
          'sets' => array(
            'wrapContent'
          ),
        ),
      ),
    ),
    'forms' => array(),
    'modules' => array(
    ),
  ),
);
return $fePkgs;

?>
