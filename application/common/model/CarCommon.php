<?php

namespace app\common\model;

/**
 *车辆 数据模型
 */

use definition\CarColor;
use definition\CarGrade;
use definition\CarStatus;
use definition\DeviceStatus;
use definition\GoodsType;
use think\cache\driver\Redis;
use think\Config;
use think\Model;
use tool\CarDeviceTool;

class CarCommon extends Model
{
    protected $insert = ['create_time'];

    /**
     * 创建时间
     * @return bool|string
     */
    protected function setCreateTimeAttr()
    {
        return date('Y-m-d H:i:s');
    }

    /**
     * 获取操作辆数据
     * @param string $store_key_id
     * @param int $dkm
     * @return bool|false|mixed|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOperationCar($store_key_id = '', $dkm = 80)
    {
        $redis = new Redis(['select' => 3]);
        if (!$redis->has('car_operation_' . $store_key_id)) {
            if (!empty($store_key_id)) {
                $field = "id,cartype_id,series_id,goods_type,cartype_name,licence_plate,store_site_id,store_site_name,car_status,device_number,location_longitude,location_latitude,store_key_id,store_key_name";
                $car_list_data = $this->where(['car_status' => ['in', CarStatus::$CarStatusLogoff['code'] . "," . CarStatus::$CarStatusNormal['code']], 'store_key_id' => $store_key_id, 'goods_type' => GoodsType::$GoodsTypeElectrocar['code']])->field($field)->select();
                $car_list = [];
                foreach ($car_list_data as &$value) {
                    $this->formatx($value);
                    if ($value['driving_mileage_num'] < $dkm || $value['car_device'] == 0) {
                        $car_list[] = $value;
                    }
                }
                $redis->set('car_operation_' . $store_key_id, json_encode($car_list), 60);
                return $car_list;
            }
        } else {
            $car_list_json = $redis->get('car_operation_' . $store_key_id);
            $car_list = json_decode($car_list_json, true);
            return $car_list;
        }
        return false;
    }

    /**
     * 获取异常车辆数据
     * @param string $store_key_id
     * @param int $dkm
     * @param int $status
     * 0 => "未选择",
     * 1 => "补电车辆",
     * 2 => "掉线车辆",
     * 3 => "收费站点车辆"
     * 4 => "车辆不在站点范围"
     * 5 => "小电瓶电压过低"
     * @return bool|false|mixed|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUnusualCar($store_key_id = '', $dkm = 80, $status = 0, $is_admin = false)
    {
//        $redis = new Redis(['select' => 3]);
//        if (!$redis->has('car_unusual_' . $store_key_id . "_" . $status)) {
//            $store_model = new Store();
//            $store_data_list = $store_model->where(['store_type' => GoodsType::$GoodsTypeElectrocar['code'], 'store_status' => 0])->field('id,store_park_price,location_longitude,location_latitude')->select();
//            //获取车辆站点信息
//            $store_arr_list = [];
//            foreach ($store_data_list as $value) {
//                $store_arr_list[intval($value['id'])] = $value;
//            }
//            if (!empty($store_key_id)) {
//                $field = "id,cartype_id,goods_type,cartype_name,licence_plate,store_site_id,store_site_name,car_status,device_number,location_longitude,location_latitude,store_key_id,store_key_name";
//                $car_list_data = $this->where(['car_status' => ['in', CarStatus::$CarStatusLogoff['code'] . "," . CarStatus::$CarStatusNormal['code']], 'store_key_id' => $store_key_id, 'goods_type' => GoodsType::$GoodsTypeElectrocar['code']])->field($field)->select();
//                $car_list = [];
//                foreach ($car_list_data as &$value) {
//                    $this->formatx($value);
//                    $store_temp = $store_arr_list[intval($value['store_site_id'])];
////                    print_r($value['location_longitude']."_".$value['location_latitude']."_".$store_temp['location_longitude']."_".$store_temp['location_latitude']);
////                    exit();
//                    $value['distance'] = get_distance($value['location_longitude'], $value['location_latitude'], $store_temp['location_longitude'], $store_temp['location_latitude']);
//                    $value['park'] = $store_temp['store_park_price'];
//                    if ($value['driving_mileage_num'] < $dkm || intval($value['car_device']) == 0 || floatval($value['park']) > 0 || floatval($value['distance']) > 1.5) {
//                        switch (intval($status)) {
//                            case 0:
//                                $car_list[] = $value;
//                                break;
//                            case 1:
//                                if ($value['driving_mileage_num'] < $dkm) {
//                                    $car_list[] = $value;
//                                }
//                                break;
//                            case 2:
//                                if (intval($value['car_device']) == 0) {
//                                    $car_list[] = $value;
//                                }
//                                break;
//                            case 3:
//                                if (floatval($value['park']) > 0) {
//                                    $car_list[] = $value;
//                                }
//                                break;
//                            case 4:
//                                if (floatval($value['distance']) > 1.5) {
//                                    $car_list[] = $value;
//                                }
//                                break;
//                        }
//                    }
//                }
//                $redis->set('car_unusual_' . $store_key_id . "_" . $status, json_encode($car_list), 60);
//                return $car_list;
//            }
//        } else {
//            $car_list_json = $redis->get('car_operation_' . $store_key_id . "_" . $status);
//            $car_list = json_decode($car_list_json, true);
//            return $car_list;
//        }
        $store_model = new Store();
        $store_data_list = $store_model->where(['store_type' => GoodsType::$GoodsTypeElectrocar['code'], 'store_status' => 0])->field('id,store_park_price,location_longitude,location_latitude')->select();
        //获取车辆站点信息
        $store_arr_list = [];
        foreach ($store_data_list as $value) {
            $store_arr_list[intval($value['id'])] = $value;
        }
        if (!empty($store_key_id)) {
            $field = "id,series_id,cartype_id,goods_type,cartype_name,licence_plate,store_site_id,store_site_name,car_status,device_number,location_longitude,location_latitude,store_key_id,store_key_name";
            $map = ['car_status' => CarStatus::$CarStatusNormal['code'], 'store_key_id' => $store_key_id, 'goods_type' => GoodsType::$GoodsTypeElectrocar['code']];
            if ($is_admin) {
                $map = ['car_status' => ['in', CarStatus::$CarStatusLogoff['code'] . "," . CarStatus::$CarStatusNormal['code']], 'store_key_id' => $store_key_id, 'goods_type' => GoodsType::$GoodsTypeElectrocar['code']];
            }
//            $car_list_data = $this->where()->field($field)->select();
            $car_list_data = $this->where($map)->field($field)->select();
            $car_list = [];
            foreach ($car_list_data as &$value) {
                $this->formatx($value);
                $store_temp = $store_arr_list[intval($value['store_site_id'])];
//                    print_r($value['location_longitude']."_".$value['location_latitude']."_".$store_temp['location_longitude']."_".$store_temp['location_latitude']);
//                    exit();
                $value['distance'] = get_distance($value['location_longitude'], $value['location_latitude'], $store_temp['location_longitude'], $store_temp['location_latitude']);
                $value['park'] = $store_temp['store_park_price'];
                if ($value['driving_mileage_num'] < $dkm || intval($value['car_device']) == 0 || floatval($value['park']) > 0 || floatval($value['distance']) > 1.5 || floatval($value['voltage']) < 11.3) {
                    $type = -1;
                    if (floatval($value['park']) > 0) {
                        $type = 6;
                        $value['type_str'] = "收费站点";
                    } else if (floatval($value['voltage']) < 11.3) {
                        $type = 3;
                        $value['type_str'] = "小电瓶电压过低";
                    } else if (intval($value['car_device']) == 0) {
                        $type = 2;
                        $value['type_str'] = "车机掉线";
                    } else if (intval($value['driving_mileage_num']) < $dkm) {
                        $type = 1;
                        $value['type_str'] = "续航里程过低";
                    } else if (floatval($value['distance']) > 1.5) {
                        $value['type_str'] = "不在站点内";
                    }
                    if ($type > 0 && !$is_admin) {
                        $operation = [
                            'goods_id' => $value['id'],
                            'series_id' => $value['series_id'],
                            'device_number' => $value['device_number'],
                            'cartype_name' => $value['cartype_name'],
                            'licence_plate' => $value['licence_plate'],
                            'type' => $type,
                            'remark' => "",
                            'store_site_id' => $value['store_site_id'],
                            'store_site_name' => $value['store_site_name'],
                            'store_key_id' => $value['store_key_id'],
                            'store_key_name' => $value['store_key_name'],
                        ];
                        $car_operation_list_model = new CarOperationList();
                        $car_operation_list_model->addOperationList($operation);
                    }
                    if ($is_admin) {
                        $abnormal = [
                            'goods_id' => $value['id'],
                            'series_id' => $value['series_id'],
                            'device_number' => $value['device_number'],
                            'cartype_name' => $value['cartype_name'],
                            'licence_plate' => $value['licence_plate'],
                            'type' => $type,
                            'remark' => "",
                            'store_site_id' => $value['store_site_id'],
                            'store_site_name' => $value['store_site_name'],
                            'store_key_id' => $value['store_key_id'],
                            'store_key_name' => $value['store_key_name'],
                        ];
                        $car_abnormal_model = new CarAbnormalLog();
                        $gain_time = $car_abnormal_model->addAbnormalLog($abnormal);
                        $value['gain_hour'] = $gain_time['gain_time'];
                    }
                    $value['type'] = $type;
                    switch (intval($status)) {
                        case 0:
                            $car_list[] = $value;
                            break;
                        case 1:
                            if ($value['driving_mileage_num'] < $dkm) {
                                $car_list[] = $value;
//                                故障类型 0续航低于80km  1车机掉线 1小时 2小电瓶电压过低 3收费停车场调度
                            }
                            break;
                        case 2:
                            if (intval($value['car_device']) == 0) {
                                $car_list[] = $value;
                            }
                            break;
                        case 3:
                            if (floatval($value['park']) > 0) {
                                $car_list[] = $value;
                            }
                            break;
                        case 4:
                            if (floatval($value['distance']) > 1.5) {
                                $car_list[] = $value;
                            }
                            break;
                        case 5:
                            if (floatval($value['voltage']) < 11.3) {
                                $car_list[] = $value;
                            }
                            break;
                    }
                }
            }
            return $car_list;
        }
        return false;
    }

    /**
     * 添加平台任务
     * @param $car_oper
     * @param int $type 7平台任务
     * @param string $remark 备注信息
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function addOperationList($car_oper, $type = 7, $remark = "")
    {
        $out_data = [
            'code' => 100,
            'info' => "参数有误"
        ];
        if (empty($car_oper) || empty($type) || empty($remark)) {
            return $out_data;
        }
        $store_model = new Store();
        $store_temp = $store_model->where(['id' => $car_oper['store_site_id']])->field('id,store_park_price,location_longitude,location_latitude')->find();
        $car_oper['distance'] = get_distance($car_oper['location_longitude'], $car_oper['location_latitude'], $store_temp['location_longitude'], $store_temp['location_latitude']);
        $car_oper['park'] = $store_temp['store_park_price'];
        $operation = [
            'goods_id' => $car_oper['id'],
            'series_id' => $car_oper['series_id'],
            'device_number' => $car_oper['device_number'],
            'cartype_name' => $car_oper['cartype_name'],
            'licence_plate' => $car_oper['licence_plate'],
            'type' => $type,
            'remark' => $remark,
            'store_site_id' => $car_oper['store_site_id'],
            'store_site_name' => $car_oper['store_site_name'],
            'store_key_id' => $car_oper['store_key_id'],
            'store_key_name' => $car_oper['store_key_name'],
        ];
        $car_operation_list_model = new CarOperationList();
        $out_data = $car_operation_list_model->addOperationList($operation);
        return $out_data;
    }

    /**
     * 获取车辆管理列表
     * @param array $map
     * @param string $order
     * @param int $page_config
     * @param int $limit
     * @param bool $is_super_admin
     * @param string $store_key_id
     * @return \think\Paginator
     */
    public function getCarList($map = array(), $order = '', $limit = 8, $is_super_admin = false, $store_key_id = '', $page_config)
    {
        //如果不是超级管理员
        if (!$is_super_admin) {
            $map['store_key_id'] = $store_key_id;
        }
        $car_list = $this->where($map)->order($order)->paginate($limit, false, $page_config);
        if (!empty($car_list)) {
            foreach ($car_list as &$value) {
                $this->formatx($value);
            }
        }
        return $car_list;
    }

