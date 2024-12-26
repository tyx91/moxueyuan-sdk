<?php
//define('ROOT',dirname(__DIR__,2));
//require_once ROOT.'/vendor/autoload.php';

use Moxueyuan\Sdk\MoxueyuanOpenapiSDK;
header("Content-type: text/html; charset=utf-8");
// 假设魔学院开放的参数如下
$key = "jWmYm7qr5nMoAUwZRjGtBxmz3KA1tkAj3ykkR6q2B2C";
$token = "QDG6eK";
$corpId = "mxy9988aaa122mmndzzzz";

//企业内部开发模式获取免登录URL
//生产环境中的生成的免登录URL仅一次有效，不能重复请求。
$ssoHost = ' http://open.test.moxueyuan.com/api/v1/sso/login?';//请自行区分生产环境和测试环境。

$moxueyuanSDK = new MoxueyuanOpenapiSDK($token, $key, $corpId);
$userInfo = '{"userid": "zhangshan", "mobile": "13987654321"}';
$redirectUrl = 'http://pc.mxy.chinamobo.com';
$ssoLoginUrlPath = $moxueyuanSDK->getSsoLoginUrlPath($userInfo, $redirectUrl);
if ($ssoLoginUrlPath) {
    print_r('企业内部开发模式获取免登录URL：', $ssoHost . $ssoLoginUrlPath);
} else {
    print_r('获取企业内部开发模式免登录URL：', $errorCode);
}

//合作商模式获取免登录URL
$pCorpid = 'mxy11ddddddddaaaadooo';//合作商授权企业的corpid
$ssoLoginUrlPath = $moxueyuanSDK->getSsoLoginUrlPath($userInfo, $redirectUrl, 'base', $pCorpid);
if ($ssoLoginUrlPath) {
    print_r('合作商获取免登录URL：', $ssoHost . $ssoLoginUrlPath);
} else {
    print_r('获取合作商免登录URL：', $errorCode);
}


//加密消息
/** 返回结果示例
 * $encryptMsg = [
 *  'msg_signature' => 'd45837d627547a2f620395e53134ed72019652cf',//签名
 *  'encrypt' => 'QNnHTStXVGbpFfBvshZHH3QzljXv4UFBndtTzhGrvFG2JpoxzLEh0bOp/9wYvckqaw/pZsFhbb03gUnILJOD+fCS9dTDEbVAZqpEmRDEaWH+v5e0C+DX2lciDM8OcC4j',//$userInfo加密后的密文
 *  'timeStamp' => 1531187989,
 *  'nonce' => 'MVhPJSDmH6'
 * ];
 */
$encryptMsg = null;
$timestamp = time();
$nonce = \Moxueyuan\Sdk\Lib\Prpcrypt::getRandomStr(10);
$errorCode = $moxueyuanSDK->EncryptMsg($userInfo, $timestamp, $nonce, $encryptMsg);
if ($errorCode == 0) {
    var_dump('加密消息：', $encryptMsg);
} else {
    var_dump('加密消息失败：', $errorCode);
}


//解密消息
/** 返回结果示例
 * $encryptMsg = [
 *  'msg_signature' => 'd45837d627547a2f620395e53134ed72019652cf',//签名
 *  'encrypt' => 'QNnHTStXVGbpFfBvshZHH3QzljXv4UFBndtTzhGrvFG2JpoxzLEh0bOp/9wYvckqaw/pZsFhbb03gUnILJOD+fCS9dTDEbVAZqpEmRDEaWH+v5e0C+DX2lciDM8OcC4j',//$userInfo加密后的密文
 *  'timeStamp' => 1531187989,
 *  'nonce' => 'MVhPJSDmH6'
 * ];
 */


$decryptMsg = null;
$errorCode = $moxueyuanSDK->DecryptMsg($encryptMsg['msg_signature'], $encryptMsg['timeStamp'], $encryptMsg['nonce'], $encryptMsg['encrypt'], $decryptMsg);
if ($errorCode == 0) {
    var_dump('解密后消息：', $decryptMsg);
} else {
    var_dump('解密消息失败：', $errorCode);
}

