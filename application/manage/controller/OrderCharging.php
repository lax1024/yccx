<?php

namespace app\manage\controller;

use app\common\model\OrderCharging as OrderModel;
use app\common\model\Customer as CustomerModel;
use app\common\model\OrderChargingComment as OrderCommentModel;
use app\common\model\OrderChargingPayLog as OrderPayLogModel;
use app\common\controller\AdminBase;
use definition\GoodsType;
use definition\OrderStatus;
use definition\PayCode;
use tool\CarDeviceTool;
use wxpay\database\WxPayRefund;
use wxpay\WxPayApi;

/**
 * 订单管理
 * Class AuthGroup
 * @package app\manage\controller
 */
class OrderCharging extends AdminBase
{
    protected $order_model;

    protected function _initialize()
    {
        parent::_initialize();
        $this->order_model = new OrderModel();
    }

    /**
     * 订单管理
     * @param string $keyword
     * @param int $page
     * @param string $order_status
     * @param string $start_time
     * @param string $end_time
     * @param string $payment_code
     * @return mixed
     */
    public function index($keyword = '', $page = 1, $order_status = '', $start_time = '', $end_time = '', $payment_code = '')
    {
        $map = array();
        if (empty($page)) {
            $page = 1;
        }
        if (empty($start_time)) {
            $start_time_num = "1483200000";
        } else {
            $start_time_num = strtotime($start_time);
        }
        if (!empty($end_time)) {
            $end_time_num = strtotime($end_time);
            $map['payment_time'] = array('between', $start_time_num . ',' . $end_time_num);
        }
        if (!empty($keyword)) {
            $map['id|store_id|order_sn|pay_sn|store_name|customer_name|order_goods'] = ['like', "%{$keyword}%"];
        }
        if (!empty($payment_code)) {
            $map['payment_code'] = $payment_code;
        }
        if ($order_status == '' || $order_status == "-1") {
            $order_status = "-1";
        } else if (intval($order_status) < 70) {
            $map['order_status'] = $order_status;
        } else if (intval($order_status) == 80) {
            $map['return_expect'] = 1;
        } else if (intval($order_status) == 90) {
            $map['is_refund'] = 1;
        } else if (intval($order_status) == 100) {
            $map['is_refund'] = 2;
        }
        $page_config = ['page' => $page, 'query' => ['order_status' => $order_status, 'start_time' => $start_time, 'end_time' => $end_time, 'payment_code' => $payment_code, 'keyword' => $keyword]];
        $order_list = $this->order_model->getPageList($map, ' id DESC ', $page_config, 15, $this->web_info['is_super_admin'], $this->web_info['store_key_id']);
        $order_status_list = OrderStatus::$ORDER_STATUS_CODE;
        $payment_code_list = PayCode::$PAY_CODE;
        if (empty($payment_code)) {
            $map['payment_code'] = array('neq', '');
        }
        $order_amount = $this->order_model->where($map)->sum('order_amount');
        $map['is_refund'] = 2;
        $refund_amount = $this->order_model->where($map)->sum('refund_amount');
        return $this->fetch('index', ['order_list' => $order_list, 'order_amount' => $order_amount, 'refund_amount' => $refund_amount, 'order_status_list' => $order_status_list, 'payment_code_list' => $payment_code_list, 'start_time' => $start_time, 'end_time' => $end_time, 'payment_code' => $payment_code, 'order_status' => $order_status, 'keyword' => $keyword]);
    }

    /**
     * 添加订单管理
     * @return mixed
     */
    public function add()
    {
        $this->error('暂时不提供此方法');
        return $this->fetch();
    }