    /**
     * 格式化数据
     * @param $data
     */
    public function formatx(&$data)
    {
        $car_status = CarStatus::$CARSTATUS_CODE;
        $car_color = CarColor::$CARCOLOR_CODE;
        $car_grade = CarGrade::$CARGRADE_CODE;
        $data['voltage'] = "无数据";
        if (isset($data['car_status'])) {
            $data['car_status_str'] = $car_status[intval($data['car_status'])];
        }
        if (isset($data['car_color'])) {
            $data['car_color_str'] = $car_color[intval($data['car_color'])];
        }
        if (isset($data['car_grade'])) {
            $data['car_grade_str'] = $car_grade[intval($data['car_grade'])];
        }
        $data['return_time_str'] = "无数据";
        if (!empty($data['return_time'])) {
            $data['return_time_str'] = date("Y-m-d H:i", $data['return_time']);
        }
        $data['driving_mileage_num'] = 0;
        if (intval($data['goods_type']) == GoodsType::$GoodsTypeElectrocar['code']) {
            $redis = new Redis();
            if (!$redis->has("login:" . $data['device_number'])) {
                $data['car_device_str'] = "离线";
                $data['car_device'] = 0;
            } else {
                $data['car_device_str'] = "在线";
                $data['car_device'] = 1;
            }
            $out_data_device = $redis->get("status:" . $data['device_number']);
            if (!empty($out_data_device)) {
                $device_data = json_decode($out_data_device, true);
                $data['driving_mileage'] = $device_data['drivingMileage'];
                $data['energy'] = $device_data['energy'];
                $data['location_latitude'] = $device_data['latitude'];
                $data['location_longitude'] = $device_data['longitude'];
                $data['voltage'] = $device_data['batteryVoltage'];
                if (empty($data['energy'])) {
                    $data['energy'] = (intval($data['driving_mileage']) / 160) * 100;
                } else if (empty($data['driving_mileage'])) {
                    $course = Config::get('course');
                    $km = $course[intval($data['series_id'])]['drive_km'];
                    $data['driving_mileage'] = intval(floatval($data['energy']) / 100 * $km);
                }
                $data['driving_mileage_num'] = $data['driving_mileage'];
            } else {
                $data['driving_mileage'] = "无数据";
                $data['driving_mileage_num'] = 0;
            }
        }
    }

