<?php

namespace app\common\model;

use definition\BalanceType;
use think\Config;
use think\Model;

class CustomerBalanceLog extends Model
{
    protected $insert = ['add_time'];

    /**
     * 创建时间
     * @return bool|string
     */
    protected function setCreateTimeAttr()
    {
        return time();
    }

    public function formatx(&$data)
    {
        $pay_code = BalanceType::$LOG_TYPE_CODE;
        $customer_model = new Customer();
        $customer_info = $customer_model->find($data['customer_id']);
        $data['mobile_phone'] = $customer_info['mobile_phone'];
        $data['customer_name'] = $customer_info['customer_name'];
        $data['customer_nickname'] = $customer_info['customer_nickname'];
        $data['nowbalance'] = 0;
        $data['type'] = strtoupper($data['type']);
        if ($data['type'] == 'ADD' || $data['type'] == 'REFUND') {
            $data['nowbalance'] = $data['balance'] + $data['balance_last'];
        } else {
            $data['nowbalance'] = $data['balance_last'] - $data['balance'];
        }
        $data['nowbalance'] = number_format($data['nowbalance'], 2);
        $data['type_str'] = $pay_code[$data['type']];
        $data['time_str'] = date('Y-m-d H:i:s', $data['add_time']);
    }


}