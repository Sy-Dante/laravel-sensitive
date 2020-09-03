<?php

namespace Sydante\LaravelSensitive;

use Generator;
use Sydante\LaravelSensitive\Exceptions\CacheException;
use Sydante\LaravelSensitive\Exceptions\FileReadException;

/**
 * 敏感词检查及过滤扩展包，采用 DFA 算法
 *
 * @package Sydante\LaravelSensitive
 */
class Sensitive
{
    /**
     * 替换码
     *
     * @var string
     */
    private $replaceCode = '*';

    /**
     * 敏感词库集合
     *
     * @var array
     */
    private $trieTreeMap = [];

    /**
     * 干扰因子集合
     *
     * @var array
     */
    private $disturbList = [];

    /**
     * 配置
     *
     * @var array|null
     */
    private $config;

    /**
     * 是否使用缓存
     *
     * @var bool
     */
    private $useCache;

    /**
     * 缓存类
     *
     * @var SensitiveCacheInterface
     */
    private $cache;

    /**
     * Sensitive constructor.
     *
     * @param array|null $config
     *
     * @throws FileReadException
     * @throws CacheException
     */
    public function __construct(?array $config = null)
    {
        $this->config = $config;

        $this->useCache = $config['cache'] ?? false;

        if (isset($config['replace_code'])) {
            $this->replaceCode($config['replace_code']);
        }

        if (isset($config['disturbs'])) {
            $this->setDisturbs($config['disturbs']);
        }

        // 是否使用缓存
        if ($this->useCache) {
            // 缓存类
            $cacheCls = $config['cache_class'] ?? SensitiveCache::class;

            if (!class_exists($cacheCls)) {
                throw new CacheException('cache class not exists');
            }

            $cache = new $cacheCls();

            if ($cache instanceof SensitiveCacheInterface) {
                $cache->setKey($config['cache_key'] ?? md5(__CLASS__));

                $this->cache = $cache;

                // 有缓存就使用缓存
                if ($trieTreeMap = $cache->get()) {
                    $this->trieTreeMap = $trieTreeMap;
                    return;
                }
            } else {
                throw new CacheException(
                    'cache not implement SensitiveCacheInterface'
                );
            }
        }

        // 没缓存就加载配置中的敏感词设置，并在使用缓存时更新缓存
        $this->resetTrieTreeMap()
            ->saveTrieTreeMap();
    }

    /**
     * 设置替换字符串
     *
     * @param string $replaceCode
     *
     * @return Sensitive
     */
    public function replaceCode(string $replaceCode): self
    {
        $this->replaceCode = $replaceCode;

        return $this;
    }

    /**
     * 设置干扰因子
     *
     * @param array $disturbList
     *
     * @return Sensitive
     */
    public function setDisturbs(array $disturbList = []): self
    {
        $this->disturbList = $disturbList;

        return $this;
    }

    /**
     * 如果使用缓存的话，保存当前敏感词库集合到缓存中
     *
     * @throws CacheException
     */
    public function saveTrieTreeMap(): bool
    {
        if ($this->useCache) {
            if ($this->cache->set($this->trieTreeMap)) {
                return true;
            }

            throw new CacheException('save cache failed');
        }

        return false;
    }

    /**
     * 使用配置中的设置重置当前的敏感词库集合
     *
     * @return Sensitive
     * @throws FileReadException
     */
    public function resetTrieTreeMap(): Sensitive
    {
        $this->emptyTrieTreeMap();

        $config = $this->config;

        if (isset($config['words'])) {
            $this->addWords($config['words']);
        }

        if (isset($config['file'])) {
            $this->addWordsFromFile($config['file']);
        }

        return $this;
    }

    /**
     * 清空敏感词库集合
     */
    public function emptyTrieTreeMap(): Sensitive
    {
        $this->trieTreeMap = [];

        return $this;
    }

