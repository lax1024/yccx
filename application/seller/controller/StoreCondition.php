<?php

namespace app\seller\controller;

use app\common\controller\SellerAdminBase;
use app\common\model\CarCommon as CarCommonModel;
use app\common\model\Store as StoreModel;
use definition\CarStatus;

/**
 * 店铺条件管理
 * Class AdminUser
 * @package app\manage\controller
 */
class StoreCondition extends SellerAdminBase
{
    protected $store_model;
    protected $car_model;

    protected function _initialize()
    {
        parent::_initialize();
        $this->store_model = new StoreModel();
        $this->car_model = new CarCommonModel();
    }

    /**
     * 店铺管理
     * @param string $keyword
     * @param int $store_status
     * @return mixed
     */
    public function index($keyword = '', $store_status = 0)
    {
        $map = [
            'is_area' => 1,
            'car_num' => array('gt', 0),
        ];
        if ($keyword) {
            $map['seller_id|store_name|address|store_principal'] = ['like', "%{$keyword}%"];
        }
        $store_list = $this->store_model->where($map)->field('id,store_name,car_num')->select();
        $store_list_arr = [];
        foreach ($store_list as $vx) {
            $store_list_arr[intval($vx['id'])] = [
                'id' => $vx['id'],
                'store_name' => $vx['store_name'],
                'car_num' => $vx['car_num'],
                'park_car' => 0,
                'mileage_car' => 0,
                'online_car' => 0,
                'store_status' => 1,//不符合要求
                'store_status_str' => "不符合要求",//符合要求
            ];
        }

        $map_car = [
            'car_status' => CarStatus::$CarStatusNormal['code']
        ];
        $car_list = $this->car_model->where($map_car)->select();
        if (!empty($car_list)) {
            foreach ($car_list as &$value) {
                $this->car_model->formatx($value);
                if (!empty($store_list_arr[intval($value['store_site_id'])])) {
                    $store_list_arr[intval($value['store_site_id'])]['park_car'] = intval($store_list_arr[intval($value['store_site_id'])]['park_car']) + 1;
                    if ($value['car_device'] == 0) {
                        //掉线车辆统计
                        $store_list_arr[intval($value['store_site_id'])]['online_car'] = intval($store_list_arr[intval($value['store_site_id'])]['online_car']) + 1;
                    }
                    if ($value['driving_mileage_num'] < 120) {
                        //续航大于120km 的车辆
                        $store_list_arr[intval($value['store_site_id'])]['mileage_car'] = intval($store_list_arr[intval($value['store_site_id'])]['mileage_car']) + 1;
                    }
                }
            }
        }
        $store_list_arr_out = [];
        foreach ($store_list_arr as $valuex) {
            $is_has = intval($valuex['park_car']) - intval($valuex['online_car']) - intval($valuex['mileage_car']);
            if ($is_has >= $valuex['car_num']) {
                $valuex['store_status'] = 2;
                $valuex['store_status_str'] = "符合要求";
            }
            if (intval($store_status) == 0) {
                $store_list_arr_out[] = $valuex;
            } else if (intval($store_status) == 1) {
                if ($valuex['store_status'] == 1) {
                    $store_list_arr_out[] = $valuex;
                }
            } else if (intval($store_status) == 2) {
                if ($valuex['store_status'] == 2) {
                    $store_list_arr_out[] = $valuex;
                }
            }
        }
        return $this->fetch('index', ['store_list' => $store_list_arr_out, 'keyword' => $keyword, "store_status" => $store_status]);
    }
}