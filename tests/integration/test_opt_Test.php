<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

final class test_opt_Test extends TestCase {
  public function testCheck(): void {
    $res = getExpectJson('opt/check');
    //print_r($check);
    $this->assertIsArray($res);
    $this->assertIsArray($res['meta']);
    $this->assertIsArray($res['data']);
    $this->assertSame('ok', $res['data']['check']);
  }

  /*
  public function testSession(): void {
    $res = getExpectJson('session');
    // we need to figure how to to create a user/session
    $this->assertIsArray($res);
    $this->assertIsArray($res['meta']);
    $this->assertSame(200, $res['meta']['code']);
  }
  */

  public function testNoSession(): void {
    $res = getExpectJson('opt/session');
    $this->assertIsArray($res);
    $this->assertIsArray($res['meta']);
    $this->assertSame(401, $res['meta']['code']);
    $this->assertSame('No Session', $res['meta']['err']);
  }

  public function testBoardsJson(): array {
    $res = getExpectJson('opt/boards.json');
    $this->assertIsArray($res);
    $this->assertIsArray($res['meta']);
    $this->assertSame(200, $res['meta']['code']);
    $this->assertIsArray($res['data']);
    return $res['data'];
  }

  /**
   * @depends testBoardsJson
   */
  public function testBoardPage(array $boards): array {
    if (!count($boards)) {
      $this->assertIsArray(array());
      return array();
    }
    shuffle($boards);
    $board = array_shift($boards);
    $boardPage = getExpectJson('opt/boards/' . $board['uri'] . '/1.json');
    $this->assertIsArray($boardPage);
    $this->assertSame(200, $boardPage['meta']['code']);
    $this->assertIsArray($boardPage['data']['board']);
    // make sure we fetched the right board
    $this->assertSame($board['uri'], $boardPage['data']['board']['uri']);
    $this->assertIsArray($boardPage['data']['page1']);
    $threads = array();
    if (count($boardPage['data']['page1'])) {
      $threads = $boardPage['data']['page1'][0]['posts'];
    }
    return array(
      'boards'  => $boards,
      'threads' => $threads,
    );
  }

  /**
   * @depends testBoardPage
   */
  public function testBoardThread(array $arr): void {
    // need a thread to test with...
    if (!count($arr['threads'])) {
      return;
    }
    $boards = $arr['boards'];
    if (!count($boards)) return;
    shuffle($boards);
    $board = array_shift($boards);
    $boardPage = getExpectJson('opt/' . $board['uri'] . '/thread/' . $arr['threads'][0]['no']);
    $this->assertIsArray($boardPage);
    $this->assertSame(200, $boardPage['meta']['code']);
    $this->assertIsArray($boardPage['data']);
    // make sure we fetched the right board
    $this->assertSame($board['uri'], $boardPage['data']['uri']);
    $this->assertIsArray($boardPage['data']['posts']);
    //print_r($boardPage['data']);
    //$this->assertSame($arr['threads'][0]['no'], $boardPage['data']['posts'][0]['no']);
  }

  /*
  public function testMyBoards(): void {
    $res = getExpectJson('/myBoards');
    $this->assertIsArray($res);
    $this->assertSame(200, $res['meta']['code']);
  }
  */

  public function testNoMyBoards(): void {
    $res = getExpectJson('/opt/myBoards');
    $this->assertIsArray($res);
    $this->assertIsArray($res['meta']);
    $this->assertSame(401, $res['meta']['code']);
    $this->assertSame('No Session', $res['meta']['err']);
  }

  /**
   * @depends testBoardsJson
   */
  public function testBoard(array $boards): void {
    if (!count($boards)) {
      $this->assertIsArray(array());
      return;
    }
    shuffle($boards);
    $board = array_shift($boards);
    $res = getExpectJson('/opt/' . $board['uri']);
    $this->assertIsArray($res);
    $this->assertIsArray($res['meta']);
    $this->assertSame(200, $res['meta']['code']);
    $this->assertIsArray($res['data']);
    $this->assertSame($board['uri'], $res['data']['uri']);
  }

}