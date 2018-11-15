<?php

namespace app\common\model;

/**
 * 订单数据 数据模型
 */

use definition\BalanceType;
use definition\CarCmd;
use definition\GoodsType;
use definition\OrderStatus;
use definition\PayCode;
use think\cache\driver\Redis;
use think\Config;
use think\Model;
use tool\CarDeviceTool;

class Order extends Model
{
    private $orderpaylog_model;
    private $customer_model;
    protected $insert = ['create_time'];

    public function __construct($data = [])
    {
        parent::__construct($data);
        $this->orderpaylog_model = new OrderPayLog();
        $this->customer_model = new Customer();
    }

    /**
     * 创建时间
     * @return bool|string
     */
    protected function setCreateTimeAttr()
    {
        return date("Y-m-d H:i:s");
    }

    /**
     * 获取订单列表
     * @param array $map
     * @param string $order
     * @param $page_config
     * @param int $limit
     * @param bool $is_super_admin
     * @param string $store_key_id
     * @return \think\Paginator
     * @throws \think\exception\DbException
     */
    public function getPageList($map = [], $order = '', $page_config, $limit = 8, $is_super_admin = false, $store_key_id = '', $is_content = false)
    {
        //如果不是超级管理员
        if (!$is_super_admin) {
            $map['store_key_id'] = $store_key_id;
        }
        $order_list = $this->where($map)->order($order)->paginate($limit, false, $page_config);
        if (!empty($order_list)) {
            $order_comment_model = new OrderComment();
            foreach ($order_list as &$value) {
                if (intval($value['goods_type']) == GoodsType::$GoodsTypeCar['code']) {
                    $this->formatx($value);
                } else if (intval($value['goods_type']) == GoodsType::$GoodsTypeElectrocar['code']) {
                    $this->formatxEle($value);
                }
                $value['star_level'] = 0;
                $value['content'] = "";
                if ($is_content) {
                    $map = [
                        'order_id' => $value['id']
                    ];
                    $order_comment_data = $order_comment_model->getComment($map, "star_level,content");
                    if (empty($order_comment_data['code'])) {
                        $value['star_level'] = $order_comment_data['data']['star_level'];
                        $value['content'] = $order_comment_data['data']['content'];
                    }
                }
            }
        }
        return $order_list;
    }

