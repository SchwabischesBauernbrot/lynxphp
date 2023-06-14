<?php
// REST API

require '../common/lib.loader.php';
ldr_require('../common/common.php');
ldr_require('../common/lib.http.server.php');

// read backend config
include 'config.php';

// if OPTIONS do CORS

// message queue

// connect to db
include 'lib/database_drivers/' . DB_DRIVER . '.php';
$driver_name = DB_DRIVER . '_driver';
$db = new $driver_name;

if (!$db->connect_db(DB_HOST, DB_USER, DB_PWD, DB_NAME)) {
  exit(1);
}

// maybe don't output SQL if devmode is off

//include '../common/lib.modules.php'; // module functions and classes
// transformations (x => y)
// access list (remove this, add this)
// change input, output (aren't these xforms tho)
// change processing is a little more sticky...


// have to be defined before we can enable modules:
// routers, db options, cache options, pipelines...

// build modules...
enableModulesType('models'); // online models from common/modules/base/models.php

include '../common/lib.pipeline.php';
include 'pipelines.php';

include 'lib/lib.board.php';
include 'lib/lib.ffmpeg.php';
include 'lib/middlewares.php';
include 'interfaces/boards.php';
include 'interfaces/posts.php';
include 'interfaces/replies.php';
include 'interfaces/threads.php';
include '../common/lib.post_tags.php';
include 'interfaces/users.php';
include 'interfaces/files.php';
include 'interfaces/sessions.php';
include 'interfaces/settings.php';

// FIXME: we need wrapper functions because, these should just be singleton/globals

// make a queue
// don't auto-detect, just get configuration
// FIXME: make configurable
include '../common/queue_implementations/db.php';
$queue_type_class = 'db' . '_queue_driver';
$queue = new $queue_type_class;

// set up workqueue
include '../common/workqueue.php';
$workqueue = new work_queue;

// do one unit of work...
// loop until specified time?
// probably should move into it's own route so it's more controlled
$cnt = $workqueue->getWorkCount();
while($cnt) {
  $workqueue->getWork();
  $cnt--;
  if ($cnt % 100 === 0) echo "$cnt left\n";
}

?>
