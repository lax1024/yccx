<?php

namespace app\api\controller;

use app\common\controller\HomeBase;
use app\common\model\Area as AreaModel;
use tool\str2PY;

/**
 * 用户地址信息列表 对外公共
 * Class Ueditor
 * @package app\api\controller
 */
class Area extends HomeBase
{
    private $area_model;

    protected function _initialize()
    {
        parent::_initialize();
        $this->area_model = new AreaModel();
    }

    /**
     * 生成市数据
     */
    public function create_city_list()
    {
        $str2 = new str2PY();

        $initial_list = $str2->getPinyins();
        $city_list_data = array();
        foreach ($initial_list as $value) {
            $map = array(
                'type' => 2,
                'initial' => $value,
            );
            $city_data = $this->area_model->where($map)->order('path ASC')->select();
            foreach ($city_data as &$v) {
                $v['name'] = str_replace('市', '', $v['name']);
                $v['name'] = str_replace('地区', '', $v['name']);
            }
            $city_list_data[$value] = $city_data;
        }
        $out_data['code'] = 0;
        $out_data['data'] = $city_list_data;
        $out_data['info'] = '获取成功';
        file_put_contents('public\data\area_city_list.json', json_encode($out_data));
        exit("生成完成");
    }

    /**
     * 获取城市列表
     */
    public function get_city_list()
    {
        $city_list_json = file_get_contents('public\data\area_city_list.json');
        exit($city_list_json);
    }

    /**
     * 搜索城市
     * @param string $keyword
     */
    public function get_city_search($keyword = '')
    {
        if (empty($keyword)) {
            $out_data = array(
                'code' => 100,
                'info' => "参数有误",
            );
            out_json_data($out_data);
        }
        $city_search_area_data = $this->area_model->get_city_search($keyword);
        $out_data = array(
            'code' => 0,
            'data' => $city_search_area_data,
            'info' => 0,
        );
        exit(json_encode($out_data));
    }

    /**
     * 获取城市区域信息
     * @param string $city
     * @param string $keyword
     */
    public function get_city_search_area($city = '', $keyword = '')
    {
        if (empty($city) || empty($keyword)) {
            $out_data = array(
                'code' => 100,
                'info' => "参数有误",
            );
            exit(json_encode($out_data));
        }
        $city_search_data = $this->area_model->get_keyword_city($city, $keyword);
        $out_data = array(
            'code' => 0,
            'data' => $city_search_data,
            'info' => 0,
        );
        exit(json_encode($out_data));
    }

    /**
     * 城市推荐地址
     * @param string $city
     */
    public function get_city_preferred_address($city = '')
    {
        if (empty($city)) {
            $out_data = array(
                'code' => 100,
                'info' => "参数有误",
            );
            exit(json_encode($out_data));
        }
        $city_data = $this->area_model->get_preferred_address($city, 6);
        $out_data = array(
            'code' => 0,
            'data' => $city_data,
            'info' => '获取成功',
        );
        exit(json_encode($out_data));
    }


    /***
     * 获取地区列表 pid 为空时 表示获取省份
     * @param int $pid
     */
    public function get_province_list($pid = 0)
    {
        $area_list = $this->area_model->get_children_list($pid);
        $dataout = array(
            'code' => 0,
            'info' => '获取成功',
            'data' => $area_list
        );
        exit(json_encode($dataout));
    }

    /**
     * 根据经纬度  获取城市名称
     * @param string $lng
     * @param string $lat
     */
    public function get_city_lng_lat($lng = '', $lat = '')
    {
        $out_city = $this->area_model->get_city_address($lng, $lat);
        out_json_data($out_city);
    }
    /**
     * 根据经纬度  获取城市名称
     * @param string $lng
     * @param string $lat
     */
    public function get_city_lng_lat_new($lng = '', $lat = '')
    {
        $out_city = $this->area_model->get_city_address_new($lng, $lat);
        out_json_data($out_city);
    }
}