    /**
     * 获取订单列表
     * @param array $map
     * @param string $order
     * @param $page
     * @param int $limit
     * @param bool $is_super_admin
     * @param string $store_key_id
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getList($map = array(), $order = '', $page, $limit = 8, $is_super_admin = false, $store_key_id = '')
    {
        //如果不是超级管理员
        if (!$is_super_admin) {
            $map['store_key_id'] = $store_key_id;
        }
        if (intval($limit) == 0) {
            $order_list = $this->where($map)->order($order)->select();
        } else {
            $order_list = $this->where($map)->order($order)->limit($page, $limit)->select();
        }
        if (!empty($order_list)) {
            foreach ($order_list as &$value) {
                if (intval($value['goods_type']) == GoodsType::$GoodsTypeCar['code']) {
                    $this->formatx($value);
                } else if (intval($value['goods_type']) == GoodsType::$GoodsTypeElectrocar['code']) {
                    $this->formatxEle($value);
                }
            }
        }
        return $order_list;
    }

    /**
     * 格式化常规订单数据
     * @param $data
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function formatx(&$data)
    {
        if (empty($data['finnshed_time'])) {
            $data['finnshed_time'] = "未完成";
        } else {
            $data['finnshed_time'] = date('Y-m-d H:i:s', $data['finnshed_time']);
        }
        if (empty($data['payment_time'])) {
            $data['payment_time_str'] = "未付款";
        } else {
            $data['payment_time_str'] = date('Y-m-d H:i:s', $data['payment_time']);
        }
        $order_status = OrderStatus::$ORDER_STATUS_CODE;
        $data['order_status_str'] = $order_status[$data['order_status']];
        $data['remain_time'] = '0';
        if (intval($data['goods_type']) == GoodsType::$GoodsTypeCar['code']) {
            $data['goods_type_str'] = "常规租车";
            if (intval($data['order_status']) == OrderStatus::$OrderStatusNopayment['code']) {
                $data['payment_time'] = "未支付";
                $data['payment_code_str'] = "未支付";
                $remain_time = time() - strtotime($data['create_time']);
                //如果下单时间 大于 30分钟 并且是没有付款 订单将自动取消
                if ($remain_time > 1800) {
                    $this->cancelOrder($data['id'], '', 0, true);
                    $data['payment_time'] = "未支付";
                    $data['payment_code_str'] = "已取消";
                    $data['order_status'] = 0;
                    $data['order_status_str'] = $order_status[$data['order_status']];
                }
                $data['remain_time'] = 1800 - $remain_time;
                $data['remain_time_str'] = diy_time_tostr(1800 - $remain_time)['timestr'];

            } else {
                if (intval($data['order_status']) == OrderStatus::$OrderStatusCanceled['code']) {
                    $data['payment_time'] = "未付款";
                } else {
                    $data['payment_time'] = date('Y-m-d H:i:s', $data['payment_time']);
                }
                $pay_code = PayCode::$PAY_CODE;
                $data['payment_code_str'] = $pay_code[$data['payment_code']];
            }
        }
        if (!empty($data['rests_cost_time'])) {
            $data['rests_cost_time'] = date('Y-m-d H:i:s', $data['rests_cost_time']);
        } else {
            $data['rests_cost_time'] = "无数据";
        }
//        if (!empty($data['customer_information'])) {
//            $data['customer_information'] = unserialize($data['customer_information']);
//        }
//        if (!empty($data['order_goods'])) {
//            $data['order_goods'] = unserialize($data['order_goods']);
//        }
        if (!empty($data['goods_amount_detail'])) {
            $data['goods_amount_detail'] = unserialize($data['goods_amount_detail']);
        }
        if (!empty($data['lease_amount_detail'])) {
            $data['lease_amount_detail'] = unserialize($data['lease_amount_detail']);
        }
        if (!empty($data['lease_data'])) {
            $data['lease_data'] = unserialize($data['lease_data']);
        }
        if (!empty($data['acquire_address'])) {
            $data['acquire_address'] = unserialize($data['acquire_address']);
        }
        if (!empty($data['return_address'])) {
            $data['return_address'] = unserialize($data['return_address']);
        }
        if (!empty($data['return_expect_address'])) {
            $data['return_expect_address'] = unserialize($data['return_expect_address']);
        }
        $data['all_mileage'] = 0;
        if (!empty($data['acquire_time'])) {
            $data['acquire_time_str'] = date("m-d H:i", $data['acquire_time']);
            $data['acquire_time_d_str'] = date("Y-m-d H:i:s", $data['acquire_time']);
        } else {
            $data['acquire_time_d_str'] = "无数据";
        }
        if (!empty($data['return_time'])) {
            $data['return_time_str'] = date("m-d H:i", $data['return_time']);
            $data['return_time_d_str'] = date("Y-m-d H:i:s", $data['return_time']);
        } else {
            $data['return_time_d_str'] = "无数据";
        }
        $data['return_time_data'] = time_toarray($data['return_time']);
        $data['reality_return_time_str'] = date("m-d H:i", $data['reality_return_time']);
        $data['interval_time'] = $data['return_time'] - $data['acquire_time'];
        $data['interval_time_str'] = diy_time_tostr($data['interval_time']);
        $data['user_time'] = intval((intval($data['reality_return_time']) - intval($data['acquire_time'])) / 3600);
        $tel = Config::get('store_tel');
        $data['acquire_store_tel'] = $tel;
        $data['return_store_tel'] = $tel;
        if (empty($data['coupon_amount'])) {
            $data['coupon_type'] = 0;
        } else {
            $data['coupon_type'] = $data['coupon_amount'];
        }
        $data['coupon_class'] = "满减劵";
    }

    /**
     * 格式化新能源订单数据
     * @param $data
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function formatxEle(&$data)
    {
        if (empty($data['finnshed_time'])) {
            $data['finnshed_time'] = "未完成";
        } else {
            $data['finnshed_time'] = date('Y-m-d H:i:s', $data['finnshed_time']);
        }
        if (!empty($data['reality_return_time'])) {
            $data['return_time'] = $data['reality_return_time'];
        }
        $order_status = OrderStatus::$ORDER_STATUS_CODE;
        $data['order_status_str'] = $order_status[$data['order_status']];
        $data['goods_type_str'] = "新能源租车";
        $data['remain_time'] = '0';
        $order_amount = $data['order_amount'];
        $goods_amount = $data['goods_amount'];
        $goods_km_amount = $data['goods_km_amount'];
        $acquire_time = $data['acquire_time'];
        if ($data['acquire_store_id'] != $data['return_store_id']) {
            $extra_cost = Config::get('extra_cost');
        } else {
            $extra_cost = 0;
        }
        $remain_time = time() - strtotime($data['create_time']);
        //如果下单时间 大于 900秒 并且是没有进入下一个环节 自动取消订单
        if ($remain_time > 30 && intval($data['order_status']) == OrderStatus::$OrderStatusNopayment['code']) {
            $this->cancelOrder($data['id'], '', 0, true);
            $data['payment_time'] = "未支付";
            $data['payment_code_str'] = "已取消";
            $data['order_status'] = 0;
            $data['order_amount'] = 0;
            $data['order_status_str'] = $order_status[$data['order_status']];
        } else {
            if (intval($data['order_status']) == OrderStatus::$OrderStatusAcquire['code']) {
                $return_time = time();
            } else {
                $return_time = $data['reality_return_time'];
                if (empty($return_time)) {
                    $return_time = $data['return_time'];
                }
                if (empty($return_time)) {
                    $return_time = $acquire_time;
                }
            }
            $goods_sum = ($return_time - $acquire_time) / 3600;
            $order_amount = floatval($goods_amount) * floatval($goods_sum);
            $order_amount = round($order_amount + floatval($extra_cost), 2);
            $all_mileage = intval($data['return_mileage']) - intval($data['acquire_mileage']);
            $order_amount = $order_amount + (floatval($all_mileage) * floatval($goods_km_amount));
            $data['order_amount'] = $order_amount;
            $data['interval_time'] = $return_time - $acquire_time;
            $data['interval_time_str'] = order_time_tostr($return_time - $acquire_time);
            $data['extra_cost'] = $extra_cost;
            if (intval($data['order_status']) == OrderStatus::$OrderStatusAcquire['code']) {
                $redis = new Redis();
                $out_data_device = $redis->get("status:" . $data['goods_device']);
                if (!empty($out_data_device)) {
//                    $device_data = json_decode($out_data_device, true);
//                    if (empty($device_data['energy'])) {
//                        $data['energy'] = intval(($device_data['drivingMileage'] / 180) * 100);
//                    } else {
//                        $data['energy'] = intval($device_data['energy']);
//                    }
//                    $data['driving_mileage'] = $device_data['drivingMileage'];
                    $device_data = json_decode($out_data_device, true);
                    $data['driving_mileage'] = $device_data['drivingMileage'];
                    $data['energy'] = $device_data['energy'];
                    if (empty($data['energy'])) {
                        $data['energy'] = intval(($data['driving_mileage'] / 160) * 100);
                    } else if (empty($data['driving_mileage'])) {
                        $data['driving_mileage'] = intval(floatval($data['energy']) / 100 * 100);
                    }
                    $data['return_mileage'] = $device_data['odometer'];
                    $all_mileage = intval($device_data['odometer']) - intval($data['acquire_mileage']);
                    $order_amount = $order_amount + (floatval($all_mileage) * floatval($goods_km_amount));
                    $this->where(array('id' => $data['id'], 'order_status' => OrderStatus::$OrderStatusAcquire['code'], 'goods_type' => $data['goods_type']))->setField(['order_amount' => $order_amount, 'return_mileage' => $device_data['odometer'], 'extra_cost' => $extra_cost, 'return_time' => $return_time]);
                }
            }
            $data['all_mileage'] = (intval($data['return_mileage']) - intval($data['acquire_mileage']));
        }
        if (empty($data['payment_time'])) {
            $data['payment_time_str'] = "未付款";
        } else {
            $data['payment_time_str'] = date('Y-m-d H:i:s', $data['payment_time']);
        }
        if (intval($data['order_status']) == OrderStatus::$OrderStatusReturn['code']) {
            $this->where(array('id' => $data['id'], 'order_status' => OrderStatus::$OrderStatusReturn['code'], 'goods_type' => $data['goods_type']))->setField(['order_amount' => $order_amount, 'extra_cost' => $extra_cost]);
        }
        $pay_code = PayCode::$PAY_CODE;
        $data['payment_code_str'] = $pay_code[$data['payment_code']];
        if (!empty($data['rests_cost_time'])) {
            $data['rests_cost_time'] = date('Y-m-d H:i:s', $data['rests_cost_time']);
        } else {
            $data['rests_cost_time'] = "无数据";
        }
//        if (!empty($data['customer_information'])) {
//            $data['customer_information'] = unserialize($data['customer_information']);
//        }
//        if (!empty($data['order_goods'])) {
//            $data['order_goods'] = unserialize($data['order_goods']);
//        }
        if (!empty($data['goods_amount_detail'])) {
            $data['goods_amount_detail'] = unserialize($data['goods_amount_detail']);
        }
        if (!empty($data['acquire_time'])) {
            $data['acquire_time_str'] = date("m-d H:i", $data['acquire_time']);
            $data['acquire_time_d_str'] = date("Y-m-d H:i:s", $data['acquire_time']);
        } else {
            $data['acquire_time_d_str'] = "无数据";
        }
//        if (!empty($data['acquire_picture'])) {
//            $data['acquire_picture'] = unserialize($data['acquire_picture']);
//        }
        if (!empty($data['reality_return_time'])) {
            $data['reality_return_time_str'] = date("m-d H:i", $data['reality_return_time']);
        } else {
            $data['reality_return_time_str'] = "无数据";
        }
//        if (!empty($data['return_picture'])) {
//            $data['return_picture'] = unserialize($data['return_picture']);
//        }
        $data['acquire_time_str'] = date("m-d H:i", $data['acquire_time']);
        $tel = Config::get('store_tel');
        $data['acquire_store_tel'] = $tel;
        $data['return_time_str'] = date("m-d H:i", $data['return_time']);
        $data['return_time_d_str'] = date("Y-m-d H:i:s", $data['return_time']);
        $data['return_store_tel'] = $tel;
        $data['user_time'] = intval((intval($data['reality_return_time']) - intval($data['acquire_time'])) / 3600);
        if (empty($data['coupon_amount'])) {
            $data['coupon_type'] = 0;
        } else {
            $data['coupon_type'] = $data['coupon_amount'];
        }
        $data['coupon_class'] = "满减劵";

    }

    /**
     * 支付订单
     * @param $pay_info 支付订单信息
     *  pay_sn 付款单号
     *  money 付款金额
     *  remark 备注信息
     * @param $payment_code 支付方式
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function payOrder($pay_info, $payment_code)
    {
        $times = time();
        $map = array();
        $map['pay_sn'] = $pay_info['pay_sn'];
//        $map['order_status'] = OrderStatus::$OrderStatusNopayment['code'];
        $ordert = $this->where($map)->find();
        if (!empty($ordert)) {
            $this->formatxEle($ordert);
            $order = $this->where($map)->find();
            $store_key_id = $order['store_key_id'];
            if (intval($order['goods_type']) == GoodsType::$GoodsTypeCar['code']) {
                if (intval($order['order_status']) == OrderStatus::$OrderStatusNopayment['code']) {
                    //正常支付
                    $order->order_status = OrderStatus::$OrderStatusPayment['code'];
                    $coupon_sn = $order['coupon_sn'];
                    $coupon_type = $order['coupon_amount'];
                    $order->payment_code = $payment_code;
                    $order->payment_time = $times;
                    $order->finnshed_time = $times;
                    if ($order->save() != false) {
                        if (!empty($coupon_sn)) {
                            $coupon_data_sn = [
                                'coupon_sn' => $coupon_sn,
                                'coupon_type' => $coupon_type
                            ];
                            $customer_coupon_model = new CustomerCoupon();
                            $customer_coupon_model->use_coupon($coupon_data_sn);
                        }
                        $paylog = array(
                            'pay_sn' => $pay_info['pay_sn'],
                            'money' => $pay_info['money'],
                            'type' => $payment_code,
                            'remark' => $pay_info['remark'],
                            'add_time' => date("Y-m-d H:i:s"),
                            'store_key_id' => $store_key_id
                        );
                        $this->orderpaylog_model->save($paylog);
                        return true;
                    }
                }
            } else if (intval($order['goods_type']) == GoodsType::$GoodsTypeElectrocar['code']) {
                if (intval($order['order_status']) == OrderStatus::$OrderStatusReturn['code']) {
                    $order->order_status = OrderStatus::$OrderStatusFinish['code'];
                    $coupon_sn = $order['coupon_sn'];
                    $coupon_type = $order['coupon_amount'];
                    $order->payment_code = $payment_code;
                    $order->payment_code = $payment_code;
                    $order->pay_amount = $pay_info['money'];
                    $order->payment_time = $times;
                    $order->finnshed_time = $times;
                    if ($order->save()) {
                        if (!empty($coupon_sn)) {
                            $coupon_data_sn = [
                                'coupon_sn' => $coupon_sn,
                                'coupon_type' => $coupon_type
                            ];
                            $customer_coupon_model = new CustomerCoupon();
                            $customer_coupon_model->use_coupon($coupon_data_sn);
                        }
                        $paylog = array(
                            'pay_sn' => $pay_info['pay_sn'],
                            'money' => $pay_info['money'],
                            'type' => $payment_code,
                            'remark' => $pay_info['remark'],
                            'add_time' => date("Y-m-d H:i:s"),
                            'store_key_id' => $store_key_id
                        );
                        $this->orderpaylog_model->save($paylog);
                        return true;
                    }
                }
            }
        } else {
            $map['lease_pay_sn'] = $pay_info['pay_sn'];
            $order = $this->where($map)->find();
            if (intval($order['goods_type']) == GoodsType::$GoodsTypeCar['code']) {
                if (intval($order['is_lease']) == 1 && (intval($order['order_status']) >= OrderStatus::$OrderStatusPayment['code'] || intval($order['order_status']) <= OrderStatus::$OrderStatusAcquire['code'])) {
                    return $this->endLeaseData($order);
                }
            }
        }
        return false;
    }

    /**
     * 获取订单支付信息 使用余额支付的 将自动扣除余额
     * @param $ordersn 订单单号数组 ['单号','单号']
     * @param bool $balance true使用余额付款 自动扣款
     * @param string $customer_id
     * @param string $coupon_sn
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getPayInfo($ordersn, $balance = false, $customer_id = '', $coupon_sn = '')
    {
        if (empty($customer_id)) {
            $out_data = array(
                'code' => 1130,
                'info' => "客户信息有误",
                'out_trade_no' => '',
                'body' => '',
                'total_price' => ''
            );
            return $out_data;
        }
        $balance_type = BalanceType::$BalanceTypePAY['code'];
        $body = PayCode::$PayCodeBody;//支付说明
        $pay_sn = $this->_create_pay_sn();
        $total_price = 0;
        $coupon_type = 0;
        $order_info_temp = [];
        $store_key_id = 153;
        if (!empty($ordersn) && !empty($ordersn[0])) {
            $order_info_temp = $this->where(array('customer_id' => $customer_id, 'order_sn' => $ordersn[0], 'order_status' => OrderStatus::$OrderStatusNopayment['code']))->find();
            $store_key_id = $order_info_temp['store_key_id'];
            if (!empty($order_info_temp)) {
                $this->formatx($order_info_temp);
                unset($order_info_temp['goods_amount_detail']);
//                unset($order_info_temp['order_goods']);
//                unset($order_info_temp['acquire_picture']);
//                unset($order_info_temp['return_picture']);
                unset($order_info_temp['goods_device']);
                $extra_cost = 0;
                $total_price += floatval($order_info_temp['order_amount']) - floatval($order_info_temp['pd_amount']);
                $order_info_updata = [];
                if (!empty($coupon_sn)) {
                    $customer_coupon_model = new CustomerCoupon();
                    $coupon_data = $customer_coupon_model->where(['coupon_sn' => $coupon_sn, 'coupon_type' => ['elt', $total_price], 'customer_id' => $customer_id, 'state' => 10])->find();
                    if (!empty($coupon_data)) {
                        $coupon_type = $coupon_data['coupon_type'];//代金券面值
                        $coupon_sn = $coupon_data['coupon_sn'];
                        $order_info_updata['coupon_sn'] = $coupon_sn;
                        $order_info_updata['coupon_amount'] = $coupon_type;
                        $order_info_temp['coupon_type'] = $coupon_type;
                        $order_info_temp['coupon_class'] = "满点劵";
                    }
                } else if (!empty($order_info_temp['coupon_sn'])) {
                    $customer_coupon_model = new CustomerCoupon();
                    $coupon_data = $customer_coupon_model->where(['coupon_sn' => $order_info_temp['coupon_sn'], 'coupon_type' => ['elt', $total_price], 'customer_id' => $customer_id, 'state' => 10])->find();
                    if (!empty($coupon_data)) {
                        $coupon_type = $coupon_data['coupon_type'];//代金券面值
                        $coupon_sn = $coupon_data['coupon_sn'];
                        $order_info_updata['coupon_sn'] = $coupon_sn;
                        $order_info_updata['coupon_amount'] = $coupon_type;
                        $order_info_temp['coupon_type'] = $coupon_type;
                        $order_info_temp['coupon_class'] = "满点劵";
                    } else {
                        $customer_coupon_model = new CustomerCoupon();
                        $coupon_data = $customer_coupon_model->where(['coupon_type' => ['elt', $total_price], 'customer_id' => $customer_id, 'state' => 10])->order('coupon_type DESC')->find();
                        if (!empty($coupon_data)) {
                            $coupon_type = $coupon_data['coupon_type'];//代金券面值
                            $coupon_sn = $coupon_data['coupon_sn'];
                            $order_info_updata['coupon_sn'] = $coupon_sn;
                            $order_info_updata['coupon_amount'] = $coupon_type;
                            $order_info_temp['coupon_type'] = $coupon_type;
                            $order_info_temp['coupon_class'] = "满点劵";
                        } else {
                            $order_info_updata['coupon_sn'] = "";
                            $order_info_updata['coupon_amount'] = 0;
                            $order_info_temp['coupon_type'] = 0;
                            $order_info_temp['coupon_class'] = "满点劵";
                        }
                    }
                } else {
                    $customer_coupon_model = new CustomerCoupon();
                    $coupon_data = $customer_coupon_model->where(['coupon_type' => ['elt', $total_price], 'customer_id' => $customer_id, 'state' => 10])->order('coupon_type DESC')->find();
                    if (!empty($coupon_data)) {
                        $coupon_type = $coupon_data['coupon_type'];//代金券面值
                        $coupon_sn = $coupon_data['coupon_sn'];
                        $order_info_updata['coupon_sn'] = $coupon_sn;
                        $order_info_updata['coupon_amount'] = $coupon_type;
                        $order_info_temp['coupon_type'] = $coupon_type;
                        $order_info_temp['coupon_class'] = "满点劵";
                    }
                }
                $total_price += floatval($order_info_temp['extra_cost']);
                $total_price -= intval($coupon_type);
                $order_info_updata['pay_sn'] = $pay_sn;
//                if (empty($order_info_temp['pay_sn'])) {
//
//                } else {
//                    $pay_sn = $order_info_temp['pay_sn'];
//                }
                $order_info_updata['extra_cost'] = $extra_cost;
                $this->save($order_info_updata, ['id' => $order_info_temp['id']]);
            }
        }
        if (floatval($total_price) <= 0) {
            $out_data = array(
                'code' => 1131,
                'info' => "订单已使用余额全额支付",
                'out_trade_no' => $pay_sn,
                'body' => $body,
                'order' => $order_info_temp,
                'total_price' => ''
            );
            $pay_info = array(
                'pay_sn' => $pay_sn,
                'money' => 0,
                'remark' => "代金券完全支付 支付单号：" . $pay_sn,
            );
            $this->payOrder($pay_info, PayCode::$PayCodeBalance['code']);
//            if (!empty($coupon_sn)) {
//                $coupon_data_sn = [
//                    'coupon_sn' => $coupon_sn,
//                    'coupon_type' => $coupon_type
//                ];
//                $customer_coupon_model = new CustomerCoupon();
//                $customer_coupon_model->use_coupon($coupon_data_sn);
//            }
            return $out_data;
        }
        if (floatval($balance) > 0) {
            $customer = $this->customer_model->getCustomer('', $customer_id, true);
            $customer_balance = floatval($customer['data']['customer_balance']);
            if ($customer_balance > 0) {
                if ($total_price <= $customer_balance) {
                    //如果 余额足够扣除
                    $money = array(
                        'customer_id' => $customer_id,// 单位 元
                        'balance' => $total_price,// 订单合计 单位 元
                        'pay_sn' => $pay_sn,// 支付单号
                        'type' => $balance_type,// 操作类型
                        'remark' => '支付单号:' . $pay_sn . " 扣除全部",// 备注
                    );
                    $out_balance = $this->customer_model->updateCustomerBalance($money);
                    if ($out_balance['code'] == 0) {
//                        $set_data = [
//                            "order_status" => OrderStatus::$OrderStatusPayment['code'],//已完成支付
//                            "payment_code" => PayCode::$PayCodeBalance['code'],//余额支付
//                            "pd_amount" => $total_price,//已付款
//                            "payment_time" => time(),//已付款
//                            "finnshed_time" => time(),//完成时间
//                        ];
//                        $this->where(array('order_sn' => $ordersn[0]))->setField($set_data);
                        $pay_info = array(
                            'pay_sn' => $pay_sn,
                            'money' => $total_price,
                            'remark' => "余额全部支付 支付单号：" . $pay_sn,
                        );
                        $this->payOrder($pay_info, PayCode::$PayCodeBalance['code']);
                        $paylog = array(
                            'pay_sn' => $pay_sn,
                            'money' => $total_price,
                            'type' => PayCode::$PayCodeBalance['code'],
                            'remark' => '余额全部支付：' . $total_price,
                            'add_time' => date("Y-m-d H:i:s"),
                            'store_key_id' => $store_key_id
                        );
//                        $this->_sms_order($pay_sn);
                        //条件支付日志
                        $this->orderpaylog_model->save($paylog);
//                        if (!empty($coupon_sn)) {
//                            $coupon_data_sn = [
//                                'coupon_sn' => $coupon_sn,
//                                'coupon_type' => $coupon_type
//                            ];
//                            $customer_coupon_model = new CustomerCoupon();
//                            $customer_coupon_model->use_coupon($coupon_data_sn);
//                        }
                        $out_data = array(
                            'code' => 1131,
                            'info' => "订单已使用余额全额支付",
                            'out_trade_no' => $pay_sn,
                            'body' => $body,
                            'order' => $order_info_temp,
                            'total_price' => ''
                        );
                        return $out_data;
                    }
                } else if ($total_price > $customer_balance) {
                    $total_price = $total_price - $customer_balance;
                    //如果余额不够扣除
                    $money = array(
                        'customer_id' => $customer_id,// 单位 元
                        'balance' => $customer_balance,// 客户全部余额 单位 元
                        'pay_sn' => $pay_sn,// 支付单号
                        'type' => $balance_type,// 操作类型
                        'remark' => '支付单号:' . $pay_sn . " 扣除部分",// 备注
                    );
                    $out_balance = $this->customer_model->updateCustomerBalance($money);
                    if ($out_balance['code'] == 0) {
                        $set_data = [
                            "pd_amount" => $customer_balance//已付款
                        ];
                        $this->where(array('order_sn' => $ordersn[0]))->setField($set_data);
                        //添加支付日志
                        $paylog = array(
                            'pay_sn' => $pay_sn,
                            'money' => $customer_balance,
                            'type' => PayCode::$PayCodeBalance['code'],
                            'remark' => '余额部分支付：' . $pay_sn,
                            'add_time' => date("Y-m-d H:i:s"),
                            'store_key_id' => $store_key_id
                        );
                        $this->orderpaylog_model->save($paylog);
                        $out_data = array(
                            'code' => 0,
                            'info' => "订单使用余额部分支付",
                            'out_trade_no' => $pay_sn,
                            'body' => $body,
                            'order' => $order_info_temp,
                            'total_price' => $total_price
                        );
                        return $out_data;
                    }
                }
            }
        }
        $pay_info = array(
            'code' => 0,
            'info' => "获取成功",
            'out_trade_no' => $pay_sn,
            'body' => $body,
            'order' => $order_info_temp,
            'total_price' => $total_price
        );
        return $pay_info;
    }

    /**
     * 获取订单续租支付信息 使用余额支付的 将自动扣除余额
     * @param $order_sn 订单单号
     * @param bool $balance true使用余额付款 自动扣款
     * @param string $customer_id
     * @param string $coupon_sn
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getPayLeaseInfo($order_sn, $balance = false, $customer_id = '', $coupon_sn = '')
    {
        if (empty($customer_id)) {
            $out_data = array(
                'code' => 1130,
                'info' => "客户信息有误",
                'out_trade_no' => '',
                'body' => '',
                'total_price' => ''
            );
            return $out_data;
        }
        $balance_type = BalanceType::$BalanceTypePAY['code'];
        $body = PayCode::$PayCodeBody;//支付说明
        $pay_sn = $this->_create_pay_sn();
        $total_price = 0;
//        $coupon_type = 0;
        $order_info_temp = [];
        $store_key_id = 153;
        if (!empty($order_sn)) {
            $order_info_temp = $this->where(array('customer_id' => $customer_id, 'order_sn' => $order_sn, 'order_status' => ['between', OrderStatus::$OrderStatusPayment['code'] . "," . OrderStatus::$OrderStatusAcquire['code']]))->find();
            $store_key_id = $order_info_temp['store_key_id'];
            if (!empty($order_info_temp)) {
                $this->formatx($order_info_temp);
                if (intval($order_info_temp['is_lease']) == 1) {
                    $order_info_temp['return_time_str'] = date("m-d H:i", $order_info_temp['lease_data']['return_time']);
                    $order_info_temp['interval_time'] = $order_info_temp['lease_data']['return_time'] - $order_info_temp['acquire_time'];
                    $order_info_temp['interval_time_str'] = diy_time_tostr($order_info_temp['interval_time']);
                }
                unset($order_info_temp['goods_amount_detail']);
//                unset($order_info_temp['order_goods']);
//                unset($order_info_temp['acquire_picture']);
//                unset($order_info_temp['return_picture']);
                unset($order_info_temp['goods_device']);
                $total_price += floatval($order_info_temp['lease_amount']) - floatval($order_info_temp['lease_pd_amount']);
                $order_info_updata = [];
//                if (!empty($coupon_sn)) {
//                    $customer_coupon_model = new CustomerCoupon();
//                    $coupon_data = $customer_coupon_model->where(['coupon_sn' => $coupon_sn, 'coupon_type' => ['elt', $total_price], 'customer_id' => $customer_id, 'state' => 10])->find();
//                    if (!empty($coupon_data)) {
//                        $coupon_type = $coupon_data['coupon_type'];//代金券面值
//                        $coupon_sn = $coupon_data['coupon_sn'];
//                        $order_info_updata['coupon_sn'] = $coupon_sn;
//                        $order_info_updata['coupon_amount'] = $coupon_type;
//                        $order_info_temp['coupon_type'] = $coupon_type;
//                        $order_info_temp['coupon_class'] = "满点劵";
//                    }
//                } else if (!empty($order_info_temp['coupon_sn'])) {
//                    $customer_coupon_model = new CustomerCoupon();
//                    $coupon_data = $customer_coupon_model->where(['coupon_sn' => $order_info_temp['coupon_sn'], 'coupon_type' => ['elt', $total_price], 'customer_id' => $customer_id, 'state' => 10])->find();
//                    if (!empty($coupon_data)) {
//                        $coupon_type = $coupon_data['coupon_type'];//代金券面值
//                        $coupon_sn = $coupon_data['coupon_sn'];
//                        $order_info_updata['coupon_sn'] = $coupon_sn;
//                        $order_info_updata['coupon_amount'] = $coupon_type;
//                        $order_info_temp['coupon_type'] = $coupon_type;
//                        $order_info_temp['coupon_class'] = "满点劵";
//                    } else {
//                        $customer_coupon_model = new CustomerCoupon();
//                        $coupon_data = $customer_coupon_model->where(['coupon_type' => ['elt', $total_price], 'customer_id' => $customer_id, 'state' => 10])->order('coupon_type DESC')->find();
//                        if (!empty($coupon_data)) {
//                            $coupon_type = $coupon_data['coupon_type'];//代金券面值
//                            $coupon_sn = $coupon_data['coupon_sn'];
//                            $order_info_updata['coupon_sn'] = $coupon_sn;
//                            $order_info_updata['coupon_amount'] = $coupon_type;
//                            $order_info_temp['coupon_type'] = $coupon_type;
//                            $order_info_temp['coupon_class'] = "满点劵";
//                        } else {
//                            $order_info_updata['coupon_sn'] = "";
//                            $order_info_updata['coupon_amount'] = 0;
//                            $order_info_temp['coupon_type'] = 0;
//                            $order_info_temp['coupon_class'] = "满点劵";
//                        }
//                    }
//                } else {
//                    $customer_coupon_model = new CustomerCoupon();
//                    $coupon_data = $customer_coupon_model->where(['coupon_type' => ['elt', $total_price], 'customer_id' => $customer_id, 'state' => 10])->order('coupon_type DESC')->find();
//                    if (!empty($coupon_data)) {
//                        $coupon_type = $coupon_data['coupon_type'];//代金券面值
//                        $coupon_sn = $coupon_data['coupon_sn'];
//                        $order_info_updata['coupon_sn'] = $coupon_sn;
//                        $order_info_updata['coupon_amount'] = $coupon_type;
//                        $order_info_temp['coupon_type'] = $coupon_type;
//                        $order_info_temp['coupon_class'] = "满点劵";
//                    }
//                }
                $total_price += floatval($order_info_temp['extra_cost']);
//                $total_price -= intval($coupon_type);
                $order_info_updata['lease_pay_sn'] = $pay_sn;
//                if (empty($order_info_temp['pay_sn'])) {
//
//                } else {
//                    $pay_sn = $order_info_temp['pay_sn'];
//                }
                $this->save($order_info_updata, ['id' => $order_info_temp['id']]);
            }
        }
        if (floatval($total_price) <= 0) {
            if (!$this->endLeaseData($order_info_temp)) {
                $out_data = array(
                    'code' => 1134,
                    'info' => "续租扣除失败",
                    'out_trade_no' => '',
                    'body' => '',
                    'total_price' => ''
                );
                return $out_data;
            }
            $out_data = array(
                'code' => 1131,
                'info' => "订单已使用余额全额支付",
                'out_trade_no' => $pay_sn,
                'body' => $body,
                'order' => $order_info_temp,
                'total_price' => ''
            );
//            if (!empty($coupon_sn)) {
//                $coupon_data_sn = [
//                    'coupon_sn' => $coupon_sn,
//                    'coupon_type' => $coupon_type
//                ];
//                $customer_coupon_model = new CustomerCoupon();
//                $customer_coupon_model->use_coupon($coupon_data_sn);
//            }
            return $out_data;
        }
        if ($balance) {
            $customer = $this->customer_model->getCustomer('', $customer_id, true);
            $customer_balance = floatval($customer['data']['customer_balance']);
            if ($customer_balance > 0) {
                if ($total_price <= $customer_balance) {
                    //如果 余额足够扣除
                    $money = array(
                        'customer_id' => $customer_id,// 单位 元
                        'balance' => $total_price,// 订单合计 单位 元
                        'pay_sn' => $pay_sn,// 支付单号
                        'type' => $balance_type,// 操作类型
                        'remark' => '支付单号:' . $pay_sn . " 扣除全部",// 备注
                    );
                    $out_balance = $this->customer_model->updateCustomerBalance($money);
                    if ($out_balance['code'] == 0) {
                        $set_data = [
                            "lease_pd_amount" => $total_price,//已付款
                        ];
                        $this->where(array('order_sn' => $order_sn))->setField($set_data);
                        //完成续租支付
                        if (!$this->endLeaseData($order_info_temp)) {
                            $out_data = array(
                                'code' => 1134,
                                'info' => "续租扣除失败",
                                'out_trade_no' => '',
                                'body' => '',
                                'total_price' => ''
                            );
                            return $out_data;
                        }
                        $paylog = array(
                            'pay_sn' => $pay_sn,
                            'money' => $total_price,
                            'type' => PayCode::$PayCodeBalance['code'],
                            'remark' => '余额全部支付：' . $total_price,
                            'add_time' => date("Y-m-d H:i:s"),
                            'store_key_id' => $store_key_id
                        );
//                        $this->_sms_order($pay_sn);
                        //条件支付日志
                        $this->orderpaylog_model->save($paylog);
//                        if (!empty($coupon_sn)) {
//                            $coupon_data_sn = [
//                                'coupon_sn' => $coupon_sn,
//                                'coupon_type' => $coupon_type
//                            ];
//                            $customer_coupon_model = new CustomerCoupon();
//                            $customer_coupon_model->use_coupon($coupon_data_sn);
//                        }
                        $out_data = array(
                            'code' => 1131,
                            'info' => "订单已使用余额全额支付",
                            'out_trade_no' => $pay_sn,
                            'body' => $body,
                            'order' => $order_info_temp,
                            'total_price' => ''
                        );
                        return $out_data;
                    }
                } else if ($total_price > $customer_balance) {
                    $total_price = $total_price - $customer_balance;
                    //如果余额不够扣除
                    $money = array(
                        'customer_id' => $customer_id,// 单位 元
                        'balance' => $customer_balance,// 客户全部余额 单位 元
                        'pay_sn' => $pay_sn,// 支付单号
                        'type' => $balance_type,// 操作类型
                        'remark' => '支付单号:' . $pay_sn . " 扣除部分",// 备注
                    );
                    $out_balance = $this->customer_model->updateCustomerBalance($money);
                    if ($out_balance['code'] == 0) {
                        $order_info = $this->where(array('order_sn' => $order_sn))->find();
                        if ($customer_balance > 0) {
                            //账户余额 大于0
                            if (($customer_balance > $order_info['order_amount'])) {
                                //账户余额 大于第一单 扣除整单金额 改单表示交易完成
                                $set_data = [
                                    "lease_pd_amount" => $customer_balance,//已付款
                                ];
                                $this->where(array('order_sn' => $order_sn))->setField($set_data);
                            } else {
                                $set_data = [
                                    "lease_pd_amount" => $customer_balance//已付款
                                ];
                                $this->where(array('order_sn' => $order_sn))->setField($set_data);
                            }
                        }
                        //添加支付日志
                        $paylog = array(
                            'pay_sn' => $pay_sn,
                            'money' => $customer_balance,
                            'type' => PayCode::$PayCodeBalance['code'],
                            'remark' => '余额部分支付：' . $pay_sn,
                            'add_time' => date("Y-m-d H:i:s"),
                            'store_key_id' => $store_key_id
                        );
                        $this->orderpaylog_model->save($paylog);
                        $out_data = array(
                            'code' => 0,
                            'info' => "订单使用余额部分支付",
                            'out_trade_no' => $pay_sn,
                            'body' => $body,
                            'order' => $order_info_temp,
                            'total_price' => $total_price
                        );
                        return $out_data;
                    }
                }
            }
        }
        $pay_info = array(
            'code' => 0,
            'info' => "获取成功",
            'out_trade_no' => $pay_sn,
            'body' => $body,
            'order' => $order_info_temp,
            'total_price' => $total_price
        );
        return $pay_info;
    }

    /**提交续租信息
     * @param $lease_data
     * @return array
     */
    public function addLeaseData($lease_data)
    {
        $out_data = array(
            'code' => 1211,
            'info' => '参数有误',
        );
        if (empty($lease_data['lease_amount']) || empty($lease_data['lease_amount_detail']) || empty($lease_data['lease_data'])) {
            return $out_data;
        }
        $up_order_data = [
            'lease_amount' => $lease_data['lease_amount'],
            'lease_pd_amount' => $lease_data['lease_pd_amount'],
            'lease_amount_detail' => serialize($lease_data['lease_amount_detail']),
            'lease_data' => serialize($lease_data['lease_data']),
            'is_lease' => 1,
            'lease_time' => date("Y-m-d H:i:s")
        ];
        $ret = $this->where(['id' => $lease_data['order_id']])->setField($up_order_data);
        if ($ret !== false) {
            $out_data['code'] = 0;
            $out_data['info'] = "提交成功";
            return $out_data;
        }
        $out_data['code'] = 1213;
        $out_data['info'] = "提交失败";
        return $out_data;
    }

