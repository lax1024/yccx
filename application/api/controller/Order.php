<?php

namespace app\api\controller;

use app\common\controller\UserBase;
use app\common\model\CarCommon as CarCommonModel;
use app\common\model\CustomerDrive as CustomerDriveModel;
use app\common\model\Order as OrderModel;
use app\common\model\OrderCharging as OrderChargingModel;
use app\common\model\Charging as ChargingModel;
use app\common\model\OrderComment;
use app\common\model\Reserve;
use app\common\model\Store;
use definition\CarStatus;
use definition\CustomerStatus;
use definition\GoodsType;
use definition\OrderStatus;
use think\cache\driver\Redis;
use think\Config;
use think\Session;

/**
 * 用户订单操作接口
 * Class Customer
 * @package app\api\controller
 */
class Order extends UserBase
{
    private $order_model;
    private $car_common_model;
    private $customer_drive_model;

    protected function _initialize()
    {
        parent::_initialize();
        $this->order_model = new OrderModel();
        $this->car_common_model = new CarCommonModel();
        $this->customer_drive_model = new CustomerDriveModel();
    }

    /**
     * 判断是否可以下单
     * @param int $goods_type
     */
    public function is_pay_order($goods_type = 1)
    {
        if (empty($goods_type)) {
            $goods_type = GoodsType::$GoodsTypeCar['code'];
        }
        $dataout = array(
            'code' => 1,
            'info' => '参数有误',
        );
        $customer_data_temp = $this->customer_model->getCustomerField($this->customer_info['customer_id'], 'customer_status,cash_is');
        $customer_status_data = CustomerStatus::$CUSTOMERSTATUS_CODE;
        $customer_status = intval($customer_data_temp['data']['customer_status']);
        switch ($customer_status) {
            case CustomerStatus::$CustomerStatusWait['code']:
                $dataout['code'] = 3;
                $dataout['info'] = $customer_status_data[$customer_status];
                out_json_data($dataout);
                break;
            case CustomerStatus::$CustomerStatusLock['code']:
                $dataout['code'] = 4;
                $dataout['info'] = $customer_status_data[$customer_status];
                out_json_data($dataout);
                break;
            case CustomerStatus::$CustomerStatusFailure['code']:
                $dataout['code'] = 3;
                $dataout['info'] = $customer_status_data[$customer_status];
                out_json_data($dataout);
                break;
            case CustomerStatus::$CustomerStatusCheck['code']:
                $dataout['code'] = 4;
                $dataout['info'] = $customer_status_data[$customer_status];
                out_json_data($dataout);
                break;
        }
        if (intval($customer_data_temp['data']['cash_is']) == 0) {
            $dataout['code'] = 5;
            $dataout['info'] = "未缴纳押金，请先缴纳押金";
            out_json_data($dataout);
        }
        $dataout = $this->order_model->IsAddOrder($this->customer_info['customer_id'], $goods_type);
        out_json_data($dataout);
    }

    /**
     * 获取图片
     * @param $goods_id
     */
    public function get_car_order_img($goods_id)
    {
        $out_data = $this->order_model->getCarOrderImg($goods_id);
        out_json_data($out_data);
    }