    /**
     * 获取车辆信息
     * @param $id
     * @param bool $is_admin
     * @return mixed
     */
    public function getCarCommon($id, $is_admin = false)
    {
        if (!is_numeric($id)) {
            $out_data['code'] = 840;
            $out_data['info'] = "车辆数据id有误";
            return $out_data;
        }
        $carcommon = $this->find($id);
        if (empty($carcommon)) {
            $out_data['code'] = 841;
            $out_data['info'] = "车辆数据不存在";
            return $out_data;
        }
        $this->formatx($carcommon);
        if (!$is_admin) {
            if (intval($carcommon['car_status']) == CarStatus::$CarStatusNormal['code']) {
                $carcommon['car_photos'] = unserialize($carcommon['car_photos']);
                $stroe_model = new Store();
                $store = $stroe_model->where(['id' => $carcommon['store_site_id']])->field('store_name,store_intro,address,store_imgs,store_park_price,take_park_remark,return_park_remark')->find();
                $stroe_model->formatx($store);
                $carcommon['store'] = $store;
                $out_data['code'] = 0;
                $out_data['data'] = $carcommon;
                $brand_config = array(
                    'default' => false,
                    'type' => 1,
                    'brand' => "",
                    'series' => "",
                    'cartype' => "",
                );
                if (!empty($carcommon['brand_id']) && !empty($carcommon['series_id']) && !empty($carcommon['cartype_id'])) {
                    $brand_config['default'] = true;
                    $brand_config['type'] = 3;
                    $brand_config['brand'] = $carcommon['brand_id'] . "";
                    $brand_config['series'] = $carcommon['series_id'] . "";
                    $brand_config['cartype'] = $carcommon['cartype_id'] . "";
                }
                $out_data['brand_config'] = $brand_config;
                $out_data['info'] = "车辆数据获取成功";
                return $out_data;
            }
            $out_data['code'] = 840;
            $out_data['info'] = "车辆数据无权获取";
            return $out_data;
        } else {
            $out_data['code'] = 0;
            $carcommon['car_photos'] = unserialize($carcommon['car_photos']);
            $stroe_model = new Store();
            $store = $stroe_model->where(['id' => $carcommon['store_site_id']])->field('store_name,store_intro,address,store_imgs,store_park_price,take_park_remark,return_park_remark')->find();
            $stroe_model->formatx($store);
            $carcommon['store'] = $store;
            $out_data['data'] = $carcommon;
            $brand_config = array(
                'default' => false,
                'type' => 1,
                'brand' => "",
                'series' => "",
                'cartype' => ""
            );
            if (!empty($carcommon['brand_id']) && !empty($carcommon['series_id']) && !empty($carcommon['cartype_id'])) {
                $brand_config['default'] = true;
                $brand_config['type'] = 3;
                $brand_config['brand'] = $carcommon['brand_id'] . "";
                $brand_config['series'] = $carcommon['series_id'] . "";
                $brand_config['cartype'] = $carcommon['cartype_id'] . "";
            }
            $out_data['brand_config'] = $brand_config;
            $out_data['info'] = "车辆数据获取成功";
            return $out_data;
        }
    }

    /**
     * 获取车辆信息
     * @param $id
     * @param bool $is_admin
     * @return mixed
     */
    public function getCarCommonField($id, $field)
    {
        if (empty($field)) {
            $out_data['code'] = 840;
            $out_data['info'] = "参数有误";
            return $out_data;
        }
        $field = $field . ",goods_type,device_number";
        $carcommon = $this->where(['id' => $id])->field($field)->find();
        if (empty($carcommon)) {
            $out_data['code'] = 841;
            $out_data['info'] = "车辆数据不存在";
            return $out_data;
        }
        $this->formatx($carcommon);
        $out_data['code'] = 0;
        $out_data['data'] = $carcommon;
        $out_data['info'] = "车辆数据获取成功";
        return $out_data;
    }

