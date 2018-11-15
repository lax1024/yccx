<?php
/**
 * Created by PhpStorm.
 * User:LongAnxiang
 * Date: 2018/3/13
 * Time: 19:59
 */

namespace tool;
/*
+--------------------------------------------------------------------------
|   由于在php7.1之后mcrypt_encrypt会被废弃，因此使用openssl_encrypt方法来替换
|   ========================================
|   by Focus
|   =======================================
+---------------------------------------------------------------------------
*/
abstract class OpensslAES
{
    /**向量
     * @var string
     */
    const IV = "Jji89lsLpIoLL91Y";//16位
    /**
     * 默认秘钥
     */
    const KEY = 'ycDlyIAj817721WL';//16位

    /**
     * 解密字符串
     * @param string $data 字符串
     * @param string $key 加密key
     * @param string $iv 向量
     * @return string
     */
    public static function decryptWithOpenssl($data, $key = self::KEY, $iv = self::IV)
    {
        return openssl_decrypt(base64_decode($data), "AES-128-CBC", $key, OPENSSL_RAW_DATA, $iv);
    }

    /**
     * 加密字符串
     * 参考网站： https://segmentfault.com/q/1010000009624263
     * @param string $data 字符串
     * @param string $key 加密key
     * @param string $iv 向量
     * @return string
     */
    public static function encryptWithOpenssl($data, $key = self::KEY, $iv = self::IV)
    {
        return base64_encode(openssl_encrypt($data, "AES-128-CBC", $key, OPENSSL_RAW_DATA, $iv));
    }

}