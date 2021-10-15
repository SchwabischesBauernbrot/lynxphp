<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

final class test_fourchan_Test extends TestCase
{
    public function testBoardsJson(): void {
      $boards = getExpectJson('4chan/boards.json');
      $this->assertIsArray($boards);
    }

    public function testBoardCatalogJson(): void {
      $threads = getExpectJson('4chan/test/catalog.json');
      $this->assertIsArray($threads);
    }

    public function testBoardThreadsJson(): void {
      $pages = getExpectJson('4chan/test/threads.json');
      $this->assertIsArray($pages);
    }

    public function testBoardPage1Json(): void {
      $threads = getExpectJson('4chan/test/1.json');
      $this->assertIsArray($threads);
    }

    public function testBoardThreadXJson(): void {
      $threads = getExpectJson('4chan/test/thread/1.json');
      $this->assertIsArray($threads);
    }

}

?>