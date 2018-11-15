<?php

namespace app\common\model;

/**
 * 订单数据加密校验 数据模型
 */
use think\Model;

class OrderEncrypt extends Model
{
    /**
     * 添加/更新加密
     * @param $order_id
     * @param $order
     * @return bool
     */
    public function addEncrypt($order_id, $order)
    {
        $order_serialize = serialize($order);
        $order_md5 = md5($order_serialize);
        $order_data = $this->where(array('order_id' => $order_id))->find();
        if (empty($order_data)) {
            $in_order_md5 = array(
                'order_id' => $order_id,
                'order_md5' => $order_md5,
            );
            if ($this->save($in_order_md5)) {
                return true;
            }
            return false;
        } else {
            $order_data->order_md5 = $order_md5;
            if ($order_data->save()) {
                return true;
            }
            return false;
        }
    }

    /**
     * 验证数据是否合法
     * @param $order_id
     * @param $order
     * @return bool
     */
    public function verifyEncrypt($order_id, $order)
    {
        $order_serialize = serialize($order);
        $order_md5 = md5($order_serialize);
        $order_data = $this->where(array('order_id' => $order_id))->find();
        if (empty($order_data)) {
            return false;
        } else {
            if (trim($order_md5) === trim($order_data['order_md5'])) {
                return true;
            }
            return false;
        }
    }
}