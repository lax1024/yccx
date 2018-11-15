<?php

namespace app\common\model;

/**
 * 订单拓展信息表 数据模型
 */

use definition\PayCode;
use think\Model;
use tool\CarDeviceTool;

class OrderExpand extends Model
{

    public function formatx(&$data)
    {
        if (!empty($data['path'])) {
            $data['path'] = unserialize($data['path']);
        }
    }

    /**
     * 添加订单拓展信息
     * @param $order_id
     * @param $information
     * @param $goods
     * @param $acquire_picture
     * @param $return_picture
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function addExpand($order_id, $information, $goods, $acquire_picture, $return_picture = '')
    {
        $out_data = [
            'code' => 100,
            'info' => "参数有误"
        ];
        if (empty($order_id) || empty($information) || empty($goods) || empty($acquire_picture)) {
            return $out_data;
        }
        if (empty($return_picture)) {
            $return_picture = [];
        }
        $in_data = [
            'order_id' => $order_id,
            'customer_information' => serialize($information),
            'order_goods' => serialize($goods),
            'acquire_picture' => serialize($acquire_picture),
            'return_picture' => serialize($return_picture),
        ];
        $data_has = $this->where(['order_id' => $order_id])->find();
        if (empty($data_has)) {
            $ret = $this->save($in_data);
            if ($ret !== false) {
                $out_data['code'] = 0;
                $out_data['info'] = "添加成功";
                return $out_data;
            }
        } else {
            $ret = $this->save($in_data, ['id' => $data_has['id']]);
            if ($ret !== false) {
                $out_data['code'] = 0;
                $out_data['info'] = "添加成功";
                return $out_data;
            }
        }
        $out_data['code'] = 101;
        $out_data['info'] = "添加失败";
        return $out_data;
    }

    /**
     * 更新还车图片
     * @param $order_id
     * @param $return_picture
     * @return array
     */
    public function updateExpand($order_id, $return_picture)
    {
        $out_data = [
            'code' => 100,
            'info' => "参数有误"
        ];
        if (empty($order_id) || empty($return_picture)) {
            return $out_data;
        }
        $up_data = [
            'return_picture' => serialize($return_picture)
        ];
        $this->where(['order_id' => $order_id])->setField($up_data);
        $out_data['code'] = 0;
        $out_data['info'] = "更新成功";
        return $out_data;
    }

    /**
     * 获取订单轨迹
     * @param $order_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getExpand($order_id)
    {
        $out_data = [
            'code' => 100,
            'info' => "参数有误"
        ];

        if (empty($order_id)) {
            return $out_data;
        }
        $out_order = $this->where(['order_id' => $order_id])->find();
        if (!empty($out_order)) {
            if (!empty($out_order['customer_information'])) {
                $out_order['customer_information'] = unserialize($out_order['customer_information']);
            }else{
                $out_order['customer_information'] = [];
            }
            if (!empty($out_order['order_goods'])) {
                $out_order['order_goods'] = unserialize($out_order['order_goods']);
            }else{
                $out_order['order_goods'] = [];
            }
            if (!empty($out_order['acquire_picture'])) {
                $out_order['acquire_picture'] = unserialize($out_order['acquire_picture']);
            }else{
                $out_order['acquire_picture'] = [];
            }
            if (!empty($out_order['return_picture'])) {
                $out_order['return_picture'] = unserialize($out_order['return_picture']);
            }else{
                $out_order['return_picture'] = [];
            }
            $out_data['code'] = 0;
            $out_data['info'] = "获取成功";
            $out_data['data'] = $out_order;
            return $out_data;
        }
        $out_data['code'] = 101;
        $out_data['info'] = "获取失败";
        return $out_data;
    }

}