<?php

namespace app\manage\controller;

use app\common\model\CustomerChannel as CustomerChannelModel;
use app\common\controller\AdminBase;
use think\Config;

/**
 * 客户渠道管理
 * Class AdminUser
 * @package app\manage\controller
 */
class CustomerChannel extends AdminBase
{
    protected $customer_channel_model;

    protected function _initialize()
    {
        parent::_initialize();
        $this->customer_channel_model = new CustomerChannelModel();
    }

    /**
     * 用户渠道管理
     * @param string $keyword
     * @param int $page
     * @return mixed
     */
    public function index($keyword = '', $page = 1)
    {
        $map = [];
        if (!empty($keyword)) {
            $map['customer_id|name'] = ['like', "%{$keyword}%"];
        }
        $page_config = ['page' => $page, 'query' => ['keyword' => $keyword]];
        $customer_channel_list = $this->customer_channel_model->getPageList($map, 'id DESC', $page_config, 15);
        return $this->fetch('index', ['customer_channel_list' => $customer_channel_list, 'keyword' => $keyword]);
    }

    /**
     * 添加用户渠道
     * @return mixed
     */
    public function add()
    {
        $condition = Config::get('ChannelCondition');
        return $this->fetch('add', ['condition' => $condition]);
    }

    /**
     * 保存用户渠道
     */
    public function save()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $in_customer_data = array(
                'customer_id' => $data['customer_id'],
                'name' => $data['name'],
                'address' => $data['address'],
                'condition' => $data['condition'],
                'status' => $data['status']
            );
            $out_data = $this->customer_channel_model->addChannel($in_customer_data);
            if ($out_data['code'] == 0) {
                $this->success('保存成功');
            } else {
                $this->error($out_data['info']);
            }
        }
    }

    /**
     * 编辑用户渠道
     * @param $id
     * @return mixed
     */
    public function edit($id)
    {
        if (empty($id)) {
            $this->error("参数有误");
        }
        $customer_channel_data = $this->customer_channel_model->getChannel($id);
        if ($customer_channel_data['code'] == 0) {
            $address = "";
            $condition = Config::get('ChannelCondition');
            if (!empty($customer_channel_data['data']['address'])) {
                foreach ($customer_channel_data['data']['address'] as $v) {
                    if (!empty($v)) {
                        $address .= $v . "|";
                    }
                }
                $customer_channel_data['data']['address'] = $address;
            }
            return $this->fetch('edit', ['customer_channel' => $customer_channel_data['data'], 'condition' => $condition]);
        } else {
            $this->error($customer_channel_data['info']);
        }
    }

    /**
     * 更新用户渠道
     * @param $id
     */
    public function update($id)
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $update_customer_data = array(
                'id' => $id,
                'customer_id' => $data['customer_id'],
                'name' => $data['name'],
                'address' => $data['address'],
                'condition' => $data['condition'],
                'status' => $data['status']
            );
            $out_data = $this->customer_channel_model->updateChannel($update_customer_data);
            if ($out_data['code'] == 0) {
                $this->success('更新成功');
            } else {
                $this->error($out_data['info']);
            }
        }
    }

    /**
     * 删除用户渠道
     * @param $id
     */
    public function delete($id)
    {
        $out_data = $this->customer_channel_model->deleteChannel($id);
        if ($out_data['code'] == 0) {
            $this->success('删除成功');
        }
        $this->error($out_data['info']);
    }

    /**
     * 锁定用户渠道
     * @param $id
     */
    public function lock($id)
    {
        if ($this->customer_channel_model->addLockChannel($id)) {
            $this->success('锁定成功');
        } else {
            $this->error('锁定失败');
        }
    }

    /**
     * 解除锁定用户渠道
     * @param $id
     */
    public function del_lock($id)
    {
        if ($this->customer_channel_model->delLockChannel($id)) {
            $this->success('解除锁定成功');
        } else {
            $this->error('解除锁定失败');
        }
    }

}