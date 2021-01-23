<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

final class test_opt_Test extends TestCase {
  public function testCheck(): void {
    $res = getExpectJson('check');
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
    $res = getExpectJson('session');
    $this->assertIsArray($res);
    $this->assertIsArray($res['meta']);
    $this->assertSame(401, $res['meta']['code']);
    $this->assertSame('No Session', $res['meta']['err']);
  }

  public function testBoardsJson(): array {
    $res = getExpectJson('boards.json');
    $this->assertIsArray($res);
    $this->assertIsArray($res['meta']);
    $this->assertSame(200, $res['meta']['code']);
    $this->assertIsArray($res['data']);
    return $res['data'];
  }

  /**
   * @depends testBoardsJson
   */
  public function testBoardPage(array $boards): void {
    if (!count($boards)) return;
    shuffle($boards);
    $board = array_shift($boards);
    $boardPage = getExpectJson('boards/' . $board['uri'] . '/1.json');
    $this->assertIsArray($boardPage);
    $this->assertSame(200, $boardPage['meta']['code']);
    $this->assertIsArray($boardPage['data']['board']);
    // make sure we fetched the right board
    $this->assertSame($board['uri'], $boardPage['data']['board']['uri']);
    $this->assertIsArray($boardPage['data']['page1']);
  }

  /*
  public function testMyBoards(): void {
    $res = getExpectJson('/myBoards');
    $this->assertIsArray($res);
    $this->assertSame(200, $res['meta']['code']);
  }
  */

  public function testNoMyBoards(): void {
    $res = getExpectJson('/myBoards');
    $this->assertIsArray($res);
    $this->assertIsArray($res['meta']);
    $this->assertSame(401, $res['meta']['code']);
    $this->assertSame('No Session', $res['meta']['err']);
  }

  /**
   * @depends testBoardsJson
   */
  public function testBoard(array $boards): void {
    if (!count($boards)) return;
    shuffle($boards);
    $board = array_shift($boards);
    $res = getExpectJson('/' . $board['uri']);
    $this->assertIsArray($res);
    $this->assertIsArray($res['meta']);
    $this->assertSame(200, $res['meta']['code']);
    $this->assertIsArray($res['data']);
    $this->assertSame($board['uri'], $res['data']['uri']);
  }

}