    /**
     * 租车提交订单
     * 输入方式 POST
     * 输入参数
     * goods_id 商品id
     * goods_type 商品类型  1 => '普通汽车', 2 => '电动车'
     * acquire_address 取车地址(可选)
     * return_address 还车地址（可选）
     * acquire_store_id 取车门店id
     * acquire_visit 取车方式 0到店取车 1送车上门
     * acquire_time 取车时间
     * return_store_id 还车门店id
     * return_visit 还车方式 0到店还车 1上门取车
     * return_time 还车时间
     * //新能源车
     * left_front 车辆左前
     * right_front 车辆右前
     * car_back 车背面
     * invoice 票据
     * other_pic 特写
     */
    public function add_car_order()
    {
        $dataout = array(
            'code' => 1,
            'info' => '参数有误',
        );
        if ($this->request->isPost()) {
            $data = $this->request->only(['goods_id', 'goods_type', 'acquire_time', 'return_time', 'acquire_visit',
                'return_visit', 'acquire_store_id', 'return_store_id', 'acquire_address', 'return_address',
                'left_front', 'right_front', 'car_back', 'invoice', 'other_pic']);
//     * @param $goodsinfo 商品信息
//            * store_id 店铺id 门店
//            * store_name 店铺店铺名称
//            * goods_amount 商品单价
//            * order_amount 订单总价格
//            * goods_id 商品id
//            * goods_name 商品名称
//            * goods_sum 商品数量
//            * goods_type 商品类
//            * order_goods 商品信息 ['goods_name'=>'商品名称','goods_price'=>'商品单价','goods_sum'=>'购买数量','goods_img'=>'商品图片','licence_plate'=>'车辆牌照']
//            * acquire_time 取用时间 时间戳
//            * acquire_address 取用地址 ['name'=>'地址名称','lng'=>'106.257','lat'=>'26.782']
//            * acquire_store_id 取用门店id
//            * acquire_store_name 取用门店名称
//            * acquire_visit 取车方式 0到店取车 1送车上门
//            * acquire_picture 取车照片['left_front'=>"",'right_front'=>"",'car_back'=>""]
//            * return_time 归还时间 时间戳
//            * return_address 归还地址 ['name'=>'地址名称','lng'=>'106.257','lat'=>'26.782']
//            * return_store_id 归还门店id
//            * return_store_name 归还门店名称
//            * return_visit 还车方式 0到店还车 1上门取车
//            * extra_cost 额外费用
//            * extra_cost_notes 额外费用说明
//     * @param $customer_info 客户信息
//            * customer_id 客户id
//            * customer_drive_id 驾驶员信息id
//            * mobile_phone 客户电话
//            * customer_information 驾驶员证件信息 ['real_name'=>'客户真实姓名','id_number'=>'身份证号码','mobile_phone'=>'电话号码']

//     * @param $channel_uid 渠道信息
//            * @param bool $is_super_admin 是否是超级管理员
//            * @param string $store_key_id 订单归属商户
            //判断商品类型
            $customer_data_temp = $this->customer_model->getCustomerField($this->customer_info['customer_id'], 'customer_status,cash_is');
            $customer_status_data = CustomerStatus::$CUSTOMERSTATUS_CODE;
            $customer_status = intval($customer_data_temp['data']['customer_status']);
            switch ($customer_status) {
                case CustomerStatus::$CustomerStatusWait['code']:
                    $dataout['code'] = 3;
                    $dataout['info'] = $customer_status_data[$customer_status];
                    out_json_data($dataout);
                    break;
                case CustomerStatus::$CustomerStatusLock['code']:
                    $dataout['code'] = 4;
                    $dataout['info'] = $customer_status_data[$customer_status];
                    out_json_data($dataout);
                    break;
                case CustomerStatus::$CustomerStatusFailure['code']:
                    $dataout['code'] = 3;
                    $dataout['info'] = $customer_status_data[$customer_status];
                    out_json_data($dataout);
                    break;
                case CustomerStatus::$CustomerStatusCheck['code']:
                    $dataout['code'] = 4;
                    $dataout['info'] = $customer_status_data[$customer_status];
                    out_json_data($dataout);
                    break;
            }

            if (empty($data['goods_type'])) {
                $goods_type = GoodsType::$GoodsTypeCar['code'];
            } else {
                $goods_type = $data['goods_type'];
            }
            $acquire_time = strtotime($data['acquire_time']);
            $return_time = strtotime($data['return_time']);
            $goods_id = $data['goods_id'];
            if (intval($goods_type) == GoodsType::$GoodsTypeElectrocar['code']) {
                $car_data = $this->car_common_model->where(['id' => $goods_id])->field('store_site_id')->find();
                $acquire_store_id = $car_data['store_site_id'];
                $return_store_id = $acquire_store_id;

            } else {
                $acquire_store_id = $data['acquire_store_id'];
                $return_store_id = $data['return_store_id'];
            }
            $goods_device = '';
            $store_key_id = '';
            $store_key_name = '';
            if (!is_numeric($goods_type)) {
                $dataout['code'] = 3;
                $dataout['info'] = '商品类型有误';
                out_json_data($dataout);
            }
            $goods_info = array();
            $order_goods = array();
            $store_model = new Store();
            if (empty($data['acquire_address'])) {
                $acquire_store = $store_model->getStoreField($acquire_store_id, 'store_name,address,location_longitude,location_latitude');
                $acquire_address = ['name' => $acquire_store['address'], 'lng' => $acquire_store['location_longitude'], 'lat' => $acquire_store['location_latitude']];
            } else {
                $acquire_address = $data['acquire_address'];
            }
            if (empty($data['return_address'])) {
                $return_store = $store_model->getStoreField($acquire_store_id, 'store_name,address,location_longitude,location_latitude');
                $return_address = ['name' => $return_store['address'], 'lng' => $return_store['location_longitude'], 'lat' => $return_store['location_latitude']];
            } else {
                $return_address = $data['return_address'];
            }
            $order_amount = 0;
            $goods_name = '';
            $goods_sum = 1;
            $rests_cost_data = array();
            $goods_amount_detail = array(
                'goods_amount' => '',
                'goods_basic' => '',
                'goods_procedure' => '',
                'goods_all' => ''
            );
            $store_model = new Store();
            $acquire_store_name = "";
            $return_store_name = "";
            $acquire_picture = "";
            $acquire_visit = 0;
            $return_visit = 0;
            switch ($goods_type) {
                //普通汽车
                case GoodsType::$GoodsTypeCar['code']:
                    $customer_data = $this->customer_model->getCustomerField($this->customer_info['customer_id'], 'mobile_phone,customer_name,id_number');
                    if (!empty($customer_data['code'])) {
                        $dataout['code'] = 3;
                        $dataout['info'] = '用户信息有误';
                        out_json_data($dataout);
                    }
                    $vehicle_drivers = $customer_data['data']['customer_name'];
                    $mobile_phone = $customer_data['data']['mobile_phone'];
                    $id_number = $customer_data['data']['id_number'];
                    if (empty($vehicle_drivers) || empty($mobile_phone) || empty($id_number)) {
                        $dataout['code'] = 3;
                        $dataout['info'] = "驾驶员信息不完善";
                        out_json_data($dataout);
                    }
                    $acquire_visit = $data['acquire_visit'];
                    $return_visit = $data['return_visit'];
                    $acquire_store_name = $store_model->getStoreName($acquire_store_id);
                    $return_store_name = $store_model->getStoreName($return_store_id);
                    if (($return_time - $acquire_time) < 1800) {
                        $dataout['code'] = 2;
                        $dataout['info'] = '使用时间必须大于30分钟';
                        out_json_data($dataout);
                    }
                    $out_goods = $this->car_common_model->getCarCommon($goods_id, true);
                    if (intval($out_goods['code']) == 0) {
                        $goods_info = $out_goods['data'];
//                        if (is_time_interval($goods_info['reserve_interval'], $acquire_time, $return_time)) {
//                            $out_error = array(
//                                'code' => 99,
//                                'info' => '时间区间已被订购，请刷新车辆列表',
//                            );
//                            out_json_data($out_error);
//
                        $goods_sum = get_acquire_return_car($acquire_time, $return_time);
                        $rests_cost_data = rests_cost_calc($data);
                        $order_amount = $goods_sum * floatval($goods_info['day_price']) + $rests_cost_data['rests_cost'];//租金+还车费用
                        $order_amount = $order_amount + $goods_sum * floatval($goods_info['day_basic']) + floatval($goods_info['day_procedure']);//基本服务费用+车行费用
//                        http://img.youchedongli.cn/public/car_show_image/A/%E5%A5%A5%E8%BF%AA/%E4%B8%80%E6%B1%BD-%E5%A4%A7%E4%BC%97%E5%A5%A5%E8%BF%AA/%E5%A5%A5%E8%BF%AAA3.jpg?x-oss-process=style/wh300
                        $goods_img = $goods_info['series_img'];
                        $order_goods = ['series_id' => $goods_info['series_id'], 'goods_name' => $goods_info['series_name'], 'goods_details' => $goods_info['cartype_name'], 'goods_price' => $goods_info['day_price'], 'goods_sum' => $goods_sum, 'goods_img' => $goods_img, 'licence_plate' => $goods_info['licence_plate']];
                        $goods_name = $goods_info['series_name'];
                        $store_key_id = $goods_info['store_key_id'];
                        $store_key_name = $goods_info['store_key_name'];
                        $goods_amount_detail = array(
                            'goods_amount' => $goods_sum * floatval($goods_info['day_price']),
                            'goods_basic' => $goods_sum * floatval($goods_info['day_basic']),
                            'goods_procedure' => floatval($goods_info['day_procedure']),
                            'goods_all' => $order_amount
                        );//商品费用明细
                    } else {
                        out_json_data($out_goods);
                    }
                    break;
                //电动车
                case GoodsType::$GoodsTypeElectrocar['code']:
                    $reserve_model = new Reserve();
                    $reserve_data = $reserve_model->isReserveCar($data['goods_id']);
                    if (!empty($reserve_model['code'])) {
                        out_json_data($reserve_data);
                    }
//                    $vehicle_drivers = $data['vehicle_drivers'];
//                    $mobile_phone = $data['mobile_phone'];
//                    $id_number = $data['id_number'];
//                    if (empty($vehicle_drivers) || empty($mobile_phone) || empty($id_number)) {
//                        $dataout['code'] = 3;
//                        $dataout['info'] = "驾驶员信息不完善";
//                        out_json_data($dataout);
//
                    $customer_data = $this->customer_model->getCustomerField($this->customer_info['customer_id'], 'mobile_phone,customer_name,id_number');
                    if (!empty($customer_data['code'])) {
                        $dataout['code'] = 3;
                        $dataout['info'] = '用户信息有误';
                        out_json_data($dataout);
                    }
                    $vehicle_drivers = $customer_data['data']['customer_name'];
                    $mobile_phone = $customer_data['data']['mobile_phone'];
                    $id_number = $customer_data['data']['id_number'];
                    //左前
                    if (empty($data['left_front'])) {
                        $dataout['code'] = 200;
                        $dataout['info'] = "左前图片无效";
                        out_json_data($dataout);
                    }
                    $acquire_picture['left_front'] = $data['left_front'];
                    //右前
                    if (empty($data['right_front'])) {
                        $dataout['code'] = 201;
                        $dataout['info'] = "右前图片无效";
                        out_json_data($dataout);
                    }
                    $acquire_picture['right_front'] = $data['right_front'];
                    //后方
                    if (empty($data['car_back'])) {
                        $dataout['code'] = 202;
                        $dataout['info'] = "后方图片无效";
                        out_json_data($dataout);
                    }
                    $acquire_picture['car_back'] = $data['car_back'];
//                    //右后
//                    if (empty($data['right_back'])) {
//                        $dataout['code'] = 203;
//                        $dataout['info'] = "右后图片无效";
//                        out_json_data($dataout);
//                    }
//                    $acquire_picture['right_back'] = $data['right_back'];
                    //票据
                    $acquire_picture['invoice'] = $data['invoice'];
                    //特写图片
                    $other_pic = array();
                    if (!empty($data['other_pic'])) {
                        if (strpos($data['other_pic'], ',') > 0) {
                            $other_pic = explode(',', $data['other_pic']);
                        }
                    }
                    $acquire_picture['other_pic'] = $other_pic;
                    if (intval($customer_data_temp['data']['cash_is']) == 0) {
                        $dataout['code'] = 5;
                        $dataout['info'] = "未缴纳押金，请先缴纳押金";
                        out_json_data($dataout);
                    }
                    $acquire_store_name = $store_model->getStoreName($acquire_store_id);
                    $return_store_name = $acquire_store_name;
                    $acquire_time = time();
                    $return_time = time();
                    $out_goods = $this->car_common_model->getCarCommon($goods_id, true);
                    if (intval($out_goods['code']) == 0) {
                        $goods_info = $out_goods['data'];
                        if ($goods_info['car_status'] != CarStatus::$CarStatusNormal['code']) {
                            $out_error = array(
                                'code' => 99,
                                'info' => '此车暂时不能被租用',
                            );
                            out_json_data($out_error);
                        }
                        $goods_sum = 0;
                        $order_amount = 0;
//                        $rests_cost_data = rests_cost_calc($data);
                        //$order_amount = $goods_sum * floatval($goods_info['day_price']) + $rests_cost_data['rests_cost'];//租金+还车费用
                        //$order_amount = $order_amount + $goods_sum * floatval($goods_info['day_basic']) + floatval($goods_info['day_procedure']);//基本服务费用+车行费用
//                        http://img.youchedongli.cn/public/car_show_image/A/%E5%A5%A5%E8%BF%AA/%E4%B8%80%E6%B1%BD-%E5%A4%A7%E4%BC%97%E5%A5%A5%E8%BF%AA/%E5%A5%A5%E8%BF%AAA3.jpg?x-oss-process=style/wh300
                        $goods_img = $goods_info['series_img'];
                        $goods_device = $goods_info['device_number'];
                        $order_goods = ['series_id' => $goods_info['series_id'], 'goods_name' => $goods_info['series_name'], 'goods_details' => $goods_info['cartype_name'], 'goods_price' => $goods_info['day_price'], 'goods_km_price' => $goods_info['km_price'], 'goods_sum' => $goods_sum, 'goods_img' => $goods_img, 'licence_plate' => $goods_info['licence_plate']];
                        $goods_name = $goods_info['series_name'];
                        $store_key_id = $goods_info['store_key_id'];
                        $store_key_name = $goods_info['store_key_name'];
                        $goods_amount_detail = array(
                            'goods_amount' => $goods_sum * floatval($goods_info['day_price']),
                            'goods_basic' => $goods_sum * floatval($goods_info['day_basic']),
                            'goods_km_price' => floatval($goods_info['km_price']),
                            'goods_all' => $order_amount
                        );//商品费用明细
                    } else {
                        out_json_data($out_goods);
                    }
                    break;
                //充电桩
                case GoodsType::$GoodsTypeCharging['code']:
                    break;
                default:
                    $out_goods['code'] = 5;
                    $out_goods['info'] = "商品类型未定义";
                    out_json_data($out_goods);
            }
            $goodsinfo = array(
                'store_id' => $acquire_store_id,
                'store_name' => $acquire_store_name,
                'goods_amount' => $goods_info['day_price'],
                'goods_km_amount' => $goods_info['km_price'],
                'order_amount' => $order_amount,
                'goods_id' => $goods_id,
                'goods_device' => $goods_device,
                'goods_name' => $goods_name,
                'goods_sum' => $goods_sum,
                'goods_type' => $goods_type,
                'goods_amount_detail' => $goods_amount_detail,
                'order_goods' => $order_goods,
                'acquire_time' => $acquire_time,
                'acquire_address' => $acquire_address,
                'acquire_store_id' => $acquire_store_id,
                'acquire_store_name' => $acquire_store_name,
                'acquire_visit' => $acquire_visit,
                'acquire_picture' => $acquire_picture,
                'return_time' => $return_time,
                'return_address' => $return_address,
                'return_store_id' => $return_store_id,
                'return_store_name' => $return_store_name,
                'return_visit' => $return_visit,
                'extra_cost' => $rests_cost_data['extra_cost'],
                'extra_cost_notes' => $rests_cost_data['extra_cost_notes'],
                'store_key_id' => $store_key_id,
                'store_key_name' => $store_key_name
            );
            $customer_drive = array(
                'customer_id' => $this->customer_info['customer_id'],
                'vehicle_drivers' => $vehicle_drivers,
                'mobile_phone' => $mobile_phone,
                'id_number' => $id_number,
            );
            //$this->customer_drive_model->addCustomerDrive($customer_drive);
            $customer_information = $customer_drive;
            $customerinfo = array(
                'customer_id' => $this->customer_info['customer_id'],
                'mobile_phone' => $this->customer_info['mobile_phone'],
                'customer_information' => $customer_information
            );
            $is_super_admin = false;
            $store_key_id = $goods_info['store_key_id'];
            $channel_uid = Session::get('channel_uid');
            if (empty($channel_uid)) {
                $channel_uid = 0;
            }
            $out_order = $dataout;
            switch ($goods_type) {
                //普通汽车
                case GoodsType::$GoodsTypeCar['code']:
                    $out_order = $this->order_model->addOrder($goodsinfo, $customerinfo, $channel_uid, $is_super_admin, $store_key_id);
                    break;
                //电动车
                case GoodsType::$GoodsTypeElectrocar['code']:
                    $out_order = $this->order_model->addEleOrder($goodsinfo, $customerinfo, $channel_uid, $is_super_admin, $store_key_id);
                    break;
                //充电桩
                case GoodsType::$GoodsTypeCharging['code']:
                    break;
                default:
                    $out_goods['code'] = 5;
                    $out_goods['info'] = "商品类型未定义";
                    out_json_data($out_goods);
            }
            out_json_data($out_order);
        }
        out_json_data($dataout);
    }

