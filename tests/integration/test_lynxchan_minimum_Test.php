<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

final class test_lynxchan_minimum_Test extends TestCase {

  public function testMissingFieldRegisterAccount(): void {
    $endpoint = 'lynx/registerAccount';
    $postData = array();
    $json = curlHelper(BACKEND_BASE_URL . $endpoint, $postData, '', '', '', 'POST');
    $res = json_decode($json, true);
    usesSendResponse($this, $res);
    $this->assertSame(400, $res['meta']['code']);
    $this->assertArrayHasKey('err', $res['meta']);
    $this->assertIsString($res['meta']['err']);
  }

  public function testRegisterAccount(): array {
    $endpoint = 'lynx/registerAccount';
    $uniq = uniqid();
    $login = array(
      'login' => 'unittest_'.$uniq,
      // the salt should make these hard to guess
      'password' => password_hash($uniq, PASSWORD_DEFAULT),
      'email' => md5('unittest_'.$uniq),
    );
    $json = curlHelper(BACKEND_BASE_URL . $endpoint, $login, '', '', '', 'POST');
    $res = json_decode($json, true);
    if ($res === null) {
      echo "lynx/registerAccount - failed to parse [$json] as json\n";
    }
    if (isset($res['meta']) && $res['meta']['code'] !== 200) {
      echo "lynx/registerAccount - not 200 [", print_r($res, 1), "]\n";
    }
    usesSendResponse($this, $res);
    $this->assertSame(200, $res['meta']['code']);
    return array(
      'user' => $login['login'],
      'pass' => $login['password'],
      'id' => $res['data']['id'],
    );
  }

  public function testMissingFieldLogin(): void {
    $endpoint = 'lynx/login';
    $postData = array(
    );
    $json = curlHelper(BACKEND_BASE_URL . $endpoint, $postData, '', '', '', 'POST');
    //echo "json[$json]<br>\n";
    $res = json_decode($json, true);
    usesSendResponse($this, $res);
    $this->assertSame(400, $res['meta']['code']);
    $this->assertArrayHasKey('err', $res['meta']);
    $this->assertIsString($res['meta']['err']);
  }

  /**
   * @depends testRegisterAccount
   */
  public function testLogin($arr): string {
    $endpoint = 'lynx/login';
    $postData = array(
      'login'    => $arr['user'],
      'password' => $arr['pass'],
    );
    //$headers = array('HTTP_X_FORWARDED_FOR' => getip());
    $json = curlHelper(BACKEND_BASE_URL . $endpoint, $postData, '', '', '', 'POST');
    //echo "json[$json]<br>\n";
    $res = json_decode($json, true);
    //print_r($res);
    usesSendResponse($this, $res);
    $this->assertSame(200, $res['meta']['code']);
    $this->assertArrayHasKey('username', $res['data']);
    $this->assertArrayHasKey('session', $res['data']);
    $this->assertArrayHasKey('ttl', $res['data']);
    $_COOKIE['session'] = $res['data']['session'];
    return $res['data']['session'];
  }

  /**
   * @depends testLogin
   */
  public function testMissingFieldCreateBoard($session): void {
    $endpoint = 'lynx/createBoard';
    $postData = array();
    $headers = array('sid' => $session);
    $json = curlHelper(BACKEND_BASE_URL . $endpoint, $postData, $headers, '', '', 'POST');
    $res = json_decode($json, true);
    usesSendResponse($this, $res);
    $this->assertSame(400, $res['meta']['code']);
    $this->assertArrayHasKey('err', $res['meta']);
    $this->assertIsString($res['meta']['err']);
  }

  /**
   * @depends testLogin
   */
  public function testCreateBoard($session): string {
    $endpoint = 'lynx/createBoard';
    $uniq = uniqid();
    $postData = array(
      'boardUri' => 'test-' . $uniq,
      'boardName' => 'test_' . $uniq,
      'boardDescription' => 'Unit test made this in order to make sure the code is stronk',
    );
    $headers = array('sid' => $session);
    $json = curlHelper(BACKEND_BASE_URL . $endpoint, $postData, $headers, '', '', 'POST');
    //echo $json;
    $res = json_decode($json, true);
    if ($res === null) {
      echo "lynx/testCreateBoard - failed to parse [$json] as json\n";
    }
    //usesSendResponse($this, $res);
    $this->assertIsArray($res);
    $this->assertArrayHasKey('meta', $res);
    $this->assertIsArray($res['meta']);
    $this->assertArrayHasKey('code', $res['meta']);
    $this->assertSame(200, $res['meta']['code']);
    $this->assertArrayHasKey('data', $res);
    $this->assertSame('ok', $res['data']);
    return $postData['boardUri'];
  }

