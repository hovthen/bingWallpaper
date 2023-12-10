<?php
/** 
 * @version 1.0
 * @author 万里无云
 * @email me@hovthen.com
 * @title 壁纸API
 * @description 壁纸API
 */

require_once 'config.php';

$format = isset($_GET['format']) ? $_GET['format'] : 'url';

if (API_OPEN !== true) {
  $format = 'url';
  $count = 1;
} else {
  $count = isset($_GET['count']) ? max(1, min(API_MAX_COUNT, (int)$_GET['count'])) : 1;
}

$bingWallpaper = new BingWallpaper();
if (isset($_GET['random'])) {
  $data = $bingWallpaper->getRandomWallpapers($count, $_GET['random']);
} else {
  $data = $bingWallpaper->getWallpaper($_GET['date'] ?? null);
}

$width = isset($_GET['width']) ? $_GET['width'] : '1920';
$height = isset($_GET['height']) ? $_GET['height'] : '1080';
$size = isset($_GET['size']) ? $_GET['size'] : $width . 'x' . $height;

if (!in_array($size, $data['images'][0]['size'])) {
  $size = '1920x1080';
}

$diffdata = $data;

switch ($format) {
  case 'js':
  case 'json':
    ob_start();
    header("HTTP/1.1 200 OK");
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST');
    header('Content-type:text/json;charset=utf-8');
    ob_end_flush();
    $data = json_encode($data, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
    $data = str_replace('\n','',$data);
    $data = str_replace('\/','/',$data);
    echo $data;
    exit();
    break;
  case 'url':
    $urlbase = $diffdata['images'][0]['urlbase'];
    ob_start();
    Header("HTTP/1.1 302 Found");
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'");
    Header('Location:'."https://www.bing.com{$urlbase}_{$size}.jpg");
    ob_end_flush();
    exit();
  default:
    $urlbase = $diffdata['images'][0]['urlbase'];
    ob_start();
    Header("HTTP/1.1 302 Found");
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'");
    Header('Location:'."https://www.bing.com{$urlbase}_{$size}.jpg");
    ob_end_flush();
    exit();
    break;
}


class BingWallpaper
{
  private $db;

  public function __construct()
  {
    $this->db = new SQLite3(DB_FILE);
  }

  public function getWallpaper($date = null, $count = 1, $random = null)
  {
    // 根据参数获取日期
    $date = $this->getDateFromParams($date);
    // 查询数据库是否存在指定日期的数据
    $result = $this->db->query("SELECT * FROM " . DB_TABLE_BING_IMAGES . " WHERE enddate = '{$date}'");
    $row = $result->fetchArray(SQLITE3_ASSOC);

    $resData = [];
    $resData['date'] = $date;
    if ($row) {
      $resData['info']['database'] = true;
      $resData['images'][0] = $row;
      $resData['images'][0]['hs'] = json_decode($resData['images'][0]['hs'], true);
      $resData['images'][0]['size'] = json_decode($resData['images'][0]['size'], true);
    	// $this->writeToFile($resData['images'][0]);
    } else {
      $resData['info']['database'] = false;
      $apiData = $this->fetchWallpaperData($date);
      if ($apiData) {
        $resData['info']['write'] = $this->writeToDatabase($apiData);
        $this->writeToFile($apiData);
        $resData['info']['api'] = true;
        $resData['images'][0] = $apiData;
      } else {
        $resData['info']['api'] = false;
        $resData['image'][0] = $apiData;
      }
    }
    return $resData;
  }

  public function getDateFromParams($date)
  {
    // 根据参数获取日期
    if (empty($date)) {
      $date = date('Ymd');
    }
    if (is_numeric($date)) {
      if (0 > $date) {
        $date = date('Ymd', strtotime('tomorrow'));
      } elseif ($date == 0) {
        $date = date('Ymd', strtotime('today'));
      } elseif (15 >= $date && $date > 0) {
        $date = date('Ymd', strtotime('- ' . $date . ' day'));
      } elseif (strlen((string)$date) == 4) {
        $date = date('Ymd', strtotime(date('Y') . $date));
      } elseif (strlen((string)$date) == 6) {
        $date = date('Ymd', strtotime(substr(date('Y'), 0, 2) . $date));
      }
    
      if (strlen((string)$date) == 8) {
        $date = date('Ymd', strtotime($date));
        if (strtotime($date) > strtotime('tomorrow midnight')) {
          $date = date('Ymd', strtotime((date('Y') - 1) . substr($date, -4)));
        }
      } else {
        $date = date('Ymd'); // 当前日期
      }
    } elseif (in_array(strtolower($date), ['today', 'tomorrow', 'yesterday'])) {
      $date = date('Ymd', strtotime($date));
    } elseif (strpos($date, 'random') !== false) {
      $date = null; // Random 模式，忽略 date 参数
    } else {
      $date = date('Ymd');
    }
    $date = date('Y-m-d', strtotime($date));
    return $date;
  }

  private function fetchWallpaperData($date)
  {
    $interval = date_diff(date_create($date), date_create(date('Y-m-d')));
    $date = intval($interval->format('%R%a'));
    if (7 >= $date && $date >= -1) {
      $apiParams = 'n=1&idx=' . $date;
    } else {
      $apiParams = 'n=1&idx=0';
    }
    $apiUrl = "https://cn.bing.com/HPImageArchive.aspx?format=js&cc=cn&{$apiParams}&video=1";
    $apiResponse = file_get_contents($apiUrl);
    $apiData = json_decode($apiResponse, true);
    if (isset($apiData['images'][0])) {
      $apiData['images'][0]['size'] = explode(",", '1920x1200,1920x1080,1366x768,1280x768,1024x768,800x600,800x480,768x1280,720x1280,640x480,480x800,400x240,320x240,240x320');
      return $apiData['images'][0];
    } else {
      return null;
    }
  }

  // 将数据写入数据库
  private function writeToFile($data)
  {
    $date = date('Y-m-d', strtotime($data['enddate']));
    $datearr = explode('-', $date);
    $file = str_replace('%YY%', $datearr[0], DB_FILE_JSON);
    $file = str_replace('%MM%', $datearr[1], $file);
    $file = str_replace('%DD%', $datearr[2], $file);
    $file = str_replace('%YMD%', str_replace('-','', $date), $file);
    if ( !file_exists($file) ) {
			$inset_data = json_encode($data);
			file_put_contents($file, $inset_data);
      if (defined('DB_FILE_JSON_LOG') && !empty(DB_FILE_JSON_LOG)){
        $log_new = date("Y-m-d H:i:s") . "    WRITE {$file}\n";
        if ( !file_exists(DB_FILE_JSON_LOG) ) {
          file_put_contents(DB_FILE_JSON_LOG, $log_new);
        } else{
          $log_old = file_get_contents(DB_FILE_JSON_LOG);
          file_put_contents(DB_FILE_JSON_LOG, $log_old . $log_new);
        }
      }
		}
  }

  // 将数据写入数据库
  private function writeToDatabase($data)
  {
    $resData = [];
    $fullstartdate = $data["fullstartdate"];
    $apiDataresult = $this->db->query("SELECT * FROM " . DB_TABLE_BING_IMAGES . " WHERE fullstartdate = '{$fullstartdate}'")->fetchArray(SQLITE3_ASSOC);
    if (empty($apiDataresult)) {
      $startdate = DateTime::createFromFormat('Ymd', $data['startdate']);
      $enddate = DateTime::createFromFormat('Ymd', $data['enddate']);
      $data['startdate'] = $startdate ? $startdate->format('Y-m-d') : $data['startdate'];
      $data['enddate'] = $enddate ? $enddate->format('Y-m-d') : $data['enddate'];
      $data['hs'] = json_encode($data['hs'], true);
      $data['size'] = json_encode(explode(",", '1920x1200,1920x1080,1366x768,1280x768,1024x768,800x600,800x480,768x1280,720x1280,640x480,480x800,400x240,320x240,240x320'), true);
      $fields = implode(', ', array_keys($data));
      $values = "'" . implode("', '", array_map(function ($value) {
        return is_array($value) ? implode(',', array_map([$this->db, 'escapeString'], $value)) : $this->db->escapeString($value);
      }, $data)) . "'";
      $this->db->exec("INSERT INTO " . DB_TABLE_BING_IMAGES . " ({$fields}) VALUES ({$values})");
      $resData['task'] = true;
      $resData['date'] = $data['enddate'];
    } else {
      $resData['task'] = false;
    }
    return $resData;
  }

  public function getRandomWallpapers($count = 1, $random)
  {
    $resData = [];
    if (empty($random) || !is_numeric($random)) {
      // 从数据库所有数据中随机抽取 $count 条
      $random = 0;
    } else {
      $random = (int)$random;
    }

    $resData['info']['count'] = $count;
    $resData['info']['random'] = $random;
    $condition = '';
    $API_LATEST = defined('API_LATEST') ? max(7, min(API_LATEST, 366)) : 0;
    $API_OLDEST = defined('API_OLDEST') ? -1 * max(7, min(API_OLDEST, 366)) : 0;
    if ($random > 7 && $random <= $API_LATEST) {
      $result = $this->db->query("SELECT id FROM " . DB_TABLE_BING_IMAGES . " ORDER BY id DESC LIMIT {$random}");
      $latestIds = [];
      while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $latestIds[] = $row['id'];
      }
      if ($count > 1) {
        $randomIds = implode(',', array_rand(array_flip($latestIds), min($count, count($latestIds))));
      } else {
        $randomIds = array_rand(array_flip($latestIds), min($count, count($latestIds)));
      }
      $condition = "WHERE id IN ({$randomIds})";
      $resData['info']['msq'] = "Random {$count} in 最近的 {$random}";
    } elseif ($random < -7 && $random >= $API_OLDEST) {
      $random = abs($random);
      $result = $this->db->query("SELECT id FROM " . DB_TABLE_BING_IMAGES . " ORDER BY id ASC LIMIT {$random}");
      $oldestIds = [];
      while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $oldestIds[] = $row['id'];
      }
      if ($count > 1) {
        $randomIds = implode(',', array_rand(array_flip($oldestIds), min($count, count($oldestIds))));
      } else {
        $randomIds = array_rand(array_flip($oldestIds), min($count, count($oldestIds)));
      }
      $condition = "WHERE id IN ({$randomIds})";
      $resData['info']['msg'] = "Random {$count} in 最早的 {$random}";
    } else {
      $condition = "ORDER BY RANDOM() LIMIT {$count}";
      $resData['info']['msg'] = "Random {$count} in 全部";
    }

    $result = $this->db->query("SELECT * FROM " . DB_TABLE_BING_IMAGES . " {$condition}");
    $randomWallpapers = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
      $row['hs'] = json_decode($row['hs'], true);
      $row['size'] = json_decode($row['size'], true);
      $randomWallpapers[] = $row;
    }
    $resData['images'] = $randomWallpapers;
    return $resData;
  }
}
