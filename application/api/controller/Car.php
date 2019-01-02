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
use think\Config;

/**
 * 车辆信息列表 对外公共
 * Class Ueditor
 * @package app\api\controller
 */
class Car extends HomeBase
{
    private $carbrand_model;
    private $carcommon_model;
    private $store_model;
    private $carseries_model;
    private $cartype_model;

    protected function _initialize()
    {
        parent::_initialize();
        $this->carcommon_model = new CarCommonModel();
        $this->store_model = new StoreModel();
        $this->carbrand_model = new CarBrandModel();
        $this->carseries_model = new CarSeriesModel();
        $this->cartype_model = new CarTypeModel();
    }

    /***
     * 获取车辆信息列表 pid 为空时 表示获取省份
     * @param int $pid
     * @param int $type
     */
    public function get_brand_list($pid = 0, $type = 1)
    {
        if ($type == 1) {
            $brand_list = $this->carbrand_model->select();
        } elseif ($type == 2) {
            $brand_list = $this->carseries_model->where(array('brand_id' => $pid))->select();
        } else {
            $brand_list = $this->cartype_model->where(array('series_id' => $pid))->select();
        }
        $dataout = array(
            'code' => 0,
            'info' => '获取成功',
            'data' => $brand_list
        );
        exit(json_encode($dataout));
    }

