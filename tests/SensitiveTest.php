<?php

use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\TestCase;
use Sydante\LaravelSensitive\Exceptions\CacheException;
use Sydante\LaravelSensitive\Exceptions\FileReadException;
use Sydante\LaravelSensitive\Sensitive;
use Sydante\LaravelSensitive\SensitiveCacheInterface;

class SensitiveTest extends TestCase
{
    /**
     * @throws CacheException
     * @throws FileReadException
     */
    public function testCacheClassNotExists(): void
    {
        $this->expectException(CacheException::class);

        $this->expectExceptionMessage('cache class not exists');

        new Sensitive([
            'cache' => true,
            'cache_class' => 'UndefinedClass',
        ]);
    }

    /**
     * @throws CacheException
     * @throws FileReadException
     */
    public function testCacheClassNotImplementInterface(): void
    {
        $this->expectException(CacheException::class);

        $this->expectExceptionMessage('cache not implement SensitiveCacheInterface');

        new Sensitive([
            'cache' => true,
            'cache_class' => Cache::class,
        ]);
    }

    /**
     * @throws CacheException
     * @throws FileReadException
     */
    public function testCacheSaveFailed(): void
    {
        $this->expectException(CacheException::class);

        $this->expectExceptionMessage('save cache failed');

        $mock = $this->createMock(SensitiveCacheInterface::class);

        new Sensitive([
            'cache' => true,
            'cache_class' => get_class($mock),
        ]);
    }

    /**
     * @throws CacheException
     * @throws FileReadException
     */
    public function testFileReadException(): void
    {
        $this->expectException(FileReadException::class);

        $filename = __DIR__ . '/undefined.txt';

        $this->expectExceptionMessage("file [{$filename}] not exists");

        new Sensitive([
            'file' => $filename,
        ]);
    }

    /**
     * @throws CacheException
     * @throws FileReadException
     */
    public function testSearch(): void
    {
        // 通过 config 使用 addWords
        $s = new Sensitive(['words' => ['笨蛋', 'sb', 'sss']]);

        $words = $s->search('你是笨蛋大sb嘛');

        self::assertEquals(['笨蛋', 'sb'], $words);
    }

    /**
     * @throws CacheException
     * @throws FileReadException
     */
    public function testAddWords(): void
    {
        // 通过 config 使用 addWords
        $s = new Sensitive(['words' => ['笨蛋', 'sb', 'sss']]);

        $words = $s->search('你是笨蛋大sb嘛');

        self::assertEquals(['笨蛋', 'sb'], $words);

        // 动态调用 addWords 增加屏蔽词
        $s->addWords(['zz']);

        $words = $s->search('你是笨蛋大zz嘛');

        self::assertEquals(['笨蛋', 'zz'], $words);

        // 清空之前的屏蔽词设置后，使用 addWords 添加屏蔽词
        $s->emptyTrieTreeMap()->addWords(['zz']);

        $words = $s->search('你是笨蛋大zz嘛');

        self::assertEquals(['zz'], $words);
    }

    /**
     * @throws CacheException
     * @throws FileReadException
     */
    public function testAddWordsFromFile(): void
    {
        $file = __DIR__ . '/sensitive.txt';

        // 通过 config 使用 addWordsFromFile
        $s = new Sensitive(['file' => $file]);

        $words = $s->search('你是笨蛋大sb嘛');

        self::assertEquals(['笨蛋', 'sb'], $words);

        // 清空之前的屏蔽词设置后，使用 addWordsFromFile 添加屏蔽词
        $s->emptyTrieTreeMap();

        // 检查清空是否生效
        $words = $s->search('你是笨蛋大zz嘛');

        self::assertEquals([], $words);

        // 动态调用 addWordsFromFile 增加屏蔽词
        $s->addWordsFromFile($file);

        $words = $s->search('你是笨蛋大zz嘛');

        self::assertEquals(['笨蛋', 'zz'], $words);
    }

    /**
     * @throws CacheException
     * @throws FileReadException
     */
    public function testFilter(): void
    {
        // 通过 config 使用 addWords
        $s = new Sensitive(['words' => ['笨蛋', 'sb', 'sss']]);

        $text = $s->filter('你是笨蛋大sb嘛');

        self::assertEquals('你是**大**嘛', $text);
    }
}
