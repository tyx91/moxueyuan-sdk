<?php
namespace Moxueyuan\Sdk;
//include_once "sha1.php";
//include_once "pkcs7Encoder.php";
//include_once "Prpcrypt.php";
//include_once "errorCode.php";

use Moxueyuan\Sdk\Lib\ErrorCode;
use Moxueyuan\Sdk\Lib\Prpcrypt;
use Moxueyuan\Sdk\Lib\SHA1;

class MoxueyuanOpenapiSDK
{
    private $m_token;
    private $m_encodingAesKey;
    private $m_suiteKey;

    /**
     * 初始化
     * @param string $token 开放平台颁发的token
     * @param string $encodingAesKey 开放平台颁发的key
     * @param string $corpid 企业corpid或合作商的appid
     */
    public function __construct(string $token, string $encodingAesKey, string $corpid)
    {
        $this->m_token = $token;
        $this->m_encodingAesKey = $encodingAesKey;
        $this->m_suiteKey = $corpid;
    }

    /**
     * 加密
     * @param $plain
     * @param $timeStamp
     * @param $nonce
     * @param $encryptMsg
     * @return int
     * Created on 2018/7/10 8:48
     * Created by moxueyuan
     */
    public function EncryptMsg($plain, $timeStamp, $nonce, &$encryptMsg)
    {
        $pc = new Prpcrypt($this->m_encodingAesKey);
        $array = $pc->encrypt($plain, $this->m_suiteKey);
        $ret = $array[0];
        if ($ret != 0) {
            return $ret;
        }
        if ($timeStamp == null) {
            $timeStamp = time();
        }
        $encrypt = $array[1];
        $sha1 = new SHA1();
        $array = $sha1->getSHA1($this->m_token, $timeStamp, $nonce, $encrypt);
        $ret = $array[0];
        if ($ret != 0) {
            return $ret;
        }
        $signature = $array[1];
        $encryptMsg = array(
            "msg_signature" => $signature,
            "encrypt" => $encrypt,
            "timeStamp" => $timeStamp,
            "nonce" => $nonce
        );
        return ErrorCode::$OK;
    }

    /**
     * 解密
     * @param $signature
     * @param null $timeStamp
     * @param $nonce
     * @param $encrypt
     * @param $decryptMsg
     * @return int
     * Created on 2018/7/10 8:48
     * Created by xiongyouliang
     */
    public function DecryptMsg($signature, $timeStamp, $nonce, $encrypt, &$decryptMsg)
    {
        if (strlen($this->m_encodingAesKey) != 43) {
            return ErrorCode::$IllegalAesKey;
        }
        $pc = new Prpcrypt($this->m_encodingAesKey);
        if ($timeStamp == null) {
            $timeStamp = time();
        }
        $sha1 = new SHA1;
        $array = $sha1->getSHA1($this->m_token, $timeStamp, $nonce, $encrypt);
        $ret = $array[0];
        if ($ret != 0) {
            return $ret;
        }
        $verifySignature = $array[1];
        if ($verifySignature != $signature) {
            return ErrorCode::$ValidateSignatureError;
        }
        $result = $pc->decrypt($encrypt, $this->m_suiteKey);
        if ($result[0] != 0) {
            return $result[0];
        }
        $decryptMsg = $result[1];
        return ErrorCode::$OK;
    }

    /**
     * 返回免登录URL的path部分
     * @param $plainText 待加密的明文
     * @param $redirectUrl //登录成功后跳转的URL
     * @param string $scope 授权作用域
     * @param null $corpid 合作商模式下的corpid，企业内部开发模式为null
     * @return bool|string
     * Created on 2018/7/10 9:27
     * Created by moxueyuan
     */
    public function getSsoLoginUrlPath($plainText, $redirectUrl, $scope = 'base', $corpid = null)
    {
        $timestamp = time();
        $nonce = Prpcrypt::getRandomStr(10);
        $encryptMsg = null;
        $errorCode = $this->EncryptMsg($plainText, $timestamp, $nonce, $encryptMsg);
        if ($errorCode == 0) {
            $signature = $encryptMsg['msg_signature'];
            $encrypt = urlencode($encryptMsg['encrypt']);
            $path = "scope=$scope&timestamp=$timestamp&nonce=$nonce&sign=$signature&ticket=$encrypt&redirect_uri=" . urlencode($redirectUrl);
            if ($corpid) {
                $path .= "&appid=$this->m_suiteKey&corpid=$corpid";
            } else {
                $path .= "&corpid=$this->m_suiteKey";
            }
            return $path;
        } else {
            return false;
        }
    }


}