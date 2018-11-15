<?php

namespace app\common\model;

/**
 * 订单评论 数据模型
 */

use definition\OrderStatus;
use think\Model;

class OrderComment extends Model
{
    /**
     * 获取评论信息
     * @param $map
     * @param string $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getComment($map, $field = '')
    {
        $out_data = array(
            'code' => 100,
            'info' => "参数有误"
        );
        if (empty($map)) {
            return $out_data;
        }

        if (!empty($field)) {
            $comment = $this->where($map)->field($field)->find();
        } else {
            $comment = $this->where($map)->find();
        }
        if (empty($comment)) {
            $out_data['code'] = 101;
            $out_data['info'] = "数据获取失败";
        }
        $this->formatx($comment);
        $out_data['data'] = $comment;
        $out_data['code'] = 0;
        $out_data['info'] = "获取成功";
        return $out_data;
    }

    /**
     * 格式化
     * @param $data
     */
    public function formatx(&$data)
    {
        if (!empty($data['tag'])) {
            $data['tag'] = unserialize($data['tag']);
        } else {
            $data['tag'] = [];
        }
        if (!empty($data['content'])) {
            $data['content'] = htmlspecialchars_decode($data['content']);
        }
        if (empty($data['is_hide'])) {
            $data['is_hide_str'] = "匿名";
        } else {
            $data['is_hide_str'] = "不匿名";
        }
    }

    /**
     * 添加评论
     * @param $comment
     * @return array
     */
    public function addComment($comment)
    {
        $out_data = [
            'code' => 100,
            'info' => "订单信息有误"
        ];
        if (empty($comment['order_id'])) {
            return $out_data;
        }
        if (empty($comment['customer_id'])) {
            $out_data['code'] = 101;
            $out_data['info'] = "用户信息有误";
            return $out_data;
        }
        $order_model = new Order();
        $map['id'] = $comment['order_id'];
        $map['customer_id'] = $comment['customer_id'];
        $map['order_status'] = OrderStatus::$OrderStatusFinish['code'];
        $map['evaluation_status'] = 0;
        $order_data = $order_model->where($map)->field('id,customer_phone,customer_name,goods_licence_plate')->find();
        if (empty($order_data)) {
            return $out_data;
        }
        if (empty($comment['tag'])) {
            $comment['tag'] = array();
        }
        $tag = "";
        foreach ($comment['tag'] as $value) {
            $tag .= $value . "、";
        }
        $in_comment = [
            'order_id' => $comment['order_id'],
            'customer_id' => $comment['customer_id'],
            'star_level' => $comment['star_level'],
            'tag' => serialize($comment['tag']),
            'content' => htmlspecialchars($comment['content']),
            'voice' => $comment['voice'],
            'is_hide' => $comment['is_hide'],
            'creat_time' => date("Y-m-d H:i:s"),
            'update_time' => date("Y-m-d H:i:s")
        ];
        $ret = $this->save($in_comment);
        if ($ret !== false) {
            $order_model->where($map)->setField('evaluation_status', 1);
            if (!empty($comment['content'])) {
                $sms_data = [
                    'user' => ":" . $order_data['customer_name'] . "(电话:" . $order_data['customer_phone'] . " 车牌:" . $order_data['goods_licence_plate'] . ")。",
                    'time' => date("Y-m-d H:i:s"),
                    'level' => $comment['star_level'],
                    'tag' => $tag,
                    'content' => $comment['content']
                ];
                send_manage_order_sms("13765004439", $sms_data);
                send_manage_order_sms("15585152100", $sms_data);
            }
            $out_data['code'] = 0;
            $out_data['info'] = '评价成功';
            return $out_data;
        }
        $out_data['code'] = 101;
        $out_data['info'] = '评价成功';
        return $out_data;
    }

    /**
     * 跟新评论
     * @param $comment
     * @return array|false|\PDOStatement|string|Model
     */
    public function updateComment($comment)
    {
        $out_data = [
            'code' => 100,
            'info' => "订单信息有误"
        ];
        if (empty($comment['order_id'])) {
            return $out_data;
        }
        if (empty($comment['customer_id'])) {
            $out_data['code'] = 101;
            $out_data['info'] = "用户信息有误";
            return $out_data;
        }
        $order_model = new Order();
        $map['id'] = $comment['order_id'];
        $map['order_status'] = OrderStatus::$OrderStatusFinish['code'];
        $map['customer_id'] = $comment['customer_id'];
        $map['evaluation_status'] = 1;
        $order_data = $order_model->where($map)->field('id')->find();
        if (empty($order_data)) {
            return $out_data;
        }
        $in_comment = [
            'star_level' => $comment['star_level'],
            'tag' => serialize($comment['tag']),
            'content' => htmlspecialchars($comment['content']),
            'voice' => $comment['voice'],
            'is_hide' => $comment['is_hide'],
            'update_time' => date("Y-m-d H:i:s")
        ];
        $ret = $this->save($in_comment, ['id' => $comment['id']]);
        if ($ret !== false) {
            $order_data = $order_model->where($map)->setField('evaluation_status', 2);
            $out_data['code'] = 0;
            $out_data['info'] = '修改评价成功';
            return $order_data;
        }
        $out_data['code'] = 101;
        $out_data['info'] = '修改评价成功';
        return $order_data;
    }

    /**
     * 删除评论
     * @param $comment
     * @return array
     */
    public function delComment($comment)
    {
        $out_data = [
            'code' => 100,
            'info' => "参数有误"
        ];
        if (empty($comment['id'])) {
            return $out_data;
        }
        if (empty($comment['customer_id'])) {
            $out_data['code'] = 101;
            $out_data['info'] = "用户信息有误";
            return $out_data;
        }
        $map = [
            'id' => $comment['id'],
            'customer_id' => $comment['customer_id'],
        ];
        $ret = $this->where($map)->delete();
        if ($ret !== false) {
            $out_data = [
                'code' => 0,
                'info' => "删除成功"
            ];
            return $out_data;
        }
        $out_data = [
            'code' => 102,
            'info' => "删除失败"
        ];
        return $out_data;
    }
}