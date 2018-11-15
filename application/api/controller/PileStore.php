<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/12
 * Time: 19:31
 */

namespace app\api\controller;


use think\Controller;
use think\Request;
use app\common\model\PileStore as PileStoreModel;
use tool\CalculateTools;

class PileStore extends Controller
{
    protected $store_model;

    function __construct(Request $request = null)
    {
        $this->store_model = new PileStoreModel();
        parent::__construct($request);
    }

    /**
     * 根据门店id查询门店的相关信息
     * @param string $store_id 所要查询的门店id
     */
    function get_store_by_id($store_id = '')
    {
        $out_data = [
            'code' => 1001,
            'info' => '该门店不存在',
        ];
        if (empty($store_id)) {
            $out_data['code'] = 1002;
            $out_data['info'] = '参数不能为空';
            out_json_data($out_data);
            return;
        }
        $result = $this->store_model->getStoreById($store_id);
        if (empty($result)) {
            $out_data['code'] = 1001;
            $out_data['info'] = '该门店不存在';
            out_json_data($out_data);
            return;
        }
        $out_data['code'] = 0;
        $out_data['info'] = '获取成功';
        $out_data['data'] = $result;
        out_json_data($out_data);
    }

    /**
     * 根据门店id查询门店的相关信息
     * @param string $store_id 所要查询的门店id
     */
    function get_store_list($page = 30, $lng = '106.633326', $lat = '26.408602')
    {
        $out_data = [
            'code' => 1001,
            'info' => '获取失败',
        ];
        if (empty($lng) || empty($lat)) {
            $out_data['code'] = 1002;
            $out_data['info'] = '参数不能为空';
            out_json_data($out_data);
            return;
        }

        $result = $this->store_model->getStoreList($page);
        if (empty($result)) {
            $out_data['code'] = 1001;
            $out_data['info'] = '获取失败';
            out_json_data($out_data);
            return;
        }
        $out_data['code'] = 0;
        $out_data['info'] = '获取成功';
        $store_list = [];
        foreach ($result as &$value) {
            $value['store_tag'] = unserialize($value['store_tag']);
            $value['distance'] = CalculateTools::get_distance(array($lng,$lat),array($value['store_lng'],$value['store_lat']));
            $store_list[] = json_decode(json_encode($value),true);;
        }
        $store_list = two_sort($store_list,'distance');
        $out_data['data'] = $store_list;
        out_json_data($out_data);
    }
}