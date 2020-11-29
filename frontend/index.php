<?php

// load frontend config
include 'config.php';

// set up backend url, cache

// if OPTIONS

// dispatch form data through post processing pipeline
// well initially I think we'll just have the form post to the backend directly
// couldn't do that because we need to navigate the user to the correct place
// well the backend could...

$router = include '../common/router.php';

include '../common/post_vars.php';
if (!defined('BASE_HREF')) {
  define('BASE_HREF', dirname(getServerField('SCRIPT_NAME', __FILE__)) . '/');
}

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
$pipelines = array(
  'boardHeaderTmpl' =>new pipeline_registry,
  'boardNav' => new pipeline_registry,
  'boardSettingNav' => new pipeline_registry,
  'boardSettingTmpl' => new pipeline_registry,
  'boardDetailsTmpl' => new pipeline_registry,
);
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
include 'lib/lib.cache.php'; // memoization functions
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

// FIXME: function('board/banners');
include '../common/modules/board/banners/frontend_handlers.php';

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

$router->exec(getServerField('REQUEST_METHOD', 'GET'), getServerField('PATH_INFO'));
?>
