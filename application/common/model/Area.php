<?php

namespace app\common\model;

/**
 *五级行政区 数据模型
 */
use baidu\baiduMap;
use think\Cache;
use think\Model;

class Area extends Model
{
    /**
     * 获取 指定数据
     * @param $id 数据id
     * @return array|false|\PDOStatement|string|Model
     */
    public function get_area($id)
    {
        $out_data = $this->where(array('id' => $id))->find();
        return $out_data;
    }

    /**
     * 获取 指定数据名称
     * @param $id 数据id
     * @return string 地区名称
     */
    public function get_area_name($id)
    {
        $out_data = $this->where(array('id' => $id))->field('name')->find();
        if (empty($out_data)) {
            return "";
        }
        return $out_data['name'];
    }

    /**
     * 搜索城市
     * @param string $keyword
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function get_city_search($keyword = '')
    {
        $map['type'] = 2;
        $map['name'] = ['like', "%{$keyword}%"];
        $out_data_list = $this->where($map)->order('path ASC')->select();
        foreach ($out_data_list as &$value) {
            $value['name'] = str_replace('市', '', $value['name']);
            $value['name'] = str_replace('地区', '', $value['name']);
        }
        return $out_data_list;
    }

    /**
     * 获取子节点列表
     * @param $pid 父级id
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function get_children_list($pid)
    {
        if (empty($pid)) {
            $out_data_list = $this->where(array('type' => 1))->order('path ASC')->select();
        } else {
            $out_data_list = $this->where(array('parentId' => $pid))->order('sort DESC')->select();
        }
        return $out_data_list;
    }

    /**
     * 获取市 常规地址 火车站 汽车站 机场 热门
     * @param string $city
     * @param int $len
     * @return array|mixed
     */
    public function get_preferred_address($city = '', $len = 6)
    {
        $baidumap = new baiduMap();
        if (empty($city)) {
            return array();
        }
        $md5_city = md5($city);
        $city_data = Cache::get($md5_city);
        if (empty($city_data)) {
            $city_search_airport_json = $baidumap->get_keyword_city($city, $city . '机场');
            $city_data['airport']['name'] = "机场";
            $city_data['airport']['img'] = "airport.png";
            $list_airport = json_decode($city_search_airport_json)->data;
            if (count($list_airport) >= $len) {
                $city_data['airport']['list'] = array_slice($list_airport, 0, $len);
            } else {
                $city_data['airport']['list'] = $list_airport;
            }
            $city_search_hot_json = $baidumap->get_keyword_city($city, $city . '中心');
            $city_data['hot']['name'] = "热门";
            $city_data['hot']['img'] = "hot.png";
            $list_hot = json_decode($city_search_hot_json)->data;
            if (count($list_hot) >= $len) {
                $city_data['hot']['list'] = array_slice($list_hot, 0, $len);
            } else {
                $city_data['hot']['list'] = $list_hot;
            }
            $city_search_railway_json = $baidumap->get_keyword_city($city, $city . '火车站');
            $city_data['railway']['name'] = "火车站";
            $city_data['railway']['img'] = "railway.png";
            $list_railway = json_decode($city_search_railway_json)->data;
            if (count($list_railway) >= $len) {
                $city_data['railway']['list'] = array_slice($list_railway, 0, $len);
            } else {
                $city_data['railway']['list'] = $list_railway;
            }
            $city_search_motor_json = $baidumap->get_keyword_city($city, $city . '汽车站');
            $city_data['motor']['name'] = "汽车站";
            $city_data['motor']['img'] = "motor.png";
            $list_motor = json_decode($city_search_motor_json)->data;
            if (count($list_motor) >= $len) {
                $city_data['motor']['list'] = array_slice($list_motor, 0, $len);
            } else {
                $city_data['motor']['list'] = $list_motor;
            }
            Cache::set($md5_city, $city_data);
        }
        return $city_data;
    }

    /**
     * 搜索关键词
     * @param $city
     * @param $keyword
     * @return array
     */
    public function get_keyword_city($city, $keyword)
    {
        $baidumap = new baiduMap();
        if (empty($city) || empty($keyword)) {
            return array();
        }
        $city_search_json = $baidumap->get_keyword_city($city, $keyword);
        $city_search_data = json_decode($city_search_json)->data;
        return $city_search_data;
    }

    /**
     * 根据经纬度坐标  获取城市名称
     * @param $lng
     * @param $lat
     * @return array
     */
    public function get_city_address($lng, $lat)
    {
        $baidumap = new baiduMap();
        if (empty($lng) || empty($lat)) {
            return array();
        }
        $outjson = $baidumap->get_city_address($lng, $lat);
        $out_json_data = json_decode($outjson, true);
        $out_data['code'] = 0;
        $out_data['data'] = array(
            'city' => str_replace('市', '', $out_json_data['data']['addressComponent']['city']),
            'district' => $out_json_data['data']['addressComponent']['district'],
            'street' => $out_json_data['data']['addressComponent']['street'],
            'description' => $out_json_data['data']['formatted_address'],
            'data' => $out_json_data['data']['addressComponent'],
            'lng' => $out_json_data['data']['location']['lng'],
            'lat' => $out_json_data['data']['location']['lat']
        );
        $out_data['info'] = '获取成功';
        return $out_data;
    }

    /**
     * 根据经纬度坐标  获取城市名称
     * @param $lng
     * @param $lat
     * @return array
     */
    public function get_city_address_new($lng, $lat)
    {
        $baidumap = new baiduMap();
        if (empty($lng) || empty($lat)) {
            return array();
        }
        $outjson = $baidumap->get_city_address($lng, $lat);
        $out_json_data = json_decode($outjson, true);
        $out_data['code'] = 0;
        $out_data['data'] = $out_json_data['data'];
        $out_data['info'] = '获取成功';
        return $out_data;
    }
}