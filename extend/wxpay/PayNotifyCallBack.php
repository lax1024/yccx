<?php

namespace wxpay;

use wxpay\database\WxPayNotify;
use wxpay\database\WxPayOrderQuery;

/**
 * 支付回调对象
 *
 * Class PayNotifyCallBack
 * @package wxpay
 * @author goldeagle
 */
class PayNotifyCallBack extends WxPayNotify
{
    //查询订单
    public function queryOrderSn($out_trade_no)
    {
        $input = new WxPayOrderQuery();
        $input->setOutTradeNo($out_trade_no);
        $result = WxPayApi::orderQuery($input);
//        print_r($result);
        //Log::DEBUG("query:" . json_encode($result));
        if (array_key_exists("return_code", $result)
            && array_key_exists("result_code", $result)
            && array_key_exists("trade_state", $result)
            && $result["return_code"] == "SUCCESS"
            && $result["result_code"] == "SUCCESS"
            && $result["trade_state"] == "SUCCESS"
        ) {
            $this->setData('out_trade_no', $result['out_trade_no']);
            $this->setData('total_fee', $result['total_fee']);
            return true;
        }
        return false;
    }
    //查询订单
    public function queryOrder($transaction_id)
    {
        $input = new WxPayOrderQuery();
        $input->setTransactionId($transaction_id);
        $result = WxPayApi::orderQuery($input);
        //Log::DEBUG("query:" . json_encode($result));
        if (array_key_exists("return_code", $result)
            && array_key_exists("result_code", $result)
            && $result["return_code"] == "SUCCESS"
            && $result["result_code"] == "SUCCESS"
        ) {
            $this->setData('out_trade_no', $result['out_trade_no']);
            $this->setData('total_fee', $result['total_fee']);
            return true;
        }
        return false;
    }

    //重写回调处理函数
    public function notifyProcess($data, &$msg)
    {
        //Log::DEBUG("call back:" . json_encode($data));
        if (!array_key_exists("transaction_id", $data)) {
            $msg = "输入参数不正确";
            return false;
        }
        //查询订单，判断订单真实性
        if (!$this->queryOrder($data["transaction_id"])) {
            $msg = "订单查询失败";
            return false;
        }
        return true;
    }

    //清除数据
    public function clearData($key){
        unset($this->values[$key]);
    }
}