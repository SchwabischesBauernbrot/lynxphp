<?php

include '../common/post_vars.php';

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

$pipelines = array();

function definePipeline($constant, $str) {
  global $pipelines;
  define($constant, $str);
  $pipelines[$str] = new pipeline_registry;
}

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

// I could move the PIPELINE_ prefix into the definePipeline function
// but then you couldn't locate these in grep
definePipeline('PIPELINE_BOARD_HEADER_TMPL',  'board_header_tmpl');
definePipeline('PIPELINE_BOARD_NAV',          'board_nav');
definePipeline('PIPELINE_BOARD_DETAILS_TMPL', 'board_details_tmpl');
definePipeline('PIPELINE_BOARD_SETTING_NAV',  'board_setting_nav');
definePipeline('PIPELINE_BOARD_SETTING_TMPL', 'board_setting_tmpl');

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
// structures
include 'lib/nav.php'; // nav structure
include 'lib/middlewares.php';


// frontend handlers
include 'handlers/mixins/board_header.php';
include 'handlers/mixins/board_nav.php';

include 'handlers/homepage.php';
include 'handlers/login.php';
include 'handlers/signup.php';
include 'handlers/control_panel.php';
include 'handlers/boards.php';

$req_method = getServerField('REQUEST_METHOD', 'GET');
$req_path   = getServerField('PATH_INFO');

$packages = array();
registerPackageGroup('board');
// build routes (and activate frontend_handlers.php)
foreach($packages as $pkg) {
  $pkg->buildFrontendRoutes($router, $req_method);
}

// should a handler set a variables (data structure)
// or define a set of functions
// functions can be variables...

// FIXME: we should be getting page conent and wrapping it here...
// FIXME: move into routes and the caching layer can go here too
$router->get('', function() {
  homepage();
});
$router->get('/boards.php', function() {
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
  $files = array();
  if (isset($_FILES)) {
    if (is_array($_FILES['file']['tmp_name'])) {
      echo "detected multiple files<br>\n";
      foreach($_FILES['file']['tmp_name'] as $i=>$path) {
        $res = sendFile($path, $_FILES['file']['type'][$i], $_FILES['file']['name'][$i]);
        // check for error?
        $files[] = $res;
      }
    } else {
      $res = sendFile($_FILES['file']['tmp_name'], $_FILES['file']['type'], $_FILES['file']['name']);
      // check for error?
      $files[] = $res;
    }
  }
  //print_r($files);
  // make post...
  if (empty($_POST['thread'])) {
    // new thead
    //echo "boardUri[$boardUri]<br>\n";
    $json = curlHelper(BACKEND_BASE_URL . 'lynx/newThread', array(
      // noFlag
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
$router->post('/login.php', function() {
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

$router->get('/logout.php', function() {
  getLogout();
});

// needs to go last...
$router->get('/:uri', function($request) {
  $boardUri = $request['params']['uri'];
  // maybe only redir if the board exists...
  redirectTo(BASE_HREF . $boardUri . '/');
});

$res = $router->exec($req_method, $req_path);
if (!$res) {
  http_response_code(404);
  echo "404 Page not found<br>\n";
}
?>
