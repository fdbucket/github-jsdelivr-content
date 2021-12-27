<?php
/*
 * @Author: yumusb
 * @Date: 2020-03-27 14:45:07
 * @LastEditors: Jackson Dou
 * @LastEditTime: 2021-12-27 18:45:34
 * @Description: 
 */
/*
URL https://github.com/fdbucket/github-jsdelivr-content

注意事项：
1. PHP 中开启 Curl 扩展
2. 如果使用 GitHub，则服务器需要能和 https://api.github.com 正常通信。（建议放到国外 http://renzhijia.com/buy/index/7/?yumu 美国免费空间推荐 优惠码 free2 ）
3. 如果使用 Gitee，请保证上传的文件遵循国内法律
4. 懒的搭建或者不会搭建，就直接用 http://chuibi.cn/
5. 如果本项目帮助到了您，请鼓励作者 http://33.al/donate ，期待能够带来更好的软件
*/

error_reporting(0);
header("Content-Type: text/html; charset=UTF-8");
date_default_timezone_set('PRC');

if (!is_callable('curl_init')) {
    $return['code'] = 500;
    $return['msg'] = '服务器不支持 Curl 扩展。';
    $return['url'] = null;
    die(json_encode($return));
}

include_once('config.php');

if (TYPE !== 'GITHUB' && TYPE !== 'GITEE') {
    $return['code'] = 500;
    $return['msg'] = '仓库类型配置有误。';
    $return['url'] = null;
    die(json_encode($return));
}

try {
    $db = new PDO('mysql:dbname=' . $database['dbname'] . ';host=' . $database['host'] . ';' . 'port=' . $database['port'] . ';', $database['user'], $database['pass'], array(PDO::MYSQL_ATTR_INIT_COMMAND => 'set names utf8'));
} catch (PDOException $e) {
    $return['code'] = 500;
    $return['msg'] = '数据库出错，请检查 config.php 中的 database 配置项。<br/> ' . $e->getMessage();
    $return['url'] = null;
    die(json_encode($return));
}

function GetIP()
{
    if (getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
        $ip = getenv('HTTP_CLIENT_IP');
    } elseif (getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
        $ip = getenv('HTTP_X_FORWARDED_FOR');
    } elseif (getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
        $ip = getenv('REMOTE_ADDR');
    } elseif (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    $ip = addslashes(preg_replace("/^([\d\.]+).*/", "\\1", $ip));
    return $ip;
}

function upload_github($filename, $content)
{
    $url = 'https://api.github.com/repos/' . USER . '/' . REPO . '/contents/' . $filename;
    $ch = curl_init();
    $defaultOptions = array(
        CURLOPT_URL => $url,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_POSTFIELDS => json_encode(array(
            'message' => 'upload By GitHub Jsdelivr Content',
            'committer' => array(
                'name' => NAME,
                'email' => EMAIL,
            ),
            'content' => $content,
        )),
        CURLOPT_USERAGENT => USER,
        CURLOPT_HTTPHEADER => array(
            'Accept: application/vnd.github.v3+json',
            'User-Agent: ' . USER,
            // "Accept:text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
            // "Accept-Language:zh-CN,en-US;q=0.7,en;q=0.3",
            // "User-Agent:Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.99 Safari/537.36",
            'Authorization:token ' . TOKEN,
        )
    );
    curl_setopt_array($ch, $defaultOptions);
    $chContents = curl_exec($ch);
    curl_close($ch);
    return $chContents;
}

function upload_gitee($filename, $content)
{
    $url = 'https://gitee.com/api/v5/repos/' . USER . '/' . REPO . '/contents/' . $filename;
    $ch = curl_init();
    $defaultOptions = [
        CURLOPT_URL => $url,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => [
            'access_token' => TOKEN,
            'message' => 'upload By GitHub Jsdelivr Content',
            'content' => $content,
            'owner' => USER,
            'repo' => REPO,
            'path' => $filename,
            'branch' => 'main'
        ],
        CURLOPT_HTTPHEADER => [
            "Accept:text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
            "Accept-Language:zh-CN,en-US;q=0.7,en;q=0.3",
            "User-Agent:Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.99 Safari/537.36"
        ],
    ];
    curl_setopt_array($ch, $defaultOptions);
    $chContents = curl_exec($ch);
    curl_close($ch);
    return $chContents;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_FILES['pic']['error'] <= 0 && $_FILES['pic']['size'] > 100) {
    $filename = date('Y') . '/' . date('m') . '/' . date('d') . '/' . md5(time() . mt_rand(10, 1000)) . '.png';
    $tmpName = './tmp' . md5($filename);
    move_uploaded_file($_FILES['pic']['tmp_name'], $tmpName);
    $filemd5 = md5_file($tmpName);
    $row = $db->query("SELECT `imgurl` FROM `{$table}` WHERE `imgmd5`= '{$filemd5}' ")->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $remoteimg = $row['imgurl'];
    } else {
        $content = base64_encode(file_get_contents($tmpName));

        if (TYPE === 'GITHUB') {
            $res = json_decode(upload_github($filename, $content), true);
        } else {
            $res = json_decode(upload_gitee($filename, $content), true);
        }

        if ($res['content']['path'] != '') {
            if (TYPE === 'GITHUB') {
                $remoteimg = 'https://cdn.jsdelivr.net/gh/' . USER . '/' . REPO . '@' . $res['commit']['sha'] . '/' . $res['content']['path'];
            } else {
                $remoteimg = $res['content']['download_url'];
            }
            $tmp = $db->prepare("INSERT INTO `{$table}`(`imgmd5`, `imguploadtime`, `imguploadip`,`imgurl`) VALUES (?,?,?,?)");
            $tmp->execute(array($filemd5, time(), GetIP(), $remoteimg));
        }
    }
    unlink($tmpName);
    if ($remoteimg != '') {
        $return['code'] = 'success';
        $return['data']['url'] = $remoteimg;
        $return['data']['filemd5'] = $filemd5;
    } else {
        $return['code'] = 500;
        $return['msg'] = '上传失败，我们会尽快修复';
        $return['url'] = null;
    }
} else {
    $return['code'] = 404;
    $return['msg'] = '无法识别你的文件';
    $return['url'] = null;
}
exit(json_encode($return));