    /**
     * 清理敏感词库集合缓存
     *
     * @return bool
     * @throws CacheException
     */
    public function clearCache(): bool
    {
        if ($this->useCache) {
            if ($this->cache->clear()) {
                return true;
            }

            throw new CacheException('clear cache failed');
        }

        return false;
    }

    /**
     * 添加敏感词
     *
     * @param array $wordsList
     *
     * @return Sensitive
     */
    public function addWords(array $wordsList): Sensitive
    {
        foreach ($wordsList as $words) {
            $this->addToTree($words);
        }

        return $this;
    }

    /**
     * 将敏感词加入敏感词库集合中
     *
     * @param string $words
     */
    private function addToTree(string $words): void
    {
        $words = trim($words, " \t\n\r\0\x0B'\"`");

        $nowWords = &$this->trieTreeMap;

        $len = mb_strlen($words);
        for ($i = 0; $i < $len; $i++) {
            $word = mb_substr($words, $i, 1);

            if (!isset($nowWords[$word])) {
                $nowWords[$word] = false;
            }

            $nowWords = &$nowWords[$word];
        }
    }

    /**
     * 从文件中读取并添加敏感词
     *
     * @param string $filename
     *
     * @return Sensitive
     * @throws FileReadException
     */
    public function addWordsFromFile(string $filename): Sensitive
    {
        foreach ($this->getWordsFromFile($filename) as $words) {
            $this->addToTree($words);
        }

        return $this;
    }

    /**
     * 使用生成器方式读取文件
     *
     * @param $filename
     *
     * @return Generator
     * @throws FileReadException
     */
    private function getWordsFromFile(string $filename): Generator
    {
        $handle = fopen($filename, 'rb');

        if (!$handle) {
            throw new FileReadException('read file failed');
        }

        while (!feof($handle)) {
            yield fgets($handle);
        }

        fclose($handle);
    }

    /**
     * 过滤敏感词
     *
     * @param string $text
     *
     * @return string
     */
    public function filter(string $text): string
    {
        $replaceCodeList = [];
        $wordsList = $this->search($text, true, $replaceCodeList);

        if (empty($wordsList)) {
            return $text;
        }

        return str_replace($wordsList, $replaceCodeList, $text);
    }

    /**
     * 查找对应敏感词
     *
     * @param string $text
     * @param bool   $hasReplace
     * @param array  $replaceCodeList
     *
     * @return array
     */
    public function search(
        string $text,
        bool $hasReplace = false,
        array &$replaceCodeList = []
    ): array {
        $wordsList = [];
        $textLength = mb_strlen($text);

        for ($i = 0; $i < $textLength; $i++) {
            $wordLength = $this->checkWord($text, $i, $textLength);

            if ($wordLength > 0) {
                $words = mb_substr($text, $i, $wordLength);
                $wordsList[] = $words;

                if ($hasReplace) {
                    $replaceCodeList[] = str_repeat($this->replaceCode, mb_strlen($words));
                }

                $i += $wordLength - 1;
            }
        }

        return $wordsList;
    }

    /**
     * 敏感词检测
     *
     * @param string $text
     * @param int    $beginIndex
     * @param int    $length
     *
     * @return int
     */
    private function checkWord(string $text, int $beginIndex, int $length): int
    {
        $flag = false;
        $wordLength = 0;
        $trieTree = &$this->trieTreeMap;

        for ($i = $beginIndex; $i < $length; $i++) {
            $word = mb_substr($text, $i, 1);

            if ($this->isDisturb($word)) {
                $wordLength++;
                continue;
            }

            if (!isset($trieTree[$word])) {
                break;
            }

            $wordLength++;

            if ($trieTree[$word] !== false) {
                $trieTree = &$trieTree[$word];
            } else {
                $flag = true;
            }
        }

        $flag || $wordLength = 0;

        return $wordLength;
    }

    /**
     * 是否为干扰因子
     *
     * @param string $word
     *
     * @return bool
     */
    private function isDisturb(string $word): bool
    {
        return in_array($word, $this->disturbList, true);
    }
}