    /**
     * 输入方式 POST
     * 输入参数
     * order_id 订单
     * return_address 还车地址（可选）
     * return_time 还车时间
     */
    public function add_lease_order()
    {
        $out_data = array(
            'code' => 1,
            'info' => '参数有误',
        );
        if ($this->request->isPost()) {
            $data = $this->request->only(['order_id', 'return_address', 'return_time']);
            $order_id = $data['order_id'];
            $customer_id = $this->customer_info['customer_id'];
            $order_data = $this->order_model->getOrder($order_id, $customer_id);
            if (!empty($order_data['code'])) {
                out_json_data($out_data);
            }
            $order_data = $order_data['data'];
            $acquire_time = $order_data['return_time'];
            $return_time = strtotime($data['return_time']);
            $car_goods = $this->car_common_model->getCarCommon($order_data['goods_id'], true);
            if (intval($car_goods['code']) == 0) {
                $goods_info = $car_goods['data'];
//                        if (is_time_interval($goods_info['reserve_interval'], $acquire_time, $return_time)) {
//                            $out_error = array(
//                                'code' => 99,
//                                'info' => '时间区间已被订购，请刷新车辆列表',
//                            );
//                            out_json_data($out_error)
                $goods_sum = get_acquire_return_car($acquire_time, $return_time);
                $lease_amount = $goods_sum * floatval($goods_info['day_price']);//租金+还车费用
                $lease_amount = $lease_amount + $goods_sum * floatval($goods_info['day_basic']);//基本服务费用+车行费用
                $lease_amount_detail = array(
                    'goods_amount' => $goods_sum * floatval($goods_info['day_price']),
                    'goods_basic' => $goods_sum * floatval($goods_info['day_basic']),
                    'goods_procedure' => 0,
                    'goods_all' => $lease_amount
                );//商品费用明细
//                'lease_amount' => $lease_data['lease_amount'],
//            'lease_amount_detail' => serialize($lease_data['lease_amount_detail']),
//            'lease_data' => $lease_data['lease_data'],
                $lease_data['order_id'] = $order_id;
                $lease_data['lease_amount'] = $lease_amount;
                $lease_add_data = [
                    'return_time' => strtotime($data['return_time']),
                    'return_address' => $data['return_address']
                ];
                $lease_data['lease_data'] = $lease_add_data;
                $lease_data['lease_amount_detail'] = $lease_amount_detail;
                $out_data = $this->order_model->addLeaseData($lease_data);
                out_json_data($out_data);
            }
            out_json_data($car_goods);
        }
        out_json_data($out_data);
    }

