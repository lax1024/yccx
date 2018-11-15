<?php

namespace chinadatapay;

use think\Config;

/**
 * Created by PhpStorm.
 * User: 龙安祥
 * Date: 2018/6/22
 * Time: 18:31
 */
abstract class ChinaDataApi
{
    static private $personal_face_key = "e25495eb41a8fd8943c79fb8a9405a2f";//人像比对（尊享版）KEY
    static private $personal_face_url = "http://api.chinadatapay.com/communication/personal/2061";//人像比对（尊享版）请求地址
    static private $driving_key = "28aaa2e257a71633c949cb409dabb512";//驾驶证三要素核验KEY
    static private $driving_url = "http://api.chinadatapay.com/communication/personal/2099";//驾驶证三要素核验 请求地址
    static private $personal_key = "62efed20a2bcf6c64e5e553fea8915f4";//实名认证(尊享版)KEY
    static private $personal_url = "http://api.chinadatapay.com/communication/personal/1882";//实名认证(尊享版) 请求地址
    static private $driving_ocr_key = "be883783f837d0cbaf18b5161e35364a";//驾驶证OCR识别KEY
    static private $driving_ocr_url = "http://api.chinadatapay.com/trade/user/2009";//驾驶证OCR识别 请求地址
    static private $idcard_ocr_key = "ae9de93f87f1bd7075cbdbb34c7247de";//身份证OCR识别（尊享版）KEY
    static private $idcard_ocr_url = "http://api.chinadatapay.com/trade/user/1985";//身份证OCR识别（尊享版）请求地址

    /**
     * 人像对比
     * @param $name
     * @param $idcard
     * @param $image
     * @return string
     */
    static public function personal_face($name, $idcard, $image)
    {
        $post_data = [
            'key' => ChinaDataApi::$personal_face_key,
            'name' => $name,
            'idcard' => $idcard,
            'image' => $image
        ];
        $out_data = send_post(ChinaDataApi::$personal_face_url, $post_data);
        if ($out_data['code'] == "10000") {
            $face_effective = Config::get('face_effective');
            $score = $out_data['data']['score'];
            if (floatval($score) < $face_effective) {
                $out_data['code'] = "10022";
                $out_data['message'] = "人像对比未能通过";
                $out_data['data'] = [];
            }
        }
        return $out_data;
    }

    /**
     * 驾驶证三要素
     * @param $name 姓名
     * @param $idcard 身份证号
     * @param $recordId 档案编号
     * @return string
     */
    static public function driving($name, $idcard, $recordId)
    {
        $post_data = [
            'key' => ChinaDataApi::$driving_key,
            'name' => $name,
            'idCard' => $idcard,
            'recordId' => $recordId
        ];
        $out_data = send_post(ChinaDataApi::$driving_url, $post_data);
        return $out_data;
    }

    /**
     * 实名认证
     * @param $name
     * @param $idcard
     * @return string
     */
    static public function personal($name, $idcard)
    {
        $post_data = [
            'key' => ChinaDataApi::$personal_key,
            'name' => $name,
            'idcard' => $idcard,
        ];
        $out_data = send_post(ChinaDataApi::$personal_url, $post_data);
        return $out_data;
    }


    /**
     * 驾驶证ORC
     * @param $image
     * @return string
     */
    static public function driving_ocr($image)
    {
        $post_data = [
            'key' => ChinaDataApi::$driving_ocr_key,
            'image' => $image
        ];
        $out_data = send_post(ChinaDataApi::$driving_ocr_url, $post_data);
        return $out_data;
    }


    /**
     * 身份证ORC
     * @param $image
     * @return string
     */
    static public function idcard_ocr($image)
    {
        $post_data = [
            'key' => ChinaDataApi::$idcard_ocr_key,
            'photo' => $image
        ];
        $out_data = send_post(ChinaDataApi::$idcard_ocr_url, $post_data);
        return $out_data;
    }

}