    /**
     * 获取可租用车辆列表
     * 输入方式 GET
     * @param int $goods_type 常规车辆1 新能源车2
     * @param string $slng 取车经度
     * @param string $slat 取车纬度
     * @param string $elng 还车经度
     * @param string $elat 还车纬度
     * @param string $acquire_time 取用时间
     * @param string $return_time 归还时间
     * @param int $is_map 是否是地图
     * @param int $km 范围
     */
    public function get_carcommon_list($goods_type = 1, $slng = '106.717801', $slat = '26.58804', $elng = '', $elat = '', $acquire_time = '', $return_time = '', $km = 160, $is_map = 0)
    {
        /*
         * 如果有缓存
         */
//        if (Cache::has('get_carcommon_list' . $is_map . $goods_type)) {
//            $list_data = Cache::get('get_carcommon_list' . $is_map . $goods_type);
//            $out_data['code'] = 0;
//            $out_data['data'] = $list_data;
//            $out_data['info'] = "获取成功";
//            out_json_data($out_data);
//        }
        $km = 200;
        if ($goods_type == GoodsType::$GoodsTypeCar['code'] || $goods_type == 0) {
            if (empty($acquire_time)) {
                $acquire_time = time();
            } else {
                $acquire_time = strtotime($acquire_time);
            }
            if (empty($return_time)) {
                $return_time = time() + 259200;
            } else {
                $return_time = strtotime($return_time);
            }
            if (($return_time - $acquire_time) < 1800) {
                $dataout['code'] = 2;
                $dataout['info'] = '使用时间必须大于30分钟';
                out_json_data($dataout);
            }
        }
        //获取经纬度范围(取车)
        $slng_lat = get_max_min_lng_lat($slng, $slat, $km);
        $store_key_id_list = array();
        $is_other = false;
        if (!empty($elng) && !empty($elat)) {
            //获取经纬度范围(还车)
            $elng_lat = get_max_min_lng_lat($elng, $elat, $km);
            $emaxlng = $elng_lat['maxLng'];//最大经度
            $eminLng = $elng_lat['minLng'];//最小经度
            $emaxLat = $elng_lat['maxLat'];//最大纬度
            $eminLat = $elng_lat['minLat'];//最小纬度
            $emap['location_longitude'] = [['EGT', $eminLng], ['ELT', $emaxlng]];
            $emap['location_latitude'] = [['EGT', $eminLat], ['ELT', $emaxLat]];
            $order = " id ASC ";
            $store_key_id_list = $this->store_model->getStoreKeyIdList($emap, $order, 0, 1000);
            $is_other = true;
        }
        $maxlng = $slng_lat['maxLng'];//最大经度
        $minLng = $slng_lat['minLng'];//最小经度
        $maxLat = $slng_lat['maxLat'];//最大纬度
        $minLat = $slng_lat['minLat'];//最小纬度
        $map['location_longitude'] = [['EGT', $minLng], ['ELT', $maxlng]];
        $map['location_latitude'] = [['EGT', $minLat], ['ELT', $maxLat]];
        $map['goods_type'] = $goods_type;
        $order = " id ASC ";
        $car_list = array();
        $level_site_list = array();
        if ($goods_type == GoodsType::$GoodsTypeCar['code']) {
            $map['car_status'] = array('in', "'" . CarStatus::$CarStatusNormal['code'] . "," . CarStatus::$CarStatusInuse['code'] . "'");
            //获取可以出租的车辆
            $car_list = $this->carcommon_model->getCarCommonList($map, $order, 0, 1000);
            //常规汽车
            $car_list = group_car_grade($car_list, $slng, $slat, $acquire_time, $return_time, $is_other, $store_key_id_list);
        } else if ($goods_type == GoodsType::$GoodsTypeElectrocar['code']) {
            $map['car_status'] = CarStatus::$CarStatusNormal['code'];
            //获取可以出租的车辆
            $car_list = $this->carcommon_model->getCarCommonList($map, $order, 0, 1000);
            //新能源车
            if (!empty($is_map)) {
                $car_list = group_elecar_map($car_list, $slng, $slat);
                foreach ($car_list as $valuex) {
                    $level_site_list[intval($valuex['store']['level'])][] = $valuex;
                }
            } else {
                $car_list = group_elecar_grade($car_list, $slng, $slat, $is_other, $store_key_id_list);
            }
        }
//        else if ($goods_type == 0) {
//            $map['goods_type'] = GoodsType::$GoodsTypeCar['code'];
//            $map['car_status'] = array('in', "'" . CarStatus::$CarStatusNormal['code'] . "," . CarStatus::$CarStatusInuse['code'] . "'");
//            //获取可以出租的车辆
//            $car_list1 = $this->carcommon_model->getCarCommonList($map, $order, 0, 100);
//            //常规汽车
//            $car_list1 = group_car_grade($car_list1, $slng, $slat, $acquire_time, $return_time, $is_other, $store_key_id_list);
//            $map['goods_type'] = GoodsType::$GoodsTypeElectrocar['code'];
//            $map['car_status'] = CarStatus::$CarStatusNormal['code'];
//            //获取可以出租的车辆
//            $car_list2 = $this->carcommon_model->getCarCommonList($map, $order, 0, 100);
//            //新能源车
//            $car_list2 = group_elecar_grade($car_list2, $slng, $slat, $is_other, $store_key_id_list);
//            $car_list = array_merge($car_list1, $car_list2);
//        }
//        Cache::set('get_carcommon_list' . $is_map . $goods_type, $car_list, '30')
        $out_data['code'] = 0;
        if (!empty($is_map)) {
            $level_site_list['3'] = rank_elecar_grade($level_site_list['3'], 'distance');
            $out_data['level'] = $level_site_list;
        } else {
            $out_data['data'] = $car_list;
        }
        $out_data['info'] = "获取成功";
        out_json_data($out_data);
    }

