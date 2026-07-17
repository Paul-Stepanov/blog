<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Article;

use App\Domain\Article\ValueObjects\ArticleReadContext;
use PHPUnit\Framework\TestCase;

/**
 * ArticleReadContext (read-side snapshot) serialization.
 *
 * Redis cache persists via PHP serialize(); the snapshot must round-trip
 * losslessly because it rides inside the cached Article aggregate.
 */
final class ArticleReadContextTest extends TestCase
{
    public function test_empty_snapshot_has_no_data(): void
    {
        $empty = ArticleReadContext::empty();

        $this->assertNull($empty->category);
        $this->assertSame([], $empty->tags);
        $this->assertNull($empty->author);
        $this->assertNull($empty->coverImageUrl);
    }

    public function test_snapshot_survives_php_serialize_round_trip(): void
    {
        $snapshot = new ArticleReadContext(
            category: ['name' => 'Tech', 'slug' => 'tech'],
            tags: [['name' => 'PHP', 'slug' => 'php'], ['name' => 'DDD', 'slug' => 'ddd']],
            author: ['name' => 'Jane Author'],
            coverImageUrl: 'http://localhost/storage/uploads/cover.jpg',
        );

        /** @var ArticleReadContext $restored */
        $restored = unserialize(serialize($snapshot));

        $this->assertEquals($snapshot, $restored);
        $this->assertSame('Tech', $restored->category['name']);
        $this->assertCount(2, $restored->tags);
        $this->assertSame('Jane Author', $restored->author['name']);
        $this->assertSame('http://localhost/storage/uploads/cover.jpg', $restored->coverImageUrl);
    }
}