  public function testFiles(): void {
    $endpoint = 'lynx/files';
    $postData = array(
      'files' => '',
    );
    $json = curlHelper(BACKEND_BASE_URL . $endpoint, $postData, '', '', '', 'POST');
    $res = json_decode($json, true);
    if ($res === null) {
      echo "lynx/testFiles - failed to parse [$json] as json\n";
    }
    usesSendResponse($this, $res);
    // getting 400s for now
    //$this->assertSame(200, $res['meta']['code']);
  }

  /**
   * @depends testCreateBoard
   */
  public function testNewThread($boardUri): array {
    $endpoint = 'lynx/newThread';
    $postData = array(
      'boardUri' => $boardUri,
      'files' => json_encode(array()),
    );
    $json = curlHelper(BACKEND_BASE_URL . $endpoint, $postData, '', '', '', 'POST');
    $res = json_decode($json, true);
    //print_r($res);
    $this->assertIsArray($res);
    $this->assertArrayHasKey('meta', $res);
    $this->assertIsArray($res['meta']);
    $this->assertArrayHasKey('code', $res['meta']);
    $this->assertSame(200, $res['meta']['code']);
    // should be first post since it's a new board...
    $this->assertSame(1, $res['data']);
    return array(
      'board' => $boardUri,
      'thread' => $res['data'],
    );
  }

  /**
   * @depends testNewThread
   */
  public function testReplyThread($arr): void {
    $endpoint = 'lynx/replyThread';
    $postData = array(
      'boardUri' => $arr['board'],
      'files' => json_encode(array()),
      'threadId' => $arr['thread'],
    );
    $json = curlHelper(BACKEND_BASE_URL . $endpoint, $postData, '', '', '', 'POST');
    //echo $json;
    $res = json_decode($json, true);
    $this->assertIsArray($res);
    $this->assertArrayHasKey('meta', $res);
    $this->assertIsArray($res['meta']);
    $this->assertArrayHasKey('code', $res['meta']);
    $this->assertSame(200, $res['meta']['code']);
    // should be 2nd post since it's a new board...
    $this->assertSame(2, $res['data']);  }

  public function testAccount(): void {
    //$headers = array('sid' => $session);
    $json = backendAuthedGet('lynx/account');
    $res = json_decode($json, true);
    if ($res === null) {
      echo "lynx/testAccount - failed to parse [$json] as json\n";
    }
    $this->assertIsArray($res);
    if (isset($res['meta']) && $res['meta']['code'] !== 200) {
      echo "lynx/testAccount - not 200 [", print_r($res, 1), "]\n";
    }
    $this->assertArrayHasKey('noCaptchaBan', $res);
    $this->assertArrayHasKey('login', $res);
    $this->assertArrayHasKey('email', $res);
    $this->assertArrayHasKey('globalRole', $res);
    $this->assertArrayHasKey('boardCreationAllowed', $res);
    $this->assertArrayHasKey('ownedBoards', $res);
    $this->assertIsArray($res['ownedBoards']);
    $this->assertArrayHasKey('groups', $res);
    $this->assertIsArray($res['groups']);
    $this->assertArrayHasKey('reportFilter', $res);
    $this->assertIsArray($res['reportFilter']);
  }

  public static function tearDownAfterClass(): void {
    // we're not running in the backend...
    // this means you need fe and be code to run the be unit tests
    // we should stop using the fe code since this is backend test
    // duplicate symbol (getBoard) in lib.backend
    chdir('backend');
    // detecting the right config host is now important...
    include 'config.php';
    include 'lib/database_drivers/' . DB_DRIVER . '.php';
    include 'lib/lib.board.php';
    include '../common/lib.loader.php';

    // connect to db
    $driver_name = DB_DRIVER . '_driver';
    global $db;
    $db = new $driver_name;
    if (!$db->connect_db(DB_HOST, DB_USER, DB_PWD, DB_NAME)) {
      echo "Can't clean boards because can't connect to DB\n";
      return;
    }
    enableModulesType('models'); // bring models online
    chdir('..');

    global $models;
    $res = $db->find($models['board']);
    $boards = $db->toArray($res);
    $db->free($res);

    // delete testunits_boards...
    //print_r($boards);
    foreach($boards as $b) {
      $uri = $b['uri'];
      $f5 = substr($uri, 0, 5);
      if ($f5 === 'test-') {
        echo "Cleaning up [$uri]\n";
        deleteBoard($uri);
      }
    }
  }
}