    /**
     * 或者车牌号 查询车辆信息
     * @param $licence_plate
     */
    public function get_qrcode_carmap($licence_plate)
    {
        $out_data['code'] = 1;
        $out_data['info'] = "参数不能为空";
        if (empty($licence_plate)) {
            out_json_data($out_data);
        }
        $car_data = $this->carcommon_model->where(array('licence_plate' => $licence_plate, 'goods_type' => GoodsType::$GoodsTypeElectrocar['code']))->find();
        if (empty($car_data)) {
            $out_data['code'] = 2;
            $out_data['info'] = "无该车辆信息";
            out_json_data($out_data);
        }
        if ($car_data['car_status'] != CarStatus::$CarStatusNormal['code']) {
            $out_data['code'] = 3;
            $info = CarStatus::$CARSTATUS_CODE[intval($car_data['car_status'])];
            $out_data['info'] = $info;
            out_json_data($out_data);
        }
        $redis = new Redis();
        if (!$redis->has("login:" . $car_data['device_number'])) {
            $out_data['code'] = 101;
            $out_data['info'] = "车机已掉线";
            return $out_data;
        } else {
            $out_data_device = $redis->get("status:" . $car_data['device_number']);
            if (empty($out_data_device)) {
                $out_data['code'] = 102;
                $out_data['info'] = "车机数据有误";
                return $out_data;
            }
            $car_device_data = json_decode($out_data_device, true);
        }

        $driving_mileage = $car_device_data['drivingMileage'];
        $energy = $car_device_data['energy'];
        if (empty($energy)) {
            $energy = (intval($driving_mileage) / 160) * 100;
        } else if (empty($driving_mileage)) {
            $course = Config::get('course');
            $km = $course[intval($car_data['series_id'])]['drive_km'];
            $driving_mileage = intval(floatval($car_data['energy']) / 100 * $km);
        }
        $car_data['location_latitude'] = $car_device_data['latitude'];
        $car_data['location_longitude'] = $car_device_data['longitude'];
        $renewal = $energy . "%(" . $driving_mileage . "km)";
        $field = 'id,location_longitude,location_latitude,store_name';
        $store_key_data = $this->store_model->getStoreField($car_data['store_site_id'], $field);
        $car_data['energy'] = $energy;
        $car_data['renewal'] = $renewal;
        $car_data['driving_mileage'] = $driving_mileage;
        $car_data_out = [
            'store' => $store_key_data,
            'car' => $car_data
        ];
        unset($car_data['engine_vin']);
        unset($car_data['car_photos']);
        unset($car_data['device_number']);
        $out_data['code'] = 0;
        $out_data['data'] = $car_data_out;
        $out_data['info'] = "获取成功";
        out_json_data($out_data);
    }

    /**
     * 根据二维码 或者车牌号 查询车辆信息
     * @param $licence_plate
     */
    public function get_qrcode_car($licence_plate)
    {
        $out_data['code'] = 1;
        $out_data['info'] = "参数不能为空";
        if (empty($licence_plate)) {
            out_json_data($out_data);
        }
        $car_data = $this->carcommon_model->where(array('licence_plate' => $licence_plate, 'goods_type' => GoodsType::$GoodsTypeElectrocar['code']))->find();
        if (empty($car_data)) {
            $out_data['code'] = 2;
            $out_data['info'] = "无该车辆信息";
            out_json_data($out_data);
        }
        if ($car_data['car_status'] != CarStatus::$CarStatusNormal['code']) {
            $out_data['code'] = 3;
            $info = CarStatus::$CARSTATUS_CODE[intval($car_data['car_status'])];
            $out_data['info'] = $info;
            out_json_data($out_data);
        }

        $redis = new Redis();
        if (!$redis->has("login:" . $car_data['device_number'])) {
            $out_data['code'] = 101;
            $out_data['info'] = "车机已掉线";
            return $out_data;
        } else {
            $out_data_device = $redis->get("status:" . $car_data['device_number']);
            if (empty($out_data_device)) {
                $out_data['code'] = 102;
                $out_data['info'] = "车机数据有误";
                return $out_data;
            }
            $car_device_data = json_decode($out_data_device, true);
        }
        $car_data['driving_mileage'] = $car_device_data['drivingMileage'];
        $car_data['energy'] = $car_device_data['energy'];
        if (empty($car_data['energy'])) {
            $car_data['energy'] = (intval($car_data['driving_mileage']) / 160) * 100;
        } else if (empty($car_data['driving_mileage'])) {
            $course = Config::get('course');
            $km = $course[intval($car_data['series_id'])]['drive_km'];
            $car_data['driving_mileage'] = intval(floatval($car_data['energy']) / 100 * $km);
        }
        $car_data['location_latitude'] = $car_device_data['latitude'];
        $car_data['location_longitude'] = $car_device_data['longitude'];
        $car_data['renewal'] = $car_data['energy'] . "%(" . $car_data['driving_mileage'] . "km)";
        unset($car_data['engine_vin']);
        unset($car_data['car_photos']);
        unset($car_data['device_number']);
        $out_data['code'] = 0;
        $out_data['data'] = $car_data;
        $out_data['info'] = "获取成功";
        out_json_data($out_data);
    }

