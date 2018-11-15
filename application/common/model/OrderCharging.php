<?php

namespace app\common\model;

/**
 * 充电桩订单数据 数据模型
 */

use definition\BalanceType;
use definition\GoodsType;
use definition\OrderStatus;
use definition\PayCode;
use think\cache\driver\Redis;
use think\Config;
use think\Model;
use tool\ChargingDeviceTool;

class OrderCharging extends Model
{
    private $orderpaylog_model;
    private $customer_model;
    protected $insert = ['create_time'];

    public function __construct($data = [])
    {
        parent::__construct($data);
        $this->orderpaylog_model = new OrderChargingPayLog();
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
     * @param int $page_config
     * @param int $limit
     * @param bool $is_super_admin
     * @param string $store_key_id
     * @return \think\Paginator
     */
    public function getPageList($map = array(), $order = '', $page_config, $limit = 8, $is_super_admin = false, $store_key_id = '')
    {
        //如果不是超级管理员
        if (!$is_super_admin) {
            $map['store_key_id'] = $store_key_id;
        }
        $order_list = $this->where($map)->order($order)->paginate($limit, false, $page_config);
        if (!empty($order_list)) {
            foreach ($order_list as &$value) {
                $this->formatx($value);
                unset($value['order_goods']);
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
     */
    public function getList($map = array(), $order = '', $page, $limit = 8, $is_super_admin = false, $store_key_id = '')
    {
        //如果不是超级管理员
        if (!$is_super_admin) {
            $map['store_key_id'] = $store_key_id;
        }
        $order_list = $this->where($map)->order($order)->limit($page, $limit)->select();
        if (!empty($order_list)) {
            foreach ($order_list as &$value) {
                if (intval($value['goods_type']) == GoodsType::$GoodsTypeCar['code']) {
                    $this->formatx($value);
                } else if (intval($value['goods_type']) == GoodsType::$GoodsTypeElectrocar['code']) {
                    $this->formatxEle($value);
                }
                unset($value['order_goods']);
            }
        }
        return $order_list;
    }

    /**
     * 格式化常规订单数据
     * @param $data
     */
    public function formatx(&$data)
    {
        if (empty($data['finnshed_time'])) {
            $data['finnshed_time'] = "未完成";
        } else {
            $data['finnshed_time'] = date('Y-m-d H:i:s', $data['finnshed_time']);
        }
        $order_status = OrderStatus::$ORDER_STATUS_CODE;
        $data['order_status_str'] = $order_status[$data['order_status']];
        $data['remain_time'] = '0';
        $goods_amount = $data['goods_amount'];
        $goods_sum = $data['goods_sum'];
        $order_amount = round(floatval($goods_amount) * floatval($goods_sum));
        $data['payment_time_str'] = "";
        $data['payment_code_str'] = "未支付";
        if (intval($data['goods_type']) == GoodsType::$GoodsTypeCharging['code']) {
            if (intval($data['order_status']) == OrderStatus::$OrderStatusNopayment['code']) {
                $data['payment_time'] = "未支付";
                $data['payment_code_str'] = "未支付";
                $remain_time = time() - strtotime($data['create_time']);
                //如果下单时间 大于 30分钟 并且是没有付款 订单将自动取消
                if ($remain_time > 30) {
                    $this->cancelOrder($data['id'], '', 0, true);
                    $data['payment_time'] = "未支付";
                    $data['payment_code_str'] = "已取消";
                    $data['order_status'] = 0;
                    $data['order_status_str'] = $order_status[$data['order_status']];
                }
                $data['remain_time'] = 30 - $remain_time;
                $data['remain_time_str'] = diy_time_tostr(30 - $remain_time)['timestr'];
            } else if (intval($data['order_status'] == OrderStatus::$OrderStatusAcquire['code'])) {
                $charging_sum = 0;
                $goods_device = $data['goods_device'];
                $goods_gun = $data['goods_gun'];
                $redis = new Redis(['select' => 2]);
                $charging_json_data = $redis->get("realTime:" . $goods_device . ":" . $goods_gun);
                if (!empty($charging_json_data)) {
                    $charging_data = json_decode($charging_json_data, true);
                    $charging_sum = $charging_data['hexb08'];//充电总电量（度）
                    $charging_time = $charging_data['hexb06'];//充电时间（秒）
                    $charging_voltage = $charging_data['hexb04'];//充电电压（V）
                    $charging_electricity = $charging_data['hexb05'];//充电电流（A）
                    $charging_remaining = $charging_data['hexb09'];//预计剩余时间(分钟)
                    $charging_ratio = $charging_data['hexb0a'];//电池百分百(%)
                    $data['charging_time'] = $charging_time;
                    $data['charging_voltage'] = $charging_voltage;
                    $data['charging_electricity'] = $charging_electricity;
                    $data['charging_remaining'] = $charging_remaining;
                    $data['charging_ratio'] = $charging_ratio;
                }
                $order_charging_model = new OrderCharging();
                $order_amount = round(floatval($goods_sum) * floatval($goods_amount));
                $up_data_charging = [
                    'goods_sum' => $charging_sum,
                    'order_amount' => $order_amount
                ];
                $order_charging_model->where(['id' => $data['id']])->setField($up_data_charging);
            } else if (intval($data['order_status']) == OrderStatus::$OrderStatusCanceled['code']) {
                $data['payment_time'] = "未付款";
            } else {
                $data['payment_time'] = date('Y-m-d H:i:s', $data['payment_time']);
            }
            $pay_code = PayCode::$PAY_CODE;
            $data['payment_code_str'] = $pay_code[$data['payment_code']];
        }

        if (!empty($data['customer_information'])) {
            $data['customer_information'] = unserialize($data['customer_information']);
        }
        $data['order_amount'] = $order_amount;
        $data['acquire_time_str'] = date("m-d H:i", $data['acquire_time']);
        $data['return_time_str'] = date("m-d H:i", $data['return_time']);
    }

    /**
     * 支付订单
     * @param $pay_info 支付订单信息
     *  pay_sn 付款单号
     *  money 付款金额
     *  remark 备注信息
     * @param $payment_code 支付方式
     * @return bool
     */
    public function payOrder($pay_info, $payment_code)
    {
        $times = time();
        $map = array();
        $map['pay_sn'] = $pay_info['pay_sn'];
        $map['order_status'] = OrderStatus::$OrderStatusNopayment['code'];
        $order = $this->where($map)->find();
        if (!empty($order)) {
            //如果是电动车 必须是 车辆归还之后 才能支付  支付完成之后 订单结束
            $order->order_status = OrderStatus::$OrderStatusFinish['code'];
            $coupon_sn = $order['coupon_sn'];
            $order->payment_code = $payment_code;
            $order->payment_time = $times;
            $order->finnshed_time = $times;
            if ($order->save()) {
                reply_customer($order['wechar_openid'], $order['goods_name'], $order['id'], 4);
                if (!empty($coupon_sn)) {
                    $customer_coupon_model = new CustomerCoupon();
                    $customer_coupon_model->use_coupon($coupon_sn);
                }
                $paylog = array(
                    'pay_sn' => $pay_info['pay_sn'],
                    'money' => $pay_info['money'],
                    'type' => $payment_code,
                    'remark' => $pay_info['remark'],
                    'create_time' => date("Y-m-d H:i:s")
                );
                $this->orderpaylog_model->save($paylog);
                return true;
            }
        }
        return false;
    }

    /**
     * 获取订单支付信息 使用余额支付的 将自动扣除余额
     * @param $ordersn 订单单号数组 ['单号','单号']
     * @param bool $balance true使用余额付款 自动扣款
     * @param string $customer_id
     * @param string $coupon_sn 代金券单号
     * @return array
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
        if (!empty($ordersn)) {
            $order_info_temp = $this->where(array('customer_id' => $customer_id, 'order_sn' => $ordersn[0], 'order_status' => OrderStatus::$OrderStatusReturn['code']))->find();
            if (!empty($order_info_temp)) {
                $this->formatx($order_info_temp);
                unset($order_info_temp['goods_amount_detail']);
                unset($order_info_temp['order_goods']);
                unset($order_info_temp['goods_device']);
                $extra_cost = 0;
//                if ($order_info['acquire_store_id'] != $order_info['return_store_id']) {
//                    $extra_cost = Config::get('extra_cost');
//                }
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
        if (floatval($total_price) == 0) {
            $out_data = array(
                'code' => 1131,
                'info' => "订单已使用余额全额支付",
                'out_trade_no' => $pay_sn,
                'body' => $body,
                'total_price' => ''
            );
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
                            'add_time' => date("Y-m-d H:i:s")
                        );
//                        $this->_sms_order($pay_sn);
                        //条件支付日志
                        $this->orderpaylog_model->save($paylog);
                        $out_data = array(
                            'code' => 1131,
                            'info' => "订单已使用余额全额支付",
                            'out_trade_no' => $pay_sn,
                            'body' => $body,
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
                            if (($customer_balance > $order_info['order_amount'])) {
                                //账户余额 大于第一单 扣除整单金额 改单表示交易完成
                                $set_data = [
                                    "order_status" => OrderStatus::$OrderStatusFinish['code'],//已完成
                                    "payment_code" => PayCode::$PayCodeBalance['code'],//余额支付
                                    "pd_amount" => $customer_balance,//已付款
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
                            'add_time' => date("Y-m-d H:i:s")
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
     * 获取订单信息
     * @param string $order_id 订单id
     * @param string $customer_id 客户id
     * @param bool $is_super_admin 是否是超级管理员
     * @param string $store_key_id 订单归属商户
     * @return mixed
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
        $this->formatx($order);
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
        $this->formatx($order);
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
     * 充电桩添加订单
     * @param $charging_id 充电桩信息
     * store_id 店铺id 门店
     * store_name 店铺店铺名称
     * goods_amount 商品单价
     * goods_hour_amount 商品每小时单价
     * order_amount 订单总价格
     * goods_id 商品id
     * goods_type 商品类
     * order_goods 商品信息 ['goods_name'=>'商品名称','goods_price'=>'商品单价','goods_img'=>'商品图片']
     * acquire_time 取用时间 时间戳
     * return_time 归还时间 时间戳
     * extra_cost 额外费用
     * extra_cost_notes 额外费用说明
     * @param $customer_id 客户信息
     * customer_id 客户id
     * mobile_phone 客户电话
     * @param $channel_uid 渠道信息
     * @return array|string
     */
    public function addOrder($charging_id, $customer_id, $channel_uid)
    {
        $out_data = array(
            'code' => 1111,
            'info' => '参数有误'
        );
        $map_cont = array(
            'customer_id' => $customer_id,
            'goods_type' => GoodsType::$GoodsTypeCharging['code'],
            'order_status' => array('between', OrderStatus::$OrderStatusAcquire['code'] . "," . OrderStatus::$OrderStatusReturn['code']),
        );
        $cont_on_end = $this->where($map_cont)->count();
        if ($cont_on_end > 0) {
            $out_data['code'] = 1112;
            $out_data['info'] = '有未完成订单';
            return $out_data;
        }
        $customer_model = new Customer();
        $customer_data = $customer_model->getCustomerField($customer_id, 'id,mobile_phone,customer_balance,wechar_openid');
        $customer_info = $customer_data['data'];

        if (empty($customer_info['mobile_phone'])) {
            $out_data['code'] = 111;
            $out_data['info'] = '请先绑定手机号码';
            return $out_data;
        }
        if (floatval($customer_info['customer_balance']) < 30) {
            $out_data['code'] = 112;
            $out_data['info'] = '请先充值余额';
            return $out_data;
        }
        $charging_model = new Charging();
        $charging_data = $charging_model->getCharging($charging_id);
        $charging_data = $charging_data['data'];
        $charging_info = [
            'store_id' => $charging_data['store_id'],
            'store_name' => $charging_data['store_name'],
            'goods_amount' => $charging_data['quantity_price'],
            'goods_hour_amount' => 0,
            'order_amount' => 0,
            'goods_id' => $charging_id,
            'goods_name' => $charging_data['name'],
            'goods_type' => $charging_data['charging_type'],
            'goods_device' => $charging_data['device_number'],
            'goods_gun' => $charging_data['device_gun'],
            'goods_amount_detail' => ['quantity_price' => $charging_data['quantity_price'], 'hour_price' => $charging_data['hour_price']],
            'order_goods' => ['device_number' => $charging_data['device_number'], 'device_gun' => $charging_data['device_gun'], 'charging_type' => $charging_data['charging_type']],
            'acquire_time' => time(),
            'return_time' => time(),
            'extra_cost' => 0,
        ];
        $store_key_id = $charging_data['store_key_id'];
        $store_key_name = $charging_data['store_key_name'];
        $order_sn = $this->_create_order_sn();
        if (!is_numeric($charging_info['store_id'])) {
            $out_data['code'] = 1100;
            $out_data['info'] = '门店铺id不合法';
            return $out_data;
        }
        if (empty($charging_info['store_name'])) {
            $out_data['code'] = 1101;
            $out_data['info'] = '店铺名称不合法';
            return $out_data;
        }
        if (!is_numeric($charging_info['goods_amount'])) {
            $out_data['code'] = 1102;
            $out_data['info'] = '商品单价不合法';
            return $out_data;
        }
        if (!is_numeric($charging_info['order_amount'])) {
            $out_data['code'] = 1103;
            $out_data['info'] = '订单总价不合法';
            return $out_data;
        }
        if (!is_numeric($charging_info['goods_id'])) {
            $out_data['code'] = 1104;
            $out_data['info'] = '商品id不合法';
            return $out_data;
        }
        if (empty($charging_info['goods_name'])) {
            $out_data['code'] = 1104;
            $out_data['info'] = '商品名称不合法';
            return $out_data;
        }
        if (!is_numeric($charging_info['goods_type'])) {
            $out_data['code'] = 1106;
            $out_data['info'] = '商品类型不合法';
            return $out_data;
        }
        if (empty($charging_info['order_goods'])) {
            $out_data['code'] = 1107;
            $out_data['info'] = '商品信息不合法';
            return $out_data;
        }
        if (!is_numeric($charging_info['acquire_time'])) {
            $out_data['code'] = 1108;
            $out_data['info'] = '取用时间不合法';
            return $out_data;
        }
        if (!is_numeric($charging_info['return_time'])) {
            $out_data['code'] = 1111;
            $out_data['info'] = '归还时间不合法';
            return $out_data;
        }
        if (!empty($charging_info['extra_cost'])) {
            if (!is_numeric($charging_info['extra_cost'])) {
                $out_data['code'] = 1114;
                $out_data['info'] = '额外费用格式不正确';
                return $out_data;
            } else {
                if (empty($charging_info['extra_cost_notes'])) {
                    $out_data['code'] = 1115;
                    $out_data['info'] = '额外费用说明不合法';
                    return $out_data;
                }
            }
        } else {
            $goodsinfo['extra_cost'] = 0.00;
            $goodsinfo['extra_cost_notes'] = "";
        }
        if (!is_numeric($customer_info['id'])) {
            $out_data['code'] = 1116;
            $out_data['info'] = '客户id不合法';
            return $out_data;
        }
        if (empty($charging_info['goods_amount_detail'])) {
            $out_data['code'] = 1119;
            $out_data['info'] = '商品明细不合法';
            return $out_data;
        }
        if (empty($charging_info['goods_device'])) {
            $out_data['code'] = 1129;
            $out_data['info'] = '设备编号不合法';
            return $out_data;
        }
        if (empty($charging_info['goods_gun'])) {
            $charging_info['goods_gun'] = 0;
        }
        if (empty($channel_uid)) {
            $channel_uid = 0;
        }
        $orderinfotemp = array(
            'order_sn' => $order_sn,//订单号
            'store_id' => $charging_info['store_id'],//店铺信息
            'store_name' => $charging_info['store_name'],//店铺名称
            'goods_amount' => $charging_info['goods_amount'],//商品价格
            'goods_hour_amount' => $charging_info['goods_hour_amount'],//商品每小时价格
            'order_amount' => $charging_info['order_amount'],//订单价格
            'extra_cost' => $charging_info['extra_cost'],//额外费用
            'extra_cost_notes' => $charging_info['extra_cost_notes'],//额外费用说明
            'goods_id' => $charging_info['goods_id'],//商品id
            'goods_device' => $charging_info['goods_device'],//商品设备编号
            'goods_gun' => $charging_info['goods_gun'],//商品设备充电桩编号
            'goods_name' => $charging_info['goods_name'],//商品名称
            'goods_type' => $charging_info['goods_type'],//商品类型
            'goods_amount_detail' => serialize($charging_info['goods_amount_detail']),//商品费用明细
            'order_goods' => serialize($charging_info['order_goods']),//商品信息
            'acquire_time' => $charging_info['acquire_time'],//取用时间
            'return_time' => $charging_info['return_time'],//归还时间
            'customer_id' => $customer_info['id'],//客户id
            'customer_name' => $customer_info['mobile_phone'],//客户电话
            'customer_notes' => '',//客户备注信息
            'order_status' => OrderStatus::$OrderStatusNopayment['code'],//订单状态 未付款
            'create_time' => time(),//订单创建时间
            'channel_uid' => $channel_uid,//分销客户的id
            'wechar_openid' => $customer_info['wechar_openid'],//微信openid
            'store_key_id' => $store_key_id,//总店铺名称
            'store_key_name' => $store_key_name,//总店铺名称
        );
        if ($this->save($orderinfotemp)) {
            $order_id = $this->getLastInsID();
            /**
             * 扫描成功
             */
            reply_customer($customer_info['wechar_openid'], $charging_info['goods_name'], $order_id, 1);

            if (!$this->_startOrder($order_id, $charging_info['goods_device'], $charging_info['goods_gun'], $customer_info['mobile_phone'], $customer_info['customer_balance'])) {
                $this->cancelOrder($order_id);
                reply_customer($customer_info['wechar_openid'], $charging_info['goods_name'], $order_id, 5);
                $out_data['code'] = 0;
                $out_data['data'] = $order_sn;
                $out_data['order_id'] = $order_id;
                $out_data['info'] = '启动失败';
                return $out_data;
            } else {
                $this->acquireOrder($charging_data['device_number'], $charging_data['device_gun']);
            }
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
     * 停止充电
     * @param $order_id
     * @param $customer_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function stopOrder($order_id, $customer_id)
    {
        $out_data = [
            'code' => 100,
            'info' => "订单参数有误"
        ];
        $order_data = $this->where(['id' => $order_id, 'customer_id' => $customer_id, 'order_status' => OrderStatus::$OrderStatusAcquire['code']])->field('goods_name,customer_name,goods_device,goods_gun,wechar_openid')->find();
        if (empty($order_data)) {
            return $out_data;
        }
        //customer_name,goods_device,goods_gun
        if ($this->_stopOrder($order_id, $order_data['goods_device'], $order_data['goods_gun'], $order_data['customer_name'])) {
            return $out_data;
        }
        $out_data['code'] = 101;
        $out_data['info'] = "停止充电失败";
        return $out_data;
    }

    /**
     * 启动充电桩
     * @param $order_id
     * @param $terminal_number
     * @param $gun_number
     * @param $mobile_phone
     * @param $money
     * @return bool
     */
    private function _startOrder($order_id, $terminal_number, $gun_number, $mobile_phone, $money)
    {
        $charging_device_tool = new ChargingDeviceTool();
        $out_data = $charging_device_tool->switchCharging($terminal_number, $gun_number, 1, $mobile_phone, $order_id, 2, 0, $money);
        if (intval($out_data['code']) == 1) {
            return true;
        }
        return false;
    }

    /**
     * 停止充电桩
     * @param $order_id
     * @param $terminal_number
     * @param $gun_number
     * @param $mobile_phone
     * @return bool
     */
    private function _stopOrder($order_id, $terminal_number, $gun_number, $mobile_phone)
    {
        $charging_device_tool = new ChargingDeviceTool();
        $out_data = $charging_device_tool->switchCharging($terminal_number, $gun_number, 2, $mobile_phone, $order_id, 2, 0, 0);
        if (intval($out_data['code']) == 1) {
            return true;
        }
        return false;
    }

    /**
     * 开始计费
     * @param $cid
     * @param $gid
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function acquireOrder($did, $gid)
    {
        $map = [
            'goods_device' => $did,
            'goods_gun' => $gid,
            'order_status' => OrderStatus::$OrderStatusNopayment['code']
        ];
        $order_data = $this->where($map)->field('id,acquire_time,order_status')->find();
        if (!empty($order_data)) {
            $order_data->acquire_time = time();
            $order_data->order_status = OrderStatus::$OrderStatusAcquire['code'];
            if ($order_data->save()) {
                /**
                 * 启动成功
                 */
                reply_customer($order_data['wechar_openid'], $order_data['goods_name'], $order_data['id'], 2);
                return true;
            }
        }
        return false;
    }

    /**
     * 充电完成
     * @param $id
     * @param $energy
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function returnOrder($id, $energy)
    {
        $map = [
            'id' => $id,
            'order_status' => OrderStatus::$OrderStatusAcquire['code']
        ];
        $order_data = $this->where($map)->field('id,return_time,goods_name,goods_sum,order_status,wechar_openid')->find();
        if (!empty($order_data)) {
            $order_data->return_time = time();
            $order_data->goods_sum = $energy;
            $order_data->order_status = OrderStatus::$OrderStatusReturn['code'];
            if ($order_data->save()) {
                reply_customer($order_data['wechar_openid'], $order_data['goods_name'], $order_data['id'], 3);
                $out_data['code'] = 0;
                $out_data['info'] = "停止充电成功";
                return true;
            }
        }
        return false;
    }

    /**
     * 判断是否可以下单
     * @param string $customer_id
     * @param int $goods_type
     * @return mixed
     */
    public function IsAddOrder($customer_id = '', $goods_type = 3)
    {
        if ($goods_type == GoodsType::$GoodsTypeCharging['code']) {
            $map_cont = array(
                'customer_id' => $customer_id,
                'goods_type' => GoodsType::$GoodsTypeCharging['code'],
                'order_status' => array('between', OrderStatus::$OrderStatusAcquire['code'] . "," . OrderStatus::$OrderStatusReturn['code']),
            );
            $cont_on_end = $this->where($map_cont)->count();
            if ($cont_on_end > 0) {
                $out_data['code'] = 1112;
                $out_data['info'] = '有未完成订单';
                return $out_data;
            }
        }
        $customer_data = $this->customer_model->getCustomerField($customer_id, 'customer_balance');
        $charging_min = Config::get('charging_min');
        if (floatval($customer_data['customer_balance']) < floatval($charging_min)) {
            $out_data['code'] = 1113;
            $out_data['info'] = '余额不足30元，请进行充值';
            return $out_data;
        }
        $out_data['code'] = 0;
        $out_data['info'] = '状态正常';
        return $out_data;
    }

    /**
     * 超时取消订单
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
        if ($order['order_status'] == OrderStatus::$OrderStatusNopayment['code']) {
            $order->order_status = OrderStatus::$OrderStatusCanceled['code'];
            $order->admin_cancel_id = $admin_id; //0表示系统操作
            $order->finnshed_time = time();
            if ($order->save()) {
                return true;
            }
        }
        return false;
    }

    /**
     * 确认订单
     * @param string $order_id 订单id
     * @param string $customer_id 客户id
     * @param string $admin_id 操作管理员id
     * @param bool $is_super_admin 是否是超级管理员
     * @param string $store_key_id 订单归属商户
     * @return bool
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
                    $charging_model = new Charging();
                    $charging_model->rentCount($goods_id);
                }
                return true;
            }
        }
        return false;
    }

    /**
     * 获取订单号
     * @return string
     */
    private function _create_order_sn()
    {
        $sn = "charging_" . date("YmdHis") . rand(1000, 9990) . rand(100, 999);
        return $sn;
    }

    /**
     * 获取支付订单号
     * @return string
     */
    private function _create_pay_sn()
    {
        $sn = "pay_charging_" . date("YmdHis") . rand(1000, 9990) . rand(100, 999);
        return $sn;
    }

}