<?php

// 获取设置的干扰因子
$disturbs = env('SENSITIVE_DISTURBS');

if ($disturbs !== null) {
    // 将干扰因子解析成单个字符的列表
    $disturbs = array_values(array_filter(array_unique(
        preg_split('//u', $disturbs)
    )));
}

return [
    // 是否使用缓存，默认 false
    'cache' => env('SENSITIVE_CACHE', false),

    // 缓存类，需继承 SensitiveCacheInterface
    // 不设置时，使用默认缓存类
    'cache_class' => null,

    // 过滤时的替换字符，默认：*
    'replace_code' => env('SENSITIVE_REPLACE_CODE'),

    // 干扰因子列表
    'disturbs' => $disturbs,

    // 敏感词列表
    'words' => null,

    // 敏感词文件路径，文件内容必须是一行一个敏感词
    'file' => null,
];
