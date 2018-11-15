<?php

namespace app\common\model;

use app\common\model\Customer as CustomerModel;
use think\Config;
use think\Model;

class CustomerChannel extends Model
{
    private $customer_model;//用户模型
    protected $insert = ['add_time'];

    public function __construct($data = [])
    {
        parent::__construct($data);
        $this->customer_model = new CustomerModel();
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
     * 格式化数据
     * @param $data
     */
    public function formatx(&$data)
    {
        $customer_data = $this->customer_model->where(array('id' => $data['customer_id']))->field('customer_nickname,wechar_nickname')->find();
        $data['customer_nickname'] = $customer_data['customer_nickname'];
        $data['wechar_nickname'] = $customer_data['wechar_nickname'];
        if (!empty($data['address'])) {
            $data['address'] = unserialize($data['address']);
        }
        $data['wechar_nickname'] = $customer_data['wechar_nickname'];
        $condition = Config::get('ChannelCondition');
        $data['condition_str'] = $condition[intval($data['condition'])]['name'];
        $status_str = "未启用";
        switch (intval($data['status'])) {
            case 0:
                $status_str = "未启用";
                break;
            case 1:
                $status_str = "正常";
                break;
            case 2:
                $status_str = "锁定";
                break;
        }

        $data['status_str'] = $status_str;
    }

    /**
     *获取渠道信息
     * @param $customer_id
     * @return array|false|\PDOStatement|string|Model
     */
    public function getCustomerChannel($customer_id)
    {
        $customer_channel_data = $this->where(array('customer_id' => $customer_id))->find();
        if (empty($customer_channel_data)) {
            $out_data['code'] = 100;
            $out_data['info'] = "数据有误";
            return $out_data;
        }
        $this->formatx($customer_channel_data);
        $out_data['code'] = 0;
        $out_data['data'] = $customer_channel_data;
        $out_data['info'] = "获取成功";
        return $out_data;
    }

    /**
     *获取渠道信息
     * @param $id
     * @return array|false|\PDOStatement|string|Model
     */
    public function getChannel($id)
    {
        $customer_channel_data = $this->where(array('id' => $id))->find();
        if (empty($customer_channel_data)) {
            $out_data['code'] = 100;
            $out_data['info'] = "数据有误";
            return $out_data;
        }
        $this->formatx($customer_channel_data);
        $out_data['code'] = 0;
        $out_data['data'] = $customer_channel_data;
        $out_data['info'] = "获取成功";
        return $out_data;
    }

    /**
     * 获取列表（有页码）
     * @param array $map
     * @param string $order
     * @param int $page_config
     * @param int $limit
     * @return \think\Paginator
     */
    public function getPageList($map = array(), $order = '', $page_config, $limit = 8)
    {
        $order_list = $this->where($map)->order($order)->paginate($limit, false, $page_config);
        if (!empty($order_list)) {
            foreach ($order_list as &$value) {
                $this->formatx($value);
            }
        }
        return $order_list;
    }

    /**
     * 获取列表
     * @param $map
     * @param $order
     * @param $page
     * @param $limit
     * @return false|\PDOStatement|string|\think\Collection
     */
    public function getList($map, $order, $page, $limit)
    {
        $device_list = $this->where($map)->order($order)->limit($page * $limit, $limit)->select();
        if (!empty($device_list)) {
            foreach ($device_list as &$value) {
                $this->formatx($value);
            }
        }
        return $device_list;
    }

    /**
     * 添加渠道
     * @param $channel
     * @return array
     */
    public function addChannel($channel)
    {
        $out_data = array(
            'code' => 111,
            'info' => "必填参数有误"
        );
        if (empty($channel['customer_id']) || empty($channel['name'])) {
            return $out_data;
        }
        if (!empty($channel['address'])) {
            if (strpos($channel['address'], "|") > 1) {
                $address = explode("|", $channel['address']);
                $channel['address'] = serialize($address);
            } else {
                $address = [$channel['address']];
                $channel['address'] = serialize($address);
            }
        }
        $channel['add_time'] = date("Y-m-d H:i:s");
        $channel['update_time'] = date("Y-m-d H:i:s");
        if ($this->save($channel)) {
            $out_data['code'] = 0;
            $out_data['info'] = "添加成功";
            return $out_data;
        }
        $out_data['code'] = 120;
        $out_data['info'] = "添加失败";
        return $out_data;
    }

    /**
     * 更新渠道
     * @param $channel
     * @return array
     */
    public function updateChannel($channel)
    {
        $out_data = array(
            'code' => 111,
            'info' => "必填参数有误"
        );
        if (empty($channel['id']) || empty($channel['customer_id']) || empty($channel['name'])) {
            return $out_data;
        }
        if (!empty($channel['address'])) {
            if (strpos($channel['address'], "|") > 1) {
                $address = explode('|', $channel['address']);
                $channel['address'] = serialize($address);
            } else {
                $address = [$channel['address']];
                $channel['address'] = serialize($address);
            }
        }
        $channel['update_time'] = date("Y-m-d H:i:s");
        if ($this->save($channel, $channel['id'])) {
            $out_data['code'] = 0;
            $out_data['info'] = "修改成功";
            return $out_data;
        }
        $out_data['code'] = 100;
        $out_data['info'] = "修改失败";
        return $out_data;
    }

    /**
     * 删除渠道
     * @param $channel_id
     * @return mixed
     */
    public function deleteChannel($channel_id)
    {
        if (!empty($channel_id)) {
            if ($this->where(array('id' => $channel_id))->delete()) {
                $out_data['code'] = 0;
                $out_data['info'] = "删除成功";
                return $out_data;
            }
        }
        $out_data['code'] = 100;
        $out_data['info'] = "删除失败";
        return $out_data;
    }

    /**
     * 锁定渠道
     * @param $id
     * @return mixed
     */
    public function addLockChannel($id)
    {
        $channel['update_time'] = date("Y-m-d H:i:s");
        $channel['status'] = 2;
        if ($this->save($channel, $id)) {
            $out_data['code'] = 0;
            $out_data['info'] = "锁定成功";
            return $out_data;
        }
        $out_data['code'] = 100;
        $out_data['info'] = "锁定失败";
        return $out_data;
    }

    /**
     *解除渠道
     * @param $id
     * @return mixed
     */
    public function delLockChannel($id)
    {
        $channel['update_time'] = date("Y-m-d H:i:s");
        $channel['status'] = 1;
        if ($this->save($channel, $id)) {
            $out_data['code'] = 0;
            $out_data['info'] = "解除成功";
            return $out_data;
        }
        $out_data['code'] = 100;
        $out_data['info'] = "解除失败";
        return $out_data;
    }
}