    /**
     * 普通租车获取订单支付信息
     * 输入方式 POST
     * 输入参数
     * orders 订单编号,订单编号,订单编号
     * balance 是否只有余额
     */
    public function get_pay_info()
    {
        $dataout = array(
            'code' => 1,
            'info' => '参数有误',
            'out_trade_no' => '',
            'body' => '',
            'total_price' => ''
        );
        if ($this->request->isPost()) {
            $data = $this->request->only(['orders', 'balance']);
            $orders = $data['orders'];
            if (strpos($orders, ',') > 0) {
                $order_arr = $orders_arr = explode(',', $orders);
            } else if (!empty($orders)) {
                $order_arr[] = $orders;
            }
            if (empty($data['balance'])) {
                $balance = false;
            } else {
                $balance = true;
            }
            $order_sn = $order_arr[0];
            if (empty($order_sn)) {
                out_json_data($dataout);
            }
            $out_order = $this->order_model->getOrderSnField($order_sn, $this->customer_info['customer_id'], 'lease_amount,is_lease,order_status');
            if (!empty($out_order['code'])) {
                out_json_data($out_order);
            }
            $order_status = intval($out_order['data']['order_status']);
            $is_lease = intval($out_order['data']['is_lease']);
            if ($is_lease == 1) {
                if ($order_status == OrderStatus::$OrderStatusPayment['code'] || $order_status == OrderStatus::$OrderStatusAcquire['code']) {
                    $order_info = $this->order_model->getPayLeaseInfo($order_sn, $balance, $this->customer_info['customer_id']);
                    out_json_data($order_info);
                } else {
                    $order_info = array(
                        'code' => 1000,
                        'info' => "该订单不能被续租",
                        'out_trade_no' => '',
                        'body' => '',
                        'total_price' => ''
                    );
                    out_json_data($order_info);
                }
            } else {
                $out_order = $this->order_model->getPayInfo($order_arr, $balance, $this->customer_info['customer_id']);
                out_json_data($out_order);
            }
        }
        out_json_data($dataout);
    }