    /**
     * 添加车数据
     * @param $carcommon_data
     * 必须数据
     * store_id 店铺id
     * initial 品牌首字母
     * brand_id 品牌id
     * series_id 车系id
     * cartype_id 车型id
     * goods_type 车辆归属类型 1普通出租车辆 2纯电动车
     * series_img 车型图片
     * licence_plate 车牌号
     * engine_vin 发动机编号
     * day_price 每天租金价格
     * day_basic 每天基本服务费
     * day_procedure 车行手续费用
     * location_longitude 地理位置经度
     * location_latitude 地理位置纬度
     * store_site_id 当前车辆所在门店id
     * store_site_name 当前车辆所在门店名称
     * age_year 车辆使用年限
     * car_color 车辆颜色
     * car_grade 车辆等级类型
     * car_status 当前车辆状态
     * car_photos 车辆照片图集
     * device_number 车机编号
     * 非必须数据
     * brand_name 品牌名称
     * series_name 车系名称
     * cartype_name 车型名称
     * @return array
     *  0 用户添加成功
     */
    public function addCarCommon($carcommon_data)
    {
        $out_data = array();
        $in_carcommon_data = array();
        //获取总店铺id
        $store_key_id = $carcommon_data['store_key_id'];
        if (!is_numeric($store_key_id)) {
            $out_data['code'] = 800;
            $out_data['info'] = "店铺id不合法";
            return $out_data;
        }
        $in_carcommon_data['store_key_id'] = $store_key_id;
        //获取总店铺id
        $store_key_name = $carcommon_data['store_key_name'];
        if (!empty($store_key_name)) {
            $in_carcommon_data['store_key_name'] = $store_key_name;
        }
        //获取车品牌id
        $brand_id = $carcommon_data['brand_id'];
        if (!is_numeric($brand_id)) {
            $out_data['code'] = 801;
            $out_data['info'] = "车品牌id不合法";
            return $out_data;
        }
        $in_carcommon_data['brand_id'] = $brand_id;
        //获取车系id
        $series_id = $carcommon_data['series_id'];
        if (!is_numeric($series_id)) {
            $out_data['code'] = 802;
            $out_data['info'] = "车系id不合法";
            return $out_data;
        }
        $in_carcommon_data['series_id'] = $series_id;
        //获取车系图片
        $series_img = $carcommon_data['series_img'];
        if (!empty($series_img)) {
            $in_carcommon_data['series_img'] = $series_img;
        }
        //获取车型id
        $cartype_id = $carcommon_data['cartype_id'];
        if (!is_numeric($cartype_id)) {
            $out_data['code'] = 803;
            $out_data['info'] = "车型id不合法";
            return $out_data;
        }
        $in_carcommon_data['cartype_id'] = $cartype_id;
        //获取车型id
        $goods_type = $carcommon_data['goods_type'];
        if (!is_numeric($goods_type)) {
            $out_data['code'] = 803;
            $out_data['info'] = "车辆归属类型不合法";
            return $out_data;
        }
        $in_carcommon_data['goods_type'] = $goods_type;
        //获取车牌号码
        $licence_plate = $carcommon_data['licence_plate'];
        if (check_licence_plate($licence_plate) === false) {
            $out_data['code'] = 804;
            $out_data['info'] = "车牌号码不合法";
            return $out_data;
        } else {
            if ($this->VerCheckLicenceRep($licence_plate, '') == false) {
                $out_data['code'] = 804;
                $out_data['info'] = "车牌号码已被占用";
                return $out_data;
            }
        }
        $in_carcommon_data['licence_plate'] = $licence_plate;
        //获取发动机编号
        $engine_vin = $carcommon_data['engine_vin'];
        $in_carcommon_data['engine_vin'] = $engine_vin;
//        if (check_engine_vin($engine_vin) === false) {
//            $out_data['code'] = 805;
//            $out_data['info'] = "发动机编号不合法";
//            return $out_data;
//        } else {
////            if ($this->VerCheckEngineVinRep($engine_vin, '') == false) {
////                $out_data['code'] = 805;
////                $out_data['info'] = "车辆发动机编号已被占用";
////                return $out_data;
////            }
//        }
        //获取租车单价/天
        $day_price = $carcommon_data['day_price'];
        if (!is_numeric($day_price) || $day_price < 0) {
            $out_data['code'] = 806;
            $out_data['info'] = "租车单价不合法";
            return $out_data;
        }
        $in_carcommon_data['day_price'] = $day_price;
        //获取基本服务保障费用/天
        $day_basic = $carcommon_data['day_basic'];
        if (!is_numeric($day_basic) || $day_basic < 0) {
            $out_data['code'] = 806;
            $out_data['info'] = "基本服务保障费用不合法";
            return $out_data;
        }
        $in_carcommon_data['day_basic'] = $day_basic;
        //获取每公里费用
        $km_price = $carcommon_data['km_price'];
        if (!is_numeric($km_price) || $km_price < 0) {
            $out_data['code'] = 806;
            $out_data['info'] = "每公里数价格不合法";
            return $out_data;
        }
        $in_carcommon_data['km_price'] = $km_price;
        //获取车行手续费用/天
        $day_procedure = $carcommon_data['day_procedure'];
        if (!is_numeric($day_procedure) || $day_procedure < 0) {
            $out_data['code'] = 806;
            $out_data['info'] = "车行手续费用不合法";
            return $out_data;
        }
        $in_carcommon_data['day_procedure'] = $day_procedure;
        //获取地理位置经度
        $location_longitude = $carcommon_data['location_longitude'];
        if (!is_numeric($location_longitude)) {
            $out_data['code'] = 807;
            $out_data['info'] = "地理位置经度不合法";
            return $out_data;
        }
        $in_carcommon_data['location_longitude'] = $location_longitude;

        //获取地理位置纬度
        $location_latitude = $carcommon_data['location_latitude'];
        if (!is_numeric($location_latitude)) {
            $out_data['code'] = 808;
            $out_data['info'] = "地理位置纬度不合法";
            return $out_data;
        }
        $in_carcommon_data['location_latitude'] = $location_latitude;

        //获取车辆所在店铺点id
        $store_site_id = $carcommon_data['store_site_id'];
        if (!is_numeric($store_site_id)) {
            $out_data['code'] = 809;
            $out_data['info'] = "车辆所在店铺点id不合法";
            return $out_data;
        }
        $in_carcommon_data['store_site_id'] = $store_site_id;
        //获取车辆所在店铺点名称
        $store_site_name = $carcommon_data['store_site_name'];
        if (empty($store_site_name)) {
            $out_data['code'] = 809;
            $out_data['info'] = "车辆所在店铺点名称不合法";
            return $out_data;
        }
        $in_carcommon_data['store_site_name'] = $store_site_name;

        //获取车辆使用年限
        $age_year = $carcommon_data['age_year'];
        if (!is_numeric($age_year)) {
            $out_data['code'] = 810;
            $out_data['info'] = "车辆使用年限不合法";
            return $out_data;
        }
        $in_carcommon_data['age_year'] = $age_year;
        //获取车辆当前状态
        $car_status = $carcommon_data['car_status'];
        if (!is_numeric($car_status)) {
            $out_data['code'] = 811;
            $out_data['info'] = "车辆当前状态不合法";
            return $out_data;
        }
        $in_carcommon_data['car_status'] = $car_status;
        //获取车辆图集
        $car_photos = $carcommon_data['car_photos'];
        if (empty($car_photos)) {
            $out_data['code'] = 812;
            $out_data['info'] = "车辆图集不能为空";
            return $out_data;
        }
        $in_carcommon_data['car_photos'] = $car_photos;
        //获取车辆品首字母
        $initial = $carcommon_data['initial'];
        if (!preg_match("/^[A-Z]/", $initial)) {
            $out_data['code'] = 813;
            $out_data['info'] = "品牌首字母不正确";
            return $out_data;
        }
        $in_carcommon_data['initial'] = strtoupper($initial);
        //获取车辆品牌名称
        $brand_name = $carcommon_data['brand_name'];
        if (!empty($brand_name)) {
            $in_carcommon_data['brand_name'] = $brand_name;
        }
        //获取车系名称
        $series_name = $carcommon_data['series_name'];
        if (!empty($series_name)) {
            $in_carcommon_data['series_name'] = $series_name;
        }
        //获取车型名称
        $cartype_name = $carcommon_data['cartype_name'];
        if (!empty($cartype_name)) {
            $in_carcommon_data['cartype_name'] = $cartype_name;
        }
        $datetime = date("Y-m-d H:i:s");

        //获取车辆等级类型
        $car_grade = $carcommon_data['car_grade'];
        if (is_numeric($car_grade)) {
            $in_carcommon_data['car_grade'] = $car_grade;
        }
        //获取车辆排量
        $car_cc = $carcommon_data['car_cc'];
        if (!empty($car_cc)) {
            $in_carcommon_data['car_cc'] = $car_cc;
        } else {
            $up_carcommon_data['car_cc'] = 0;
        }
        //获取车机编号
        $device_number = $carcommon_data['device_number'];
        $device_data_id = 0;
        if (!empty($device_number)) {
            $car_device_model = new CarDeviceTool();
            $device_data = $car_device_model->getDevice($device_number);
            $device_data_id = intval($device_data['data']['id']);
            if (intval($device_data['data']['deviceStatus']) == DeviceStatus::$DeviceCheck['code']) {
                $out_data['code'] = 816;
                $out_data['info'] = "该设备未被审核";
                return $out_data;
            }
            $in_carcommon_data['device_number'] = $device_number;
        }
        //获取获取车辆颜色
        $car_color = $carcommon_data['car_color'];
        if (!is_numeric($car_color)) {
            $out_data['code'] = 814;
            $out_data['info'] = "颜色代码不正确！";
            return $out_data;
        }
        $in_carcommon_data['car_color'] = $car_color;
        //创建时间
        $in_carcommon_data['create_time'] = $datetime;
        //最近一次更新时间
        $in_carcommon_data['update_time'] = $datetime;
        if ($this->save($in_carcommon_data)) {
            $id = $this->getLastInsID();
            if (!empty($device_number)) {
                $car_device_model = new CarDeviceTool();
                $car_device_model->updateDevice(['id' => $device_data_id, "device_status" => DeviceStatus::$DeviceBind['code'], 'goods_id' => $id]);
            }
            $out_data['code'] = 0;
            $out_data['info'] = "车辆添加成功";
            return $out_data;
        }
        $out_data['code'] = 815;
        $out_data['info'] = "车辆添加失败";
        return $out_data;
    }