    /**
     * 根据二维码 或者车牌号 查询车辆信息
     * @param $licence_plate
     */
    public function get_qrcode_car_oper($licence_plate)
    {
        $out_data['code'] = 1;
        $out_data['info'] = "参数不能为空";
        if (empty($licence_plate)) {
            out_json_data($out_data);
        }
        $car_data = $this->carcommon_model->where(array('licence_plate' => $licence_plate, 'goods_type' => GoodsType::$GoodsTypeElectrocar['code']))->find();
        if (empty($car_data)) {
            $out_data['code'] = 2;
            $out_data['info'] = "无该车辆信息";
            out_json_data($out_data);
        }
        $return_data_device = getCarDevice($car_data['device_number']);
        $car_data['car_device_str'] = "在线";
        $car_data['car_device'] = 1;
        $car_data['driving_mileage'] = "无数据";
        $car_data['energy'] = "无数据";
        $car_data['voltage'] = "无数据";
        $car_data['location_latitude'] = 0;
        $car_data['location_longitude'] = 0;
        $car_data['driving_mileage_num'] = 0;
        $car_data['odometer'] = 0;
        if (empty($return_data_device['code'])) {
            $data_device = $return_data_device['data'];
            $car_data['car_device_str'] = $data_device['car_device_str'];
            $car_data['car_device'] = $data_device['car_device'];
            $car_data['driving_mileage'] = $data_device['driving_mileage'];
            $car_data['energy'] = $data_device['energy'];
            $car_data['voltage'] = $data_device['voltage'];
            $car_data['location_latitude'] = $data_device['location_latitude'];
            $car_data['location_longitude'] = $data_device['location_longitude'];
            $car_data['driving_mileage_num'] = $data_device['driving_mileage_num'];
            $car_data['odometer'] = $data_device['odometer'];
        }
        unset($car_data['engine_vin']);
        unset($car_data['car_photos']);
        unset($car_data['device_number']);
        $out_data['code'] = 0;
        $out_data['data'] = $car_data;
        $out_data['info'] = "获取成功";
        out_json_data($out_data);
    }

    /**
     * 获取可还车点
     * @param $car_id 车id
     * @param $lng 车经度
     * @param $lat 车纬度
     */
    public function get_elesite_list($car_id = '', $lng = '', $lat = '')
    {
        $out_data['code'] = 1;
        $out_data['info'] = "参数不能为空";
        if (empty($car_id)) {
            out_json_data($out_data);
        }
        $car_data = $this->carcommon_model->where(array('id' => $car_id))->field('store_key_id')->find();
        if (empty($car_data)) {
            $out_data['code'] = 2;
            $out_data['info'] = "车辆数据有误";
            out_json_data($out_data);
        }
        $store_key_id = $car_data['store_key_id'];
        //获取经纬度范围(还车)
//        $elng_lat = get_max_min_lng_lat($lng, $lat, 200);
//        $emaxlng = $elng_lat['maxLng'];//最大经度
//        $eminLng = $elng_lat['minLng'];//最小经度
//        $emaxLat = $elng_lat['maxLat'];//最大纬度
//        $eminLat = $elng_lat['minLat'];//最小纬度
        $emap['store_key_id'] = $store_key_id;
        $emap['store_status'] = 0;
        $emap['store_type'] = StoreType::$StoreTypeElectrocar['code'];
//        $emap['location_longitude'] = [['EGT', $eminLng], ['ELT', $emaxlng]];
//        $emap['location_latitude'] = [['EGT', $eminLat], ['ELT', $emaxLat]];
        $store_key_list = $this->store_model->getSiteList($emap);
        $store_key_list = array2levelc($store_key_list);
        $store_key_site_list = [];
        foreach ($store_key_list as &$value) {
            if (intval($value['level']) == 3) {
                $distance = get_distance($lng, $lat, $value['location_longitude'], $value['location_latitude']);
                $value->distance = $distance;
                $store_key_site_list[] = $value;
            }
        }
        $store_key_site_list = json_encode($store_key_site_list);
        $store_key_site_list = json_decode($store_key_site_list, true);
        $store_key_site_list = rank_elecar_grade($store_key_site_list, 'distance');
        $out_data['code'] = 0;
        $out_data['data'] = $store_key_site_list;
        $out_data['info'] = "获取成功";
        out_json_data($out_data);
    }

