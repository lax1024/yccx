<?php

namespace app\api\controller;

use app\common\controller\HomeBase;
use app\common\model\CarCommon as CarCommonModel;
use app\common\model\CarOperationList;
use app\common\model\OrderOperation;
use app\common\model\Store;
use app\common\model\Store as StoreModel;
use definition\CarCmd;
use definition\CarStatus;
use definition\OrderStatus;
use think\cache\driver\Redis;
use think\Session;

/**
 * 运维车辆信息列表
 * Class Ueditor
 * @package app\api\controller
 */
class OperationCar extends HomeBase
{
    private $carcommon_model;
    private $store_model;
    private $operation_id;//运维人员id
    private $operation_phone;//运维人员电话
    private $operation_name;//运维人员名称
    private $store_area_ids;//区域id
    private $store_ids;//区域店铺id
    private $grade;//等级
    private $store_key_id;//归属店铺
    private $store_key_name;//归属店铺名称

    protected function _initialize()
    {
        parent::_initialize();
        $this->operation_id = Session::get('operation_id');
        $this->operation_phone = Session::get('operation_phone');
        $this->operation_name = Session::get('operation_name');
        $this->store_key_id = Session::get('store_key_id');
        $this->grade = Session::get('grade');
        $this->store_key_name = Session::get('store_key_name');
        $store_area_ids = Session::get('store_area_ids');
        if (!empty($store_area_ids)) {
            $this->store_area_ids = json_decode($store_area_ids, true);
        }
        $store_ids = Session::get('store_ids');
        if (!empty($store_ids)) {
            $this->store_ids = json_decode($store_ids, true);
        }
//        $this->operation_id = 2;
//        $this->operation_phone = "18785160986";
//        $this->operation_name = "龙安祥";
//        $this->store_key_id = "153";
//        $this->store_key_name = "优车出行";
//        if (empty($operation_id) || empty($operation_phone)) {
//            $out_data = [
//                'code' => 11,
//                'info' => "运维人员信息有误"
//            ];
//            out_json_data($out_data);
//        }
        $this->carcommon_model = new CarCommonModel();
        $this->store_model = new StoreModel();
    }

    /**
     * 获取车辆需要维护的车辆数据信息
     * @param int $sid
     * @param $lng
     * @param $lat
     * @param $type
     */
    public function get_operation_car_list($sid = 153, $lng = '106.717801', $lat = '26.58804', $type = 6)
    {
        $out_data = [
            'code' => 100,
            'info' => "数据已加载完"
        ];
        $this->carcommon_model->getUnusualCar($sid, 90, 0);
        $operation_list_model = new CarOperationList();
        $out_car_list = $operation_list_model->getOperationPerList($sid, 0, $type);
        if ($out_car_list['code'] == 0) {
            $car_list = $out_car_list['data'];
            $car_list_arr = [];
            foreach ($car_list as &$value) {
                if (in_array($value['store_site_id'], $this->store_ids)) {
                    if (empty($value['location_latitude']) || empty($value['location_latitude'])) {
                        $value['distance_per'] = "无数据";
                    } else {
                        $value['distance_per'] = get_distance($lng, $lat, $value['location_longitude'], $value['location_latitude']);
                    }
                    $value['time_str'] = round((time() - $value['gain_time']) / 3600, 2);

                    $car_list_arr[] = json_decode(json_encode($value), true);
                }
            }
            $car_list_arr = rank_elecar_grade($car_list_arr, 'distance_per');
            $out_data['code'] = 0;
            $out_data['data'] = $car_list_arr;
            $out_data['info'] = "获取成功";
        }
        out_json_data($out_data);
    }

