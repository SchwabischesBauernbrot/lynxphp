<?php
$now = microtime(true);

include '../common/post_vars.php';

// REQUEST_URI seems to be more accruate in NGINX
$req_path   = getServerField('PATH_INFO', getServerField('REQUEST_URI'));
$req_method = getServerField('REQUEST_METHOD', 'GET');

//echo "req_path[$req_path]<br>\n";
if ((($req_path !== '/login.php' && $req_method !== 'POST') || $req_path === '/logout.php') && strpos($req_path, '/.youtube') === false) {
  echo '<div style="height: 40px;"></div>', "\n"; flush();
}

// load frontend config
include 'config.php';

if (DEV_MODE) {
  ini_set('display_errors', true);
  error_reporting(E_ALL);
}

// work around nginx weirdness with PHP and querystrings
$isNginx = stripos(getServerField('SERVER_SOFTWARE'), 'nginx') !== false;
if ($isNginx && strpos($_SERVER['REQUEST_URI'], '?') !== false) {
  $parts = explode('?', $_SERVER['REQUEST_URI']);
  $querystring = $parts[1];
  $chunks = explode('&', $querystring);
  $qs = array();
  foreach($chunks as $c) {
    list($k, $v) = explode('=', $c);
    $qs[$k] = $v;
    if (empty($_GET[$k])) {
      $_GET[$k] = $v;
      $_REQUEST[$k] = $v;
    }
  }
  //print_r($qs);
}

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
definePipeline('PIPELINE_HOMEPAGE_BOARDS_FIELDS');

definePipeline('PIPELINE_BOARD_HEADER_TMPL');
definePipeline('PIPELINE_BOARD_FOOTER_TMPL');
definePipeline('PIPELINE_BOARD_NAV');
definePipeline('PIPELINE_BOARD_STICKY_NAV');
definePipeline('PIPELINE_BOARD_DETAILS_TMPL');
definePipeline('PIPELINE_BOARD_SETTING_NAV');
definePipeline('PIPELINE_BOARD_SETTING_TMPL');
definePipeline('PIPELINE_BOARD_SETTING_GENERAL');

definePipeline('PIPELINE_FORM_CAPTCHA');

definePipeline('PIPELINE_POST_PREPROCESS');
definePipeline('PIPELINE_POST_POSTPREPROCESS');
definePipeline('PIPELINE_POST_TEXT_FORMATTING');
definePipeline('PIPELINE_POST_FORM_FIELDS');
definePipeline('PIPELINE_POST_FORM_OPTIONS');
definePipeline('PIPELINE_POST_FORM_TAGS');
definePipeline('PIPELINE_POST_FORM_VALUES');
definePipeline('PIPELINE_POST_VALIDATION');

definePipeline('PIPELINE_ADMIN_NAV');
definePipeline('PIPELINE_ADMIN_HEADER_TMPL');
definePipeline('PIPELINE_ADMIN_SETTING_GENERAL');

definePipeline('PIPELINE_GLOBALS_NAV');
definePipeline('PIPELINE_GLOBALS_HEADER_TMPL');

definePipeline('PIPELINE_USER_NAV');
definePipeline('PIPELINE_USER_HEADER_TMPL');


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
include 'handlers/mixins/board_portal.php';
include 'handlers/mixins/admin_portal.php';
include 'handlers/mixins/global_portal.php';
include 'handlers/mixins/user_portal.php';
include 'handlers/mixins/post_renderer.php';
include 'handlers/mixins/post_form.php';
include 'handlers/mixins/post_actions.php';
include 'handlers/mixins/tabs.php'; // maybe more of a lib...


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
registerPackageGroup('protection');
// build routes (and activate frontend_handlers.php)
foreach($packages as $pkg) {
  $pkg->buildFrontendRoutes($router, $req_method);
}

// should a handler set a variables (data structure)
// or define a set of functions
// functions can be variables...

// FIXME: we should be getting page content and wrapping it here...
// FIXME: move into routes and the caching layer can go here too
$router->post('/boards.php', function() {
  getBoardsHandler();
});
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
  global $pipelines;
  $boardUri = $request['params']['uri'];

  $res = processFiles();
  $files = isset($res['handles']['file']) ? $res['handles']['file'] : array();

  $endpoint = 'lynx/newThread';
  $redir = BASE_HREF . $boardUri . '/';
  $headers = array('HTTP_X_FORWARDED_FOR' => getip(), 'sid' => getCookie('session'));
  $row = array(
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
  );
  if (!empty($_POST['thread'])) {
    $row['threadId'] = $_POST['thread'];
    $endpoint = 'lynx/replyThread';
    $redir .= 'thread/' . $_POST['thread'];
  }
  $io = array(
    'boardUri' => $boardUri,
    'endpoint' => $endpoint,
    'headers'  => $headers,
    'values'   => $row,
    'redir'    => $redir,
    'error'    => false,
    'redirNow' => false,
  );
  // validate results
  $pipelines[PIPELINE_POST_VALIDATION]->execute($io);
  //print_r($io);
  $row     = $io['values'];
  $headers = $io['headers'];
  $redir   = $io['redir'];
  if (!empty($io['error'])) {
    echo "error";
    //print_r($io);
    wrapContent($io['error']);
    return;
  }
  if (!empty($io['redirNow'])) {
    echo "redirNow";
    redirectTo($io['redirNow']);
    return;
  }

  // make post...
  $json = curlHelper(BACKEND_BASE_URL . $endpoint, $row, $headers);
  // can't use this because we need better handling of results...
  //$result = expectJson($json, $endpoint)
  //echo "json[$json]<br>\n";
  $result = json_decode($json, true);
  if ($result === false) {
    wrapContent('Post Error: <pre>' . $json . '</pre>');
  } else {
    //echo "<pre>", $endpoint, print_r($result, 1), "</pre>\n";
    //echo "redir[$redir]<br>\n";
    //return;
    if (is_numeric($result['data'])) {
      // success
      redirectTo($redir);
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