    /**
     * 获取车门店
     * @param $lng 车经度
     * @param $lat 车纬度
     */
    public function get_carsite_list($lng, $lat)
    {
        $out_data['code'] = 1;
        $out_data['info'] = "参数不能为空";
        $emap['store_status'] = 0;
        $emap['store_type'] = StoreType::$StoreTypeCar['code'];
//        $emap['location_longitude'] = [['EGT', $eminLng], ['ELT', $emaxlng]];
//        $emap['location_latitude'] = [['EGT', $eminLat], ['ELT', $emaxLat]];
        $store_key_list = $this->store_model->getSiteList($emap);
        foreach ($store_key_list as &$value) {
            $distance = get_distance($lng, $lat, $value['location_longitude'], $value['location_latitude']);
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

    /**
     * 通过车辆id 获取车辆信息
     */
    public function get_car_id($id)
    {
        $dataout = array(
            'code' => 1,
            'info' => '参数有误',
        );
        if (empty($id)) {
            out_json_data($dataout);
        }
        $acquire_car = $this->carcommon_model->getCarCommon($id, true);
        $car_data = $acquire_car['data'];
        $redis = new Redis();
        if (!$redis->has("login:" . $car_data['device_number'])) {
            $out_data['code'] = 101;
            $out_data['info'] = "车机已掉线";
            return $out_data;
        } else {
            $out_data_device = $redis->get("status:" . $car_data['device_number']);
            if (empty($out_data_device)) {
                $out_data['code'] = 102;
                $out_data['info'] = "车机数据有误";
                return $out_data;
            }
            $car_device_data = json_decode($out_data_device, true);
        }
        $car_data['driving_mileage'] = $car_device_data['drivingMileage'];
        $car_data['energy'] = $car_device_data['energy'];
        if (empty($car_data['energy'])) {
            $car_data['energy'] = (intval($car_data['driving_mileage']) / 160) * 100;
        } else if (empty($car_data['driving_mileage'])) {
            $course = Config::get('course');
            $km = $course[intval($car_data['series_id'])]['drive_km'];
            $car_data['driving_mileage'] = intval(floatval($car_data['energy']) / 100 * $km);
        }
        $car_data['location_latitude'] = $car_device_data['latitude'];
        $car_data['location_longitude'] = $car_device_data['longitude'];
        $dataout['code'] = 0;
        $dataout['data'] = $car_data;
        $dataout['info'] = '获取成功';
        out_json_data($dataout);
    }

    /**
     * 通过车辆id 获取车辆信息
     */
    public function get_car_id_task($id)
    {
        $dataout = array(
            'code' => 1,
            'info' => '参数有误',
        );
        if (empty($id)) {
            out_json_data($dataout);
        }
        $acquire_car = $this->carcommon_model->getCarCommon($id, true);
        $car_data = $acquire_car['data'];
        $dataout['code'] = 0;
        $dataout['data'] = $car_data;
        $dataout['info'] = '获取成功';
        out_json_data($dataout);
    }
}