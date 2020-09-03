<?php

namespace Sydante\LaravelSensitive\Facades;

use Illuminate\Support\Facades\Facade;
use Sydante\LaravelSensitive\Sensitive as Accessor;

/**
 * Class Sensitive
 *
 * @package Sydante\LaravelSensitive\Facades
 *
 * @method static static replaceCode(string $replaceCode) 设置替换字符串
 * @method static static setDisturbs(array $disturbList = []) 设置干扰因子
 * @method static bool saveTrieTreeMap() 如果使用缓存的话，保存当前敏感词库集合到缓存中
 * @method static static resetTrieTreeMap() 使用配置中的设置重置当前的敏感词库集合
 * @method static static emptyTrieTreeMap() 清空敏感词库集合
 * @method static bool clearCache() 清理敏感词库集合缓存
 * @method static static addWords(array $wordsList) 添加敏感词
 * @method static static addWordsFromFile(string $filename) 从文件中读取并添加敏感词
 * @method static string filter(string $text) 过滤敏感词
 * @method static array search(string $text) 查找对应敏感词
 */
class Sensitive extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return Accessor::class;
    }
}
