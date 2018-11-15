<?php

namespace app\api\controller;

use app\common\controller\HomeBase;
use app\common\model\CarCommon as CarCommonModel;
use app\common\model\Store as StoreModel;
use app\common\model\CarBrand as CarBrandModel;
use app\common\model\CarSeries as CarSeriesModel;
use app\common\model\CarType as CarTypeModel;
use app\common\model\Store;
use definition\CarStatus;
use definition\GoodsType;
use definition\StoreType;
use think\Cache;
use think\cache\driver\Redis;

/**
 * 充电桩信息列表 对外公共
 * Class Ueditor
 * @package app\api\controller
 */
class Charging extends HomeBase
{
    private $store_model;

    protected function _initialize()
    {
        parent::_initialize();
        $this->store_model = new StoreModel();
    }

    /**
     * 获取车门店
     * @param $lng 车经度
     * @param $lat 车纬度
     */
    public function get_charging_site_list($lng = '106.578', $lat = '26.443')
    {
        $out_data['code'] = 1;
        $out_data['info'] = "参数不能为空";
        $emap['store_status'] = 0;
        $emap['store_type'] = StoreType::$StoreTypeCharging['code'];
//        $emap['location_longitude'] = [['EGT', $eminLng], ['ELT', $emaxlng]];
//        $emap['location_latitude'] = [['EGT', $eminLat], ['ELT', $emaxLat]];
        $store_key_list = $this->store_model->getCSiteList($emap);
        foreach ($store_key_list as &$value) {
            $distance = get_distance($lng, $lat, $value['location_longitude'], $value['location_latitude']);
            $charging = [
                'tag' => ['有空闲', '支持排队', '对外开放'],
                'direct_current' => rand(1, 10),
                'alternating_current' => rand(1, 10),
                'price' => 1.2,
                'parking_price' => 4.00,
                'remark' => "停车场不适合车型（公交车、大型物流车）"
            ];
            $value->charging = $charging;
            $value->distance = $distance;
        }
        $store_key_list = json_encode($store_key_list);
        $store_key_list = json_decode($store_key_list, true);
        $store_key_list = rank_elecar_grade($store_key_list, 'distance');
        $out_data['code'] = 0;
        $out_data['data'] = $store_key_list;
        $out_data['info'] = "获取成功";
        out_json_data($out_data);
    }


}