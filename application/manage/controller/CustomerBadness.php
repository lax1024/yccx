<?php

namespace app\manage\controller;

use app\common\controller\AdminBase;
use app\common\model\CustomerBadness as CustomerBadnessModel;
use definition\CustomerBadnessType;

/**
 * 不良记录管理
 * Class AdminUser
 * @package app\manage\controller
 */
class CustomerBadness extends AdminBase
{
    protected $customer_badness_model;

    protected function _initialize()
    {
        parent::_initialize();
        $this->customer_badness_model = new CustomerBadnessModel();
    }

    /**
     * 不良记录管理
     * @param string $keyword
     * @param int $page
     * @return mixed
     */
    public function index($keyword = '', $page = 1)
    {
        $map = [];
        if ($keyword) {
            $map['customer_name|customer_phone|badness_notes'] = ['like', "%{$keyword}%"];
        }
        $page_config = ['page' => $page, 'query' => ['keyword' => $keyword]];
        $customer_badness_list = $this->customer_badness_model->getPagelist($map, $order = 'id DESC', $page_config, 15);
        return $this->fetch('index', ['customer_badness_list' => $customer_badness_list, 'keyword' => $keyword]);
    }

    /**
     * 添加不良记录
     * @return mixed
     */
    public function add()
    {
        $type_list = CustomerBadnessType::$CUSTOMERSTATUS_CODE;
        return $this->fetch('add', ['type_list' => $type_list]);
    }

    /**
     * 保存不良记录信息
     */
    public function save()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
//            * customer_phone 用户电话
//            * badness_notes 不良记录
//            * badness_img 不良记录图片
//            * type 不良记录类型
//            * admin_id 操作管理员id
//            * admin_name 操作管理员账号
            $in_badness_data = array(
                'customer_phone' => $data['customer_phone'],
                'badness_notes' => $data['badness_notes'],
                'badness_img' => $data['badness_img'],
                'type' => $data['type'],
                'admin_id' => $this->manage_info['manage_id'],
                'admin_name' => $this->manage_info['manage_name']
            );
            $out_data = $this->customer_badness_model->addCustomerBadness($in_badness_data);
            if ($out_data['code'] == 0) {
                $this->success('保存成功');
            } else {
                $this->error($out_data['info']);
            }
        }
    }

    /**
     * 编辑不良记录信息
     * @param $id
     * @return mixed
     */
    public function edit($id)
    {
        $badness_data = $this->customer_badness_model->getCustomerBadness($id, '', true);
        if ($badness_data['code'] == 0) {
            $type_list = CustomerBadnessType::$CUSTOMERSTATUS_CODE;
            return $this->fetch('edit', ['badness_data' => $badness_data['data'], 'type_list' => $type_list]);
        } else {
            $this->error($badness_data['info']);
        }
    }

    /**
     * 更新不良记录信息
     * @param $id
     */
    public function update($id)
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
//            * customer_phone 用户电话
//            * badness_notes 不良记录
//            * badness_img 不良记录图片
//            * type 不良记录类型
//            * admin_id 操作管理员id
//            * admin_name 操作管理员账号
            $update_badness_data = array(
                'id' => $id,
                'customer_phone' => $data['customer_phone'],
                'badness_notes' => $data['badness_notes'],
                'badness_img' => $data['badness_img'],
                'type' => $data['type'],
                'admin_id' => $this->manage_info['manage_id'],
                'admin_name' => $this->manage_info['manage_name']
            );
            $out_data = $this->customer_badness_model->updateCustomerBadness($update_badness_data);
            if ($out_data['code'] === 0) {
                $this->success('更新成功');
            } else {
                $this->error($out_data['info']);
            }
        }
    }

    /**
     * 删除不良记录
     * @param $id
     */
    public function delete($id)
    {
        $ret = $this->customer_badness_model->where(['id' => $id])->delete();
        if ($ret !== false) {
            $this->success('删除成功');
        }
        $this->success('删除失败');
    }
}