    /**
     * 新能源租车获取订单支付信息
     * 输入方式 POST
     * 输入参数
     * orders 订单编号,订单编号,订单编号
     * balance 是否只有余额
     */
    public function get_ele_pay_info()
    {
        $dataout = array(
            'code' => 1,
            'info' => '参数有误'
        );
        if ($this->request->isPost()) {
            $data = $this->request->only(['orders', 'balance', 'coupon_sn']);
            $orders = $data['orders'];
            $coupon_sn = $data['coupon_sn'];
            if (strpos($orders, ',') > 0) {
                $order_arr = $orders_arr = explode(',', $orders);
            } else if (!empty($orders)) {
                $order_arr[] = $orders;
            }
            if (empty($data['balance'])) {
                $balance = false;
            } else {
                $balance = true;
            }
            $out_order = $this->order_model->getElePayInfo($order_arr, $balance, $this->customer_info['customer_id'], $coupon_sn);
            out_json_data($out_order);
        }
        out_json_data($dataout);
    }

    /**
     * 等待审核订单
     * @param string $order_id
     */
    public function wait_order($order_id = '')
    {
        $dataout = array(
            'code' => 1,
            'info' => '参数有误',
        );
        if (!empty($order_id)) {
            $dataout = $this->order_model->waitEleOrder($order_id, $this->customer_info['customer_id']);
        }
        out_json_data($dataout);
    }

    /**
     * 判断是否可以还车
     * @param $order_id
     */
    public function get_order_car_info($order_id)
    {
        $dataout = array(
            'code' => 100,
            'info' => '参数有误',
        );
        if (empty($order_id)) {
            out_json_data($dataout);
        }
        $dataout = $this->order_model->VerifyOrderCarStatus($order_id, $this->customer_info['customer_id']);
        out_json_data($dataout);
    }

    /**
     * 更新还车门店
     * @param $order_id 订单id
     * @param $return_store_id 还车门店id
     */
    public function return_store_site($order_id, $return_store_id)
    {
        $dataout = array(
            'code' => 1,
            'info' => '参数有误',
        );
        if (empty($order_id) || empty($return_store_id)) {
            out_json_data($dataout);
        }
        $store_model = new Store();
        $return_store = $store_model->getStoreField($return_store_id, 'store_name,address,location_longitude,location_latitude');
        $acquire_address = ['name' => $return_store['address'], 'lng' => $return_store['location_longitude'], 'lat' => $return_store['location_latitude']];
        $dataout = $this->order_model->returnStoreSite($order_id, $return_store_id, $return_store['store_name'], $acquire_address, $this->customer_info['customer_id']);
        out_json_data($dataout);
    }