    /**
     * 更新车辆信息
     * @param $carcommon_data
     * 必输数据
     * id 数据id
     * 必输数据
     * 非必须数据
     * initial 品牌首字母
     * brand_id 品牌id
     * brand_name 品牌名称
     * series_id 车系id
     * series_name 车系名称
     * series_img 车系图片
     * cartype_id 车型id
     * cartype_name 车型名称
     * goods_type 车辆归属类型 1普通出租车辆 2纯电动车
     * licence_plate 车牌号
     * engine_vin 发动机编号
     * day_price 每天租金价格
     * day_basic 每天基本服务费
     * day_procedure 车行手续费用
     * location_longitude 地理位置经度
     * location_latitude 地理位置纬度
     * store_site_id 当前车辆所在门店id
     * store_site_name 当前车辆所在门店名称
     * age_year 车辆使用年限
     * car_color 车辆颜色
     * car_grade 车辆等级类型
     * car_status 当前车辆状态
     * car_photos 车辆照片图集
     * device_number 车机编号
     * remark 变动备注信息
     * @return array
     */
    public function updateCarCommon($carcommon_data)
    {
        $out_data = array();
        $up_carcommon_data = array();
        if (!is_numeric($carcommon_data['id'])) {
            $out_data['code'] = 830;
            $out_data['info'] = "车辆数据id有误";
            return $out_data;
        }
        //获取店铺id
        $store_id = $carcommon_data['store_id'];
        if (is_numeric($store_id)) {
            $up_carcommon_data['store_id'] = $store_id;
        }
        //获取车品牌id
        $brand_id = $carcommon_data['brand_id'];
        if (is_numeric($brand_id)) {
            $up_carcommon_data['brand_id'] = $brand_id;
        }
        //获取车系id
        $series_id = $carcommon_data['series_id'];
        if (is_numeric($series_id)) {
            $up_carcommon_data['series_id'] = $series_id;
        }
        //获取车型id
        $cartype_id = $carcommon_data['cartype_id'];
        if (is_numeric($cartype_id)) {
            $up_carcommon_data['cartype_id'] = $cartype_id;
        }
        //获取车辆品首字母
        $initial = $carcommon_data['initial'];
        if (!empty($initial)) {
            $up_carcommon_data['initial'] = $initial;
        }
        $initial = $carcommon_data['initial'];
        if (!preg_match("/^[A-Za-z]/", $initial)) {
            $up_carcommon_data['initial'] = strtoupper($initial);
        }
        //获取车辆品牌名称
        $brand_name = $carcommon_data['brand_name'];
        if (!empty($brand_name)) {
            $up_carcommon_data['brand_name'] = $brand_name;
        }
        //获取车系名称
        $series_name = $carcommon_data['series_name'];
        if (!empty($series_name)) {
            $up_carcommon_data['series_name'] = $series_name;
        }
        //获取车系图片
        $series_img = $carcommon_data['series_img'];
        if (!empty($series_img)) {
            $up_carcommon_data['series_img'] = $series_img;
        }
        //获取车型名称
        $cartype_name = $carcommon_data['cartype_name'];
        if (!empty($cartype_name)) {
            $up_carcommon_data['cartype_name'] = $cartype_name;
        }
        //车辆归属类型
        $goods_type = $carcommon_data['goods_type'];
        if (!empty($goods_type)) {
            $up_carcommon_data['goods_type'] = $goods_type;
        }
        //获取车牌号码
        $licence_plate = $carcommon_data['licence_plate'];
        if (check_licence_plate($licence_plate) === true) {
            if ($this->VerCheckLicenceRep($licence_plate, $carcommon_data['id']) == false) {
                $out_data['code'] = 834;
                $out_data['info'] = "车牌号码已被占用";
                return $out_data;
            }
            $up_carcommon_data['licence_plate'] = $licence_plate;
        } else {
            $out_data['code'] = 834;
            $out_data['info'] = "车牌号无效";
            return $out_data;
        }

        $engine_vin = $carcommon_data['engine_vin'];
        $up_carcommon_data['engine_vin'] = $engine_vin;
//        if (check_engine_vin($engine_vin) === true) {
////            if ($this->VerCheckEngineVinRep($engine_vin, $carcommon_data['id']) == false) {
////                $out_data['code'] = 834;
////                $out_data['info'] = "车辆发动机编号已被占用";
////                return $out_data;
////            }
//            $up_carcommon_data['engine_vin'] = $engine_vin;
//        } else {
//            $out_data['code'] = 835;
//            $out_data['info'] = "车辆发动机编号（VIN码）无效";
//            return $out_data;
//        }
        //获取租车单价/天
        $day_price = $carcommon_data['day_price'];
        if (is_numeric($day_price) && $day_price >= 0) {
            $up_carcommon_data['day_price'] = $day_price;
        } else {
            $out_data['code'] = 835;
            $out_data['info'] = "租车单价不合法";
            return $out_data;
        }
        //获取基本服务保障费用/天
        $day_basic = $carcommon_data['day_basic'];
        if (is_numeric($day_basic) && $day_basic >= 0) {
            $up_carcommon_data['day_basic'] = $day_basic;
        } else {
            $out_data['code'] = 836;
            $out_data['info'] = "基本服务保障费用不合法";
            return $out_data;
        }
        //获取车行手续费用/天
        $day_procedure = $carcommon_data['day_procedure'];
        if (is_numeric($day_procedure) && $day_procedure >= 0) {
            $up_carcommon_data['day_procedure'] = $day_procedure;
        } else {
            $out_data['code'] = 837;
            $out_data['info'] = "车行手续费用不合法";
            return $out_data;
        }
        //获取每公里费用
        $km_price = $carcommon_data['km_price'];
        if (is_numeric($km_price) && $km_price >= 0) {
            $up_carcommon_data['km_price'] = $km_price;
        } else {
            $out_data['code'] = 837;
            $out_data['info'] = "每公里费用不合法";
            return $out_data;
        }
        //获取地理位置经度
        $location_longitude = $carcommon_data['location_longitude'];
        if (is_numeric($location_longitude)) {
            $up_carcommon_data['location_longitude'] = $location_longitude;
        }
        //获取地理位置纬度
        $location_latitude = $carcommon_data['location_latitude'];
        if (is_numeric($location_latitude)) {
            $up_carcommon_data['location_latitude'] = $location_latitude;
        }
        //获取车辆所在店铺点id
        $store_site_id = $carcommon_data['store_site_id'];
        if (is_numeric($store_site_id)) {
            $up_carcommon_data['store_site_id'] = $store_site_id;
            //获取车辆所在店铺点名称
            $store_site_name = $carcommon_data['store_site_name'];
            if (!empty($store_site_name)) {
                $in_carcommon_data['store_site_name'] = $store_site_name;
            }
        }

        //获取车辆使用年限
        $age_year = $carcommon_data['age_year'];
        if (is_numeric($age_year)) {
            $up_carcommon_data['age_year'] = $age_year;
        }
        //获取车辆当前状态
        $car_status = $carcommon_data['car_status'];
        if (is_numeric($car_status)) {
            $up_carcommon_data['car_status'] = $car_status;
        }
        //获取车辆图集
        $car_photos = $carcommon_data['car_photos'];
        if (!empty($car_photos)) {
            $up_carcommon_data['car_photos'] = $car_photos;
        }
        //获取车辆备注
        $remark = $carcommon_data['remark'];
        if (!empty($remark)) {
            $up_carcommon_data['remark'] = $remark;
        }

        //获取车辆等级类型
        $car_grade = $carcommon_data['car_grade'];
        if (is_numeric($car_grade)) {
            $up_carcommon_data['car_grade'] = $car_grade;
        }
        //获取车辆排量
        $car_cc = $carcommon_data['car_cc'];
        if (!empty($car_cc)) {
            $up_carcommon_data['car_cc'] = $car_cc;
        } else {
            $up_carcommon_data['car_cc'] = 0;
        }
        //获取车机编号
        $device_number = $carcommon_data['device_number'];
        $device_data_id = 0;
        if (!empty($device_number)) {
            $car_device_model = new CarDeviceTool();
            $device_data = $car_device_model->getDevice($device_number);
            $device_data_id = intval($device_data['data']['id']);
            if (intval($device_data['data']['deviceStatus']) == DeviceStatus::$DeviceCheck['code']) {
                $out_data['code'] = 816;
                $out_data['info'] = "该设备未被审核";
                return $out_data;
            }
            $up_carcommon_data['device_number'] = $device_number;
        }
        //获取获取车辆颜色
        $car_color = $carcommon_data['car_color'];
        if (!is_numeric($car_color)) {
            $out_data['code'] = 838;
            $out_data['info'] = "颜色代码不正确！";
            return $out_data;
        }
        $up_carcommon_data['car_color'] = $car_color;
        $datetime = date("Y-m-d H:i:s");
        $up_carcommon_data['update_time'] = $datetime;
        if ($this->save($up_carcommon_data, array('id' => $carcommon_data['id']))) {
            if (!empty($device_number)) {
                $car_device_model = new CarDeviceTool();
                if (!empty($device_data_id)) {
                    $car_device_model->updateDevice(['id' => $device_data_id, "device_status" => DeviceStatus::$DeviceBind['code'], 'goods_id' => $carcommon_data['id']]);
                }
            }
            $out_data['code'] = 0;
            $out_data['info'] = "车辆信息更新成功";
            return $out_data;
        }
        $out_data['code'] = 839;
        $out_data['info'] = "车辆信息更新失败";
        return $out_data;
    }

