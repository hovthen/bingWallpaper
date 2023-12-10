<?php
define('INSTALL', FALSE); // 是否安装 FALSE TRUE
define('IMPORT', FALSE); // 是否导入数据 FALSE TRUE
define('IMPORT_FILE_JSON', 'data/json/*.json'); // 'JSON'
define('IMPORT_FILE_INFO', 'data/info/*.info'); // 'INFO'
require_once 'config.php';

if (DB_TYPE === 'sqlite3' && !empty(DB_FILE)) {
  // 尝试创建SQLite数据库
  if (!file_exists(DB_FILE) && INSTALL == TRUE) {
    echo date('H:i:s') . "尝试创建SQLite数据库. <br/>";
    try {
      $db = new SQLite3(DB_FILE);
      $db->exec('
        CREATE TABLE IF NOT EXISTS ' . DB_TABLE_BING_IMAGES . ' (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            startdate DATE,
            fullstartdate TEXT,
            enddate DATE,
            url TEXT,
            urlbase TEXT,
            copyright TEXT,
            copyrightlink TEXT,
            title TEXT,
            quiz TEXT,
            wp INTEGER,
            hsh TEXT,
            drk INTEGER,
            top INTEGER,
            bot INTEGER,
            hs TEXT,
            size TEXT,
            UNIQUE(fullstartdate)
        )
      ');
      $db->close();
    } catch (Exception $e) {
      echo date('H:i:s') . " 数据库安装失败:<br/>";
      die($e->getMessage());
    } finally {
      echo date('H:i:s') . " 数据库安装成功.<br/><br/>";
    }
  } elseif (INSTALL !== TRUE) {
    if (!file_exists(DB_FILE)) {
      echo date('H:i:s') . " 如需安装 SQLite 数据库安装请检查配置文件.<br/>";
      die('数据库未安装成功.');
    }
    echo date('H:i:s') . " SQLite 数据库安装已跳过.<br/><br/>";
  } else {
    if (!file_exists(DB_FILE)) {
      echo date('H:i:s') . " 如需安装 SQLite 数据库安装请检查配置文件.<br/>";
      die('数据库未安装成功.');
    }
    echo date('H:i:s') . " 已安装 SQLite 数据库.<br/><br/>";
  }

  // 尝试导入SQLite数据库数据
  if (IMPORT == TRUE) {
    $Count = [];

    $db = new SQLite3(DB_FILE);
    if (defined('IMPORT_FILE_JSON')) {
      echo date('H:i:s') . " 开始导入 JSON 数据库数据.<br/>";
      $files = !empty(IMPORT_FILE_JSON) ? glob(IMPORT_FILE_JSON) : [];
      $Count['JSON']['total'] = count($files);
      $Count['JSON']['success'] = 0;
      $Count['JSON']['failure'] = 0;
      $Count['JSON']['same'] = 0;
      foreach ($files as $file) {
        $jsonContent = file_get_contents($file);
        $data = json_decode($jsonContent, true);
        // 检查是否包含 images[0] 数据
        if (isset($data['images'][0])) {
          $imageData = $data['images'][0];
          if (is_array($imageData)) {
            // 格式化导入数据
            $startdate = DateTime::createFromFormat('Ymd', $imageData['startdate']);
            $enddate = DateTime::createFromFormat('Ymd', $imageData['enddate']);
            $imageData['startdate'] = $startdate ? $startdate->format('Y-m-d') : $imageData['startdate'];
            $imageData['enddate'] = $enddate ? $enddate->format('Y-m-d') : $imageData['enddate'];
            $imageData['hs'] = json_encode($imageData['hs'], true);
            $imageData['size'] =
              json_encode(explode(",", '1920x1200,1920x1080,1366x768,1280x768,1024x768,800x600,800x480,768x1280,720x1280,640x480,480x800,400x240,320x240,240x320'), true);
            // 检查重复
            $fullstartdate = $imageData['fullstartdate'];
            $hasDataresult = $db->query("SELECT * FROM " . DB_TABLE_BING_IMAGES . " WHERE fullstartdate = '{$fullstartdate}'")->fetchArray(SQLITE3_ASSOC);
            if (empty($hasDataresult)) {
              $query = $db->prepare('
                INSERT INTO ' . DB_TABLE_BING_IMAGES . ' (
                    startdate, fullstartdate, enddate, url, urlbase, copyright, copyrightlink,
                    title, quiz, wp, hsh, drk, top, bot, hs, size
                ) VALUES (
                    :startdate, :fullstartdate, :enddate, :url, :urlbase, :copyright, :copyrightlink,
                    :title, :quiz, :wp, :hsh, :drk, :top, :bot, :hs, :size
                )
            ');
              $query->bindValue(':fullstartdate', $fullstartdate, SQLITE3_TEXT);
              foreach ($imageData as $key => $value) {
                $query->bindValue(':' . $key, $value, SQLITE3_TEXT);
              }
              $result = $query->execute();
              if ($result) {
                $Count['JSON']['success']++;
              } else {
                $Count['JSON']['failure']++;
                echo date('H:i:s') . " 导入文件 {$file} 失败 [{$Count['JSON']['failure']}].<br/>";
              }
            } else {
              $Count['JSON']['same']++;
              echo date('H:i:s') . " 文件 {$file} 内容重复，无法导入 [{$Count['JSON']['same']}].<br/>";
            }
          } else {
            $Count['JSON']['failure']++;
            echo date('H:i:s') . " 文件 {$file} 内容 images[0] 错误 [{$Count['JSON']['failure']}].<br/>";
          }
        } else {
          $Count['JSON']['failure']++;
          echo date('H:i:s') . " 文件 {$file} 内未找到有效 images 数据 [{$Count['JSON']['failure']}].<br/>";
        }
      }
      echo date('H:i:s') . " 结束导入 JSON 数据库数据.<br/><br/>";
    }
    if (defined('IMPORT_FILE_INFO')) {
      echo date('H:i:s') . " 开始导入 INFO 数据库数据.<br/>";
      $files = !empty(IMPORT_FILE_INFO) ? glob(IMPORT_FILE_INFO) : [];
      $Count['INFO']['total'] = count($files);
      $Count['INFO']['success'] = 0;
      $Count['INFO']['failure'] = 0;
      $Count['INFO']['same'] = 0;
      foreach ($files as $file) {
        $infoContent = file_get_contents($file);
        // 将数据分割成每一行
        $lines = explode("\n", trim($infoContent));
        // 创建一个关联数组来存储键值对
        $keys = [];
        foreach ($lines as $line) {
          list($key, $value) = explode(':', $line, 2);
          $key = !empty($key) ? trim($key) : 'error';
          $value = !empty($value) ? trim($value) : null;
          $keys[$key] = $value;
        }
        if (isset($keys['calendar']) && !empty($keys['calendar'])) {
          $imageData = [];
          $imageData['startdate'] = date('Y-m-d', strtotime('-1 day' . $keys['calendar']));
          $imageData['enddate'] = date('Y-m-d', strtotime($keys['calendar']));
          $imageData['fullstartdate'] = str_replace('-', '', $imageData['enddate']) . '1600';
          if (strtotime($keys['calendar']) >= strtotime('2018-09-10 12:00:00')) {
            $imageData['urlbase'] = '/th?id=OHR.' . $keys['name'];
            $imageData['url'] = $imageData['urlbase'] . '_1920x1080.jpg&pid=hp';
          } else {
            $imageData['urlbase'] = '/az/hprichbg/rb/' . $keys['name'];
            $imageData['url'] = $imageData['urlbase'] . '_1920x1080.jpg&pid=hp';
          }
          $imageData['copyright'] = $keys['description'];
          if (!empty($keys['location'])) {
            $imageData['copyrightlink'] = 'https://www.bing.com/search?q=' . urlencode($keys['location']) . '&form=hpcapt&mkt=zh-cn';
          } else {
            $imageData['copyrightlink'] = 'https://www.bing.com/search?q=' . urlencode(preg_replace('/.*\(([^)]+)\).*/', '$1', $keys['description'])) . '&form=hpcapt&mkt=zh-cn';
          }
          $imageData['title'] = trim(preg_replace('/\([^)]*\)/', '', $keys['description']));
          $quizname = explode('_', $keys['name']);
          $imageData['quiz'] = '/search?q=Bing+homepage+quiz&filters=WQOskey:%22HPQuiz_' . str_replace('-', '', $imageData['startdate']) . '_' . $quizname[0] . '%22&FORM=HPQUIZ';
          $imageData['wp'] = null;
          $imageData['hsh'] = null;
          $imageData['drk'] = null;
          $imageData['top'] = null;
          $imageData['bot'] = null;
          $imageData['hs'] = json_encode([], true);
          $imageData['size'] = json_encode(explode(',', $keys['resolutions']), true);
          // 检查重复
          $fullstartdate = $imageData['fullstartdate'];
          $hasDataresult = $db->query("SELECT * FROM " . DB_TABLE_BING_IMAGES . " WHERE fullstartdate = '{$fullstartdate}'")->fetchArray(SQLITE3_ASSOC);
          if (empty($hasDataresult)) {
            $query = $db->prepare('
                INSERT INTO ' . DB_TABLE_BING_IMAGES . ' (
                    startdate, fullstartdate, enddate, url, urlbase, copyright, copyrightlink,
                    title, quiz, wp, hsh, drk, top, bot, hs, size
                ) VALUES (
                    :startdate, :fullstartdate, :enddate, :url, :urlbase, :copyright, :copyrightlink,
                    :title, :quiz, :wp, :hsh, :drk, :top, :bot, :hs, :size
                )
            ');
            $query->bindValue(':fullstartdate', $fullstartdate, SQLITE3_TEXT);
            foreach ($imageData as $key => $value) {
              $query->bindValue(':' . $key, $value, SQLITE3_TEXT);
            }
            $result = $query->execute();
            if ($result) {
              $Count['INFO']['success']++;
            } else {
              $Count['INFO']['failure']++;
              echo date('H:i:s') . " 导入文件 {$file} 失败 [{$Count['INFO']['failure']}].<br/>";
            }
          } else {
            $Count['INFO']['same']++;
            echo date('H:i:s') . " 文件 {$file} 内容重复，无法导入 [{$Count['INFO']['same']}].<br/>";
          }
        } else {
          $Count['INFO']['failure']++;
          echo date('H:i:s') . " 文件 {$file} 内未找到有效 images 数据 [{$Count['INFO']['failure']}].<br/>";
        }
      }
      echo date('H:i:s') . " 结束导入 INFO 数据库数据.<br/><br/>";
    }
    $db->close();

    echo date('H:i:s') . " 导入数据库数据结束.<br/>";
    echo "............总数: JSON {$Count['JSON']['total']} / INFO {$Count['INFO']['total']} / " . $Count['JSON']['total'] + $Count['INFO']['total'] . "<br/> ";
    echo "............成功: JSON {$Count['JSON']['success']} / INFO {$Count['INFO']['success']} / " . $Count['JSON']['success'] + $Count['INFO']['success'] . "<br/> ";
    echo "............失败: JSON {$Count['JSON']['failure']} / INFO {$Count['INFO']['failure']} / " . $Count['JSON']['failure'] + $Count['INFO']['failure'] . "<br/> ";
    echo "............重复: JSON {$Count['JSON']['same']} / INFO {$Count['INFO']['same']} / " . $Count['JSON']['same'] + $Count['INFO']['same'] . "<br/> ";

    echo "<br/>";
  }

  echo date('H:i:s') . " 测试数据库数据.<br/>";
  // 查询表中的行数
  $db = new SQLite3(DB_FILE);
  $result = $db->query("SELECT COUNT(*) as count FROM " . DB_TABLE_BING_IMAGES . "");
  $row = $result->fetchArray(SQLITE3_ASSOC);
  $rowCount = $row['count'];
  echo "............数据库总共: {$rowCount} 条<br/>";
  if ($rowCount > 0) {
    $randomRow = rand(1, $rowCount);
    $randomResult = $db->query("SELECT * FROM " . DB_TABLE_BING_IMAGES . " LIMIT 1 OFFSET $randomRow");
    $randomRowData = $randomResult->fetchArray(SQLITE3_ASSOC);
    echo "............数据库第 {$randomRow} 条数据：<br/>";
    print_r($randomRowData);
  }
  $db->close();
} elseif (DB_TYPE === 'mysql') {
  echo date('H:i:s') . "尝试连接 MySQL 数据库.<br/>";
  die('暂不支持 MySQL 数据库.');
} else {
  echo date('H:i:s') . "如需安装数据库安装请检查配置文件.<br/>";
  die('数据库未找到.');
}