    /**
     * 等待还车审核订单
     * @param $order_id
     */
    public function wait_return_order($order_id)
    {
        $dataout = array(
            'code' => 1,
            'info' => '参数有误'
        );
        if (!empty($order_id)) {
            $dataout = $this->order_model->waitReturnOrder($order_id, $this->customer_info['customer_id']);
        }
        out_json_data($dataout);
    }

    /**
     * 存储还车图片
     * @return array
     */
    public function return_order_picture()
    {
        $dataout = array(
            'code' => 1,
            'info' => '参数有误',
        );
        $data = $this->request->only(['order_id', 'left_front', 'right_front', 'car_back', 'interior', 'ticket', 'add_pic']);
        if (empty($data['order_id'])) {
            $dataout['code'] = 199;
            $dataout['info'] = "订单信息有误";
            out_json_data($dataout);
        }
        $return_picture = array();
        //左前
        if (empty($data['left_front'])) {
            $dataout['code'] = 200;
            $dataout['info'] = "左前图片无效";
            out_json_data($dataout);
        }
        $return_picture['left_front'] = $data['left_front'];
        //右前
        if (empty($data['right_front'])) {
            $dataout['code'] = 201;
            $dataout['info'] = "右前图片无效";
            out_json_data($dataout);
        }
        $return_picture['right_front'] = $data['right_front'];
        //左后
        if (empty($data['car_back'])) {
            $dataout['code'] = 202;
            $dataout['info'] = "车后方图片无效";
            out_json_data($dataout);
        }
        $return_picture['car_back'] = $data['car_back'];
        //左后
        if (empty($data['interior'])) {
            $dataout['code'] = 202;
            $dataout['info'] = "内部图片无效";
            out_json_data($dataout);
        }
        $return_picture['interior'] = $data['interior'];
        $return_picture['ticket'] = $data['ticket'];
        //特写图片
        $add_pic = array();
        if (!empty($data['add_pic'])) {
            if (strpos($data['add_pic'], ',') > 0) {
                $add_pic = explode(',', $data['add_pic']);
            }
        }
        $return_picture['add_pic'] = $add_pic;
        $dataout = $this->order_model->returnOrderPicture($data['order_id'], $this->customer_info['customer_id'], $return_picture);
        out_json_data($dataout);
    }

    /**
     * 快速还车接口
     * @param $car_id
     * @param $order_id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function quick_elesite($car_id, $order_id)
    {
        //众泰车型可还车站点
        $zt_store = [188, 335, 336];
        $out_data['code'] = 1;
        $out_data['info'] = "参数不能为空";
        if (empty($car_id)) {
            out_json_data($out_data);
        }
        $car_data = $this->car_common_model->where(array('id' => $car_id))->field('store_key_id,device_number,series_id')->find();
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
            $out_data['info'] = "当前位置无还车点";
            out_json_data($out_data);
        }
        $distance = get_distance($device_data['longitude'], $device_data['latitude'], $store_key_list[0]['location_longitude'], $store_key_list[0]['location_latitude'], 1);
        if (intval($distance) > intval($store_key_list[0]['store_scope'])) {
            $out_data['code'] = 1010;
            $out_data['info'] = '未在还车区域内，不可快速还车';
            out_json_data($out_data);
        }
        $is_parking = true;
        if (!empty($car_data['series_id'])) {
            $series_id = intval($car_data['series_id']);
            $store_pid = intval($store_key_list[0]['store_pid']);
//            if (($series_id == 4664 && $store_pid != 307) || ($series_id == 4664 && !in_array($return_store_id, $zt_store))) {
            if (($series_id == 4664 && $store_pid != 307)) {
                if (!in_array($return_store_id, $zt_store)) {
                    $out_data['code'] = 1020;
                    $out_data['info'] = '众泰车型仅限大学城、金石产业园区域、贵州新昌还车';
                    out_json_data($out_data);
                }
            }
            if ($series_id == 4663 || $series_id == 4664 || $series_id == 4666) {
                $is_parking = false;
            }
        }
        $out_data = $this->order_model->Car_device_status($device_data['carStatus'], true, $is_parking);
        if (!empty($out_data['code'])) {
            out_json_data($out_data);
        }
        $store_model = new Store();
        $return_store = $store_model->getStoreField($return_store_id, 'store_name,address,location_longitude,location_latitude');
        $acquire_address = ['name' => $return_store['address'], 'lng' => $return_store['location_longitude'], 'lat' => $return_store['location_latitude']];
        $out_data = $this->order_model->returnStoreSite($order_id, $return_store_id, $return_store['store_name'], $acquire_address, $this->customer_info['customer_id']);
        out_json_data($out_data);
    }

    /**
     * 获取订单列表
     * 输入方式 GET
     * @param int $page 页码
     * @param int $status 状态
     */
    public function get_list($page = 1, $status = 0)
    {
        if (empty($page)) {
            $page = 1;
        }
        $map['customer_id'] = $this->customer_info['customer_id'];
        switch (intval($status)) {
            case 1:
                $map['order_status'] = ['in', [OrderStatus::$OrderStatusNopayment['code'], OrderStatus::$OrderStatusPayment['code'], OrderStatus::$OrderStatusAcquire['code']]];
                break;
            case 2:
                $map['order_status'] = ['in', [OrderStatus::$OrderStatusCanceled['code'], OrderStatus::$OrderStatusReturn['code'], OrderStatus::$OrderStatusFinish['code']]];
                break;
        }
        $limit = 15;
        $order = ' create_time DESC ';
        $page_config = ['page' => $page, 'query' => ['keyword' => '']];
        $order_list = $this->order_model->getPageList($map, $order, $page_config, $limit, true);
//        echo  $this->order_model->getLastSql();
        $order_list_data = json_encode($order_list);
        $order_list_data = json_decode($order_list_data, true);
        if (empty($order_list_data['data'])) {
            $out_data['code'] = 100;
            $out_data['info'] = "数据已加载完";
            out_json_data($out_data);
        }
        $order_list_data['code'] = 0;
        $order_list_data['info'] = "获取成功";
        out_json_data($order_list_data);
    }

