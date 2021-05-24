<?php
$now = microtime(true);
$board_settings = false;

// load frontend config
include 'config.php';

if (DEV_MODE) {
  ini_set('display_errors', true);
  error_reporting(E_ALL);
}

global $BASE_HREF;
$BASE_HREF = BASE_HREF;

// set up backend url, cache

// if OPTIONS

// dispatch form data through post processing pipeline
// well initially I think we'll just have the form post to the backend directly
// couldn't do that because we need to navigate the user to the correct place
// well the backend could...

$router = include '../common/router.php';
$router->defaultContentType = 'text/html';

// connect to scatch
include '../common/scratch_implementations/' . SCRATCH_DRIVER . '.php';
$scratch_type_class = SCRATCH_DRIVER . '_scratch_driver';
$scratch = new $scratch_type_class;


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
$frontEndPipelines = array(
  'PIPELINE_HOMEPAGE_BOARDS_FIELDS',

  'PIPELINE_BOARD_HEADER_TMPL',
  'PIPELINE_BOARD_FOOTER_TMPL',
  'PIPELINE_BOARD_NAV',
  'PIPELINE_BOARD_STICKY_NAV',
  'PIPELINE_BOARD_DETAILS_TMPL',
  'PIPELINE_BOARD_SETTING_NAV',
  'PIPELINE_BOARD_SETTING_TMPL',
  'PIPELINE_BOARD_SETTING_GENERAL',

  'PIPELINE_FORM_CAPTCHA',
  'PIPELINE_FORM_WIDGET_THEMETHUMBNAILS',

  'PIPELINE_POST_PREPROCESS',
  'PIPELINE_POST_POSTPREPROCESS',
  'PIPELINE_POST_TEXT_FORMATTING',
  'PIPELINE_POST_FORM_FIELDS',
  'PIPELINE_POST_FORM_OPTIONS',
  'PIPELINE_POST_FORM_TAGS',
  'PIPELINE_POST_FORM_VALUES',
  'PIPELINE_POST_VALIDATION',

  'PIPELINE_ADMIN_NAV',
  'PIPELINE_ADMIN_HEADER_TMPL',
  'PIPELINE_ADMIN_SETTING_GENERAL',

  'PIPELINE_GLOBALS_NAV',
  'PIPELINE_GLOBALS_HEADER_TMPL',

  // SETTINGS be included in the next 2?
  'PIPELINE_USER_NAV',
  'PIPELINE_USER_HEADER_TMPL',

  'PIPELINE_SITE_HEAD',
  // we can have one pipeline adjust all this data
  // and likely would be less overhead
  'PIPELINE_SITE_FOOTER_HEADER',
  'PIPELINE_SITE_FOOTER_NAV',
  'PIPELINE_SITE_FOOTER_FOOTER',
  'PIPELINE_SITE_END_HTML',

  'PIPELINE_AFTER_WORK',
);

definePipelines($frontEndPipelines);

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
include '../frontend_lib/lib/lib.http.php'; // comms lib
include '../frontend_lib/lib/lib.backend.php'; // comms lib
include '../frontend_lib/lib/lib.handler.php'; // output functions
include '../frontend_lib/lib/lib.files.php'; // file upload functions
include '../frontend_lib/lib/lib.form.php'; // form helper
// structures
include '../frontend_lib/lib/nav.php'; // nav structure
include '../frontend_lib/lib/middlewares.php';

// frontend handlers

// mixins
include '../frontend_lib/handlers/mixins/board_portal.php';
include '../frontend_lib/handlers/mixins/admin_portal.php';
include '../frontend_lib/handlers/mixins/global_portal.php';
include '../frontend_lib/handlers/mixins/user_portal.php';
include '../frontend_lib/handlers/mixins/post_renderer.php';
include '../frontend_lib/handlers/mixins/post_form.php';
include '../frontend_lib/handlers/mixins/post_actions.php';
include '../frontend_lib/handlers/mixins/tabs.php'; // maybe more of a lib...


// handlers
include '../frontend_lib/handlers/login.php';
//include '../frontend_lib/handlers/signup.php';
include '../frontend_lib/handlers/control_panel.php';
include '../frontend_lib/handlers/boards.php';
include '../frontend_lib/handlers/admin.php';
include '../frontend_lib/handlers/global.php';

registerPackages();
// build routes (and activate frontend_handlers.php)
foreach($packages as $pkg) {
  $pkg->buildFrontendRoutes($router, $req_method);
}