    /**
     * 保存订单管理
     */
    public function save()
    {
        $this->error('暂时不提供此方法');
        if ($this->request->isPost()) {
            $data = $this->request->post();
//     * @param $goodsinfo 商品信息
//            * store_id 店铺id 门店
//            * store_name 店铺店铺名称
//            * goods_amount 商品单价
//            * order_amount 订单总价格
//            * goods_id 商品id
//            * goods_type 商品类
//            * order_goods 商品信息 ['goods_name'=>'商品名称','goods_price'=>'商品单价','goods_sum'=>'购买数量','goods_img'=>'商品图片']
//            * acquire_time 取用时间 时间戳
//            * acquire_address 取用地址 ['name'=>'地址名称','lng'=>'106.257','lat'=>'26.782']
//            * return_time 归还时间 时间戳
//            * return_address 归还地址 ['name'=>'地址名称','lng'=>'106.257','lat'=>'26.782']
//     * @param $customer_info 客户信息
//            * customer_id 客户id
//            * mobile_phone 客户电话
//            * customer_information 客户证件信息 ['real_name'=>'客户真实姓名','id_number'=>'身份证号码','mobile_phone'=>'电话号码']
//     * @param $channel_uid 渠道信息
            $goodsinfo = array(
                'store_id' => $data['store_id'],
                'store_name' => $data['store_name'],
                'goods_amount' => $data['goods_amount'],
                'order_amount' => $data['order_amount'],
                'goods_id' => $data['goods_id'],
                'goods_type' => $data['goods_type'],
                'order_goods' => $data['order_goods'],
                'acquire_time' => $data['acquire_time'],
                'acquire_address' => $data['acquire_address'],
                'return_time' => $data['return_time'],
                'return_address' => $data['return_address']
            );
            $customer_info = array(
                'customer_id' => $data['customer_id'],
                'mobile_phone' => $data['mobile_phone'],
                'customer_information' => $data['customer_information']
            );
            $channel_uid = 0;
            $out_data = $this->order_model->addOrder($goodsinfo, $customer_info, $channel_uid, $this->web_info['is_super_admin'], $this->web_info['store_key_id']);
            if (intval($out_data['code']) == 0) {
                $this->success('保存成功');
            } else {
                $this->error('保存失败');
            }
        }
    }

    /**
     * 编辑订单管理
     * @param $id
     * @return mixed
     */
    public function edit($id)
    {
        $out_data = $this->order_model->getOrder($id, '', $this->web_info['is_super_admin'], $this->web_info['store_key_id']);
        $order_comment_model = new OrderCommentModel();
        $map['order_id'] = $id;
        $order_comment = $order_comment_model->getComment($map);
        return $this->fetch('edit', ['order' => $out_data['data'], 'order_comment' => $order_comment['data']]);
    }

    /**
     * 更新订单管理
     * @param $id
     */
    public function update($id)
    {
        $this->error('暂时不提供次方法');
    }

    /**
     * 取消订单
     * @param $id
     */
    public function cancel_order($id)
    {
        if ($this->order_model->cancelOrder($id, '', $this->manage_info['manage_id'], $this->web_info['is_super_admin'], $this->web_info['store_key_id'])) {
            $this->success('取消成功');
        } else {
            $this->error('取消失败');
        }
    }

    /**
     * 提取商品
     * @param $id
     */
    public function acquire_order($id)
    {
        if ($this->order_model->acquireOrder($id, '', $this->manage_info['manage_id'], $this->web_info['is_super_admin'], $this->web_info['store_key_id'])) {
            $this->success('提取商品成功');
        } else {
            $this->error('提取商品失败');
        }
    }

    /**
     * 提取商品
     * @param $id
     */
    public function return_order($id)
    {
        if ($this->order_model->returnOrder($id, '', $this->manage_info['manage_id'], $this->web_info['is_super_admin'], $this->web_info['store_key_id'])) {
            $order_data = $this->order_model->getOrderIdField($id, '', "goods_type,goods_device");
            if (intval($order_data['data']['goods_type']) === GoodsType::$GoodsTypeElectrocar['code']) {
                $device_model = new CarDeviceTool();
                $device_ID = $order_data['data']['goods_device'];
                $out_data = $device_model->clearOrder($device_ID);
                $device_model->clearOrder($device_ID);
                $device_model->powerFailure($device_ID);
                $this->success($out_data['info']);
            }
            $this->success("归还商品成功");
        } else {
            $this->error('归还商品失败');
        }
    }

    /**
     * 其他费用设定
     * @param $id
     */
    public function rests_cost_order($id)
    {
        if ($this->request->isPost()) {
            $data = $this->request->only(['rests_cost', 'rests_cost_notes']);
            $out_data = $this->order_model->restsCostOrder($data['rests_cost'], $data['rests_cost_notes'], $id, '', $this->manage_info['manage_id'], $this->web_info['is_super_admin'], $this->web_info['store_key_id']);
            if ($out_data['code'] == 0) {
                $this->success('其他费用设定成功');
            } else {
                $this->error($out_data['info']);
            }
        }
        $this->error('参数有误');
    }