    /**
     * 添加操作订单
     * @param $id
     * @param string $notes
     * @param int $type
     */
    public function add_order_operation($id, $notes = "", $type = 0)
    {
        $operation_ids = [2, 5, 11];
        $out_data = [
            'code' => 100,
            'info' => "车辆id不合法"
        ];

//        if (!in_array($this->operation_id, $operation_ids)) {
//            $stime = ['H' => 6, 'i' => 0];
//            $etime = ['H' => 21, 'i' => 30];
//            if (!verify_time_interval($stime, $etime)) {
//                $out_data['code'] = 110;
//                $out_data['info'] = "未到接单时间，请到21:30-第二天06:00时间段内接单";
//                out_json_data($out_data);
//            }
//        }
        if (intval($this->grade) == 0) {
            $stime = ['H' => 6, 'i' => 0];
            $etime = ['H' => 21, 'i' => 30];
            if (!verify_time_interval($stime, $etime)) {
                $out_data['code'] = 110;
                $out_data['info'] = "未到接单时间，请到21:30-第二天06:00时间段内接单";
                out_json_data($out_data);
            }
        }
        if (empty($id)) {
            out_json_data($out_data);
        }
        $order_operation_model = new OrderOperation();
//    * @param $operation_data 任务信息
//    * goods_id 商品id
//    * licence_plate 车牌号
//    * goods_device 设备编号
//    * goods_name 商品名称
//    * goods_type 商品类
//    * order_type 任务类型
//    * order_goods 任务信息 ['goods_name'=>'任务名称','goods_price'=>'任务单价','goods_img'=>'任务图片']
//    * acquire_store_id 取用点id
//    * acquire_time 取用时间 时间戳
//    * acquire_img 接受任务图片
//    * @param $operation_info 管理员信息
//    * operation_id 接单人员id
//    * operation_name 接单人员姓名
//    * operation_phone 接单人员电话
//    * customer_notes 接单人员备注信息
//    * store_key_id 归属总店id
//    * store_key_name 归属总店名称
        $car_data = $this->carcommon_model->getCarCommon($id, true);
        $car_data = $car_data['data'];
        $operation_data = [
            'goods_id' => $car_data['id'],
            'series_id' => $car_data['series_id'],
            'goods_device' => $car_data['device_number'],
            'goods_name' => $car_data['cartype_name'],
            'goods_type' => $car_data['goods_type'],
            'licence_plate' => $car_data['licence_plate'],
            'order_type' => $type,
            'order_goods' => $car_data['id']
        ];
        $operation_info = [
            'operation_id' => $this->operation_id,
            'operation_name' => $this->operation_name,
            'operation_phone' => $this->operation_phone,
            'customer_notes' => $notes,
            'store_key_id' => $this->store_key_id,
            'store_key_name' => $this->store_key_name
        ];
        $out_data = $order_operation_model->addOrderOperation($operation_data, $operation_info);
        out_json_data($out_data);
    }

