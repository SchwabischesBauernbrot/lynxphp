<?php

// expects $packages, $req_method
// exports $router

$router = include 'router.php';

// build routes
foreach($packages as $pkg) {
  $pkg->frontendPrepare($router, $req_method);
}

if ($router->isTooBig()) {
  /*
  if (!$sentBump) {
    echo '<div style="height: 40px;"></div>', "\n";
  }
  */
  http_response_code(413);
  wrapContent('This POST request has sent too much data [' . formatBytes($_SERVER['CONTENT_LENGTH']). '] for this server [' . formatBytes($router->max_length) . '], try sending less data.');
  exit();
}

// FIXME: we should be getting page content and wrapping it here...
// but each handle is best to determine what portals need to be wrapped

// FIXME: move into routes and the caching layer can go here too
// well we kind of did with setup.php

/*
function getRoute($idealUrl, $noRewrite) {
  // FIXME: get config value to set this
  if (0) {
    return $noRewrite;
  } else {
    return $idealUrl;
  }
}
*/
/*
$signupRoute = getRoute('/signup', '/signup.php');
$router->get($signupRoute, function() {
  getSignup();
});
$router->post($signupRoute, function() {
  postSignup();
});
*/
$frontendRouterData = array(
  'login' => array(
    'file'   => 'login',
    'routes' => array(
      'form_get' => array(
        'route'  => '/forms/login.html',
        'func'   => 'getLogin',
        'options' => array(
          'cacheSettings' => array(
            'files' => array(
              // theme is also would affect this caching
              'templates/header.tmpl', // wrapContent
              'templates/footer.tmpl', // wrapContent
              'templates/login.tmpl', // login
              '../frontend_lib/handlers/login.php', // login
            ),
          ),
        ),
      ),
      'form_post' => array(
        'method' => 'POST',
        'route'  => '/forms/login.php',
        'func'   => 'postLogin',
      ),
      'logout' => array(
        'route'  => '/logout.php',
        'func'   => 'getLogout',
        'loggedIn' => true,
      ),
    ),
  ),
  'account' => array(
    'file'   => 'control_panel',
    'routes' => array(
      'control_panel' => array(
        'route'  => '/control_panel.php',
        'func'   => 'getControlPanel',
        'loggedIn' => true,
      ),
      'page' => array(
        'route'  => '/account.php',
        'func'   => 'getAccountSettingsHandler',
        'loggedIn' => true,
      ),
    ),
  ),
  'globals' => array(
    'file'   => 'global',
    'routes' => array(
      'page' => array(
        'route'  => '/global.php',
        'func'   => 'getGlobalPage',
        'loggedIn' => true,
        'portals' => array(),
      ),
    ),
  ),
  /*
  'site' => array(
    'file'   => 'textfiles',
    'routes' => array(
      'robots' => array(
        'route'  => '/robots.txt',
        'func'   => 'getRobotsHandler',
        'options' => array(
          'cacheSettings' => array(
            'files' => array(
            ),
          ),
        ),
      ),
      'humans' => array(
        'route'  => '/humans.txt',
        'func'   => 'getHumansHandler',
      ),
    ),
  ),
  */
  'boards' => array(
    'file'   => 'boards',
    'portals' => array('board'),
    'routes' => array(
      'inline_loader_list' => array(
        'route'  => '/boards_cacheable.html',
        'func'   => 'getInlineBoardsLoaderHandler',
        'options' => array(
          // FIXME: a settings to just flat out ALWAYS cache this forever
          'cacheSettings' => array(
            'files' => array(
              // theme is also would affect this caching
              'templates/header.tmpl', // wrapContent
              'templates/footer.tmpl', // wrapContent
            ),
            'backend' => array(
              array(
                'route' => 'opt/settings',
                'method' => 'GET',
              ),
            ),
          ),
        ),
      ),
      'newPost' => array(
        'method' => 'POST',
        'route'  => '/:uri/post',
        'func'   => 'makePostHandlerHtml',
      ),
      'newPostJson' => array(
        'method' => 'POST',
        'route'  => '/:uri/post.json',
        'func'   => 'makePostHandlerJson',
      ),
      // needs to go last
      'fileRedirect' => array(
        'route'  => '/:uri',
        'func'   => 'getBoardFileRedirect',
        // in static mode, webserver should do this automatically
        'dontGen' => true,
      ),
    ),
  ),
);
$router->import($frontendRouterData);