    /**
     * 获取订单信息
     * @param $id
     */
    public function get_order_info($id)
    {
        $out_data = array(
            'code' => 1,
            'info' => "参数有误"
        );
        if (empty($id)) {
            out_json_data($out_data);
        }
        $out_order = $this->order_model->getOrder($id, $this->customer_info['customer_id']);
        $store_model = new Store();
        $acquire_store = $store_model->getStore($out_order['data']['acquire_store_id']);
        if ($out_order['data']['acquire_store_id'] != $out_order['data']['return_store_id']) {
            $return_store = $store_model->getStore($out_order['data']['return_store_id']);
        } else {
            $return_store = $acquire_store;
        }
        $out_order['data']['acquire_store'] = $acquire_store['data'];
        $out_order['data']['return_store'] = $return_store['data'];
        $course = Config::get('course');
        $car_data = $course[intval($out_order['data']['order_goods']['series_id'])];
        $out_order['data']['car'] = $car_data;
        out_json_data($out_order);
    }

    /**
     * 获取订单信息
     * @param $order_sn
     */
    public function get_ordersn_info($order_sn)
    {
        $out_data = array(
            'code' => 1,
            'info' => "参数有误"
        );
        if (empty($order_sn)) {
            out_json_data($out_data);
        }
        $out_order = $this->order_model->getOrderSn($order_sn, $this->customer_info['customer_id']);
        out_json_data($out_order);
    }

    /**
     * 获取订单信息
     * @param $order_sn
     */
    public function get_ordersn_end_info($order_sn)
    {
        $out_data = array(
            'code' => 1,
            'info' => "参数有误"
        );
        if (empty($id)) {
            out_json_data($out_data);
        }
        $out_order = $this->order_model->getOrderSn($order_sn, $this->customer_info['customer_id']);
        $out_order['data']['order_goods'] = unserialize($out_order['data']['order_goods']);
        $out_order['data']['goods_amount_detail'] = unserialize($out_order['data']['goods_amount_detail']);
        $store_model = new Store();
        $acquire_store = $store_model->getStore($out_order['data']['acquire_store_id']);
//        $baidumap = new baiduMap();
//        $lng = $acquire_store['data']['location_longitude'];
//        $lat = $acquire_store['data']['location_latitude'];
//        $address_info = json_decode($baidumap->get_city_address($lng, $lat), true);
//        $acquire_store['data']['address_info'] = $address_info['formatted_address'];
        if ($out_order['data']['acquire_store_id'] != $out_order['data']['return_store_id']) {
            $return_store = $store_model->getStore($out_order['data']['return_store_id']);
            $lng = $return_store['data']['location_longitude'];
            $lat = $return_store['data']['location_latitude'];
//            $address_info = json_decode($baidumap->get_city_address($lng, $lat), true);
//            $return_store['data']['address_info'] = $address_info['formatted_address'];
        } else {
            $return_store = $acquire_store;
        }
        $out_order['data']['acquire_store'] = $acquire_store['data'];
        $out_order['data']['return_store'] = $return_store['data'];
        out_json_data($out_order);
    }

    /**取消订单
     * @param $order_id
     */
    public function cancel_order($order_id)
    {
        $dataout = array(
            'code' => 1,
            'info' => '参数有误'
        );
        if (empty($order_id)) {
            out_json_data($dataout);
        }
        if ($this->order_model->cancelOrder($order_id, $this->customer_info['customer_id'])) {
            $dataout['code'] = 0;
            $dataout['info'] = '取消成功';
            out_json_data($dataout);
        } else {
            $dataout['code'] = 2;
            $dataout['info'] = '取消失败';
            out_json_data($dataout);
        }
    }

    /**
     * 订单命令控制
     * @param $order_id
     * @param $cmd
     */
    public function order_cmd($order_id, $cmd)
    {
        $dataout = array(
            'code' => 1,
            'info' => '参数有误'
        );
        if (empty($order_id) || empty($cmd)) {
            out_json_data($dataout);
        }
        $dataout = $this->order_model->OrderCarCmd($order_id, $this->customer_info['customer_id'], $cmd);
        out_json_data($dataout);
    }

    /**
     * 获取订单还车店铺
     * @param string $order_id
     */
    public function order_retunr_store($order_id = '')
    {
        $dataout = array(
            'code' => 1,
            'info' => '参数有误'
        );
        if (empty($order_id)) {
            out_json_data($dataout);
        }
        $out_order = $this->order_model->getOrder($order_id, $this->customer_info['customer_id']);
        $stroe_model = new Store();
        $store = $stroe_model->where(['id' => $out_order['data']['return_store_id']])->field('store_name,store_intro,address,store_imgs,store_park_price,take_park_remark,return_park_remark')->find();
        $stroe_model->formatx($store);
        $dataout['data'] = $store;
        $dataout['code'] = 0;
        $dataout['info'] = "获取成功";
        out_json_data($dataout);

    }