    /**
     * 获取任务列表详细
     * @param string $lng
     * @param string $lat
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_operation_list($lng = '106.717801', $lat = '26.58804')
    {
        $order_operation_model = new OrderOperation();
        $map = ['operation_id' => $this->operation_id, 'order_status' => ['between', OrderStatus::$OrderStatusNopayment['code'] . "," . OrderStatus::$OrderStatusAcquire['code']]];
        $out_order_data = $order_operation_model->getList($map, 'id ASC', 0, 200, false, $this->store_key_id);
        foreach ($out_order_data as &$value) {
            if (empty($value['location_latitude']) || empty($value['location_latitude'])) {
                $value['distance_per'] = "无数据";
            } else {
                $value['distance_per'] = get_distance($lng, $lat, $value['location_longitude'], $value['location_latitude']);
            }
            $value['time_str'] = round((time() - strtotime($value['create_time'])) / 3600, 2);
        }
        $out_data['code'] = 0;
        $out_data['data'] = $out_order_data;
        $out_data['info'] = "获取成功";
        out_json_data($out_data);
    }

    /**
     * 获取站点需求列表
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_store_condition_list($lng = '106.717801', $lat = '26.58804')
    {
        $map = [
            'is_area' => 1,
            'car_num' => array('gt', 0),
        ];
        $store_list = $this->store_model->where($map)->field('id,store_name,car_num,location_longitude,location_latitude')->select();
        $store_list_arr = [];
        foreach ($store_list as $vx) {
            if (in_array($vx['id'], $this->store_ids)) {
                $distance = get_distance($lng, $lat, $vx['location_longitude'], $vx['location_latitude']);
                $store_list_arr[intval($vx['id'])] = [
                    'id' => $vx['id'],
                    'store_name' => $vx['store_name'],
                    'car_num' => $vx['car_num'],
                    'park_car' => 0,
                    'mileage_car' => 0,
                    'online_car' => 0,
                    'distance' => $distance,
                    'store_status' => 1,//不符合要求
                    'store_status_str' => "不符合要求",//符合要求
                ];
            }
        }
        $map_car = [
            'car_status' => CarStatus::$CarStatusNormal['code']
        ];
        $car_list = $this->carcommon_model->where($map_car)->select();
        if (!empty($car_list)) {
            foreach ($car_list as &$value) {
                $this->carcommon_model->formatx($value);
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
            $store_list_arr_out[] = $valuex;
        }
        $store_list_arr_out = json_encode($store_list_arr_out);
        $store_list_arr_out = json_decode($store_list_arr_out, true);
        $store_list_arr_out = rank_elecar_grade($store_list_arr_out, 'distance');
        $out_data['code'] = 0;
        $out_data['data'] = $store_list_arr_out;
        $out_data['info'] = "获取成功";
        out_json_data($out_data);
    }

    /**
     * 开始维护车辆
     * @param $order_id
     * @param $acquire_img
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function acquire_order_operation($order_id = '', $acquire_img = array())
    {
        $out_data = [
            'code' => 100,
            'info' => "参数有误"
        ];
        if (empty($acquire_img) || empty($order_id)) {
            out_json_data($out_data);
        }
        $order_operation_model = new OrderOperation();
        $out_operation_data = $order_operation_model->getOrderOperation($order_id, '', true);
        $out_operation = $out_operation_data['data'];
        $operation_data = [
            'id' => $order_id,
            'acquire_store_id' => $out_operation['store_site_id'],
            'acquire_store_name' => $out_operation['store_site_name'],
            'acquire_img' => $acquire_img,
        ];
        $out_data = $order_operation_model->acquireOrderOperation($operation_data, $this->operation_id);
        out_json_data($out_data);
    }

    /**
     * 获取车辆还车位置
     * @param $car_id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_elesite($car_id = '')
    {
        $out_data['code'] = 1;
        $out_data['info'] = "参数不能为空";
        if (empty($car_id)) {
            out_json_data($out_data);
        }
        $car_common_model = new CarCommonModel();
        $car_data = $car_common_model->where(array('id' => $car_id))->field('store_key_id,device_number,series_id')->find();
        if (empty($car_data)) {
            $out_data['code'] = 2;
            $out_data['info'] = "车辆数据有误";
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
            $device_data = json_decode($out_data_device, true);
        }
        $store_key_id = $car_data['store_key_id'];
        $lng = $device_data['longitude'];
        $lat = $device_data['latitude'];
        //获取经纬度范围(还车)
        $elng_lat = get_max_min_lng_lat($lng, $lat, 2);
        $emaxlng = $elng_lat['maxLng'];//最大经度
        $eminLng = $elng_lat['minLng'];//最小经度
        $emaxLat = $elng_lat['maxLat'];//最大纬度
        $eminLat = $elng_lat['minLat'];//最小纬度
        $emap['store_key_id'] = $store_key_id;
        $emap['store_status'] = 0;
        $emap['is_area'] = 1;
        $emap['location_longitude'] = [['EGT', $eminLng], ['ELT', $emaxlng]];
        $emap['location_latitude'] = [['EGT', $eminLat], ['ELT', $emaxLat]];
        $store_model = new Store();
        $store_key_list = $store_model->getSiteList($emap);
        if (empty($store_key_list)) {
            $out_data['code'] = 3;
            $out_data['info'] = "当前位置无还车点";
            out_json_data($out_data);
        }
        foreach ($store_key_list as &$value) {
            $distance = get_distance($lng, $lat, $value['location_longitude'], $value['location_latitude']);
            $value->distance = $distance;
        }
        $store_key_list = json_encode($store_key_list);
        $store_key_list = json_decode($store_key_list, true);
        $store_key_list = rank_elecar_grade($store_key_list, 'distance');
        $return_store_id = $store_key_list[0]['id'];
        if (empty($return_store_id)) {
            $out_data['code'] = 3;
            $out_data['info'] = "当前位置无还车点，请手动选择还车点";
            out_json_data($out_data);
        }
        $distance = get_distance($device_data['longitude'], $device_data['latitude'], $store_key_list[0]['location_longitude'], $store_key_list[0]['location_latitude'], 1);
        if (intval($distance) > intval($store_key_list[0]['store_scope'])) {
            $out_data['code'] = 1010;
            $out_data['info'] = '未在还车区域内，请手动选择还车点';
            out_json_data($out_data);
        }
        $out_data['code'] = 0;
        $out_data['data'] = $return_store_id;
        $out_data['info'] = "获取成功";
        out_json_data($out_data);
    }

    /**
     * 完成任务
     * @param string $order_id
     * @param array $return_img
     * @param string $order_type
     * @param string $store_id
     * @param string $car_status
     * @param int $is_unusual
     * @param string $notes
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function submit_order_operation($order_id = '', $return_img = array(), $order_type = '1', $store_id = '', $car_status = '4', $is_unusual = 0, $notes = "")
    {
        $out_data['code'] = 1;
        $out_data['info'] = "参数不能为空";
        if (empty($order_id) || empty($return_img) || empty($order_type) || empty($store_id) || empty($car_status)) {
            out_json_data($out_data);
        }
        $order_operation_model = new OrderOperation();
        $operation_data = [
            'id' => $order_id,
            'return_store_id' => $store_id,
            'return_img' => $return_img,
            'order_type' => $order_type,
            'car_status' => $car_status,
            'is_unusual' => $is_unusual,
            'notes' => $notes,
        ];
        $out_data = $order_operation_model->submitOrderOperation($this->operation_id, $operation_data);
        out_json_data($out_data);
    }

    /**
     * 订单操作
     * @param $order_id
     * @param $cmd 1取车 2还车 3供电 4断电 5开门 6关门 7寻车
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function car_cmd($order_id = '', $cmd = '')
    {
        $out_data = [
            'code' => 100,
            'info' => "参数有误"
        ];
        if (empty($order_id) || empty($cmd)) {
            out_json_data($out_data);
        }
        $cmd = intval($cmd);
        $order_operation_model = new OrderOperation();
        switch ($cmd) {
            case 1:
                //1取车
                $out_data = $order_operation_model->OrderCarCmd($order_id, $this->operation_id, CarCmd::$CarCmdAcquire['code']);
                break;
            case 2:
                //2还车
                $out_data = $order_operation_model->OrderCarCmd($order_id, $this->operation_id, CarCmd::$CarCmdReturn['code']);
                break;
            case 3:
                //3供电
                $out_data = $order_operation_model->OrderCarCmd($order_id, $this->operation_id, CarCmd::$CarCmdIgnite['code']);
                break;
            case 4:
                //4断电
                $out_data = $order_operation_model->OrderCarCmd($order_id, $this->operation_id, CarCmd::$CarCmdFlameout['code']);
                break;
            case 5:
                //5开门
                $out_data = $order_operation_model->OrderCarCmd($order_id, $this->operation_id, CarCmd::$CarCmdOpenDoor['code']);
                break;
            case 6:
                //6关门
                $out_data = $order_operation_model->OrderCarCmd($order_id, $this->operation_id, CarCmd::$CarCmdCloseDoor['code']);
                break;
            case 7:
                //7寻车
                $out_data = $order_operation_model->OrderCarCmd($order_id, $this->operation_id, CarCmd::$CarCmdFind['code']);
                break;
        }
        out_json_data($out_data);
    }

    /**
     * 获取已完成的订单列表
     * @param int $page
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_order_operation_end($page = 0)
    {
        $order_operation_model = new OrderOperation();
        $map = ['operation_id' => $this->operation_id, 'order_status' => OrderStatus::$OrderStatusFinish['code']];
        $out_order_data = $order_operation_model->getEndList($map, 'id ASC', $page * 15, 15, false, $this->store_key_id);
        if (!empty($out_order_data)) {
            $out_data['code'] = 0;
            $out_data['data'] = $out_order_data;
            $out_data['info'] = "获取成功";
        } else {
            $out_data['code'] = 100;
            $out_data['info'] = "获取完成";
        }
        out_json_data($out_data);
    }

}