    /**
     * 调度车辆
     * @param $carcommon_data
     * 必须数据
     * id 车辆id
     * store_site_id 门店id
     * @param bool $is_super_admin
     * @param string $store_key_id
     * @return array
     */
    public function dispatchCarCommon($carcommon_data, $is_super_admin = false, $store_key_id = '')
    {
        $out_data = array();
        $id = $carcommon_data['id'];
        if (!is_numeric($id)) {
            $out_data['code'] = 950;
            $out_data['info'] = '车辆id不合法';
            return $out_data;
        }
        $store_site_id = $carcommon_data['store_site_id'];
        if (!is_numeric($store_site_id)) {
            $out_data['code'] = 951;
            $out_data['info'] = '门店id不合法';
            return $out_data;
        }
        $store_model = new Store();
        $map['id'] = $store_site_id;
        if (!$is_super_admin) {
            $map['store_key_id'] = $store_key_id;
        }
        $store_site = $store_model->where($map)->field('store_name,location_longitude,location_latitude')->find();
        if (empty($store_site)) {
            $out_data['code'] = 952;
            $out_data['info'] = '店铺不存在或者无权调度';
            return $out_data;
        }
        $store_site_name = $store_site['store_name'];
        $location_longitude = $store_site['location_longitude'];
        $location_latitude = $store_site['location_latitude'];
        $car_data = $this->where(array('id' => $id))->field('id,goods_type,store_site_id,store_site_name')->find();
        if (empty($car_data)) {
            $out_data['code'] = 988;
            $out_data['info'] = "车辆不存在";
            return $out_data;
        }
        $car_model = new CarCommon();
        if (intval($car_data['goods_type']) === GoodsType::$GoodsTypeElectrocar['code']) {
            if ($car_model->carEleDispatch($id, $store_site_id, $store_site_name)) {
                $out_data['code'] = 0;
                $out_data['info'] = "车辆调度成功";
                return $out_data;
            }
        } else {
            if ($car_model->carDispatch($id, $store_site_id, $store_site_name, $location_longitude, $location_latitude)) {
                $out_data['code'] = 0;
                $out_data['info'] = "车辆调度成功";
                return $out_data;
            }
        }
        $out_data['code'] = 990;
        $out_data['info'] = "车辆调度失败";
        return $out_data;
    }

