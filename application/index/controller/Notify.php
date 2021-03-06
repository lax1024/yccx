<?php

namespace app\index\controller;

use app\common\model\CustomerBalance;
use app\common\model\CustomerCash;
use definition\OrderStatus;
use definition\PayCode;
use wxpay\PayNotifyCallBack;
use app\common\model\Order as OrderModel;
use app\common\controller\HomeBase;

class Notify extends HomeBase
{
    private $order_model;

    protected function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
        $this->order_model = new OrderModel();
    }

    public function index()
    {
        exit('非法入侵');
    }

    /**
     * 支付查询回调
     * @param $pay_sn
     */
    public function query_weixin($pay_sn)
    {
//        $pay_sn = 'pay_201808080310082767574';
        if (empty($pay_sn)) {
            $dataout = array(
                'code' => 1,
                'info' => '参数有误',
            );
            exit(json_encode($dataout));
        }
        $notify = new PayNotifyCallBack();
        if ($notify->queryOrderSn($pay_sn)) {
            $orders = $ret = $this->order_model->where(array('pay_sn' => $pay_sn, 'order_status' => OrderStatus::$OrderStatusNopayment['code']))->select();
            if (empty($orders)) {
                $orders = $ret = $this->order_model->where(array('pay_sn' => $pay_sn, 'order_status' => OrderStatus::$OrderStatusReturn['code']))->select();
            }
            $total_fee = 0;
            foreach ($orders as $v) {
                $this->order_model->formatxEle($v);
                $total_fee += floatval($v['order_amount']) - floatval($v['pd_amount']);
            }
            $pay_info = array(
                'pay_sn' => $pay_sn,
                'money' => floatval($total_fee),
                'remark' => "微信支付 支付单号：" . $pay_sn,
            );
            $this->order_model->payOrder($pay_info, PayCode::$PayCodeWeixin['code']);
            $dataout = array(
                'code' => 0,
                'info' => '支付成功',
            );
            out_json_data($dataout);
        } else {
            $dataout = array(
                'code' => 1,
                'info' => '支付失败',
            );
            out_json_data($dataout);
        }
    }

    /**
     * 押金查询回调
     * @param $pay_sn
     */
    public function query_weixin_cash($pay_sn)
    {
        if (empty($pay_sn)) {
            $dataout = array(
                'code' => 1,
                'info' => '参数有误',
            );
            exit(json_encode($dataout));
        }
        $notify = new PayNotifyCallBack();
        if ($notify->queryOrderSn($pay_sn)) {
            $customer_cash = new CustomerCash();
            $cash = $customer_cash->where(['pay_sn' => $pay_sn])->field('cash')->find();
            $pay_info = array(
                'pay_sn' => $pay_sn,
                'money' => floatval($cash['cash']),
                'channel' => 2,
                'remark' => "微信支付 押金单号：" . $pay_sn,
            );
            if ($customer_cash->pay_cash($pay_info, PayCode::$PayCodeWeixin['code'])) {
//                Log::write('支付订单' . $order_info['out_trade_no'] . '状态更新成功');
                $dataout = array(
                    'code' => 0,
                    'info' => '支付成功',
                );
                out_json_data($dataout);
            }
        }
        $dataout = array(
            'code' => 1,
            'info' => '支付失败',
        );
        out_json_data($dataout);
    }

    /**
     * 余额查询回调
     * @param $pay_sn
     */
    public function query_weixin_balance($pay_sn)
    {
        if (empty($pay_sn)) {
            $dataout = array(
                'code' => 1,
                'info' => '参数有误',
            );
            exit(json_encode($dataout));
        }
        $notify = new PayNotifyCallBack();
        if ($notify->queryOrderSn($pay_sn)) {
            $customer_balance = new CustomerBalance();
            $balance = $customer_balance->where(['pay_sn' => $pay_sn])->field('balance')->find();
            $pay_info = array(
                'pay_sn' => $pay_sn,
                'money' => floatval($balance['balance']),
                'channel' => 2,
                'remark' => "微信支付 充值单号：" . $pay_sn,
            );
            if ($customer_balance->pay_balance($pay_info, PayCode::$PayCodeWeixin['code'])) {
//                Log::write('支付订单' . $order_info['out_trade_no'] . '状态更新成功');
                $dataout = array(
                    'code' => 0,
                    'info' => '支付成功',
                );
                out_json_data($dataout);
            }
        }
        $dataout = array(
            'code' => 1,
            'info' => '支付失败',
        );
        out_json_data($dataout);
    }

    /**
     * H5支付查询回调
     * @param $pay_sn
     */
    public function query_weixin_h5($pay_sn)
    {
        sleep(2);
        $notify = new PayNotifyCallBack();
        if ($notify->queryOrderSn($pay_sn)) {
            $orders = $ret = $this->order_model->where(array('pay_sn' => $pay_sn, 'order_state' => 10))->select();
            $total_fee = 0;
            foreach ($orders as $v) {
                $total_fee += floatval($v['order_amount']) - floatval($v['pd_amount']);
            }
            $pay_info = array(
                'pay_sn' => $pay_sn,
                'money' => floatval($total_fee),
                'remark' => "微信支付 支付单号：" . $pay_sn,
            );
            $this->order_model->payOrder($pay_info, PayCode::$PayCodeWeixin['code']);
        }
        $url = url('wap/index/myorder');
        echo "<script type='text/javascript'>window.location.href='$url' </script>";
    }

    /**
     * 微信支付回调
     */
    public function weixin()
    {
        $notify = new PayNotifyCallBack();
        $notify->handle(false);
        $order_info = $notify->getValues();
        $succeed = ($notify->getReturnCode() == 'SUCCESS') ? true : false;
        if ($succeed) {
            $pay_info = array(
                'pay_sn' => $order_info['out_trade_no'],
                'money' => floatval($order_info['total_fee']) / 100,
                'remark' => "微信支付 支付单号：" . $order_info['out_trade_no'],
            );
            if ($this->order_model->payOrder($pay_info, PayCode::$PayCodeWeixin['code'])) {
//                Log::write('支付订单' . $order_info['out_trade_no'] . '状态更新成功');
                $notify->clearData('out_trade_no');
                $notify->clearData('total_fee');
                $notify->toXml();
            }
        } else {
//            Log::write('支付订单' . $order_info['out_trade_no'] . '支付失败');
            echo "数据有误！";
        }
    }

    /**
     * 押金回调
     */
    public function weixincash()
    {
        $notify = new PayNotifyCallBack();
        $notify->handle(false);
        $order_info = $notify->getValues();
        $succeed = ($notify->getReturnCode() == 'SUCCESS') ? true : false;
        if ($succeed) {
            $pay_info = array(
                'pay_sn' => $order_info['out_trade_no'],
                'money' => floatval($order_info['total_fee']) / 100,
                'channel' => 2,
                'remark' => "微信支付 押金单号：" . $order_info['out_trade_no'],
            );
            $customer_cash = new CustomerCash();
            if ($customer_cash->pay_cash($pay_info, 'weixin')) {
//                Log::write('支付订单' . $order_info['out_trade_no'] . '状态更新成功');
                $notify->clearData('out_trade_no');
                $notify->clearData('total_fee');
                $notify->toXml();
            }
        } else {
//            Log::write('支付订单' . $order_info['out_trade_no'] . '支付失败');
            echo "数据有误！";
        }
    }

    /**
     * 充值回调
     */
    public function weixinbalance()
    {
        $notify = new PayNotifyCallBack();
        $notify->handle(false);
        $order_info = $notify->getValues();
        $succeed = ($notify->getReturnCode() == 'SUCCESS') ? true : false;
        if ($succeed) {
            $pay_info = array(
                'pay_sn' => $order_info['out_trade_no'],
                'money' => floatval($order_info['total_fee']) / 100,
                'remark' => "微信支付 充值单号：" . $order_info['out_trade_no'],
            );
            $customer_balance = new CustomerBalance();
            if ($customer_balance->pay_balance($pay_info, 'weixin')) {
//                Log::write('支付订单' . $order_info['out_trade_no'] . '状态更新成功');
                $notify->clearData('out_trade_no');
                $notify->clearData('total_fee');
                $notify->toXml();
            }
        } else {
//            Log::write('支付订单' . $order_info['out_trade_no'] . '支付失败');
            echo "数据有误！";
        }
    }

    /**
     * 支付宝回调
     */
    public function alipay()
    {

    }
}
