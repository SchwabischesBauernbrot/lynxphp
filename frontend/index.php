<?php
$now = microtime(true);

include '../common/post_vars.php';

// REQUEST_URI seems to be more accruate in NGINX
$req_path   = getServerField('PATH_INFO', getServerField('REQUEST_URI'));
$req_method = getServerField('REQUEST_METHOD', 'GET');

if (($req_path !== '/login.php' && $req_method !== 'POST') || $req_path === '/logout.php') {
  echo '<div style="height: 40px;"></div>', "\n"; flush();
}

// load frontend config
include 'config.php';

// set up backend url, cache

// if OPTIONS

// dispatch form data through post processing pipeline
// well initially I think we'll just have the form post to the backend directly
// couldn't do that because we need to navigate the user to the correct place
// well the backend could...

$router = include '../common/router.php';

// nav, pages
// routes make a page exist
// but a page could have multiple routes
// so page is like a handler...
// an each page/handler needs a static page output...
  // so there's some magic between apache/nginx access and non-rewrite...
// and then a handler has queries, templates and transformations...
// but page as a concept; homepage, boards, board page, thread page etc
// page specific nav?

// also page's content could be a nav...
// so nav is just a data-driven template element
// so queries are more than db/backend, but general arrays too
// so routers <=> handle mappings can get crazy
// and then the links themselves in the templates...

// and then there's the js side vs non-js side
// js settings...
//

include '../common/lib.modules.php'; // module functions and classes

// pipelines...
// - content page?
// template pipelines
// - siteNav (logged in vs logged out?)
// - boardNav
// - threadNav
// - postNav
// - panelNav
// - catalog tile
// - page tmpl
// - thread tmpl
// - reply tmpl
// - boardListing
// - board search
// - boardSettingTmpl

include '../common/lib.pipeline.php';
// we could move these into a pipelines.php file...

// I could move the PIPELINE_ prefix into the definePipeline function
// but then you couldn't locate these in grep
definePipeline('PIPELINE_HOMEPAGE_BOARDS_FIELDS',  'homepage_boards_fields');

definePipeline('PIPELINE_BOARD_HEADER_TMPL',  'board_header_tmpl');
definePipeline('PIPELINE_BOARD_NAV',          'board_nav');
definePipeline('PIPELINE_BOARD_DETAILS_TMPL', 'board_details_tmpl');
definePipeline('PIPELINE_BOARD_SETTING_NAV',  'board_setting_nav');
definePipeline('PIPELINE_BOARD_SETTING_TMPL', 'board_setting_tmpl');
definePipeline('PIPELINE_BOARD_SETTING_GENERAL',  'board_setting_general');

definePipeline('PIPELINE_POST_PREPROCESS',  'post_preprocess');
definePipeline('PIPELINE_POST_POSTPREPROCESS',  'post_postpreprocess');
definePipeline('PIPELINE_POST_TEXT_FORMATTING',  'post_text_formatting');

definePipeline('PIPELINE_ADMIN_NAV',          'admin_nav');
definePipeline('PIPELINE_ADMIN_HEADER_TMPL',  'admin_heading_tmpl');
definePipeline('PIPELINE_ADMIN_SETTING_GENERAL',  'admin_setting_general');

definePipeline('PIPELINE_GLOBALS_NAV', 'globals_nav');
definePipeline('PIPELINE_GLOBALS_HEADER_TMPL', 'globals_heading_tmpl');

definePipeline('PIPELINE_USER_NAV', 'user_nav');
definePipeline('PIPELINE_USER_HEADER_TMPL', 'user_heading_tmpl');


// forms pipelines
// - newThreadForm
// - newReplyForm
// - boardSettingsForm
// handler pipelines (pipelines creating pipelines)
// well maybe each module should leave it's own pipeline?
// but what consequences does that mean for the eco-system...
// - login
// - logout

// frontend libraries
include 'lib/lib.http.php'; // comms lib
include 'lib/lib.backend.php'; // comms lib
include 'lib/lib.handler.php'; // output functions
include 'lib/lib.files.php'; // file upload functions
include 'lib/lib.form.php'; // form helper
// structures
include 'lib/nav.php'; // nav structure
include 'lib/middlewares.php';

// frontend handlers

// mixins
include 'handlers/mixins/board_header.php';
include 'handlers/mixins/board_nav.php';
include 'handlers/mixins/admin_portal.php';
include 'handlers/mixins/global_portal.php';
include 'handlers/mixins/user_portal.php';
include 'handlers/mixins/post_renderer.php';
include 'handlers/mixins/post_form.php';
include 'handlers/mixins/post_actions.php';

// handlers
include 'handlers/login.php';
include 'handlers/signup.php';
include 'handlers/control_panel.php';
include 'handlers/boards.php';
include 'handlers/admin.php';
include 'handlers/global.php';

$packages = array();
$packages['base'] = registerPackage('base');
registerPackageGroup('board');
registerPackageGroup('post');
registerPackageGroup('user');
registerPackageGroup('site');
// build routes (and activate frontend_handlers.php)
foreach($packages as $pkg) {
  $pkg->buildFrontendRoutes($router, $req_method);
}

// should a handler set a variables (data structure)
// or define a set of functions
// functions can be variables...

