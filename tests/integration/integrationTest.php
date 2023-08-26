<?php declare(strict_types=1);

function deleteBoard($boardUri) {
  global $db, $models;

  // delete files table
  $fm = getPostFilesModel($boardUri);
  $db->delete($fm, array());

  // delete posts table
  $pm = getPostsModel($boardUri);
  $db->delete($pm, array());

  // delete board row
  $db->delete($models['board'], array('criteria'=>array('uri' => $boardUri)));
}

if (!defined('IN_TEST')) {
  // not loaded by test.php
  // allow manual run with phpunit direct
  define('IN_TEST', true);

  $host = getenv('USE_CONFIG');
  // argument overrides environment

  // ./phpunit-nightly.phar tests/ dev.wrongthink.net
  if (isset($GLOBALS['argv'][2])) {
    $host = $GLOBALS['argv'][2];
  }
  // ./phpunit-nightly.phar --testdox tests/ dev.wrongthink.net
  // 3 will override $host if --testdox is set
  if (isset($GLOBALS['argv'][3])) {
    $host = $GLOBALS['argv'][3];
  }

  if ($host) {
    $_SERVER['HTTP_HOST'] = $host;
  }

  global $module_base;
  $module_base = 'common/modules/';

  chdir('frontend');
  require '../common/lib.loader.php';
  ldr_require('../common/common.php');
  ldr_require('../common/lib.http.server.php');
  // how do we get the correct server name?
  // there is only one config on the frontend side...
  include 'config.php';
  chdir('..');
}

chdir('frontend');
require '../common/lib.http.php';
include '../frontend_lib/lib/lib.perms.php'; // permission helper
require '../frontend_lib/lib/lib.backend.php';
chdir('..');

function wrapContent($content) {
  echo "wrapContent called[$content]\n";
}

//if (!function_exists('redirectTo')) {
function redirectTo($url) {
  echo "redirectTo called[$url]\n";
}
//}

/*
use PHPUnit\Framework\TestSuite;
use PHPUnit\TextUI\TestRunner;
//use PHPUnit\TextUI\Command;
//use PHPUnit_TextUI

//registerTestPackageGroup('base');
include 'common/modules/base/fe/tests/test_base_Test.php';

//$command = new Command();
//$command->run(['phpunit', 'tests']);



//$test = new test_base_Test;
//$test->run();

//$test = new TestSuite();
//$test->addTestSuite(test_base_Test::class);
//$result = $test->run();

//$phpunit = new TestRunner;
//$phpunit->dorun($suite);
*/
/*
$suite = new TestSuite('test_base_Test');
$suite->run();
*/
//TestRunner::run($suite);

function usesSendResponse($t, $res) {
  $t->assertIsArray($res);
  $t->assertArrayHasKey('meta', $res);
  $t->assertIsArray($res['meta']);
  $t->assertArrayHasKey('code', $res['meta']);
  $t->assertArrayHasKey('data', $res);
  $t->assertIsArray($res['data']);
}

use PHPUnit\Framework\TestCase;

final class integrationTest extends TestCase {
  public function testTestSetup(): void {
    $this->assertIsArray(array());
  }
}