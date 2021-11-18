<?php

// expects $packages, $req_method
// exports $router

$router = include 'router.php';

// build routes
foreach($packages as $pkg) {
  $pkg->frontendPrepare($router, $req_method);
}

if ($router->isTooBig()) {
  if (!$sentBump) {
    echo '<div style="height: 40px;"></div>', "\n";
  }
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
      ),
    ),
  ),
  'admins' => array(
    'file'   => 'admin',
    'routes' => array(
      'page' => array(
        'route'  => '/admin.php',
        'func'   => 'getAdminPage',
        'loggedIn' => true,
      ),
      'modules' => array(
        'route'  => '/admin/modules.php',
        'func'   => 'getAdminModulesPage',
        'loggedIn' => true,
      ),
      'install' => array(
        'route'  => '/admin/install.php',
        'func'   => 'getAdminInstallPage',
        'loggedIn' => true,
      ),

      'fe_routes' => array(
        'route'  => '/admin/fe_routes.php',
        'func'   => 'getAdminFERoutesPage',
        'loggedIn' => true,
      ),
      'be_routes' => array(
        'route'  => '/admin/be_routes.php',
        'func'   => 'getAdminBERoutesPage',
        'loggedIn' => true,
      ),

    ),
  ),
  'boards' => array(
    'file'   => 'boards',
    'routes' => array(
      'list' => array(
        'route'  => '/boards.html',
        'func'   => 'getBoardsHandler',
        'options' => array(
          'cacheSettings' => array(
            'files' => array(
              // theme is also would affect this caching
              'templates/header.tmpl', // wrapContent
              'templates/footer.tmpl', // wrapContent
            ),
          ),
        ),
      ),
      'filter' => array(
        'method' => 'POST',
        'route'  => '/boards.php',
        'func'   => 'getBoardsHandler',
      ),
      // move into module?
      'overboard' => array(
        // non-dynamic version
        'route'  => '/overboard.html',
        'func'   => 'getOverboardHandler',
        'options' => array(
          'cacheSettings' => array(
            'files' => array(
              // theme is also would affect this caching
              'templates/header.tmpl', // wrapContent
              'templates/footer.tmpl', // wrapContent
            ),
          ),
        ),
      ),
      'view' => array(
        'route'  => '/:uri/',
        'func'   => 'getBoardThreadListingHandler',
        'options' => array(
          'cacheSettings' => array(
            'backend' => array(
              array(
                'route' => 'opt/boards/:uri/:page',
                'method' => 'GET',
              ),
            ),
            'files' => array(
              // theme is also would affect this caching
              'templates/header.tmpl', // wrapContent
              'templates/footer.tmpl', // wrapContent
              'templates/thread_listing.tmpl', // boards
              'templates/mixins/board_header.tmpl', // board_portal
              'templates/mixins/board_footer.tmpl', // board_portal
              'templates/mixins/post_detail.tmpl', // renderPost
              'templates/mixins/post_actions.tmpl', // renderPostActions
            ),
            'sets' => array(
              'wrapContent',
              'board_portal',
              'renderPost',
              'renderPostActions',
            ),
          ),
        ),
      ),
      'page' => array(
        'route'  => '/:uri/page/:page.html',
        'func'   => 'getBoardThreadListingPageHandler',
        'options' => array(
          'cacheSettings' => array(
            'files' => array(
              // theme is also would affect this caching
              'templates/header.tmpl', // wrapContent
              'templates/footer.tmpl', // wrapContent
              'templates/mixins/board_header.tmpl', // board_portal
              'templates/mixins/board_footer.tmpl', // board_portal
              'templates/mixins/post_detail.tmpl', // renderPost
              'templates/mixins/post_actions.tmpl', // renderPostActions
            ),
          ),
        ),
      ),
      /*
      'thread' => array(
        'route'  => '/:uri/thread/:num.html',
        'func'   => 'getThreadHandler',
        'options' => array(
          'cacheSettings' => array(
            'files' => array(
              // theme is also would affect this caching
              'templates/header.tmpl', // wrapContent
              'templates/footer.tmpl', // wrapContent
              'templates/mixins/board_header.tmpl', // board_portal
              'templates/mixins/board_footer.tmpl', // board_portal
              'templates/mixins/post_detail.tmpl', // renderPost
              'templates/mixins/post_actions.tmpl', // renderPostActions
            ),
          ),
        ),
      ),
      */
      'newPost' => array(
        'method' => 'POST',
        'route'  => '/:uri/post',
        'func'   => 'makePostHandler',
      ),
      'catalog' => array(
        'route'  => '/:uri/catalog.html',
        'func'   => 'getBoardCatalogHandler',
        'options' => array(
          'cacheSettings' => array(
            'files' => array(
              // theme is also would affect this caching
              'templates/header.tmpl', // wrapContent
              'templates/footer.tmpl', // wrapContent
              'templates/mixins/board_header.tmpl', // board_portal
              'templates/mixins/board_footer.tmpl', // board_portal
              'templates/mixins/post_detail.tmpl', // renderPost
              'templates/mixins/post_actions.tmpl', // renderPostActions
            ),
          ),
        ),
      ),
      'settings' => array(
        // need to pass :uri into the script somehow...
        // also how will the non-router version know?
        'route'  => '/:uri/board_settings.php',
        'func'   => 'getBoardSettingsHandler',
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