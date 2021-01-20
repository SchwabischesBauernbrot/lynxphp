<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

final class test_fourchan_Test extends TestCase
{
    public function testBoardsJson(): void
    {
      $boards = getExpectJson('4chan/boards.json');
      $this->assertIsArray($boards);
    }

    public function testBoardCatalogJson(): void
    {
      $boards = getExpectJson('/board/catalog.json');
      $this->assertIsArray($boards);
    }

    public function testBoardThreadXJson(): void
    {
      $threads = getExpectJson('/board/thread/X.json');
      $this->assertIsArray($threads);
    }

    public function testBoardPage1Json(): void
    {
      $threads = getExpectJson('/board/1.json');
      $this->assertIsArray($threads);
    }

}