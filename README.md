# laravel-sensitive

敏感词检查及过滤扩展包


## 特点

- 采用 DFA 算法
- **可配置使用缓存**，减少重复构建敏感词库集合，减少资源占用
- **支持非 Laravel 框架**（不使用缓存功能，或者自定义缓存类）
- 可通过配置文件管理配置，也可动态修改配置
- 使用缓存时，修改配置后可通过命令行更新缓存
- 使用 Facade 时，IDE 也能完美提示


## 系统要求

- php 7.1 及以上版本
- mbstring 扩展
- composer


## 安装

通过 composer 安装

```bash
composer require sydante/laravel-sensitive
```


## 初始

#### Laravel

Laravel 使用包自动发现，所以不需要手动添加服务提供器

如果你没有使用自动发现，就必须手动添加服务提供器到 `config/app.php` 的 providers 数组中：

```php
'providers' => [
    //...
    Sydante\LaravelSensitive\ServiceProvider::class,
],
```

如果你想使用 facade，添加如下代码到 `config/app.php` 的 aliases 数组中：

```php
'aliases' => [
    //...
    'Sensitive' => Sydante\LaravelSensitive\Facades\Sensitive::class,
],
```


## 添加配置

#### Laravel

在项目目录下，使用如下命令发布配置：

```bash
php artisan vendor:publish --provider="Sydante\LaravelSensitive\ServiceProvider"
```

之后可以在 `.env` 中设置 `SENSITIVE_CACHE` 为 `true` 来开启缓存功能

#### 其他框架

可在实例化 `Sydante\LaravelSensitive\Sensitive` 时自行传入配置


## 更新缓存

在使用缓存时，如果修改了敏感词配置 `words` 或 `file` 或者修改了 `file` 配置的文件的内容时，需要通过如下命令更新缓存：

> 如果缓存了配置，需先更新配置缓存：`php artisan config:cache`

```bash
# 更新缓存
php artisan sensitive:cache
```

也可清理缓存，缓存被清理后，在下次被使用时会根据配置自动重建缓存

```bash
php artisan sensitive:clear
```


## 使用

#### 示例代码
```php
// Laravel 可直接通过如下方式获取实例
$sensitive = app(Sydante\LaravelSensitive\Sensitive::class);

$sensitive->filter('这是一个示例');

// 或者使用 Facade
\Sydante\LaravelSensitive\Facades\Sensitive::filter('这是一个示例');


// 其他骚操作：

// 1、动态修改敏感词，并在使用缓存时缓存
$sensitive->emptyTrieTreeMap()
    ->addWords(['test'])
    ->saveTrieTreeMap();

// 2、重置敏感词为初始化时的默认设置，并在使用缓存时缓存
$sensitive->resetTrieTreeMap()
    ->saveTrieTreeMap();
```

#### 可用方法包括：

 * `replaceCode(string $replaceCode): self` 设置替换字符串
 * `setDisturbs(array $disturbList = []): self` 设置干扰因子
 * `saveTrieTreeMap(): bool` 如果使用缓存的话，保存当前敏感词库集合到缓存中
 * `resetTrieTreeMap(): self` 使用配置中的设置重置当前的敏感词库集合
 * `emptyTrieTreeMap(): self` 清空敏感词库集合
 * `clearCache(): bool` 清理敏感词库集合缓存
 * `addWords(array $wordsList): self` 添加敏感词
 * `addWordsFromFile(string $filename): self` 从文件中读取并添加敏感词
 * `filter(string $text): string` 过滤敏感词
 * `search(string $text): array` 查找对应敏感词


## 在其他框架中使用

#### 不使用缓存

在不使用缓存时，可以直接在其他框架中直接使用 `Sydante\LaravelSensitive\Sensitive`。

#### 使用缓存

如果需要使用缓存功能，请自行编写实现了 `Sydante\LaravelSensitive\SensitiveCacheInterface` 接口的缓存类，在初始化 `implements SensitiveCacheInterface` 时传入类名到 `cache_class` 键中：

```php
use Sydante\LaravelSensitive\Sensitive;
use Sydante\LaravelSensitive\SensitiveCacheInterface;

// 编写自定义的缓存类
class CustomSensitiveCache implements SensitiveCacheInterface
{
    // ...
}

// 使用
$sensitive = new Sensitive([
    'cache' => true,
    'words' => ['test'],
    // 使用自定义缓存类
    'cache_class' => CustomSensitiveCache::class,
]);

$sensitive->filter('我们来一个test'); // 我们来一个****
```


## 为什么要写这个包

在写这个包之前，我使用的是 [yankewei/laravel-sensitive](https://github.com/yankewei/laravel-sensitive)，在此感谢此包提供的敏感词处理思路。不过在实际使用中，压测发现此包 IO 占用太高，且函数调用频繁：

| 函数                                                | 调用次数  | 执行耗时   | CPU时间  | 内存占用    | 内存巅峰    |
|---------------------------------------------------|-------|--------|--------|---------|---------|
| mb_substr                                         | 21099 | 13.9ms | 28μs   | 660.0KB | 161.4KB |
| str_replace                                       | 5858  | 4.2ms  | 10μs   | 213.9KB | 136.0KB |
| mb_strlen                                         | 5771  | 3.4ms  | 2μs    | 1.1KB   | 112Bit  |
| Yankewei\LaravelSensitive\Sensitive::getGeneretor | 5771  | 0μs    | 56.4ms | 0Bit    | 0Bit    |
| feof                                              | 5770  | 0μs    | 9.4ms  | 0Bit    | 0Bit    |
| fgets                                             | 5769  | 0μs    | 9.9ms  | 0Bit    | 0Bit    |

遂打算自己重写，将生成的敏感词库集合缓存起来，优化掉读敏感词文件和生成敏感词集合的部分，一次生成，即可重复使用。


## Tips

可使用如下方式，来使干扰因子支持使用 `SENSITIVE_DISTURBS` 配置

```php
// in config/sensitive.php

// 获取设置的干扰因子
$disturbs = env('SENSITIVE_DISTURBS');

if ($disturbs !== null) {
    // 将干扰因子解析成单个字符的列表
    $disturbs = array_values(array_unique(
        preg_split('//u', $disturbs)
    ));
}

return [
    // 是否使用缓存，默认 false
    'cache' => env('SENSITIVE_CACHE', false),

    // 干扰因子列表
    'disturbs' => $disturbs,

    // ...
];
```

> 如果缓存了配置，调整了 `SENSITIVE_DISTURBS` 后需要更新配置缓存：`php artisan config:cache`