function formatBytes($bytes, $precision = 2) {
  $units = array('B', 'KB', 'MB', 'GB', 'TB');

  $bytes = max($bytes, 0);
  $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
  $pow = min($pow, count($units) - 1);

  // Uncomment one of the following alternatives
  //$bytes /= pow(1024, $pow);
  $bytes /= (1 << (10 * $pow));

  return round($bytes, $precision) . ' ' . $units[$pow];
}

if ($router->isTooBig()) {
  if (!$sentBump) {
    echo '<div style="height: 40px;"></div>', "\n";
  }
  wrapContent('This POST has too much data [' . formatBytes($_SERVER['CONTENT_LENGTH']). '] for this server [' . formatBytes($router->max_length) . '], try sending less data.');
  exit();
}


// should a handler set a variables (data structure)
// or define a set of functions
// functions can be variables...

function getRoute($idealUrl, $noRewrite) {
  // FIXME: get config value to set this
  if (0) {
    return $noRewrite;
  } else {
    return $idealUrl;
  }
}

// FIXME: we should be getting page content and wrapping it here...
// FIXME: move into routes and the caching layer can go here too
// but each handle is best to determine what portals need to be wrapped
$router->post(getRoute('/boards', '/boards.php'), function() {
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
$router->get(getRoute('/overboard', '/overboard.php'), function() {
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
  global $pipelines, $max_length;
  $boardUri = $request['params']['uri'];

  /*
  echo '<pre>_POST: ', print_r($_POST, 1), "</pre>\n";
  echo "max_length[$max_length]<br>\n";
  echo '<pre>_SERVER: ', print_r($_SERVER, 1), "</pre>\n";
  echo '<pre>_FILES: ', print_r($_FILES, 1), "</pre>\n";
  */

  $res = processFiles();
  //echo '<pre>res: ', print_r($res, 1), "</pre>\n";
  $files = isset($res['handles']['files']) ? $res['handles']['files'] : array();
  //echo '<pre>files: ', print_r($files, 1), "</pre>\n";

  $endpoint = 'lynx/newThread';
  global $BASE_HREF;
  $redir = $BASE_HREF . $boardUri . '/';
  $headers = array('HTTP_X_FORWARDED_FOR' => getip(), 'sid' => getCookie('session'));
  $row = array(
    // noFlag
    'name'     => getOptionalPostField('name'),
    'email'    => getOptionalPostField('email'),
    'message'  => getOptionalPostField('message'),
    'subject'  => getOptionalPostField('subject'),
    'boardUri' => $boardUri,
    'password' => getOptionalPostField('postpassword'),
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

/*
$signupRoute = getRoute('/signup', '/signup.php');
$router->get($signupRoute, function() {
  getSignup();
});
$router->post($signupRoute, function() {
  postSignup();
});
*/

$loginRoute = getRoute('/forms/login', '/login.php');
$router->get($loginRoute, function() {
  getLogin();
});
$router->post($loginRoute, function() {
  postLogin();
});

$router->get(getRoute('/control_panel', '/control_panel.php'), function() {
  getControlPanel();
});
$router->get(getRoute('/account', '/account.php'), function() {
  getAccountSettings();
});

$change_userpassRoute = getRoute('/account/change_userpass', '/account/change_userpass.php');
$router->get($change_userpassRoute, function() {
  getChangeUserPass();
});
$router->post($change_userpassRoute, function() {
  postChangeUserPass();
});

$change_emailRoute = getRoute('/account/change_email', '/account/change_email.php');
$router->get($change_emailRoute, function() {
  getChangeEmail();
});
$router->post($change_emailRoute, function() {
  postChangeEmail();
});

$cbRoute = getRoute('/create_board', '/create_board.php');
$router->get($cbRoute, function() {
  getCreateBoard();
});
$router->post($cbRoute, function() {
  postCreateBoard();
});

$router->get(getRoute('/admin', '/admin.php'), function() {
  getAdminPage();
});
$router->get(getRoute('/admin/modules', '/admin/modules.php'), function() {
  getAdminModulesPage();
});
$router->get(getRoute('/admin/install', '/admin/install.php'), function() {
  getAdminInstallPage();
});

$router->get(getRoute('/global', '/global.php'), function() {
  getGlobalPage();
});

$router->get(getRoute('/logout', '/logout.php'), function() {
  getLogout();
});

// needs to go last...
$router->get('/:uri', function($request) {
  $boardUri = $request['params']['uri'];
  if ($boardUri) {
    $boardUri .= '/';
  }
  // FIXME: only redir if the board exists...
  global $BASE_HREF;
  redirectTo($BASE_HREF . $boardUri);
});

?>