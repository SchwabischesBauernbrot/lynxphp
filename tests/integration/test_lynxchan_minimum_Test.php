<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

final class test_lynxchan_minimum_Test extends TestCase {

  public function testRegisterAccount(): void {
    $res = getExpectJson('registerAccount');
    $this->assertIsArray($res);
  }

  public function testLogin(): void {
    $res = getExpectJson('login');
    $this->assertIsArray($res);
  }

  public function testCreateBoard(): void {
    $res = getExpectJson('createBoard');
    $this->assertIsArray($res);
  }

  public function testFiles(): void {
    $res = getExpectJson('files');
    $this->assertIsArray($res);
  }

  public function testNewThread(): void {
    $res = getExpectJson('newThread');
    $this->assertIsArray($res);
  }

  public function testReplyThread(): void {
    $res = getExpectJson('replyThread');
    $this->assertIsArray($res);
  }

  public function testAccount(): void {
    $res = getExpectJson('account');
    $this->assertIsArray($res);
  }
}