    /**
     * 刷新门店车辆经纬度地址
     * @param $store_site_id 门店id
     * @param $longitude 经度
     * @param $latitude 纬度
     * @return bool
     */
    public function updateCarLocation($store_site_id, $longitude, $latitude)
    {
        $ret = $this->where(array('store_site_id' => $store_site_id))->setField(['location_longitude' => $longitude, 'location_latitude' => $latitude]);
        if ($ret) {
            return true;
        }
        return false;
    }

    /**
     * 车辆下线
     * @param $car_id
     * @return bool
     *  0下线成功
     *  150 下线失败
     */
    public function addLockCar($car_id)
    {
        $lock_car_data = array(
            'car_status' => CarStatus::$CarStatusLogoff['code']
        );
        if ($this->save($lock_car_data, array('id' => $car_id))) {
            return true;//车辆下线成功
        }
        return false;//车辆下线失败
    }

    /**
     * 车辆正在使用中
     * @param $car_id
     * @return bool
     */
    public function useLockCar($car_id)
    {
        $lock_car_data = array(
            'car_status' => CarStatus::$CarStatusInuse['code']
        );
        if ($this->save($lock_car_data, array('id' => $car_id))) {
            $car_data = $this->where(['id' => $car_id])->field('store_site_id,store_site_name,store_key_id')->find();
            $leisure = [
                'store_id' => $car_data['store_site_id'],
                'store_name' => $car_data['store_site_name'],
                'store_key_id' => $car_data['store_key_id'],
            ];
            $store_leisure_model = new StoreLeisure();
            $store_leisure_model->addLeisure($leisure);
            $this->rentCount($car_id);
            return true;//车辆下使用中
        }
        return false;//车辆使用失败
    }

    /**
     * 车辆维修中
     * @param $car_id
     * @return bool
     *  0 维护成功
     *  150 维护失败
     */
    public function vindicateCar($car_id)
    {
        $lock_car_data = array(
            'car_status' => CarStatus::$CarStatusVindicate['code']
        );
        if ($this->save($lock_car_data, array('id' => $car_id))) {
            return true;//车辆下线成功
        }
        return false;//车辆下线失败
    }