// FIXME: we should be getting page content and wrapping it here...
// FIXME: move into routes and the caching layer can go here too
$router->get('/boards.php', function() {
  getBoardsHandler();
});
// nginx support...
$router->get('/boards', function() {
  getBoardsHandler();
});

// FIXME: move into module
$router->get('/overboard.php', function() {
  getOverboardHandler();
});

$router->get('/:uri/', function($request) {
  $boardUri = $request['params']['uri'];
  getBoardThreadListing($boardUri);
});
$router->get('/:uri/page/:page', function($request) {
  $boardUri = $request['params']['uri'];
  $page = $request['params']['page'] ? $request['params']['page'] : 1;
  getBoardThreadListing($boardUri, $page);
});

$router->get('/:uri/catalog', function($request) {
  $boardUri = $request['params']['uri'];
  getBoardCatalogHandler($boardUri);
});
$router->get('/:uri/settings', function($request) {
  $boardUri = $request['params']['uri'];
  getBoardSettingsHandler($boardUri);
});

$router->get('/:uri/thread/:num', function($request) {
  $boardUri = $request['params']['uri'];
  $threadNum = str_replace('.html', '', $request['params']['num']);
  getThreadHandler($boardUri, $threadNum);
});


$router->post('/:uri/post', function($request) {
  $boardUri = $request['params']['uri'];
  // valid board name
  // validate results
  $res = processFiles();
  $files = isset($res['handles']['file']) ? $res['handles']['file'] : array();

  // make post...
  if (empty($_POST['thread'])) {
    // new thead
    //echo "boardUri[$boardUri]<br>\n";
    $json = curlHelper(BACKEND_BASE_URL . 'lynx/newThread', array(
      // noFlag
      'name'     => getOptionalPostField('name'),
      'email'    => getOptionalPostField('email'),
      'message'  => $_POST['message'],
      'subject'  => getOptionalPostField('subject'),
      'boardUri' => $boardUri,
      'password' => $_POST['postpassword'],
      // captcha
      'spoiler'  => empty($_POST['spoiler_all']) ? '' : $_POST['spoiler_all'],
      'files'    => json_encode($files),
      // flag
    ), array('HTTP_X_FORWARDED_FOR' => getip(), 'sid' => getCookie('session')));
    //echo "json[$json]<Br>\n";
    $result = json_decode($json, true);
    if ($result === false) {
      wrapContent('Post Error: <pre>' . $json . '</pre>');
    }
    //echo "<pre>thread", print_r($result, 1), "</pre>\n";
    //return;
    if (is_numeric($result['data'])) {
      // success
      redirectTo(BASE_HREF . $boardUri . '/');
    } else {
      wrapContent('Post Error: ' . print_r($result, 1));
    }
  } else {
    // reply
    //echo "boardUri[$boardUri]<br>\n";
    $json = curlHelper(BACKEND_BASE_URL . 'lynx/replyThread', array(
      // noFlag
      'threadId' => $_POST['thread'],
      'name'     => getOptionalPostField('name'),
      'email'    => getOptionalPostField('email'),
      'message'  => $_POST['message'],
      'subject'  => getOptionalPostField('subject'),
      'boardUri' => $boardUri,
      'password' => $_POST['postpassword'],
      // captcha
      'spoiler'  => empty($_POST['spoiler_all']) ? '' : $_POST['spoiler_all'],
      // flag
      'files'    => json_encode($files),
    ), array('HTTP_X_FORWARDED_FOR' => getip(), 'sid' => getCookie('session')));
    //echo "json[$json]<Br>\n";
    $result = json_decode($json, true);
    // can't parse
    if ($result === false) {
      wrapContent('Post Error: <pre>' . $json . '</pre>');
    }
    //echo "<pre>reply", print_r($result, 1), "</pre>\n";
    //return;
    if (is_numeric($result['data'])) {
      // success
      redirectTo(BASE_HREF . $boardUri . '/thread/' . $_POST['thread']);
    } else {
      wrapContent('Post Error: ' . print_r($result, 1));
    }
  }
});

$router->get('/signup.php', function() {
  getSignup();
});
$router->post('/signup.php', function() {
  postSignup();
});
$router->get('/login.php', function() {
  getLogin();
});
$router->post('/forms/login', function() {
  postLogin();
});
$router->get('/control_panel.php', function() {
  getControlPanel();
});
$router->get('/create_board.php', function() {
  getCreateBoard();
});
$router->post('/create_board.php', function() {
  postCreateBoard();
});

$router->get('/admin.php', function() {
  getAdminPage();
});
$router->get('/admin/modules', function() {
  getAdminModulesPage();
});
$router->get('/admin/install', function() {
  getAdminInstallPage();
});

$router->get('/global.php', function() {
  getGlobalPage();
});

$router->get('/logout.php', function() {
  getLogout();
});

// needs to go last...
$router->get('/:uri', function($request) {
  $boardUri = $request['params']['uri'];
  if ($boardUri) {
    $boardUri .= '/';
  }
  // FIXME: only redir if the board exists...
  redirectTo(BASE_HREF . $boardUri);
});

$res = $router->exec($req_method, $req_path);
if (!$res) {
  http_response_code(404);
  echo "404 Page not found<br>\n";
}
?>
