<?php

return [
    // bool 是否使用缓存，默认 false
    'cache' => env('SENSITIVE_CACHE', false),

    // string|null 缓存类，需继承 SensitiveCacheInterface
    // 不设置时，使用默认缓存类
    'cache_class' => null,

    // string|null 过滤时的替换字符，默认：*
    'replace_code' => env('SENSITIVE_REPLACE_CODE'),

    // array|null 干扰因子列表
    'disturbs' => null,

    // array|null 敏感词列表
    'words' => null,

    // string|null 敏感词文件路径，文件内容必须是一行一个敏感词
    'file' => null,
];
