<?php

namespace Moxueyuan\Sdk\Lib;

class Prpcrypt
{
    public $key;

    public $useOpenssl;//true使用openssl加解密 一般适用于php7

    protected $iv;

    function __construct($k)
    {
        $this->key  = base64_decode($k . "=");
        $this->iv   = substr($this->key, 0, 16);
        $phpVersion = explode('-', phpversion());
        $phpVersion = $phpVersion[0];
        if ( strnatcasecmp($phpVersion, '7') >= 0 ) {
            $this->useOpenssl = true;
        }

    }

    public function encrypt($text, $corpid)
    {
        try {
            if ( $this->useOpenssl ) {
                //拼接
                $text = $this->getRandomStr() . pack('N', strlen($text)) . $text . $corpid;
                //添加PKCS#7填充
                $pkc_encoder = new PKCS7Encoder;
                $text        = $pkc_encoder->encode($text);
                //加密
                $encrypted = openssl_encrypt($text, 'AES-256-CBC', $this->key, OPENSSL_ZERO_PADDING, $this->iv);

                return [
                    ErrorCode::$OK,
                    $encrypted
                ];
            } else {
                //获得16位随机字符串，填充到明文之前
                $random = $this->getRandomStr();
                $text   = $random . pack("N", strlen($text)) . $text . $corpid;
                // 网络字节序
                $size   = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
                $module = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');

                //使用自定义的填充方式对明文进行补位填充
                $pkc_encoder = new PKCS7Encoder;
                $text        = $pkc_encoder->encode($text);
                mcrypt_generic_init($module, $this->key, $this->iv);
                //加密
                $encrypted = mcrypt_generic($module, $text);
                mcrypt_generic_deinit($module);
                mcrypt_module_close($module);
                //print(base64_encode($encrypted));
                //使用BASE64对加密后的字符串进行编码
                return array (
                    ErrorCode::$OK,
                    base64_encode($encrypted)
                );
            }

        } catch ( \Exception $e ) {
            print $e;

            return array (
                ErrorCode::$EncryptAESError,
                null
            );
        }
    }

    public function decrypt($encrypted, $corpid)
    {
        try {
            if ( $this->useOpenssl ) {
                $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $this->key, OPENSSL_ZERO_PADDING, $this->iv);
            } else {
                $ciphertext_dec = base64_decode($encrypted);
                $module         = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_CBC, '');
                mcrypt_generic_init($module, $this->key, $this->iv);
                $decrypted = mdecrypt_generic($module, $ciphertext_dec);
                mcrypt_generic_deinit($module);
                mcrypt_module_close($module);
            }
        } catch ( \Exception $e ) {
            return array (
                ErrorCode::$DecryptAESError,
                null
            );
        }
        try {
            //去除补位字符
            $pkc_encoder = new PKCS7Encoder;
            $result      = $pkc_encoder->decode($decrypted);
            //去除16位随机字符串,网络字节序和AppId
            if ( strlen($result) < 16 ) {
                return "";
            }
            $content     = substr($result, 16, strlen($result));
            $len_list    = unpack("N", substr($content, 0, 4));
            $xml_len     = $len_list[1];
            $xml_content = substr($content, 4, $xml_len);
            $from_corpid = substr($content, $xml_len + 4);
        } catch ( \Exception $e ) {
            print $e;

            return array (
                ErrorCode::$DecryptAESError,
                null
            );
        }
        if ( $from_corpid != $corpid ) {
            return array (
                ErrorCode::$ValidateSuiteKeyError,
                null
            );
        }

        return array (
            0,
            $xml_content
        );
    }

    public static function getRandomStr($len = 16)
    {
        $str     = "";
        $str_pol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
        $max     = strlen($str_pol) - 1;
        for ( $i = 0; $i < $len; $i++ ) {
            $str .= $str_pol[ mt_rand(0, $max) ];
        }

        return $str;
    }
}