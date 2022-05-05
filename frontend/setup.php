<?php
// pages/ and static/ both use setup.php
// so it kind of makes sense to have it in frontend/ instead of frontend_lib/

// no syscalls needed to get the current time
$now = $_SERVER['REQUEST_TIME_FLOAT'];
// cache of settings
$board_settings = false;

// load frontend config
include 'config.php';

if (DEV_MODE) {
  ini_set('display_errors', true);
  error_reporting(E_ALL);
}

// to override BASE_HREF constant for static/ and pages/
// it's strips out the auto detected base and fixes it towards the frontend webroot base
// base tag takes care to it everywhere but the redirects...
global $BASE_HREF;
$BASE_HREF = BASE_HREF;

// set up backend url, cache

// if OPTIONS

// dispatch form data through post processing pipeline
// well initially I think we'll just have the form post to the backend directly
// couldn't do that because we need to navigate the user to the correct place
// well the backend could...

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

require '../common/lib.modules.php'; // module functions and classes

require '../common/lib.pipeline.php';
// we could move these into a pipelines.php file...

require 'pipelines.php';

// frontend libraries
require '../frontend_lib/lib/lib.http.php'; // comms lib
require '../frontend_lib/lib/lib.backend.php'; // comms lib
ldr_require('../frontend_lib/lib/lib.template.php'); // template functions
ldr_require('../frontend_lib/lib/lib.handler.php'); // output functions
require '../frontend_lib/lib/lib.captcha.php'; // load captcha infrastructure
require '../frontend_lib/lib/lib.files.php'; // file upload functions
require '../frontend_lib/lib/lib.form.php'; // form helper
require '../frontend_lib/lib/lib.perms.php'; // permission helper
require '../frontend_lib/lib/lib.actions.php'; // UI helper
// structures
require '../frontend_lib/lib/nav.php'; // nav structure
require '../frontend_lib/lib/expand.php'; // maybe more of a lib...
require '../frontend_lib/lib/middlewares.php';

// frontend handlers

// mixins
// it would be nice to scope these somehow...
require '../frontend_lib/handlers/mixins/board_portal.php';
// maybe only include if SID is set?
require '../frontend_lib/handlers/mixins/board_settings_portal.php';
require '../frontend_lib/handlers/mixins/admin_portal.php';
require '../frontend_lib/handlers/mixins/global_portal.php';
require '../frontend_lib/handlers/mixins/user_portal.php';
require '../frontend_lib/handlers/mixins/post_renderer.php';
require '../frontend_lib/handlers/mixins/post_form.php';
require '../frontend_lib/handlers/mixins/post_actions.php';
require '../frontend_lib/handlers/mixins/tabs.php'; // maybe more of a lib...

registerPackages();

function js_add_script($pkg, $script, $options = false) {
  extract(ensureOptions(array(
    'orderConstraints' => false,
  ), $options));

  $bsn = new pipeline_module(PIPELINE_SITE_END_SCRIPTS . '_' . $pkg->name . '_' . $script);
  $bsn->attach(PIPELINE_SITE_END_SCRIPTS,
    function(&$io, $options = false) use ($pkg, $script) {
      $io['scripts'][] = array('module' => $pkg->name, 'script' => $script);
  });

}

?>