    /**
     * 确认订单
     * @param $id
     */
    public function end_order($id)
    {
        if ($this->order_model->endOrder($id, '', $this->manage_info['manage_id'], $this->web_info['is_super_admin'], $this->web_info['store_key_id'])) {
            $this->success('确认订单成功');
        } else {
            $this->error('确认订单失败');
        }
    }

    /**
     * 确认退还订单
     * @param $pay_sn
     * @throws \wxpay\WxPayException
     */
    public function refund($pay_sn)
    {
//        $out_data = $this->order_model->getOrder($id, '', $this->web_info['is_super_admin'], $this->web_info['store_key_id']);
        $order_data = $this->order_model->where(['pay_sn' => $pay_sn, 'is_refund' => 1, 'order_status' => OrderStatus::$OrderStatusFinish['code']])->field('customer_id,pay_sn,pay_amount,pd_amount,payment_code,refund_amount,is_refund,refund_time')->find();
        $refund_sn = str_replace('pay', 'refund', $pay_sn);
        if (floatval($order_data['pay_amount']) < floatval($order_data['refund_amount'])) {
            $pd_amount = floatval($order_data['refund_amount']) - floatval($order_data['pay_amount']);
            $customer_model = new CustomerModel();
            $balance = array(
                'balance' => $pd_amount,// 单位 元
                'pay_sn' => $refund_sn,// 订单号
                'customer_id' => $order_data['customer_id'],//用户id
                'remark' => "操作员后台退款;退款金额" . $pd_amount//备注信息
            );
            $customer_model->refundBalance($balance);
            if (floatval($order_data['pay_amount']) == 0) {
                $order_data->is_refund = 2;
                $order_data->refund_time = date("Y-m-d H:i:s");
                $order_data->save();
                $orderpaylog_model = new OrderPayLogModel();
                $paylog = array(
                    'pay_sn' => $refund_sn,
                    'money' => $order_data['refund_amount'],
                    'type' => 'balance',
                    'remark' => "余额 退款单号:" . $refund_sn,
                    'add_time' => date("Y-m-d H:i:s"),
                    'pay_type' => 'refund'
                );
                $orderpaylog_model->save($paylog);
                $this->success("退款申请成功");
            }
            $order_data['refund_amount'] = $order_data['pay_amount'];
        }
        //退款订单
        $input = new WxPayRefund();
        $input->setOutTradeNo($pay_sn);
        $input->setOutRefundNo($refund_sn);
        $input->setTotalFee($order_data['pay_amount'] * 100);
        $input->setRefundFee($order_data['refund_amount'] * 100);
        $input->setOpUserId("1501465161@1501465161");
        $order = WxPayApi::refund($input);
        if ($order['return_code'] == 'FAIL') {
            $this->error($order['return_msg']);
        } else if ($order['return_code'] == "SUCCESS" && $order['return_code'] == "SUCCESS") {
            $paylog = array(
                'pay_sn' => $refund_sn,
                'money' => $order_data['refund_amount'],
                'type' => $order_data['payment_code'],
                'remark' => "微信退款单号:" . $refund_sn,
                'add_time' => date("Y-m-d H:i:s"),
                'pay_type' => 'refund',
            );
            $order_data->is_refund = 2;
            $order_data->refund_time = date("Y-m-d H:i:s");
            $order_data->save();
            $orderpaylog_model = new OrderPayLogModel();
            $orderpaylog_model->save($paylog);
            $this->success("退款申请成功");
        }
        $this->error('退款申请失败');
    }

    /**
     * 申请退还订单
     * @param $pay_sn
     * @param $refund_amount
     */
    public function update_refund($pay_sn, $refund_amount)
    {
        $out_data = $this->order_model->updateRefund($pay_sn, floatval($refund_amount));
        if (empty($out_data['code'])) {
            $this->success($out_data['info']);
        }
        $this->error($out_data['info']);
    }

    /**
     * 删除订单管理
     * @param $id
     */
    public function delete($id)
    {
        $this->error('暂时不提供次方法');

        if ($this->order_model->destroy($id)) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }
}