    /**
     * 车辆正常上线（还车）
     * @param $car_id
     * @return bool
     *  0上线成功
     *  150 上线失败
     */
    public function delLockCar($car_id)
    {
        $car_data = $this->where(array('id' => $car_id))->field('id,car_status,store_site_id,store_site_name,store_key_id')->find();
        if (empty($car_data)) {
            return false;
        }
        $car_data->car_status = CarStatus::$CarStatusNormal['code'];
        if ($car_data->save()) {
            $leisure = [
                'store_id' => $car_data['store_site_id'],
                'store_name' => $car_data['store_site_name'],
                'store_key_id' => $car_data['store_key_id']
            ];
            $store_leisure_model = new StoreLeisure();
            $store_leisure_model->addLeisure($leisure);
            return true;//车辆上线成功
        }
        return false;//车辆上线失败
    }

    /**
     * 常规调度车辆
     * @param $car_id
     * @param $store_site_id
     * @param $store_site_name
     * @param $lat
     * @param $lng
     * @return bool
     */
    public function carDispatch($car_id, $store_site_id, $store_site_name, $lat, $lng)
    {
        $car_data = $this->where(array('id' => $car_id))->field('id,store_site_id,store_site_name')->find();
        if (empty($car_data)) {
            return false;
        }
        $car_data->store_site_id = $store_site_id;
        $car_data->store_site_name = $store_site_name;
        $car_data->location_longitude = $lng;
        $car_data->location_latitude = $lat;
        $car_data->save();
        return true;//车辆调度成功
    }

    /**
     *新能源车调度
     * @param $car_id
     * @param $store_site_id
     * @param $store_site_name
     * @return bool
     */
    public function carEleDispatch($car_id, $store_site_id, $store_site_name)
    {
        $car_data = $this->where(array('id' => $car_id))->field('id,store_site_id,store_site_name,store_key_id')->find();
        if (empty($car_data)) {
            return false;
        }
        $leisure = [
            'store_id' => $car_data['store_site_id'],
            'store_name' => $store_site_name,
            'store_key_id' => $car_data['store_key_id'],
        ];
        $store_leisure_model = new StoreLeisure();
        $store_leisure_model->addLeisure($leisure);
        $car_data->store_site_id = $store_site_id;
        $car_data->store_site_name = $store_site_name;
        $car_data->save();
        return true;//车辆调度成功
    }

    /**
     * 验证车辆是否可以用
     * @param $car_id
     * @return bool
     */
    public function verCarIsUse($car_id)
    {
        $map = array(
            'id' => $car_id,
            'car_status' => CarStatus::$CarStatusNormal['code']
        );
        $data = $this->where($map)->field('id')->find();
        if (!empty($data)) {
            return true;//可以使用
        }
        return false;//不可使用
    }

    /**
     * 累计出租数量
     * @param $car_id
     * @return bool
     */
    public function rentCount($car_id)
    {
        if ($this->where(array('id' => $car_id))->setInc('rent_count', 1)) {
            return true;
        }
        return false;
    }

    /**
     * 添加车辆预约时间区间
     * @param $car_id 车辆id
     * @param $start_time 开始时间戳
     * @param $end_time 结束时间戳
     * @return bool
     */
    public function addReserveInterval($car_id, $start_time, $end_time)
    {
        $car_data = $this->where(array('id' => $car_id))->find();
        if (!is_time_interval($car_data['reserve_interval'], $start_time, $end_time)) {
            $reserve_interval = unserialize($car_data['reserve_interval']);
            $reserve_interval = filter_time_interval($reserve_interval);
            $reserve_interval[] = array(
                'start_time' => $start_time,
                'end_time' => $end_time
            );
            if ($this->where(array('id' => $car_id))->setField('reserve_interval', serialize($reserve_interval))) {
                return true;
            }
        }
        return false;
    }

    /**
     * 删除车辆预约时间区间
     * @param $car_id 车辆id
     * @param $start_time 开始时间戳
     * @param $end_time 结束时间戳
     * @return bool
     */
    public function delReserveInterval($car_id, $start_time, $end_time)
    {
        $car_data = $this->where(array('id' => $car_id))->find();
        $reserve_interval = del_time_interval($car_data['reserve_interval'], $start_time, $end_time);
        if ($this->where(array('id' => $car_id))->setField('reserve_interval', serialize($reserve_interval))) {
            return true;
        }
        return false;
    }

    /**
     * 根据条件获取车辆列表
     * @param $map
     * @param $order
     * @param $page
     * @param int $limit
     * @return \think\Paginator
     */
    public function getCarCommonList($map, $order, $page, $limit = 15)
    {
        $map['car_status'] = CarStatus::$CarStatusNormal['code'];
        $field = "id,brand_id,brand_name,series_id,series_name,series_img,cartype_id,goods_type,cartype_name,reserve_interval,licence_plate,device_number,engine_vin,location_longitude,location_latitude,day_price,km_price,day_basic,day_procedure,store_site_id,store_site_name,car_status,car_grade,rent_count,store_key_id,store_key_name";
        $car_common_list = $this->where($map)->order($order)->field($field)->paginate($limit, false, ['page' => $page]);
        return $car_common_list;
    }

    /**
     * 判断车牌号是否可用
     * @param $licence_plate
     * @param string $id
     * @return bool true可用  false不可用
     */
    public function VerCheckLicenceRep($licence_plate, $id = '')
    {
        $data = $this->where(array('licence_plate' => $licence_plate))->field('id')->find();
        if (!empty($data)) {
            if ($id == $data['id']) {
                return true;
            }
        } else {
            return true;
        }
        return false;
    }

    /**
     * 判断发动机编号是否可用
     * @param $engine_vin
     * @param string $id 车辆id
     * @return bool true可用  false不可用
     */
    public function VerCheckEngineVinRep($engine_vin, $id = '')
    {
        $data = $this->where(array('engine_vin' => $engine_vin))->field('id')->find();
        if (!empty($data)) {
            if ($id == $data['id']) {
                return true;
            }
        } else {
            return true;
        }
        return false;
    }
}