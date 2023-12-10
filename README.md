# 必应壁纸

“必应壁纸” 包含必应主页上特别推荐的一批来自世界各地的精美图片。您不仅每天都会在桌面上看到一个新图像，而且还可以了解其背后的故事。

访问 [演示页面](https://lab.hovthen.com/pages/bing/) 查看效果，[了解更多](https://www.hovthen.com/wallpaper-bing.html)

接口数据来源：

- [微软必应中国](https://cn.bing.com/)
- [ioliu](https://bing.ioliu.cn/) [Github@xCss](https://github.com/xCss/bing)
- [Github@out0fmemory](https://github.com/out0fmemory/BingDailyWallpaper)

## 接口说明

### 请求说明

| URL | request |
| :--- | :--- |
| https://yours.domain.com/bing/index.php | GET |

### 请求参数说明

| 请求参数 | 默认值     | 参数说明         |
| :---     | :---      | :---             |
| date     | today     | 指定返回壁纸日期 |
| random   | false     | 启用随机返回方式 |
| count    | 1         | 随机返回壁纸数量 |
| size     | 1920x1080 | 返回必应壁纸尺寸 |
| width    | 1920      | 返回必应壁纸尺寸 |
| height   | 1080      | 返回必应壁纸尺寸 |

#### date 指定日期模式

根据传递的不同值自动计算对应日期

| 标识 |  取值格式或范围    | 说明         |
| :---     | :---      | :---             |
| 天数差     | [-1,7]     | -1明天\0今天\1昨天，以此类推 |
| 年月日   | YYMMDD     | 如20231001，不能超过明天 |
| 年月日   | MMDD     | 如1001，根据当前日期自动确定为今年或去年 |
| 明今昨   | 英文单词  | today、tomorrow、yesterday |
| 兜底     | today     | 现在的日期 |

如果日期错误后超过 tomorrow 将自动以 today 兜底。该方式下数量 count 强制为 1

#### random 随机模式

存在该字段默认为随机模式，即使值为空。根据传递的不同值自动调整规则：

| 标识 |  取值格式或范围  | 说明         |
| :---     | :---        | :---             |
| 全部随机 | [-7,7]      | 从数据库所有数据中随机取 |
| 近期随机 | [7,+MAX]    | 如：10。从数据库最新的 random 行数据中随机取 |
| 历史随机 | [-MIN,-7]   | 如：-10。从数据库最早的 random 行数据中随机取 |
| 空       | 0           | 从数据库所有数据中随机取 |
| 兜底     | 0           | 从数据库所有数据中随机取 |
| unset    |             | 以 date 指定日期模式 |

该模式下 count 生效，默认为 1 条。count 及 random 具体允许的取值范围以配置文件设置为准。

#### size 壁纸尺寸

size 存在时，width 和 height 无效。不存在30则将后两者拼接后进行检查。尺寸错误时默认 1920x1080

### 返回示例

与必应官方的信息一致，实际返回的图片信息可能与请求的不一致（请自行比对）。

```json
{
    "date": "2023-11-30", // 请求的日期
    "info": {
        "database": true
    }, // 调试信息
    "images": [
        {
            "id": 2790,
            "startdate": "2023-11-29",
            "fullstartdate": "202311291600",
            "enddate": "2023-11-30",
            "url": "/th?id=OHR.TrotternishStorr_ZH-CN2508882441_1920x1080.jpg&rf=LaDigue_1920x1080.jpg&pid=hp",
            "urlbase": "/th?id=OHR.TrotternishStorr_ZH-CN2508882441",
            "copyright": "斯托尔，斯凯岛上展露的岩石尖峰，苏格兰，英国 (© Juan Maria Coy Vergara/Getty Images)",
            "copyrightlink": "https://www.bing.com/search?q=%E8%8B%8F%E6%A0%BC%E5%85%B0%E6%96%AF%E5%87%AF%E5%B2%9B&form=hpcapt&mkt=zh-cn",
            "title": "守望的老人",
            "quiz": "/search?q=Bing+homepage+quiz&filters=WQOskey:%22HPQuiz_20231129_TrotternishStorr%22&FORM=HPQUIZ",
            "wp": 1,
            "hsh": "4235bbff630810ae94cf7c2b959e684f",
            "drk": 1,
            "top": 1,
            "bot": 1,
            "hs": [],
            "size": [
                "1920x1200",
                "1920x1080",
                ...,
                "320x240",
                "240x320"
            ]
        }
    ]
}
```

## 配置说明

使用前你需要安装、导入数据，支持两种数据格式：

- INFO 文件：[数据下载](https://github.com/out0fmemory/BingDailyWallpaper)，[示例](./data/info/)
- JSON 文件：[示例](./data/json/)

配置详见 [config.php](./config.php)，安装、导入数据详见 [install.php](./install.php) 文件，按照提示修改相关常量，设置好路径，访问网址等待即可。**安装完成后记得删除该文件或修改相关常量为 FALSE**。

## 效果展示

访问 [演示页面](./index.html) 查看效果。说明：

1. 指定日期：`./index.html?date=20231001`
2. 随机日期：`./index.html?random`
3. 按下空格键：立即随机显示一张图片
4. 按下回车键：显示当前日期的前一天的图片