    /**
     * 续租支付完成
     * @param $order
     * @return bool
     */
    public function endLeaseData($order)
    {
        $order_id = $order['id'];
        $lease_data = $order['lease_data'];
        $lease_amount = $order['lease_amount'];
        $lease_pd_amount = $order['lease_pd_amount'];
        $order_amount = floatval($order['order_amount']) + floatval($lease_amount);
        $pd_amount = floatval($order['pd_amount']) + floatval($lease_pd_amount);
        if (!empty($lease_data)) {
            $lease_data = unserialize($lease_data);
            if (!empty($lease_data)) {
                $up_lease = [
                    'order_amount' => $order_amount,
                    'pd_amount' => $pd_amount,
                    'return_time' => $lease_data['return_time'],
                    'return_address' => $lease_data['return_address'],
                    'lease_time' => date("Y-m-d H:i:s"),
                    'is_lease' => 2
                ];
                $ret = $this->where(['id' => $order_id, 'is_lease' => 1])->setField($up_lease);
                if ($ret !== false) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * 获取（新能源车）订单支付信息 使用余额支付的 将自动扣除余额
     * @param $ordersn 订单单号数组 ['单号','单号']
     * @param bool $balance true使用余额付款 自动扣款
     * @param string $customer_id
     * @param string $coupon_sn
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getElePayInfo($ordersn, $balance = false, $customer_id = '', $coupon_sn = '')
    {
        if (empty($customer_id)) {
            $out_data = array(
                'code' => 1130,
                'info' => "客户信息有误",
                'out_trade_no' => '',
                'body' => '',
                'total_price' => ''
            );
            return $out_data;
        }
        $balance_type = BalanceType::$BalanceTypePAY['code'];
        $body = PayCode::$PayCodeBody;//支付说明
        $pay_sn = $this->_create_pay_sn();
        $total_price = 0;
        $coupon_type = 0;
        $store_key_id = 153;
        if (!empty($ordersn) && !empty($ordersn[0])) {
            $order_info_temp = $this->where(array('customer_id' => $customer_id, 'order_sn' => $ordersn[0], 'order_status' => OrderStatus::$OrderStatusReturn['code']))->find();
            $store_key_id = $order_info_temp['store_key_id'];
            if (!empty($order_info_temp)) {
                $this->formatxEle($order_info_temp);
                unset($order_info_temp['goods_amount_detail']);
//                unset($order_info_temp['order_goods']);
//                unset($order_info_temp['acquire_picture']);
//                unset($order_info_temp['return_picture']);
                unset($order_info_temp['goods_device']);
                $extra_cost = 0;
//                if ($order_info['acquire_store_id'] != $order_info['return_store_id']) {
//                    $extra_cost = Config::get('extra_cost');
//                }
                $this->_is_first_order($order_info_temp);
                $total_price += floatval($order_info_temp['order_amount']) - floatval($order_info_temp['pd_amount']) - floatval($order_info_temp['first_sub_money']);
                $order_info_updata = [];
                if (!empty($coupon_sn)) {
                    $customer_coupon_model = new CustomerCoupon();
                    $coupon_data = $customer_coupon_model->where(['coupon_sn' => $coupon_sn, 'coupon_type' => ['elt', $total_price], 'customer_id' => $customer_id, 'state' => 10])->find();
                    if (!empty($coupon_data)) {
                        $coupon_type = $coupon_data['coupon_type'];//代金券面值
                        $coupon_sn = $coupon_data['coupon_sn'];
                        $order_info_updata['coupon_sn'] = $coupon_sn;
                        $order_info_updata['coupon_amount'] = $coupon_type;
                        $order_info_temp['coupon_type'] = $coupon_type;
                        $order_info_temp['coupon_class'] = "满点劵";
                    }
                } else if (!empty($order_info_temp['coupon_sn'])) {
                    $customer_coupon_model = new CustomerCoupon();
                    $coupon_data = $customer_coupon_model->where(['coupon_sn' => $order_info_temp['coupon_sn'], 'coupon_type' => ['elt', $total_price], 'customer_id' => $customer_id, 'state' => 10])->find();
                    if (!empty($coupon_data)) {
                        $coupon_type = $coupon_data['coupon_type'];//代金券面值
                        $coupon_sn = $coupon_data['coupon_sn'];
                        $order_info_updata['coupon_sn'] = $coupon_sn;
                        $order_info_updata['coupon_amount'] = $coupon_type;
                        $order_info_temp['coupon_type'] = $coupon_type;
                        $order_info_temp['coupon_class'] = "满点劵";
                    } else {
                        $customer_coupon_model = new CustomerCoupon();
                        $coupon_data = $customer_coupon_model->where(['coupon_type' => ['elt', $total_price], 'customer_id' => $customer_id, 'state' => 10])->order('coupon_type DESC')->find();
                        if (!empty($coupon_data)) {
                            $coupon_type = $coupon_data['coupon_type'];//代金券面值
                            $coupon_sn = $coupon_data['coupon_sn'];
                            $order_info_updata['coupon_sn'] = $coupon_sn;
                            $order_info_updata['coupon_amount'] = $coupon_type;
                            $order_info_temp['coupon_type'] = $coupon_type;
                            $order_info_temp['coupon_class'] = "满点劵";
                        } else {
                            $order_info_updata['coupon_sn'] = "";
                            $order_info_updata['coupon_amount'] = 0;
                            $order_info_temp['coupon_type'] = 0;
                            $order_info_temp['coupon_class'] = "满点劵";
                        }
                    }
                } else {
                    $customer_coupon_model = new CustomerCoupon();
                    $coupon_data = $customer_coupon_model->where(['coupon_type' => ['elt', $total_price], 'customer_id' => $customer_id, 'state' => 10])->order('coupon_type DESC')->find();
                    if (!empty($coupon_data)) {
                        $coupon_type = $coupon_data['coupon_type'];//代金券面值
                        $coupon_sn = $coupon_data['coupon_sn'];
                        $order_info_updata['coupon_sn'] = $coupon_sn;
                        $order_info_updata['coupon_amount'] = $coupon_type;
                        $order_info_temp['coupon_type'] = $coupon_type;
                        $order_info_temp['coupon_class'] = "满点劵";
                    }
                }
                $total_price += floatval($order_info_temp['extra_cost']);
                $total_price -= intval($coupon_type);
                $order_info_updata['pay_sn'] = $pay_sn;
//                if (empty($order_info_temp['pay_sn'])) {
//
//                } else {
//                    $pay_sn = $order_info_temp['pay_sn'];
//                }
                if (floatval($total_price) == 0) {
                    $order_info_updata = [
                        "order_status" => OrderStatus::$OrderStatusFinish['code'],//已完成
                        "payment_code" => PayCode::$PayCodeBalance['code'],//余额支付
                        "pd_amount" => $total_price,//已付款
                        "payment_time" => time(),//已付款
                        "finnshed_time" => time(),//完成时间
                    ];
                }
                $order_info_updata['extra_cost'] = $extra_cost;
                $this->save($order_info_updata, ['id' => $order_info_temp['id']]);
            }
        } else {
            $out_data = array(
                'code' => 1130,
                'info' => "订单信息有误",
                'out_trade_no' => '',
                'body' => '',
                'total_price' => ''
            );
            return $out_data;
        }
        if (floatval($total_price) <= 0) {
            $out_data = array(
                'code' => 1131,
                'info' => "订单已使用余额全额支付",
                'out_trade_no' => $pay_sn,
                'body' => $body,
                'order' => $order_info_temp,
                'total_price' => ''
            );
            if (!empty($coupon_sn)) {
                $coupon_data_sn = [
                    'coupon_sn' => $coupon_sn,
                    'coupon_type' => $coupon_type
                ];
                $customer_coupon_model = new CustomerCoupon();
                $customer_coupon_model->use_coupon($coupon_data_sn);
            }
            return $out_data;
        }
        if (floatval($balance) > 0) {
            $customer = $this->customer_model->getCustomer('', $customer_id, true);
            $customer_balance = floatval($customer['data']['customer_balance']);
            if ($customer_balance > 0) {
                if ($total_price <= $customer_balance) {
                    //如果 余额足够扣除
                    $money = array(
                        'customer_id' => $customer_id,// 单位 元
                        'balance' => $total_price,// 订单合计 单位 元
                        'pay_sn' => $pay_sn,// 支付单号
                        'type' => $balance_type,// 操作类型
                        'remark' => '支付单号:' . $pay_sn . " 扣除全部",// 备注
                    );
                    $out_balance = $this->customer_model->updateCustomerBalance($money);
                    if ($out_balance['code'] == 0) {
                        $set_data = [
                            "order_status" => OrderStatus::$OrderStatusFinish['code'],//已完成
                            "payment_code" => PayCode::$PayCodeBalance['code'],//余额支付
                            "pd_amount" => $total_price,//已付款
                            "payment_time" => time(),//已付款
                            "finnshed_time" => time(),//完成时间
                        ];
                        $this->where(array('order_sn' => $ordersn[0]))->setField($set_data);
                        $paylog = array(
                            'pay_sn' => $pay_sn,
                            'money' => $total_price,
                            'type' => PayCode::$PayCodeBalance['code'],
                            'remark' => '余额全部支付：' . $total_price,
                            'add_time' => date("Y-m-d H:i:s"),
                            'store_key_id' => $store_key_id
                        );
//                        $this->_sms_order($pay_sn);
                        //条件支付日志
                        $this->orderpaylog_model->save($paylog);
                        if (!empty($coupon_sn)) {
                            $coupon_data_sn = [
                                'coupon_sn' => $coupon_sn,
                                'coupon_type' => $coupon_type
                            ];
                            $customer_coupon_model = new CustomerCoupon();
                            $customer_coupon_model->use_coupon($coupon_data_sn);
                        }
                        $out_data = array(
                            'code' => 1131,
                            'info' => "订单已使用余额全额支付",
                            'out_trade_no' => $pay_sn,
                            'body' => $body,
                            'order' => $order_info_temp,
                            'total_price' => ''
                        );
                        return $out_data;
                    }
                } else if ($total_price > $customer_balance) {
                    $total_price = $total_price - $customer_balance;
                    //如果余额不够扣除
                    $money = array(
                        'customer_id' => $customer_id,// 单位 元
                        'balance' => $customer_balance,// 客户全部余额 单位 元
                        'pay_sn' => $pay_sn,// 支付单号
                        'type' => $balance_type,// 操作类型
                        'remark' => '支付单号:' . $pay_sn . " 扣除部分",// 备注
                    );
                    $out_balance = $this->customer_model->updateCustomerBalance($money);
                    if ($out_balance['code'] == 0) {
                        $order_info = $this->where(array('order_sn' => $ordersn[0]))->find();
                        if ($customer_balance > 0) {
                            //账户余额 大于0
                            if (($customer_balance >= $order_info['order_amount'])) {
                                //账户余额 大于第一单 扣除整单金额 改单表示交易完成
                                $set_data = [
                                    "order_status" => OrderStatus::$OrderStatusFinish['code'],//已完成
                                    "payment_code" => PayCode::$PayCodeBalance['code'],//余额支付
                                    "pd_amount" => $total_price,//已付款
                                    "payment_time" => time(),//已付款
                                    "finnshed_time" => time(),//完成时间
                                ];
                                $this->where(array('order_sn' => $ordersn[0]))->setField($set_data);
                            } else {
                                $set_data = [
                                    "pd_amount" => $customer_balance//已付款
                                ];
                                $this->where(array('order_sn' => $ordersn[0]))->setField($set_data);
                            }
                        }
                        //添加支付日志
                        $paylog = array(
                            'pay_sn' => $pay_sn,
                            'money' => $customer_balance,
                            'type' => PayCode::$PayCodeBalance['code'],
                            'remark' => '余额部分支付：' . $pay_sn,
                            'add_time' => date("Y-m-d H:i:s"),
                            'store_key_id' => $store_key_id
                        );
                        $this->orderpaylog_model->save($paylog);
                        $out_data = array(
                            'code' => 0,
                            'info' => "订单使用余额部分支付",
                            'out_trade_no' => $pay_sn,
                            'body' => $body,
                            'order' => $order_info_temp,
                            'total_price' => $total_price
                        );
                        return $out_data;
                    }
                }
            }
        }
        $pay_info = array(
            'code' => 0,
            'info' => "获取成功",
            'out_trade_no' => $pay_sn,
            'body' => $body,
            'order' => $order_info_temp,
            'total_price' => $total_price
        );
        return $pay_info;
    }

    /**
     * 获取首单减少金额
     * @param $order_data
     * @return float|int
     */
    private function _is_first_order(&$order_data)
    {
        $sub_money = 0;
        $is_first_count = $order_data['is_first_count'];
        $goods_amount = $order_data['goods_amount'];
        $goods_km_amount = $order_data['goods_km_amount'];
        //如果是首次用车
        if (intval($is_first_count) == 0) {
            $temp_time = $order_data['reality_return_time'] - $order_data['acquire_time'];
            //如果首次用车小于3小时
            if ($temp_time <= 3600 * 3) {
                $mileage = intval($order_data['return_mileage'] - $order_data['acquire_mileage']);
                if ($mileage <= 200) {
                    //如果3小时以内 200公里以内  只需要付款1元
                    $sub_money = floatval($order_data['order_amount']) - 1;
                } else {
                    $mileage = intval($mileage) - 200;
                    $sub_money = floatval($order_data['order_amount']) - intval($mileage) * floatval($goods_km_amount) - 1;
                }
            } else {
                $temp_time = $temp_time - 3600 * 3;
                $goods_sum = $temp_time / 3600;
                $sub_money_time = floatval($goods_amount) * floatval($goods_sum);
                $mileage = intval($order_data['return_mileage'] - $order_data['acquire_mileage']);
                $sub_money_mileage = 0;
                //如果首次用车大于3小时
                if ($mileage > 200) {
                    //如果3小时以内 200公里以内  只需要付款1元
                    $mileage = intval($mileage) - 200;
                    $sub_money_mileage = intval($mileage) * floatval($goods_km_amount);
                }
                $sub_money = floatval($order_data['order_amount']) - $sub_money_time - $sub_money_mileage - 1;
            }
        }
        $order_model = new Order();
        $order_model->save(['first_sub_money' => $sub_money], ['id' => $order_data['id']]);
        $order_data['first_sub_money'] = $sub_money;
        return $sub_money;
    }

    /**
     * 获取订单信息
     * @param string $order_id 订单id
     * @param string $customer_id 客户id
     * @param bool $is_super_admin 是否是超级管理员
     * @param string $store_key_id 订单归属商户
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOrder($order_id = '', $customer_id = '', $is_super_admin = false, $store_key_id = '')
    {
        if (empty($order_id)) {
            return false;
        }
        $map = array();
        $map['id'] = $order_id;
        //验证是否是客户操作
        if (!empty($customer_id)) {
            $map['customer_id'] = $customer_id;
        }
        //判断是不是 超级管理员 并且没有客户操作 如果不是 过滤数据
        if (!$is_super_admin && empty($customer_id)) {
            $map['store_key_id'] = $store_key_id;
        }
        $order = $this->where($map)->find();
        if (intval($order['goods_type']) == GoodsType::$GoodsTypeCar['code']) {
            $this->formatx($order);
            $order_expand_model = new OrderExpand();
            $order_data = $order_expand_model->getExpand($order_id);
            if (empty($order_data['code'])) {
                $order['customer_information'] = $order_data['data']['customer_information'];
                $order['order_goods'] = $order_data['data']['order_goods'];
                $order['acquire_picture'] = $order_data['data']['acquire_picture'];
                $order['return_picture'] = $order_data['data']['return_picture'];
            } else {
                $order['customer_information'] = [];
                $order['order_goods'] = [];
                $order['acquire_picture'] = [];
                $order['return_picture'] = [];
            }
        } else if (intval($order['goods_type']) == GoodsType::$GoodsTypeElectrocar['code']) {
            $this->formatxEle($order);
            $order_expand_model = new OrderExpand();
            $order_data = $order_expand_model->getExpand($order_id);
            if (empty($order_data['code'])) {
                $order['customer_information'] = $order_data['data']['customer_information'];
                $order['order_goods'] = $order_data['data']['order_goods'];
                $order['acquire_picture'] = $order_data['data']['acquire_picture'];
                $order['return_picture'] = $order_data['data']['return_picture'];
            } else {
                $order['customer_information'] = [];
                $order['order_goods'] = [];
                $order['acquire_picture'] = [];
                $order['return_picture'] = [];
            }
        }
        if (empty($order)) {
            $out_data['code'] = 1190;
            $out_data['info'] = "订单不存在";
            return $out_data;
        } else {
            $out_data['code'] = 0;
            $out_data['data'] = $order;
            $out_data['info'] = "获取成功";
            return $out_data;
        }
    }

    /**
     * 获取订单信息
     * @param string $order_sn 订单编号
     * @param string $customer_id 客户id
     * @param bool $is_super_admin 是否是超级管理员
     * @param string $store_key_id 订单归属商户
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOrderSn($order_sn = '', $customer_id = '', $is_super_admin = false, $store_key_id = '')
    {
        if (empty($order_sn)) {
            return false;
        }
        $map = array();
        $map['order_sn'] = $order_sn;
        //验证是否是客户操作
        if (!empty($customer_id)) {
            $map['customer_id'] = $customer_id;
        }
        //判断是不是 超级管理员 并且没有客户操作 如果不是 过滤数据
        if (!$is_super_admin && empty($customer_id)) {
            $map['store_key_id'] = $store_key_id;
        }
        $order = $this->where($map)->find();
        if (intval($order['goods_type']) == GoodsType::$GoodsTypeCar['code']) {
            $this->formatx($order);
            $order_expand_model = new OrderExpand();
            $order_data = $order_expand_model->getExpand($order['id']);
            if (empty($order_data['code'])) {
                $order['customer_information'] = $order_data['data']['customer_information'];
                $order['order_goods'] = $order_data['data']['order_goods'];
                $order['acquire_picture'] = $order_data['data']['acquire_picture'];
                $order['return_picture'] = $order_data['data']['return_picture'];
            } else {
                $order['customer_information'] = [];
                $order['order_goods'] = [];
                $order['acquire_picture'] = [];
                $order['return_picture'] = [];
            }
        } else if (intval($order['goods_type']) == GoodsType::$GoodsTypeElectrocar['code']) {
            $this->formatxEle($order);
            $order_expand_model = new OrderExpand();
            $order_data = $order_expand_model->getExpand($order['id']);
            if (empty($order_data['code'])) {
                $order['customer_information'] = $order_data['data']['customer_information'];
                $order['order_goods'] = $order_data['data']['order_goods'];
                $order['acquire_picture'] = $order_data['data']['acquire_picture'];
                $order['return_picture'] = $order_data['data']['return_picture'];
            } else {
                $order['customer_information'] = [];
                $order['order_goods'] = [];
                $order['acquire_picture'] = [];
                $order['return_picture'] = [];
            }
        }
        if (empty($order)) {
            $out_data['code'] = 1190;
            $out_data['info'] = "订单不存在";
            return $out_data;
        } else {
            $out_data['code'] = 0;
            $out_data['data'] = $order;
            $out_data['info'] = "获取成功";
            return $out_data;
        }
    }

    /**
     * 获取订单制定字段
     * @param string $order_sn
     * @param $customer_id
     * @param $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOrderSnField($order_sn = '', $customer_id, $field)
    {
        if (empty($order_sn)) {
            return false;
        }
        $map = array();
        $map['order_sn'] = $order_sn;
        //验证是否是客户操作
        if (!empty($customer_id)) {
            $map['customer_id'] = $customer_id;
        }
        if (empty($field)) {
            $order = $this->where($map)->find();
        } else {
            $order = $this->where($map)->field($field)->find();
        }
        if (empty($order)) {
            $out_data['code'] = 1190;
            $out_data['info'] = "订单不存在";
            return $out_data;
        } else {
            $out_data['code'] = 0;
            $out_data['data'] = $order;
            $out_data['info'] = "获取成功";
            return $out_data;
        }
    }

    /**
     * 获取订单制定字段
     * @param string $order_sn
     * @param $customer_id
     * @param $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOrderIdField($order_id = '', $customer_id, $field)
    {
        if (empty($order_id)) {
            return false;
        }
        $map = array();
        $map['id'] = $order_id;
        //验证是否是客户操作
        if (!empty($customer_id)) {
            $map['customer_id'] = $customer_id;
        }
        if (empty($field)) {
            $order = $this->where($map)->find();
        } else {
            $order = $this->where($map)->field($field)->find();
        }
        if (empty($order)) {
            $out_data['code'] = 1190;
            $out_data['info'] = "订单不存在";
            return $out_data;
        } else {
            $out_data['code'] = 0;
            $out_data['data'] = $order;
            $out_data['info'] = "获取成功";
            return $out_data;
        }
    }

    /**
     * 常规汽车添加订单
     * @param $goodsinfo 商品信息
     * store_id 店铺id 门店
     * store_name 店铺店铺名称
     * goods_amount 商品单价
     * order_amount 订单总价格
     * goods_id 商品id
     * goods_sum 商品数量
     * goods_type 商品类
     * order_goods 商品信息 ['goods_name'=>'商品名称','goods_price'=>'商品单价','goods_sum'=>'购买数量','goods_img'=>'商品图片']
     * acquire_time 取用时间 时间戳
     * acquire_address 取用地址 ['name'=>'地址名称','lng'=>'106.257','lat'=>'26.782']
     * acquire_store_id 取用门店id
     * acquire_store_name 取用门店
     * acquire_visit 取车方式 0到店取车 1送车上门
     * return_time 归还时间 时间戳
     * return_address 归还地址 ['name'=>'地址名称','lng'=>'106.257','lat'=>'26.782']
     * return_store_id 归还门店id
     * return_store_name 归还门店
     * return_visit 还车方式 0到店还车 1上门取车
     * extra_cost 额外费用
     * extra_cost_notes 额外费用说明
     * @param $customer_info 客户信息
     * customer_id 客户id
     * customer_drive_id 驾驶员信息id
     * mobile_phone 客户电话
     * customer_information 驾驶员证件信息 ['real_name'=>'客户真实姓名','id_number'=>'身份证号码','mobile_phone'=>'电话号码']
     * @param $channel_uid 渠道信息
     * @param bool $is_super_admin 是否是超级管理员
     * @param string $store_key_id 订单归属商户
     * @return array|string
     */
    public function addOrder($goodsinfo, $customer_info, $channel_uid, $is_super_admin = false, $store_key_id = '')
    {
        $out_data = array(
            'code' => 1111,
            'info' => '参数有误',
        );
//        $this->_update_order($customer_info['customer_id']);
//        $map_cont = array(
//            'customer_id' => $customer_info['customer_id'],
//            'goods_type' => GoodsType::$GoodsTypeCar['code'],
//            'order_status' => OrderStatus::$OrderStatusNopayment['code'],
//        );
//        $cont_on_end = $this->where($map_cont)->count();
//        if ($cont_on_end > 0) {
//            $out_data['code'] = 1112;
//            $out_data['info'] = '有未完成订单';
//            return $out_data;
//        }
        $order_sn = $this->_create_order_sn();
        if (!is_numeric($goodsinfo['store_id'])) {
            $out_data['code'] = 1100;
            $out_data['info'] = '门店铺id不合法';
            return $out_data;
        }
        if (empty($goodsinfo['store_name'])) {
            $out_data['code'] = 1101;
            $out_data['info'] = '店铺名称不合法';
            return $out_data;
        }
        if (!is_numeric($goodsinfo['goods_amount'])) {
            $out_data['code'] = 1102;
            $out_data['info'] = '商品单价不合法';
            return $out_data;
        }
        if (!is_numeric($goodsinfo['order_amount'])) {
            $out_data['code'] = 1103;
            $out_data['info'] = '订单总价不合法';
            return $out_data;
        }
        if (!is_numeric($goodsinfo['goods_id'])) {
            $out_data['code'] = 1104;
            $out_data['info'] = '商品id不合法';
            return $out_data;
        }
        if (empty($goodsinfo['goods_name'])) {
            $out_data['code'] = 1104;
            $out_data['info'] = '商品名称不合法';
            return $out_data;
        }
        if (!is_numeric($goodsinfo['goods_sum'])) {
            $out_data['code'] = 1105;
            $out_data['info'] = '商品数量不合法';
            return $out_data;
        }
        if (!is_numeric($goodsinfo['goods_type'])) {
            $out_data['code'] = 1106;
            $out_data['info'] = '商品类型不合法';
            return $out_data;
        }
        if (empty($goodsinfo['order_goods'])) {
            $out_data['code'] = 1107;
            $out_data['info'] = '商品信息不合法';
            return $out_data;
        }
        if (!is_numeric($goodsinfo['acquire_time'])) {
            $out_data['code'] = 1108;
            $out_data['info'] = '取用时间不合法';
            return $out_data;
        }
        if (empty($goodsinfo['acquire_address'])) {
            $out_data['code'] = 1109;
            $out_data['info'] = '取用地址不合法';
            return $out_data;
        }
        if (!is_numeric($goodsinfo['acquire_store_id'])) {
            $out_data['code'] = 1110;
            $out_data['info'] = '取用车门店id不合法';
            return $out_data;
        }
        if (empty($goodsinfo['acquire_store_name'])) {
            $out_data['code'] = 1110;
            $out_data['info'] = '取用车门店名称不合法';
            return $out_data;
        }
        if (!is_numeric($goodsinfo['acquire_visit'])) {
            $goodsinfo['acquire_visit'] = 0;
        }
        if (!is_numeric($goodsinfo['return_time'])) {
            $out_data['code'] = 1111;
            $out_data['info'] = '归还时间不合法';
            return $out_data;
        }
        if (empty($goodsinfo['return_address'])) {
            $out_data['code'] = 1112;
            $out_data['info'] = '归还地址不合法';
            return $out_data;
        }
        if (!is_numeric($goodsinfo['return_store_id'])) {
            $out_data['code'] = 1113;
            $out_data['info'] = '归还门店id不合法';
            return $out_data;
        }
        if (empty($goodsinfo['return_store_name'])) {
            $out_data['code'] = 1113;
            $out_data['info'] = '归还门店名称不合法';
            return $out_data;
        }
        if (!is_numeric($goodsinfo['return_visit'])) {
            $goodsinfo['return_visit'] = 0;
        }
        if (!empty($goodsinfo['extra_cost'])) {
            if (!is_numeric($goodsinfo['extra_cost'])) {
                $out_data['code'] = 1114;
                $out_data['info'] = '额外费用格式不正确';
                return $out_data;
            } else {
                if (empty($goodsinfo['extra_cost_notes'])) {
                    $out_data['code'] = 1115;
                    $out_data['info'] = '额外费用说明不合法';
                    return $out_data;
                }
            }
        } else {
            $goodsinfo['extra_cost'] = 0.00;
            $goodsinfo['extra_cost_notes'] = "";
        }
        if (!is_numeric($customer_info['customer_id'])) {
            $out_data['code'] = 1116;
            $out_data['info'] = '客户id不合法';
            return $out_data;
        }
        if (check_mobile_number($customer_info['mobile_phone']) === false) {
            $out_data['code'] = 1116;
            $out_data['info'] = '客户电话不合法';
            return $out_data;
        }
        if (empty($customer_info['customer_information'])) {
            $out_data['code'] = 1118;
            $out_data['info'] = '客户证件信息不合法';
            return $out_data;
        }
        if (empty($goodsinfo['goods_amount_detail'])) {
            $out_data['code'] = 1119;
            $out_data['info'] = '商品明细不合法';
            return $out_data;
        }
        $goods_licence_plate = "";
        if (!empty($goodsinfo['order_goods'])) {
            $goods_licence_plate = $goodsinfo['order_goods']['licence_plate'];
        }
        $orderinfotemp = array(
            'order_sn' => $order_sn,//订单号
            'store_id' => $goodsinfo['store_id'],//店铺信息
            'store_name' => $goodsinfo['store_name'],//店铺名称
            'goods_amount' => $goodsinfo['goods_amount'],//商品价格
            'order_amount' => $goodsinfo['order_amount'],//订单价格
            'extra_cost' => $goodsinfo['extra_cost'],//额外费用
            'extra_cost_notes' => $goodsinfo['extra_cost_notes'],//额外费用说明
            'goods_id' => $goodsinfo['goods_id'],//商品id
            'goods_device' => $goodsinfo['goods_device'],//商品设备编号
            'goods_name' => $goodsinfo['goods_name'],//商品名称
            'goods_type' => $goodsinfo['goods_type'],//商品类型
            'goods_sum' => $goodsinfo['goods_sum'],//商品数量
            'goods_licence_plate' => $goods_licence_plate,//车牌号
            'goods_amount_detail' => serialize($goodsinfo['goods_amount_detail']),//商品费用明细
//            'order_goods' => serialize($goodsinfo['order_goods']),//商品信息
            'acquire_time' => $goodsinfo['acquire_time'],//取用时间
            'acquire_address' => serialize($goodsinfo['acquire_address']),//取用地址
            'acquire_store_id' => $goodsinfo['acquire_store_id'],//取用门店id
            'acquire_store_name' => $goodsinfo['acquire_store_name'],//取用门店名称
            'acquire_visit' => $goodsinfo['acquire_visit'],//取车方式 0到店取车 1送车上门
//            'acquire_picture' => serialize($goodsinfo['acquire_picture']),//取用车图片
            'return_time' => $goodsinfo['return_time'],//归还时间
            'return_address' => serialize($goodsinfo['return_address']),//归还地址
            'return_store_id' => $goodsinfo['return_store_id'],//归还门店id
            'return_store_name' => $goodsinfo['return_store_name'],//归还门店名称
            'return_visit' => $goodsinfo['return_visit'],//还车方式 0到店还车 1上门取车
            'customer_id' => $customer_info['customer_id'],//客户id
            'customer_phone' => $customer_info['mobile_phone'],//客户电话
            'customer_name' => $customer_info['customer_information']['vehicle_drivers'],//客户姓名
//            'customer_information' => serialize($customer_info['customer_information']),//客户证件信息
            'customer_notes' => $customer_info['customer_notes'],//客户备注信息
            'order_status' => OrderStatus::$OrderStatusNopayment['code'],//订单状态 未付款
            'create_time' => time(),//订单创建时间
            'channel_uid' => $channel_uid,//分销客户的id
            'store_key_name' => $goodsinfo['store_key_name'],//总店铺名称
        );
        //判断是不是超级管理员 如果不是 添加标记
        if (!$is_super_admin) {
            $orderinfotemp['store_key_id'] = $store_key_id;
        } else {
            $orderinfotemp['store_key_id'] = 0;
        }
        if ($this->save($orderinfotemp)) {
            $order_id = $this->getLastInsID();
            $order_expand_model = new OrderExpand();
            $information = $customer_info['customer_information'];
            $goods = $goodsinfo['order_goods'];
            $acquire_picture = ['1', '2'];
            $order_expand_model->addExpand($order_id, $information, $goods, $acquire_picture);
            $out_data['code'] = 0;
            $out_data['data'] = $order_sn;
            $out_data['order_id'] = $order_id;
            $out_data['info'] = '添加成功';
            return $out_data;
        }
        $out_data['code'] = 1121;
        $out_data['info'] = '添加失败';
        return $out_data;
    }

    /**
     * 新能源车添加订单
     * @param $goodsinfo 商品信息
     * store_id 店铺id 门店
     * store_name 店铺店铺名称
     * goods_amount 商品单价
     * order_amount 订单总价格
     * goods_id 商品id
     * goods_sum 商品数量
     * goods_type 商品类
     * order_goods 商品信息 ['goods_name'=>'商品名称','goods_price'=>'商品单价','goods_sum'=>'购买数量','goods_img'=>'商品图片']
     * acquire_time 取用时间 时间戳
     * acquire_address 取用地址 ['name'=>'地址名称','lng'=>'106.257','lat'=>'26.782']
     * acquire_store_id 取用门店id
     * acquire_picture 取用车图片
     * return_time 归还时间 时间戳
     * return_address 归还地址 ['name'=>'地址名称','lng'=>'106.257','lat'=>'26.782']
     * return_store_id 归还门店id
     * extra_cost 额外费用
     * extra_cost_notes 额外费用说明
     * @param $customer_info 客户信息
     * customer_id 客户id
     * customer_drive_id 驾驶员信息id
     * mobile_phone 客户电话
     * customer_information 驾驶员证件信息 ['real_name'=>'客户真实姓名','id_number'=>'身份证号码','mobile_phone'=>'电话号码']
     * @param $channel_uid 渠道信息
     * @param bool $is_super_admin 是否是超级管理员
     * @param string $store_key_id 订单归属商户
     * @return array|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function addEleOrder($goodsinfo, $customer_info, $channel_uid, $is_super_admin = false, $store_key_id = '')
    {
        //判断是否有违章
        $car_regulations_model = new CarRegulations();
        $out_data = $car_regulations_model->is_return_cash($customer_info['customer_id']);
        if (!empty($out_data['code'])) {
            return $out_data;
        }
        $out_data = array(
            'code' => 1111,
            'info' => '参数有误',
        );
        $this->_update_order($customer_info['customer_id']);
        $map_cont = array(
            'customer_id' => $customer_info['customer_id'],
            'goods_type' => GoodsType::$GoodsTypeElectrocar['code'],
            'order_status' => array('between', OrderStatus::$OrderStatusNopayment['code'] . "," . OrderStatus::$OrderStatusReturn['code']),
        );
        $cont_on_end = $this->where($map_cont)->count();
        if ($cont_on_end > 0) {
            $out_data['code'] = 1112;
            $out_data['info'] = '有未完成订单';
            return $out_data;
        }
        $map_cont = array(
            'customer_id' => $customer_info['customer_id'],
            'goods_type' => GoodsType::$GoodsTypeElectrocar['code'],
            'order_status' => OrderStatus::$OrderStatusFinish['code']
        );
        $is_first_count = $this->where($map_cont)->count();
        $order_sn = $this->_create_order_sn();
        if (!is_numeric($goodsinfo['store_id'])) {
            $out_data['code'] = 1100;
            $out_data['info'] = '门店铺id不合法';
            return $out_data;
        }
        if (empty($goodsinfo['store_name'])) {
            $out_data['code'] = 1101;
            $out_data['info'] = '店铺名称不合法';
            return $out_data;
        }
        if (!is_numeric($goodsinfo['goods_amount'])) {
            $out_data['code'] = 1102;
            $out_data['info'] = '商品单价不合法';
            return $out_data;
        }
        if (!is_numeric($goodsinfo['goods_km_amount'])) {
            $out_data['code'] = 1102;
            $out_data['info'] = '商品单价不合法';
            return $out_data;
        }
        if (!is_numeric($goodsinfo['order_amount'])) {
            $out_data['code'] = 1103;
            $out_data['info'] = '订单总价不合法';
            return $out_data;
        }
        if (!is_numeric($goodsinfo['goods_id'])) {
            $out_data['code'] = 1104;
            $out_data['info'] = '商品id不合法';
            return $out_data;
        }
        if (empty($goodsinfo['goods_name'])) {
            $out_data['code'] = 1104;
            $out_data['info'] = '商品名称不合法';
            return $out_data;
        }
        if (!is_numeric($goodsinfo['goods_sum'])) {
            $out_data['code'] = 1105;
            $out_data['info'] = '商品数量不合法';
            return $out_data;
        }
        if (!is_numeric($goodsinfo['goods_type'])) {
            $out_data['code'] = 1106;
            $out_data['info'] = '商品类型不合法';
            return $out_data;
        }
        if (empty($goodsinfo['order_goods'])) {
            $out_data['code'] = 1107;
            $out_data['info'] = '商品信息不合法';
            return $out_data;
        }
        if (!is_numeric($goodsinfo['acquire_time'])) {
            $out_data['code'] = 1108;
            $out_data['info'] = '取用时间不合法';
            return $out_data;
        }
        if (empty($goodsinfo['acquire_address'])) {
            $out_data['code'] = 1109;
            $out_data['info'] = '取用地址不合法';
            return $out_data;
        }
        if (!is_numeric($goodsinfo['acquire_store_id'])) {
            $out_data['code'] = 1110;
            $out_data['info'] = '取用车门店id不合法';
            return $out_data;
        }
        if (empty($goodsinfo['acquire_store_name'])) {
            $out_data['code'] = 1110;
            $out_data['info'] = '取用车门店名称不合法';
            return $out_data;
        }
        if (empty($goodsinfo['acquire_picture'])) {
            $out_data['code'] = 1110;
            $out_data['info'] = '取用图片不合法';
            return $out_data;
        } else if (count($goodsinfo['acquire_picture']) < 3) {
            //判断图片是否符合要求
            $out_data['code'] = 1110;
            $out_data['info'] = "取用图片不合法";
            return $out_data;
        }
        if (empty($goodsinfo['return_address'])) {
            $out_data['code'] = 1112;
            $out_data['info'] = '归还地址不合法';
            return $out_data;
        }
        if (!is_numeric($goodsinfo['return_store_id'])) {
            $out_data['code'] = 1113;
            $out_data['info'] = '归还门店id不合法';
            return $out_data;
        }
        if (empty($goodsinfo['return_store_name'])) {
            $out_data['code'] = 1113;
            $out_data['info'] = '归还门店名称不合法';
            return $out_data;
        }
        if (!empty($goodsinfo['extra_cost'])) {
            if (!is_numeric($goodsinfo['extra_cost'])) {
                $out_data['code'] = 1114;
                $out_data['info'] = '额外费用格式不正确';
                return $out_data;
            } else {
                if (empty($goodsinfo['extra_cost_notes'])) {
                    $out_data['code'] = 1115;
                    $out_data['info'] = '额外费用说明不合法';
                    return $out_data;
                }
            }
        } else {
            $goodsinfo['extra_cost'] = 0.00;
            $goodsinfo['extra_cost_notes'] = "";
        }
        if (!is_numeric($customer_info['customer_id'])) {
            $out_data['code'] = 1116;
            $out_data['info'] = '客户id不合法';
            return $out_data;
        }
        if (check_mobile_number($customer_info['mobile_phone']) === false) {
            $out_data['code'] = 1116;
            $out_data['info'] = '客户电话不合法';
            return $out_data;
        }
        if (empty($customer_info['customer_information'])) {
            $out_data['code'] = 1118;
            $out_data['info'] = '客户证件信息不合法';
            return $out_data;
        }
        if (empty($goodsinfo['goods_amount_detail'])) {
            $out_data['code'] = 1119;
            $out_data['info'] = '商品明细不合法';
            return $out_data;
        }
        $goods_licence_plate = "";
        if (!empty($goodsinfo['order_goods'])) {
            $goods_licence_plate = $goodsinfo['order_goods']['licence_plate'];
        }
        $orderinfotemp = array(
            'order_sn' => $order_sn,//订单号
            'store_id' => $goodsinfo['store_id'],//店铺信息
            'store_name' => $goodsinfo['store_name'],//店铺名称
            'goods_amount' => $goodsinfo['goods_amount'],//商品价格
            'goods_km_amount' => $goodsinfo['goods_km_amount'],//商品公里价格
            'order_amount' => $goodsinfo['order_amount'],//订单价格
            'goods_licence_plate' => $goods_licence_plate,//车牌号
            'extra_cost' => $goodsinfo['extra_cost'],//额外费用
            'extra_cost_notes' => $goodsinfo['extra_cost_notes'],//额外费用说明
            'goods_id' => $goodsinfo['goods_id'],//商品id
            'goods_name' => $goodsinfo['goods_name'],//商品名称
            'goods_type' => $goodsinfo['goods_type'],//商品类型
            'goods_device' => $goodsinfo['goods_device'],//设备编号
            'goods_sum' => $goodsinfo['goods_sum'],//商品数量
            'goods_amount_detail' => serialize($goodsinfo['goods_amount_detail']),//商品费用明细
//            'order_goods' => serialize($goodsinfo['order_goods']),//商品信息
            'acquire_time' => $goodsinfo['acquire_time'],//取用时间
            'acquire_address' => serialize($goodsinfo['acquire_address']),//取用地址
            'acquire_store_id' => $goodsinfo['acquire_store_id'],//取用门店id
            'acquire_store_name' => $goodsinfo['acquire_store_name'],//取用门店名称
//            'acquire_picture' => serialize($goodsinfo['acquire_picture']),//取用车图片
            'return_store_id' => $goodsinfo['return_store_id'],//归还门店id
            'return_store_name' => $goodsinfo['return_store_name'],//归还门店名称
            'customer_id' => $customer_info['customer_id'],//客户id
            'customer_phone' => $customer_info['mobile_phone'],//客户电话
            'customer_name' => $customer_info['customer_information']['vehicle_drivers'],//客户姓名
//            'customer_information' => serialize($customer_info['customer_information']),//客户证件信息
            'customer_notes' => $customer_info['customer_notes'],//客户备注信息
            'order_status' => OrderStatus::$OrderStatusNopayment['code'],//订单状态 未付款
            'create_time' => time(),//订单创建时间
            'is_first_count' => $is_first_count,//统计订单是不是第一次使用
            'channel_uid' => $channel_uid,//分销客户的id
            'store_key_name' => $goodsinfo['store_key_name'],//总店铺名称
        );
        //判断是不是超级管理员 如果不是 添加标记
        if (!$is_super_admin) {
            $orderinfotemp['store_key_id'] = $store_key_id;
        } else {
            $orderinfotemp['store_key_id'] = 0;
        }
        if ($this->save($orderinfotemp)) {
            $order_id = $this->getLastInsID();
            $reserve_model = new Reserve();
            $reserve_model->endReserve($goodsinfo['goods_id'], $customer_info['customer_id']);
            $order_expand_model = new OrderExpand();
            $information = $customer_info['customer_information'];
            $goods = $goodsinfo['order_goods'];
            $acquire_picture = $goodsinfo['acquire_picture'];
            $order_expand_model->addExpand($order_id, $information, $goods, $acquire_picture);
            $out_data['code'] = 0;
            $out_data['data'] = $order_sn;
            $out_data['order_id'] = $order_id;
            $out_data['info'] = '添加成功';
            return $out_data;
        }
        $out_data['code'] = 1121;
        $out_data['info'] = '添加失败';
        return $out_data;
    }

    /**
     * 判断是否可以下单
     * @param string $customer_id
     * @param int $goods_type
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function IsAddOrder($customer_id = '', $goods_type = 1)
    {
        $this->_update_order($customer_id);
        if ($goods_type == GoodsType::$GoodsTypeElectrocar['code']) {
            $map_cont = array(
                'customer_id' => $customer_id,
                'goods_type' => GoodsType::$GoodsTypeElectrocar['code'],
                'order_status' => array('between', OrderStatus::$OrderStatusNopayment['code'] . "," . OrderStatus::$OrderStatusReturn['code']),
            );
            $cont_on_end = $this->where($map_cont)->count();
            if ($cont_on_end > 0) {
                $out_data['code'] = 1112;
                $out_data['info'] = '有未完成订单';
                return $out_data;
            }
        } else if ($goods_type == GoodsType::$GoodsTypeCar['code']) {
            $map_cont = array(
                'customer_id' => $customer_id,
                'goods_type' => GoodsType::$GoodsTypeCar['code'],
                'order_status' => OrderStatus::$OrderStatusNopayment['code'],
            );
            $cont_on_end = $this->where($map_cont)->count();
            if ($cont_on_end > 0) {
                $out_data['code'] = 1112;
                $out_data['info'] = '有未完成订单';
                return $out_data;
            }
        }
        $out_data['code'] = 0;
        $out_data['info'] = '状态正常';
        return $out_data;
    }

    /**
     * 常规车取消订单
     * @param string $order_id 订单id
     * @param string $customer_id 客户id
     * @param string $admin_id 操作管理员id
     * @param bool $is_super_admin 是否是超级管理员
     * @param string $store_key_id 订单归属商户
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function cancelOrder($order_id = '', $customer_id = '', $admin_id = '', $is_super_admin = false, $store_key_id = '')
    {
        if (empty($order_id)) {
            return false;
        }
        $map = array();
        $map['id'] = $order_id;
        //验证是否是客户操作
        if (!empty($customer_id)) {
            $map['customer_id'] = $customer_id;
        }
        //判断是不是 超级管理员 并且没有客户操作 如果不是 过滤数据
        if (!$is_super_admin && empty($customer_id)) {
            $map['store_key_id'] = $store_key_id;
        }
        $order = $this->where($map)->find();
        if ($order['order_status'] == OrderStatus::$OrderStatusPayment['code']) {
            $money = array(
                'customer_id' => $order['customer_id'],// 客户id
                'balance' => $order['order_amount'],// 订单合计 单位 元
                'pay_sn' => $order['pay_sn'],// 支付单号
                'type' => BalanceType::$BalanceTypeREFUND['code'],// 操作类型 退款
                'remark' => '退款单号:' . $order['order_sn'] . " 退回余额",// 备注
            );
            if ($this->customer_model->updateCustomerBalance($money)) {
                $order->order_status = OrderStatus::$OrderStatusCanceled['code'];
                $order->admin_cancel_id = $admin_id; //0表示系统操作
                $order->finnshed_time = time();
//                $goods_type = $order['goods_type'];
//                $goods_id = $order['goods_id'];
//                $acquire_time = $order['acquire_time'];
//                $return_time = $order['return_time'];
                if ($order->save()) {
                    //如果是常规汽车 更新租车预约区间
//                    if (intval($goods_type) == GoodsType::$GoodsTypeCar) {
//                        $car_common_model = new CarCommon();
//                        $car_common_model->delReserveInterval($goods_id, $acquire_time, $return_time);
//                    }
                    return true;
                }
            }
        } else if ($order['order_status'] == OrderStatus::$OrderStatusNopayment['code']) {
            $pd_amount = floatval($order['pd_amount']);
            if (!empty($pd_amount)) {
                $pd_amount = intval($pd_amount);
                if ($pd_amount > 0) {
                    $money = array(
                        'customer_id' => $order['customer_id'],// 客户id
                        'balance' => $order['pd_amount'],// 订单合计 单位 元
                        'pay_sn' => $order['pay_sn'],// 支付单号
                        'type' => BalanceType::$BalanceTypeREFUND['code'],// 操作类型 退款
                        'remark' => '退款单号:' . $order['order_sn'] . " 退回余额",// 备注
                    );
                    if ($this->customer_model->updateCustomerBalance($money)) {
                        $order->order_status = OrderStatus::$OrderStatusCanceled['code'];
                        $order->admin_cancel_id = $admin_id; //0表示系统操作
                        $order->finnshed_time = time();
                        if ($order->save()) {
                            return true;
                        }
                    }
                }
            } else {
                $order->order_status = OrderStatus::$OrderStatusCanceled['code'];
                $order->admin_cancel_id = $admin_id; //0表示系统操作
                $order->finnshed_time = time();
                $goods_id = $order['goods_id'];
                $customer_id_temp = $order['customer_id'];
                if ($order->save()) {
                    $reserve_model = new Reserve();
                    $reserve_model->endReserve($goods_id, $customer_id_temp);
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * 等待取车审核订单
     * @param string $order_id
     * @param string $customer_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function waitEleOrder($order_id = '', $customer_id = '')
    {
        $out_data = array(
            'code' => 900,
            'info' => "审核失败,订单信息有误"
        );
        if (empty($order_id)) {
            $out_data['code'] = 902;
            $out_data['info'] = '参数有误';
            return $out_data;
        }
        $map = array();
        $map['id'] = $order_id;
        $map['goods_type'] = GoodsType::$GoodsTypeElectrocar['code'];
        $map['customer_id'] = $customer_id;
        $order = $this->where($map)->field('goods_id,goods_device,id,order_status')->find();
        $order_expand_model = new OrderExpand();
        $order_expand_data = $order_expand_model->getExpand($order_id);
        if ($order['order_status'] == OrderStatus::$OrderStatusNopayment['code'] && !empty($order_expand_data['data']['acquire_picture'])) {
            $goods_id = $order['goods_id'];
            $car_common_model = new CarCommon();
            if (!$car_common_model->verCarIsUse($goods_id)) {
                $out_data['code'] = 100;
                $out_data['info'] = "车辆已被占用或者下线";
                $this->cancelOrder($order_id, '', 0, true);
                return $out_data;
            }
            $redis = new Redis();
            if (!$redis->has("login:" . $order['goods_device'])) {
                $out_data['code'] = 101;
                $out_data['info'] = "车机已掉线";
                $this->cancelOrder($order_id, '', 0, true);
                return $out_data;
            } else {
                $out_data_device = $redis->get("status:" . $order['goods_device']);
                if (empty($out_data_device)) {
                    $out_data['code'] = 101;
                    $out_data['info'] = "车机数据有误";
                    return $out_data;
                }
                $device_data = json_decode($out_data_device, true);
                //判断车手刹是否拉起
//                $car_lock_status_code = mb_substr($device_data['carStatus'], 20, 1);
                $car_lock_status_code = 1;
                if (intval($car_lock_status_code) == 1) {
                    $device_model = new CarDeviceTool();
                    $device_number = $device_data['deviceId'];
                    $data_arr = $device_model->startOrder($device_number);
                    if (intval($data_arr['code']) == 0) {
                        $out_data['code'] = 101;
                        $out_data['info'] = '下单失败 ' . $data_arr['info'];
                        $this->cancelOrder($order_id, '', 0, true);
                        return $out_data;
                    }
                    $out_data['code'] = 0;
                    $out_data['info'] = "审核通过";
                    $this->where($map)->setField('acquire_mileage', $device_data['odometer']);
                    $this->acquireOrder($order['id'], $customer_id);
                    return $out_data;
                } else {
                    $device_model = new CarDeviceTool();
                    $device_number = $device_data['deviceId'];
                    $data_arr = $device_model->startOrder($device_number);
                    if (intval($data_arr['code']) == 1) {
                        $out_data['code'] = 0;
                        $out_data['info'] = "审核通过";
                        $this->where($map)->setField('acquire_mileage', $device_data['odometer']);
                        $this->acquireOrder($order['id'], $customer_id);
                        return $out_data;
                    } else {
                        $out_data['code'] = 101;
                        $out_data['info'] = '下单失败 ' . $data_arr['info'];
                        $this->cancelOrder($order_id, '', 0, true);
                        return $out_data;
                    }
                }
            }
        }
        $this->cancelOrder($order_id, '', 0, true);
        return $out_data;
    }

    /**
     * 等待审核还车
     * @param string $order_id
     * @param string $customer_id
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function waitReturnOrder($order_id = '', $customer_id = '')
    {
        $out_data = array(
            'code' => 901,
            'info' => "正在审核中"
        );
        if (empty($order_id)) {
            $out_data['code'] = 902;
            $out_data['info'] = '参数有误';
            return $out_data;
        }
        $map = array();
        $map['id'] = $order_id;
        $map['goods_type'] = GoodsType::$GoodsTypeElectrocar['code'];
        $map['customer_id'] = $customer_id;
        $order = $this->where($map)->field('id,goods_id,goods_device,goods_type,id,goods_amount,acquire_time,extra_cost,order_status,return_store_id')->find();
        if ($order['order_status'] == OrderStatus::$OrderStatusAcquire['code']) {
            $order_expand_model = new OrderExpand();
            $order_expand_data = $order_expand_model->getExpand($order['id']);
            //判断是否提交还车图片
            if (empty($order_expand_data['code'])) {
                $return_picture = $order_expand_data['data']['return_picture'];
                if (empty($return_picture)) {
                    $out_data['code'] = 1001;
                    $out_data['info'] = '请先提交还车图片';
                    return $out_data;
                }
            } else {
                $out_data['code'] = 1002;
                $out_data['info'] = '请先提交还车图片';
                return $out_data;
            }
            $store_model = new Store();
            $return_store_data = $store_model->where(array('id' => $order['return_store_id'], 'store_status' => 0))->field('location_longitude,location_latitude,store_scope')->find();
            if (empty($return_store_data)) {
                $out_data['code'] = 1009;
                $out_data['info'] = '请选择可用的还车点';
                return $out_data;
            }
            $redis = new Redis();
            if (!$redis->has("login:" . $order['goods_device'])) {
                $out_data['code'] = 101;
                $out_data['info'] = "车机已掉线";
                return $out_data;
            } else {
                $out_data_device = $redis->get("status:" . $order['goods_device']);
                if (empty($out_data_device)) {
                    $out_data['code'] = 102;
                    $out_data['info'] = "车机数据有误";
                    return $out_data;
                }
                $device_data = json_decode($out_data_device, true);
            }
            $distance = get_distance($device_data['longitude'], $device_data['latitude'], $return_store_data['location_longitude'], $return_store_data['location_latitude'], 1);
            if (intval($distance) > intval($return_store_data['store_scope'])) {
                $out_data['code'] = 1010;
                $out_data['info'] = '还未停到还车区域内,或者请勿开到地下停车场内';
                return $out_data;
            }
            $is_parking = true;
            if (!empty($order_expand_data['data']['order_goods'])) {
                $order_goods = $order_expand_data['data']['order_goods'];
                $series_id = intval($order_goods['series_id']);
                if ($series_id == 4663 || $series_id == 4664) {
                    $is_parking = false;
                }
            }
            $out_datax = $this->_car_device_status($device_data['carStatus'], true, $is_parking);
            $device_number = $order['goods_device'];
            $device_model = new CarDeviceTool();
            if (intval($out_datax['code']) == 0) {
                $data_arr = $device_model->clearOrder($device_number);
                if (intval($data_arr['code']) != 1) {
                    $out_data['code'] = 803;
                    $out_data['info'] = $data_arr['info'];
                    return $out_data;
                }
                $out_data['code'] = 0;
                $out_data['info'] = "审核通过,还车成功";
                $goods_amount = $order['goods_amount'];
                $acquire_time = $order['acquire_time'];
                $extra_cost = $order['extra_cost'];
                $reality_return_time = time();
                $goods_sum = ($reality_return_time - $acquire_time) / 3600;
                $order_amount = floatval($goods_amount) * floatval($goods_sum);
                $order_amount = round($order_amount + floatval($extra_cost), 2);
                $ret = $this->where(array('id' => $order['id'], 'goods_type' => $order['goods_type']))->setField(['order_amount' => $order_amount, 'reality_return_time' => $reality_return_time]);
                if ($ret !== false) {
                    //刷新车辆数据 变为在线正常模式
                    if (!$this->returnOrder($order['id'], $customer_id)) {
                        return array('code' => 90, 'info' => "归还车辆失败,请联系客服");
                    } else {
                        $device_model->powerFailure($device_number);
                        $device_model->closeDoor($device_number);
                    }
                } else {
                    return array('code' => 90, 'info' => "归还车辆失败,请重试");
                }
                return $out_data;
            } else {
                return $out_datax;
            }
        }
        return $out_data;
    }

    /**
     * 提取商品
     * @param string $order_id
     * @param string $customer_id
     * @param string $admin_id
     * @param bool $is_super_admin
     * @param string $store_key_id
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function acquireOrder($order_id = '', $customer_id = '', $admin_id = '', $is_super_admin = false, $store_key_id = '')
    {
        if (empty($order_id)) {
            return false;
        }
        $map = array();
        $map['id'] = $order_id;
        //验证是否是客户操作
        if (!empty($customer_id)) {
            $map['customer_id'] = $customer_id;
        }
        //判断是不是 超级管理员 并且没有客户操作 如果不是 过滤数据
        if (!$is_super_admin && empty($customer_id)) {
            $map['store_key_id'] = $store_key_id;
        }
        $order = $this->where($map)->find();
        $car_common_model = new CarCommon();
        $goods_id = $order['goods_id'];
        if ($order['order_status'] == OrderStatus::$OrderStatusPayment['code']) {
            $order->order_status = OrderStatus::$OrderStatusAcquire['code'];
            $order->admin_acquire_id = $admin_id;
            if ($order->save()) {
                $car_common_model->useLockCar($goods_id);
                return true;
            }
        } else if ($order['goods_type'] == GoodsType::$GoodsTypeElectrocar['code']) {
            //如果是电动车
            if ($order['order_status'] == OrderStatus::$OrderStatusNopayment['code']) {
                $order->order_status = OrderStatus::$OrderStatusAcquire['code'];
                $order->admin_acquire_id = $admin_id;
                $order->acquire_time = time();
                $order->acquire_month = date("Y-m");
                $order->acquire_date = date("Y-m-d");
                $order->acquire_hour = date("H");
                $order->acquire_week = date("w");
                $order->acquire_year_week = date("Y-W");
                if ($order->save()) {
                    $car_common_model->useLockCar($goods_id);
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * 提交还车照片
     * @param string $order_id
     * @param string $customer_id
     * @param $return_picture
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function returnOrderPicture($order_id = '', $customer_id = '', $return_picture)
    {
        $out_data = array(
            'code' => 301,
            'info' => '参数有误',
        );
        if (empty($return_picture) || empty($order_id) || empty($customer_id)) {
            return $out_data;
        }
        //判断图片是否符合要求
        if (count($return_picture) < 3) {
            $out_data['code'] = 302;
            $out_data['info'] = "上传的图片不合法";
            return $out_data;
        }
        $map = array(
            'id' => $order_id,
            'customer_id' => $customer_id
        );
        $order_data = $this->where($map)->field('order_status,goods_id')->find();
//        if (intval($order_data['order_status']) != OrderStatus::$OrderStatusAcquire['code']) {
//            $out_data['code'] = 303;
//            $out_data['info'] = "上传图片不能修改";
//            return $out_data;
//        }
//        $order_data->return_picture = serialize($return_picture);
        $order_data->return_picture_time = time();
        if ($order_data->save()) {
            $order_expand_model = new OrderExpand();
            $order_expand_model->updateExpand($order_id, $return_picture);
            $car_common_model = new CarCommon();
            $car_common_model->where(['id' => $order_data['goods_id']])->setField(['return_img' => serialize($return_picture)]);
            $out_data['code'] = 0;
            $out_data['info'] = "提交完成";
        }
        return $out_data;
    }

    /**
     * 归还商品
     * @param string $order_id 订单id
     * @param string $customer_id 客户id
     * @param string $admin_id 客户id
     * @param bool $is_super_admin 是否是超级管理员
     * @param string $store_key_id 订单归属商户
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function returnOrder($order_id = '', $customer_id = '', $admin_id = '', $is_super_admin = false, $store_key_id = '')
    {
        if (empty($order_id)) {
            return false;
        }
        $map = array();
        $map['id'] = $order_id;
        //验证是否是客户操作
        if (!empty($customer_id)) {
            $map['customer_id'] = $customer_id;
        }
        //判断是不是 超级管理员 并且没有客户操作 如果不是 过滤数据
        if (!$is_super_admin && empty($customer_id)) {
            $map['store_key_id'] = $store_key_id;
        }
        $order = $this->where($map)->find();
        if ($order['order_status'] == OrderStatus::$OrderStatusAcquire['code']) {
            $order->order_status = OrderStatus::$OrderStatusReturn['code'];
            $order->admin_return_id = $admin_id;
            $order->reality_return_time = time();
            $acquire_mileage = $order['acquire_mileage'];
            $return_mileage = $order['return_mileage'];
            $all_mileage = intval($return_mileage) - intval($acquire_mileage);
            $order->all_mileage = $all_mileage;
            $order->return_month = date("Y-m");
            $order->return_date = date("Y-m-d");
            $order->return_hour = date("H");
            $order->return_week = date("w");
            $order->return_year_week = date("Y-W");
            $goods_type = $order['goods_type'];
            $goods_device = $order['goods_device'];
            $goods_id = $order['goods_id'];
            $acquire_time = $order['acquire_time'];
            $return_time = $order['return_time'];
            if ($order->save()) {
                //如果是常规汽车 更新租车预约区间
                $car_common_model = new CarCommon();
                if (intval($goods_type) == GoodsType::$GoodsTypeCar['code']) {
                    $car_common_model->delReserveInterval($goods_id, $acquire_time, $return_time);
                } else if (intval($goods_type) == GoodsType::$GoodsTypeElectrocar['code']) {
                    $car_common_model->carEleDispatch($goods_id, $order['return_store_id'], $order['return_store_name']);
                    //参数说明：参数1[请求目标地址的主域名]，参数2[路劲，一般是"入口文件/模块/控制器/操作方法"，当然也不排除你的单个php文件访问，后面就是你要进行传递的数据了了]
//                    $order_id, $device_id, $start_time, $end_time
                    //更新最后一次还车时间
                    $this->customer_model->update_drive_km($customer_id, $all_mileage);
                    $car_common_model->where(['id' => $goods_id])->setField(['return_time' => time()]);
                    doRequest('www.youchedongli.cn', '/api/common/order_return', array(
                            'customer_id' => $customer_id,
                            'order_id' => $order_id,
                            'device_id' => $goods_device,
                            'start_time' => $acquire_time,
                            'end_time' => time(),
                            'key' => "ycdlreturn2018"
                        )
                    );
                }
                $car_common_model->delLockCar($goods_id);
                return true;
            }
        }
        return false;
    }

    /**
     *其他费用产生 逾期费用 损坏费用
     * @param $rests_cost 费用
     * @param $rests_cost_notes   费用备注
     * @param string $order_id 订单id
     * @param string $admin_id 操作管理员id
     * @param bool $is_super_admin 是否是超级管理员
     * @param string $store_key_id 订单归属商户
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function restsCostOrder($rests_cost, $rests_cost_notes, $order_id = '', $admin_id = '', $is_super_admin = false, $store_key_id = '')
    {
        if (!is_numeric($rests_cost)) {
            $out_data['code'] = 1180;
            $out_data['info'] = '费用格式不正确';
            return $out_data;
        }
        if (empty($rests_cost_notes)) {
            $out_data['code'] = 1181;
            $out_data['info'] = '备注不能为空';
            return $out_data;
        }
        if (empty($order_id)) {
            $out_data['code'] = 1182;
            $out_data['info'] = '提交参数有误';
            return $out_data;
        }
        $map = array();
        $map['id'] = $order_id;
        //判断是不是 超级管理员 如果不是 过滤数据
        if (!$is_super_admin) {
            $map['store_key_id'] = $store_key_id;
        }
        $order = $this->where($map)->find();
        if ($order['order_status'] == OrderStatus::$OrderStatusAcquire['code']) {
            $order->rests_cost = $rests_cost;
            $order->admin_rests_id = $admin_id;
            $order->rests_cost_time = time();
            $order->rests_cost_notes = $rests_cost_notes;
            if ($order->save()) {
                $out_data['code'] = 0;
                $out_data['info'] = '费用设定成功';
                return $out_data;
            }
        }
        $out_data['code'] = 1183;
        $out_data['info'] = '费用设定失败';
        return $out_data;
    }

    /**
     * 确认订单
     * @param string $order_id 订单id
     * @param string $customer_id 客户id
     * @param string $admin_id 操作管理员id
     * @param bool $is_super_admin 是否是超级管理员
     * @param string $store_key_id 订单归属商户
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function endOrder($order_id = '', $customer_id = '', $admin_id = '', $is_super_admin = false, $store_key_id = '')
    {
        if (empty($order_id)) {
            return false;
        }
        $map = array();
        $map['id'] = $order_id;
        //验证是否是客户操作
        if (!empty($customer_id)) {
            $map['customer_id'] = $customer_id;
        }
        //判断是不是 超级管理员 并且没有客户操作 如果不是 过滤数据
        if (!$is_super_admin && empty($customer_id)) {
            $map['store_key_id'] = $store_key_id;
        }
        $order = $this->where($map)->find();
        if ($order['order_status'] == OrderStatus::$OrderStatusReturn['code']) {
            $order->order_status = OrderStatus::$OrderStatusFinish['code'];
            $order->admin_end_id = $admin_id;
            $order->finnshed_time = time();
            $goods_type = $order['goods_type'];
            $goods_id = $order['goods_id'];
            if ($order->save()) {
                //如果是常规汽车 更新租车次数
                if (intval($goods_type) == GoodsType::$GoodsTypeCar) {
                    $car_common_model = new CarCommon();
                    $car_common_model->rentCount($goods_id);
                }
                return true;
            }
        }
        return false;
    }

    /**
     * 更新还车门店
     * @param string $order_id
     * @param string $return_store_id
     * @param $return_store_name
     * @param $return_address
     * @param string $customer_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function returnStoreSite($order_id = '', $return_store_id = '', $return_store_name, $return_address, $customer_id = '')
    {
        $map = array(
            'id' => $order_id,
            'customer_id' => $customer_id,
            'order_status' => OrderStatus::$OrderStatusAcquire['code'],
        );
        $order_data = $this->where($map)->field('acquire_store_id,order_amount,extra_cost,return_store_id,return_store_name')->find();
        if (empty($order_data)) {
            $out_data = array(
                'code' => 100,
                'info' => '还车失败',
            );
            return $out_data;
        }
        if (intval($order_data['acquire_store_id']) != intval($return_store_id)) {
            $extra_cost = Config::get('extra_cost');
            $order_data->extra_cost = $extra_cost;
        } else {
            $order_data->extra_cost = 0;
        }
        $order_data->return_store_id = $return_store_id;
        $order_data->return_store_name = $return_store_name;
        $order_data->return_address = serialize($return_address);
        $order_data->save();
        $out_data = array(
            'code' => 0,
            'info' => '选择成功',
        );
        return $out_data;
    }

    /**
     * 根据车辆id 获取上一次订单图片
     * @param $goods_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getCarOrderImg($goods_id)
    {
        $out_data = [
            'code' => 100,
            'info' => "参数有误"
        ];
        if (empty($goods_id)) {
            return $out_data;
        }
        $order_data = $this->where(['goods_id' => $goods_id, 'order_status' => ['egt', OrderStatus::$OrderStatusReturn['code']]])->order("id DESC")->field('id')->find();
        $order_expand_model = new OrderExpand();
        $order_expand_data = $order_expand_model->getExpand($order_data['id']);
        if (empty($order_expand_data['code'])) {
            $out_data['code'] = 0;
            if (empty($order_expand_data['data']['return_picture'])) {
                $out_data['code'] = 102;
                $out_data['info'] = "没有图片信息";
                return $out_data;
            }
            $out_data['data'] = $order_expand_data['data']['return_picture'];
            $out_data['info'] = "获取成功";
        } else {
            $out_data['code'] = 101;
            $out_data['info'] = "没有订单信息";
        }
        return $out_data;
    }

    /**
     * 申请提前还车
     * @param string $order_id
     * @param string $customer_id
     * @param $return_address
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function returnReserveOrder($order_id = '', $customer_id = '', $return_address)
    {
        $out_data = [
            'code' => 100,
            'info' => "参数有误"
        ];
        $order_data = $this->where(['id' => $order_id, 'customer_id' => $customer_id, 'goods_type' => GoodsType::$GoodsTypeCar['code']])->field('id,order_sn,return_address,order_status,customer_information,return_visit,return_expect_time,return_expect_address,return_expect,return_store_id,store_key_id')->find();
        if (empty($order_data)) {
            return $out_data;
        }
        if ($order_data['order_status'] != OrderStatus::$OrderStatusAcquire['code']) {
            $out_data['code'] = 102;
            $out_data['info'] = "车辆未取用或者已还车，不可再次还车";
            return $out_data;
        }
        $order_data->return_expect_time = date("Y-m-d H:i:s");
        if (empty($return_address)) {
            $order_data->return_expect_address = $order_data['return_address'];
            $order_data->return_visit = 0;
        } else {
            $order_data->return_expect_address = serialize($return_address);
            $order_data->return_visit = 1;
        }
        $order_data->return_expect = 1;
        $name = $order_data['customer_name'];
        $phone = $order_data['customer_phone'];
        $order_sn = $order_data['order_sn'];
        $ret = $order_data->save();
        if ($ret !== false) {
            $store_id = $order_data['return_store_id'];
            if (empty($order_data['return_store_id'])) {
                $store_id = $order_data['store_key_id'];
            }
            $store_model = new Store();
            $return_store = $store_model->where(['id' => $store_id])->field('store_tel')->find();
            if (check_mobile_number($return_store['store_tel'])) {
                //向商家发送信息
                send_return_reserve_sms($return_store['store_tel'], $name, $phone, $order_sn);
            }
            $out_data['code'] = 0;
            $out_data['info'] = "提交成功";
            return $out_data;
        }
        $out_data['code'] = 101;
        $out_data['info'] = "提交失败";
        return $out_data;
    }

    /**
     * 退还操作
     * @param $pay_sn
     * @param $refund_amount
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function updateRefund($pay_sn, $refund_amount)
    {
        $out_data = [
            'code' => 100,
            'info' => "参数有误",
        ];
        if (empty($pay_sn) || empty($refund_amount)) {
            return $out_data;
        }
        if (floatval($refund_amount) <= 0) {
            $out_data['code'] = 101;
            $out_data['info'] = "退还金额有误";
            return $out_data;
        }
        $order_data = $this->where(['pay_sn' => $pay_sn])->field('id,pay_amount,pd_amount,is_refund,refund_amount,refund_time')->find();
        if (empty($order_data)) {
            $out_data['code'] = 102;
            $out_data['info'] = "退还订单不存在";
            return $out_data;
        }
        if (intval($order_data['is_refund']) == 0) {

            if (floatval($order_data['pay_amount']) + floatval($order_data['pd_amount']) < $refund_amount) {
                $out_data['code'] = 105;
                $out_data['info'] = "退款金额大于支付金额";
                return $out_data;
            }
            $up_order_data = [
                'is_refund' => 1,
                'refund_amount' => $refund_amount,
                'refund_time' => date("Y-m-d H:i:s")
            ];
            $ret = $this->save($up_order_data, ['id' => $order_data['id']]);
            if ($ret !== false) {
                $out_data['code'] = 0;
                $out_data['info'] = "退还申请成功";
                return $out_data;
            } else {
                $out_data['code'] = 104;
                $out_data['info'] = "退还申请失败";
                return $out_data;
            }
        } else {
            $out_data['code'] = 103;
            $out_data['info'] = "订单已退还，不能重复操作";
            return $out_data;
        }
    }

    /**
     * 验证是否可以还车（已过期）
     * @param $device_number
     * @param $device_type
     * @return array
     */
    public function _verifyCarStatus($device_number, $device_type = '')
    {
        $redis = new Redis();
        if (!$redis->has("login:" . $device_number)) {
            $out_data['code'] = 101;
            $out_data['info'] = "车机已掉线";
            return $out_data;
        } else {
            $out_data_device = $redis->get("status:" . $device_number);
            if (empty($out_data_device)) {
                $out_data['code'] = 102;
                $out_data['info'] = "车机数据有误";
                return $out_data;
            }
            $car_device_data = json_decode($out_data_device, true);
        }
        $out_data = $this->_car_device_status($car_device_data['carStatus']);
        return $out_data;
    }

    /**
     * 验证订单车辆状态
     * @param $order_id
     * @param $customer_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function VerifyOrderCarStatus($order_id = '', $customer_id = '')
    {
        $out_data = array(
            'code' => 100,
            'info' => '数据有误',
        );
        $order_data = $this->where(['id' => $order_id, 'customer_id' => $customer_id])->field('goods_id,goods_device,goods_type,return_store_id')->find();
        if (empty($order_data)) {
            return $out_data;
        }
        $order_expand_model = new OrderExpand();
        $order_expand_data = $order_expand_model->getExpand($order_data['id']);
        //判断是否提交还车图片
        if (empty($order_expand_data['code'])) {
            $return_picture = $order_expand_data['data']['return_picture'];
            if (empty($return_picture)) {
                $out_data['code'] = 1001;
                $out_data['info'] = '请先提交还车图片';
                return $out_data;
            }
        } else {
            $out_data['code'] = 1001;
            $out_data['info'] = '请先提交还车图片';
            return $out_data;
        }
        $redis = new Redis();
        if (!$redis->has("login:" . $order_data['goods_device'])) {
            $out_data['code'] = 101;
            $out_data['info'] = "车机已掉线";
            return $out_data;
        } else {
            $out_data_device = $redis->get("status:" . $order_data['goods_device']);
            if (empty($out_data_device)) {
                $out_data['code'] = 102;
                $out_data['info'] = "车机数据有误";
                return $out_data;
            }
            $car_device_data = json_decode($out_data_device, true);
        }
        $store_model = new Store();
        $return_store_data = $store_model->where(array('id' => $order_data['return_store_id'], 'store_status' => 0))->field('location_longitude,location_latitude,store_scope')->find();
        if (empty($return_store_data)) {
            $out_data['code'] = 1009;
            $out_data['info'] = '请选择可用的还车点';
            return $out_data;
        }
        $distance = get_distance($car_device_data['longitude'], $car_device_data['latitude'], $return_store_data['location_longitude'], $return_store_data['location_latitude'], 1);
        if (intval($distance) > intval($return_store_data['store_scope'])) {
            $out_data['code'] = 1010;
            $out_data['info'] = '车辆未停到还车区域内,请勿开到地下停车场内';
            return $out_data;
        }
        $is_parking = true;
        if (!empty($order_expand_data['data']['order_goods'])) {
            $order_goods = $order_expand_data['data']['order_goods'];
            $series_id = intval($order_goods['series_id']);
            if ($series_id == 4663 || $series_id == 4664) {
                $is_parking = false;
            }
        }
        $out_data = $this->_car_device_status($car_device_data['carStatus'], true, $is_parking);
        return $out_data;
    }

    /**
     * 订单命令控制
     * @param string $order_id
     * @param string $customer_id
     * @param string $cmd
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function OrderCarCmd($order_id = '', $customer_id = '', $cmd = '')
    {
        $out_data = array(
            'code' => 200,
            'info' => '操作无效'
        );
        $redis = new Redis();
        if ($redis->has($order_id . "" . $customer_id . "" . $cmd)) {
            $out_data['code'] = 201;
            $out_data['info'] = "时间间隔太短，请稍后操作";
            return $out_data;
        }
        $order_data = $this->where(array('id' => $order_id, 'customer_id' => $customer_id, 'order_status' => OrderStatus::$OrderStatusAcquire['code']))->field('goods_id,goods_device')->find();
        if (empty($order_data)) {
            return $out_data;
        }
        $redis = new Redis();
        if (!$redis->has("login:" . $order_data['goods_device'])) {
            $out_data['code'] = 101;
            $out_data['info'] = "车机已掉线";
            return $out_data;
        } else {
            $out_data_device = $redis->get("status:" . $order_data['goods_device']);
            if (empty($out_data_device)) {
                $out_data['code'] = 102;
                $out_data['info'] = "车机数据有误";
                return $out_data;
            }
        }
        $order_expand_model = new OrderExpand();
        $order_data_out = $order_expand_model->getExpand($order_id);
        if (empty($order_data_out['data']['acquire_picture'])) {
            if (!(intval($cmd) == CarCmd::$CarCmdOpenDoor['code'] || intval($cmd) == CarCmd::$CarCmdCloseDoor['code'])) {
                $out_data['code'] = 202;
                $out_data['info'] = '非法指令操作';
                return $out_data;
            }
        }
        $car_device_model = new CarDeviceTool();
        switch (intval($cmd)) {
            case CarCmd::$CarCmdFind['code']:
                $out_data = $car_device_model->findCar($order_data['goods_device']);
                if (intval($out_data['code']) == 1) {
                    $out_data['code'] = 0;
                    $out_data['info'] = "控制指令发送成功";
                    $redis->set($order_id . "" . $customer_id . "" . $cmd, "has", 10);
                    return $out_data;
                }
                break;
            case CarCmd::$CarCmdOpenDoor['code']:
                $out_data = $car_device_model->openDoor($order_data['goods_device']);
                if (intval($out_data['code']) == 1) {
                    $out_data['code'] = 0;
                    $out_data['info'] = "控制指令发送成功";
                    $redis->set($order_id . "" . $customer_id . "" . $cmd, "has", 10);
                    return $out_data;
                }
                break;
            case CarCmd::$CarCmdCloseDoor['code']:
                $out_data = $car_device_model->closeDoor($order_data['goods_device']);
                if (intval($out_data['code']) == 1) {
                    $out_data['code'] = 0;
                    $out_data['info'] = "控制指令发送成功";
                    $redis->set($order_id . "" . $customer_id . "" . $cmd, "has", 10);
                    return $out_data;
                }
                break;
            case CarCmd::$CarCmdIgnite['code']:
                $out_data = $car_device_model->powerSupply($order_data['goods_device']);
                if (intval($out_data['code']) == 1) {
                    $out_data['code'] = 0;
                    $out_data['info'] = "控制指令发送成功";
                    $redis->set($order_id . "" . $customer_id . "" . $cmd, "has", 10);
                    return $out_data;
                }
                break;
            case CarCmd::$CarCmdFlameout['code']:
                $out_data = $car_device_model->powerFailure($order_data['goods_device']);
                if (intval($out_data['code']) == 1) {
                    $out_data['code'] = 0;
                    $out_data['info'] = "控制指令发送成功";
                    $redis->set($order_id . "" . $customer_id . "" . $cmd, "has", 10);
                    return $out_data;
                }
                break;
        }
        return $out_data;
    }

    /**
     * 判断是否可以申请退还押金
     * @param $customer_id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function IsReturnCash($customer_id)
    {
        if (empty($customer_id)) {
            $out_data['code'] = 1200;
            $out_data['info'] = '参数有误';
            return $out_data;
        }
        $car_regulations = new CarRegulations();
        $out_data = $car_regulations->is_return_cash($customer_id);
        if ($out_data['code'] !== 0) {
            return $out_data;
        }
        $map_cont = array(
            'customer_id' => $customer_id,
            'goods_type' => GoodsType::$GoodsTypeElectrocar['code'],
            'order_status' => array('between', OrderStatus::$OrderStatusNopayment['code'] . "," . OrderStatus::$OrderStatusReturn['code']),
        );
        $cont_on_end = $this->where($map_cont)->count();
        if ($cont_on_end > 0) {
            $out_data['code'] = 1112;
            $out_data['info'] = '有未完成订单';
            return $out_data;
        }
        $map_order = array(
            'customer_id' => $customer_id,
            'order_status' => array('egt', 50)
        );
        $order_data = $this->where($map_order)->field('finnshed_time,payment_time,reality_return_time')->order('id DESC')->find();
        if (empty($order_data)) {
            $out_data['code'] = 0;
            $out_data['info'] = '可以申请退款';
            return $out_data;
        } else {
            if (empty($order_data['finnshed_time'])) {
                $order_data['finnshed_time'] = $order_data['payment_time'];
                if (empty($order_data['finnshed_time'])) {
                    $order_data['finnshed_time'] = $order_data['reality_return_time'];
                }
            }
            $time = time() - $order_data['finnshed_time'];
            //如果时间差大于10天可以申请退款
            if ($time > 10 * 3600 * 24) {
                $out_data['code'] = 0;
                $out_data['info'] = '可以申请退款';
                return $out_data;
            } else {
                $out_data['code'] = 1300;
                $out_data['data'] = 10 * 3600 * 24 - $time;
                $out_data['end_time'] = date('Y-m-d H:i:s', $order_data['finnshed_time']);
                $out_data['info'] = '用车完成后，10天之后才能退款';
                return $out_data;
            }
        }
    }

    /**
     * 判断车辆状态
     * @param $car_status_code
     * @param  bool $is_acc 判断ACC
     * @param  bool $is_parking 判断手刹
     * @return mixed
     */
    public function Car_device_status($car_status_code, $is_acc = false, $is_parking = true)
    {
        $out_data = array(
            'code' => 0,
            'info' => "验证通过",
        );
        $car_status_code_h = mb_substr($car_status_code, 20, 10);
        $car_status_code_l = mb_substr($car_status_code, 30, 10);
        $car_status_code_h = str_pad(base_convert($car_status_code_h, 16, 2), 40, '0', STR_PAD_LEFT);
        $car_status_code_l = str_pad(base_convert($car_status_code_l, 16, 2), 40, '0', STR_PAD_LEFT);
        $car_status_code = $car_status_code_h . $car_status_code_l;
        if ($is_acc) {
            $acc = mb_substr($car_status_code, 7, 1);
            if (intval($acc) == 1) {
                $out_data['code'] = 8;
                $out_data['info'] = "ACC没有关闭,请将要钥匙拨至仪表盘熄灭";
                return $out_data;
            }
        }
        if ($is_parking) {
            $parking = mb_substr($car_status_code, 3, 1);
            if (intval($parking) == 0) {
                $out_data['code'] = 10;
                $out_data['info'] = "手刹没有拉起";
                return $out_data;
            }
        }
        //判断门
        $door = strrev(mb_substr($car_status_code, 10, 6));
        $index_door = strpos($door, "1");
        if ($index_door != false) {
            $code = 11;
            $info = "左前门没有关好";
            switch ($index_door) {
                case 0:
                    $code = 11;
                    $info = "左前门没有关好";
                    break;
                case 1:
                    $code = 11;
                    $info = "右前门没有关好";
                    break;
                case 2:
                    $code = 11;
                    $info = "左后门没有关好";
                    break;
                case 3:
                    $code = 11;
                    $info = "右后门没有关好";
                    break;
                case 4:
                    $code = 11;
                    $info = "后备箱没有关好";
                    break;
                case 5:
                    $code = 11;
                    $info = "发动机盖没有关好";
                    break;
            }
            $out_data['code'] = $code;
            $out_data['info'] = $info;
            return $out_data;
        }
        //判断窗状态
        $window = mb_substr($car_status_code, 27, 5);
        $index_window = strpos($window, "1");
        if ($index_window != false) {
            $code = 31;
            $info = "左前窗没有关好";
            switch ($index_door) {
                case 0:
                    $code = 31;
                    $info = "左前窗没有关好";
                    break;
                case 1:
                    $code = 32;
                    $info = "右前窗没有关好";
                    break;
                case 2:
                    $code = 33;
                    $info = "左后窗没有关好";
                    break;
                case 3:
                    $code = 34;
                    $info = "右后窗没有关好";
                    break;
                case 4:
                    $code = 35;
                    $info = "天窗没有关好";
                    break;
            }
            $out_data['code'] = $code;
            $out_data['info'] = $info;
            return $out_data;
        }

        //判断灯状态
        $light = mb_substr($car_status_code, 32, 8);
        $index_light = strpos($light, "1");
        if ($index_light != false) {
            $code = 41;
            $info = "灯没有关好";
//            switch ($index_door) {
//                case 0:
//                    $code = 31;
//                    $info = "左前窗没有关好";
//                    break;
//                case 1:
//                    $code = 32;
//                    $info = "右前窗没有关好";
//                    break;
//                case 2:
//                    $code = 33;
//                    $info = "左后窗没有关好";
//                    break;
//                case 3:
//                    $code = 34;
//                    $info = "右后窗没有关好";
//                    break;
//                case 4:
//                    $code = 35;
//                    $info = "天窗没有关好";
//                    break;
//            }
            $out_data['code'] = $code;
            $out_data['info'] = $info;
            return $out_data;
        }
        //挡位
        $gears = mb_substr($car_status_code, 48, 4);
        $gears = base_convert($gears, 2, 10);
        if (!(intval($gears) == 0 || intval($gears) == 2)) {
            $code = 51;
            $info = "挡位没有调整到N档或者P档";
            $out_data['code'] = $code;
            $out_data['info'] = $info;
            return $out_data;
        }
        //判断锁状态
        $lock = mb_substr($car_status_code, 20, 4);
        $index_lock = strpos($lock, "0");
        if ($index_lock != false) {
            $code = 21;
            $info = "左前锁没有关好";
            switch ($index_door) {
                case 0:
                    $code = 21;
                    $info = "左前锁没有关好";
                    break;
                case 1:
                    $code = 21;
                    $info = "右前锁没有关好";
                    break;
                case 2:
                    $code = 21;
                    $info = "左后锁没有关好";
                    break;
                case 3:
                    $code = 21;
                    $info = "右后锁没有关好";
                    break;
            }
            $out_data['code'] = $code;
            $out_data['info'] = $info;
            return $out_data;
        }
        return $out_data;
    }

    /**
     * 判断车辆状态
     *
     * @param $car_status_code
     * @param  bool $is_acc 判断ACC
     * @param  bool $is_parking 判断手刹
     * @return mixed
     */
    private function _car_device_status($car_status_code, $is_acc = false, $is_parking = true)
    {
        $out_data = array(
            'code' => 0,
            'info' => "验证通过",
        );
        $car_status_code_h = mb_substr($car_status_code, 20, 10);
        $car_status_code_l = mb_substr($car_status_code, 30, 10);
        $car_status_code_h = str_pad(base_convert($car_status_code_h, 16, 2), 40, '0', STR_PAD_LEFT);
        $car_status_code_l = str_pad(base_convert($car_status_code_l, 16, 2), 40, '0', STR_PAD_LEFT);
        $car_status_code = $car_status_code_h . $car_status_code_l;
        if ($is_acc) {
            $acc = mb_substr($car_status_code, 7, 1);
            if (intval($acc) == 1) {
                $out_data['code'] = 8;
                $out_data['info'] = "ACC没有关闭,请将要钥匙拨至仪表盘熄灭";
                return $out_data;
            }
        }
        if ($is_parking) {
            $parking = mb_substr($car_status_code, 3, 1);
            if (intval($parking) == 0) {
                $out_data['code'] = 10;
                $out_data['info'] = "手刹没有拉起";
                return $out_data;
            }
        }
        //判断门
        $door = strrev(mb_substr($car_status_code, 10, 6));
        $index_door = strpos($door, "1");
        if ($index_door != false) {
            $code = 11;
            $info = "左前门没有关好";
            switch ($index_door) {
                case 0:
                    $code = 11;
                    $info = "左前门没有关好";
                    break;
                case 1:
                    $code = 11;
                    $info = "右前门没有关好";
                    break;
                case 2:
                    $code = 11;
                    $info = "左后门没有关好";
                    break;
                case 3:
                    $code = 11;
                    $info = "右后门没有关好";
                    break;
                case 4:
                    $code = 11;
                    $info = "后备箱没有关好";
                    break;
                case 5:
                    $code = 11;
                    $info = "发动机盖没有关好";
                    break;
            }
            $out_data['code'] = $code;
            $out_data['info'] = $info;
            return $out_data;
        }
        //判断窗状态
        $window = mb_substr($car_status_code, 27, 5);
        $index_window = strpos($window, "1");
        if ($index_window != false) {
            $code = 31;
            $info = "左前窗没有关好";
            switch ($index_door) {
                case 0:
                    $code = 31;
                    $info = "左前窗没有关好";
                    break;
                case 1:
                    $code = 32;
                    $info = "右前窗没有关好";
                    break;
                case 2:
                    $code = 33;
                    $info = "左后窗没有关好";
                    break;
                case 3:
                    $code = 34;
                    $info = "右后窗没有关好";
                    break;
                case 4:
                    $code = 35;
                    $info = "天窗没有关好";
                    break;
            }
            $out_data['code'] = $code;
            $out_data['info'] = $info;
            return $out_data;
        }

        //判断灯状态
        $light = mb_substr($car_status_code, 32, 8);
        $index_light = strpos($light, "1");
        if ($index_light != false) {
            $code = 41;
            $info = "灯没有关好";
//            switch ($index_door) {
//                case 0:
//                    $code = 31;
//                    $info = "左前窗没有关好";
//                    break;
//                case 1:
//                    $code = 32;
//                    $info = "右前窗没有关好";
//                    break;
//                case 2:
//                    $code = 33;
//                    $info = "左后窗没有关好";
//                    break;
//                case 3:
//                    $code = 34;
//                    $info = "右后窗没有关好";
//                    break;
//                case 4:
//                    $code = 35;
//                    $info = "天窗没有关好";
//                    break;
//            }
            $out_data['code'] = $code;
            $out_data['info'] = $info;
            return $out_data;
        }
        //挡位
        $gears = mb_substr($car_status_code, 48, 4);
        $gears = base_convert($gears, 2, 10);
        if (!(intval($gears) == 0 || intval($gears) == 2)) {
            $code = 51;
            $info = "挡位没有调整到N档或者P档";
            $out_data['code'] = $code;
            $out_data['info'] = $info;
            return $out_data;
        }
        //判断锁状态
        $lock = mb_substr($car_status_code, 20, 4);
        $index_lock = strpos($lock, "0");
        if ($index_lock != false) {
            $code = 21;
            $info = "左前锁没有关好";
            switch ($index_door) {
                case 0:
                    $code = 21;
                    $info = "左前锁没有关好";
                    break;
                case 1:
                    $code = 21;
                    $info = "右前锁没有关好";
                    break;
                case 2:
                    $code = 21;
                    $info = "左后锁没有关好";
                    break;
                case 3:
                    $code = 21;
                    $info = "右后锁没有关好";
                    break;
            }
            $out_data['code'] = $code;
            $out_data['info'] = $info;
            return $out_data;
        }
        return $out_data;
    }

    /**
     * 获取订单号
     * @return string
     */
    private function _create_order_sn()
    {
        $sn = date("YmdHis") . rand(1000, 9990) . rand(100, 999);
        return $sn;
    }

    /**
     * 获取支付订单号
     * @return string
     */
    private function _create_pay_sn()
    {
        $sn = "pay_" . date("YmdHis") . rand(1000, 9990) . rand(100, 999);
        return $sn;
    }

    /**
     * 刷新订单状态数据
     * @param string $customer_id
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    private function _update_order($customer_id = '')
    {
        $mapx['customer_id'] = $customer_id;
        $mapx['order_status'] = OrderStatus::$OrderStatusNopayment['code'];
        $this->getList($mapx, '', 0, 10);
        return true;
    }

}