<?php
/**
 * Created by PhpStorm.
 * User:LongAnxiang
 * Date: 2018/3/12
 * Time: 23:32
 */

namespace baidu;

/**
 * 百度地图检查
 * Class baiduMap
 * @package baidu
 */
class baiduMap
{
    private $ak = 'DplTpLn9EpGEIwimAKUNscH6YWGAyoH3';
    /**
     * 百度关键字搜索
     * @param $city
     * @param $keyword
     * @return bool|mixed|string
     */
    public function get_keyword_city($city, $keyword)
    {
        $url = "http://api.map.baidu.com/place/v2/suggestion?query=" . $keyword . "&region=" . $city . "&city_limit=true&output=json&ak=".$this->ak;
        $out_json = file_get_contents($url);
        $out_json = str_replace('status', 'code', $out_json);
        $out_json = str_replace('message', 'info', $out_json);
        $out_json = str_replace('result', 'data', $out_json);
        return $out_json;
    }

    /**
     * 根据 经纬度获取 城市数据
     * @param $lng
     * @param $lat
     * @return bool|mixed|string
     */
    public function get_city_address($lng,$lat){
//        $url = "http://api.map.baidu.com/cloudrgc/v1?location=".$lat.",".$lng."&geotable_id=135675&coord_type=bd09ll&ak=".$this->ak;
        $url = "http://api.map.baidu.com/geocoder/v2/?location=".$lat.",".$lng."&output=json&pois=1&ak=".$this->ak;
        $out_json = file_get_contents($url);
        $out_json = str_replace('status', 'code', $out_json);
        $out_json = str_replace('message', 'info', $out_json);
        $out_json = str_replace('result', 'data', $out_json);
        return $out_json;
    }


}