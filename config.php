<?php
// FALSE TRUE
define('DEBUG', FALSE);

// 数据库
define('DB_TYPE', 'sqlite3'); // 数据库类型
define('DB_FILE', 'data/sqlite3.db');  // 数据库文件路径
// 必应壁纸数据表名称
define('DB_TABLE_BING_IMAGES', 'wallpaper_bing'); // 必应壁纸数据表名称

// 保存 'JSON' 文件版本 %YY% %MM% %DD% %YMD%
define('DB_FILE_JSON', 'data/file/%YY%/%MM%/%YMD%.json'); // 'JSON' 数据文件保存路径
define('DB_FILE_JSON_LOG', 'data/file/i.log'); // 'JSON' 数据文件保存写入日志

// API 接口配置
define('API_OPEN', TRUE); // 是否开启 API 接口，否则总是重定向
define('API_MAX_COUNT', 5); // 随机最大t'p数量
define('API_LATEST', 30); // 随机最近取值允许范围、建议[7，30]
define('API_OLDEST', 0); // 随机最早取值允许范围、建议[7，30]


if (DEBUG === TRUE) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

if (DB_TYPE === 'sqlite3') {
    if (!file_exists(DB_FILE) && !defined('INSTALL')) {
        die('未找到数据库.');
    }
}
