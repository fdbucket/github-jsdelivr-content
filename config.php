<?php
// 配置文件
define('TYPE', 'GITHUB'); // 选择GITHUB/GITEE, 选择gitee，如果使用gitee，需要手动建立master分支，可以看这里 https://gitee.com/help/articles/4122
define('USER', 'pic-cdn'); // 你的GitHub/Gitee的用户名
define('REPO', 'cdn2'); // 必须是上面用户名下的 公开仓库
define('NAME', '游客'); // 提交者显示名字
define('EMAIL', 'user@foxmail.com'); // 提交者邮箱地址，可以是账号绑定邮箱亦可以任意邮箱
define('TOKEN', 'd0d16844-dd7f-49ab-8765-79dc400564b8');
// Github 去这个页面 https://github.com/settings/tokens生成一个有写权限的token（repo：Full control of private repositories 和write:packages前打勾）
// Gitee 去往这个页面 https://gitee.com/personal_access_tokens

// 数据库配置文件
// 请确保把当前目录下的 pic.sql 导入到你的数据库
$database = array(
    'dbname' => 'YourDbName', // 你的数据库名字
    'host' => 'localhost',
    'port' => 3306,
    'user' => 'YourDbUser', // 你的数据库用户名
    'pass' => 'YourDbPass', // 你的数据库用户名对应的密码
);


$table = 'remote_imgs'; // 表名字
