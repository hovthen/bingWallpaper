<?php
define('UPDATA', FALSE);
require_once 'config.php';

$tableName = DB_TABLE_BING_IMAGES;

$update_sql = <<<SQL
   SELECT COUNT(*) FROM {$tableName}
SQL;

if (DB_TYPE === 'sqlite3' && !empty(DB_FILE)) {
    if (file_exists(DB_FILE) && UPDATA == TRUE) {
        echo date('H:i:s') . " 尝试更新 SQLite 数据库.<br/>";
        $db = new SQLite3(DB_FILE);
        $result = $db->exec($update_sql);
        if ($result === false) {
            echo date('H:i:s') . " 更新数据库出错: <br/>" . $db->lastErrorMsg();
        } else {
            echo date('H:i:s') . " 更新数据库成功!<br/>";
        }
        $db->close();
    } else {
        echo date('H:i:s') . " 如需更新 SQLite 数据库请检查配置文件.<br/>";
        die();
    }
}
?>