    /**
     * 提前还车
     * order_id 订单
     * return_address 还车地址（可选）
     * return_time 还车时间
     * @return array
     */
    public function return_reserve_order()
    {
        $out_data = array(
            'code' => 1,
            'info' => '参数有误',
        );
        if ($this->request->isPost()) {
            $data = $this->request->only(['order_id', 'return_address', 'return_time']);
            $order_id = $data['order_id'];
            $customer_id = $this->customer_info['customer_id'];
            $out_data = $this->order_model->returnReserveOrder($order_id, $customer_id, $data['return_address']);
        }
        return $out_data;
    }

//------------------------评论订单--------------------------------

    /**
     * 添加评论
     */
    public function add_comment()
    {
        $out_data = array(
            'code' => 100,
            'info' => '参数有误',
        );
        if ($this->request->isPost()) {
            $data = $this->request->only(['order_id', 'star_level', 'tag', 'content'
                , 'voice', 'is_hide']);
            $data['customer_id'] = $this->customer_info['customer_id'];
            $order_comment_model = new OrderComment();
            $out_data = $order_comment_model->addComment($data);
        }
        out_json_data($out_data);
    }

    /**
     * 添加评论
     * @param string $order_id
     */
    public function get_comment($order_id = '')
    {
        $out_data = array(
            'code' => 100,
            'info' => '参数有误',
        );
        $data['customer_id'] = $this->customer_info['customer_id'];
        $order_data = $this->order_model->getOrder($order_id, $this->customer_info['customer_id']);
        if (!empty($order_data['code'])) {
            $out_data['code'] = 101;
            $out_data['info'] = "订单数据有误";
            out_json_data($out_data);
        }
        $order_comment_model = new OrderComment();
        $map['order_id'] = $order_id;
        $map['customer_id'] = $this->customer_info['customer_id'];
        $comment_data = $order_comment_model->getComment($map);
        if (!empty($comment_data['code'])) {
            $out_data['code'] = 102;
            $out_data['info'] = "评论有误";
            out_json_data($out_data);
        }
        $out_data['data'] = $comment_data['data'];
        $out_data['data']['car_info'] = $order_data['data']['order_goods'];
        $out_data['data']['store_key_name'] = $order_data['data']['store_key_name'];
        $out_data['data']['interval_time_str'] = $order_data['data']['interval_time_str'];
        $out_data['data']['all_mileage'] = $order_data['data']['all_mileage'];
        $out_data['data']['order_amount'] = $order_data['data']['order_amount'];
        $out_data['code'] = 0;
        $out_data['info'] = "获取成功";
        out_json_data($out_data);
    }

    /**
     * 修改评论
     */
    public function update_comment()
    {
        $out_data = array(
            'code' => 100,
            'info' => '参数有误',
        );
        if ($this->request->isPost()) {
            $data = $this->request->only(['order_id', 'id', 'star_level', 'tag', 'content'
                , 'voice', 'is_hide']);
            $order_comment_model = new OrderComment();
            $data['customer_id'] = $this->customer_info['customer_id'];
            $out_data = $order_comment_model->updateComment($data);
        }
        out_json_data($out_data);
    }

    /**
     * 删除评论
     */
    public function del_comment()
    {
        $out_data = array(
            'code' => 100,
            'info' => '参数有误',
        );
        if ($this->request->isPost()) {
            $data = $this->request->only(['id', $this->customer_info['customer_id']]);
            $order_comment_model = new OrderComment();
            $out_data = $order_comment_model->delComment($data);
        }
        out_json_data($out_data);
    }

//------------------------评论订单结束--------------------------------

//-------------------------充电桩订单----------------------------------


    public function addOrderCharging()
    {
        $out_data = array(
            'code' => 1,
            'info' => '参数有误',
        );
        //cid  充电桩编号
        //gid  充电枪编号
        if ($this->request->isPost()) {
            $data = $this->request->only(['cid', 'gid']);
            $cid = $data['cid'];
            $gid = $data['gid'];
            if (empty($gid)) {
                $gid = 0;
            }
            if (empty($cid)) {
                $out_data['code'] = 100;
                $out_data['info'] = "设备编号不正常";
            }
//            * @param $charging_info 充电桩信息
//            * store_id 店铺id 门店
//            * store_name 店铺店铺名称
//            * goods_amount 商品单价
//            * goods_hour_amount 商品每小时单价
//            * order_amount 订单总价格
//            * goods_id 商品id
//            * goods_type 商品类
//            * order_goods 商品信息 ['goods_name'=>'商品名称','goods_price'=>'商品单价','goods_img'=>'商品图片']
//     * acquire_time 取用时间 时间戳
//            * return_time 归还时间 时间戳
//            * extra_cost 额外费用
//            * extra_cost_notes 额外费用说明
//            * @param $customer_info 客户信息
//            * customer_id 客户id
//            * mobile_phone 客户电话
//            * @param $channel_uid 渠道信息
//            * @param bool $is_super_admin 是否是超级管理员
//            * @param string $store_key_id 订单归属商户
            $charging_model = new ChargingModel();
            $map = [
                'device_number' => $cid,
                'device_gun' => $gid
            ];
            $charging_out_data = $charging_model->getChargingWhere($map, true);
            $charging_data = $charging_out_data['data'];
            $charging_info = [
                'store_id' => $charging_data['store_id'],
                'store_name' => $charging_data['store_name'],
                'goods_amount' => $charging_data['quantity_price'],
                'goods_hour_amount' => $charging_data['hour_price'],
                'order_amount' => 0,
            ];
            $order_charging_model = new OrderChargingModel();
//            $order_charging_model->addOrder();
        }
    }

//-------------------------充电桩订单结束----------------------------------
}