<?php

namespace app\common\model;

use think\Model;

/**
 * 打款数据记录
 * Class Lecturer
 * @package app\common\model
 */
class Remittance extends Model
{
    /**
     * 添加打款记录
     * @param $settlement_data
     * @return bool
     */
    public function addSettlement($settlement_data)
    {
        $datetime = date('Y-m-d H:i:s');
        $in_settlement_data = array(
            'uid' => $settlement_data['uid'],
            'store_name' => $settlement_data['store_name'],
            'status' => 0,
            'money' => $settlement_data['money'],
            'order_amount' => $settlement_data['order_amount'],
            'settlement_time' => $settlement_data['settlement_time'],
            'create_time' => $datetime,
            'update_time' => $datetime
        );
        if ($this->save($in_settlement_data)) {
            return true;
        }
        return false;
    }
    /**
     * 审核失败 备注问题
     * @param $settlement_data
     * @return bool
     */
    public function notSettlement($settlement_data)
    {
        $id = $settlement_data['id'];
        $datetime = date('Y-m-d H:i:s');
        $up_settlement_data = array(
            'status' => 1,
            'check_notes' =>$settlement_data['check_notes'],
            'update_time' => $datetime
        );
        if ($this->save($up_settlement_data,array('id'=>$id))) {
            return true;
        }
        return false;
    }

    /**
     * 确定打款
     * @param $settlement_data
     * @return bool
     */
    public function okSettlement($settlement_data)
    {
        $id = $settlement_data['id'];
        $datetime = date('Y-m-d H:i:s');
        $up_settlement_data = array(
            'status' => 2,
            'remittance_name' => $settlement_data['remittance_name'],
            'remittance_account' => $settlement_data['remittance_account'],
            'remittance_money' =>$settlement_data['remittance_money'],
            'check_notes' =>$settlement_data['check_notes'],
            'remittance_time' => $datetime,
            'update_time' => $datetime
        );
        if ($this->save($up_settlement_data,array('id'=>$id))) {
            return true;
        }
        return false;
    }
}