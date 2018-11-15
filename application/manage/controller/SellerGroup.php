<?php

namespace app\manage\controller;

use app\common\model\SellerGroup as SellerGroupModel;
use app\common\model\AuthRule as AuthRuleModel;
use app\common\controller\AdminBase;
use think\Session;

/**
 * 商户权限组
 * Class AuthGroup
 * @package app\manage\controller
 */
class SellerGroup extends AdminBase
{
    protected $seller_group_model;
    protected $auth_rule_model;

    protected function _initialize()
    {
        parent::_initialize();
        $this->seller_group_model = new SellerGroupModel();
        $this->auth_rule_model = new AuthRuleModel();
    }

    /**
     * 权限组
     * @return mixed
     */
    public function index()
    {
        $map = array(
            'store_key_id' => $this->web_info['store_key_id']
        );
        $seller_group_list = $this->seller_group_model->where($map)->select();
        return $this->fetch('index', ['seller_group_list' => $seller_group_list]);
    }

    /**
     * 添加权限组
     * @return mixed
     */
    public function add()
    {
        return $this->fetch();
    }

    /**
     * 保存权限组
     */
    public function save()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $data['store_key_id'] = $this->web_info['store_key_id'];
            if ($this->seller_group_model->save($data) !== false) {
                $this->success('保存成功');
            } else {
                $this->error('保存失败');
            }
        }
    }

    /**
     * 编辑权限组
     * @param $id
     * @return mixed
     */
    public function edit($id)
    {
        $map['store_key_id'] = $this->web_info['store_key_id'];
        $map['id'] = $id;
        $seller_group = $this->seller_group_model->where($map)->find();
        return $this->fetch('edit', ['seller_group' => $seller_group]);
    }

    /**
     * 更新权限组
     * @param $id
     */
    public function update($id)
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            if ($id == 1 && $data['status'] != 1) {
                $this->error('超级管理组不可禁用');
            }
            $map['store_key_id'] = $this->web_info['store_key_id'];
            $map['id'] = $id;
            if ($this->seller_group_model->save($data, $map) !== false) {
                $this->success('更新成功');
            } else {
                $this->error('更新失败');
            }
        }
    }

    /**
     * 删除权限组
     * @param $id
     */
    public function delete($id)
    {
        if ($id == 1) {
            $this->error('超级管理组不可删除');
        }
        $map['store_id'] = $this->web_info['store_id'];
        $map['id'] = $id;
        if ($this->seller_group_model->destroy($map)) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }

    /**
     * 授权
     * @param $id
     * @return mixed
     */
    public function auth($id)
    {
        return $this->fetch('auth', ['id' => $id]);
    }

    /**
     * AJAX获取规则数据
     * @param $id
     * @return mixed
     */
    public function getJson($id)
    {
        $map['store_key_id'] = $this->seller_info['store_key_id'];
        $map['id'] = $id;
        $seller_group_data = $this->seller_group_model->where($map)->find()->toArray();
        $auth_rules = explode(',', $seller_group_data['rules']);
        $auth_rule_list = $this->auth_rule_model->where(array('is_admin' => 0))->field('id,pid,title')->select();
        foreach ($auth_rule_list as $key => $value) {
            in_array($value['id'], $auth_rules) && $auth_rule_list[$key]['checked'] = true;
        }
        return $auth_rule_list;
    }

    /**
     * 更新权限组规则
     * @param $id
     * @param $auth_rule_ids
     */
    public function updateAuthGroupRule($id, $auth_rule_ids = '')
    {
        if ($this->request->isPost()) {
            if ($id) {
                $map['store_key_id'] = $this->seller_info['store_key_id'];
                $map['id'] = $id;
                $group_data['id'] = $id;
                $group_data['rules'] = is_array($auth_rule_ids) ? implode(',', $auth_rule_ids) : '';
                if ($this->seller_group_model->save($group_data, $map) !== false) {
                    $this->success('授权成功');
                } else {
                    $this->error('授权失败');
                }
            }
        }
    }
}