<?php

namespace app\seller\controller;

use app\common\model\AuthRule as AuthRuleModel;
use app\common\controller\SellerAdminBase;
use think\Db;

/**
 * 后台菜单
 * Class Menu
 * @package app\seller\controller
 */
class Menu extends SellerAdminBase
{

    protected $auth_rule_model;

    protected function _initialize()
    {
        parent::_initialize();
        $this->auth_rule_model = new AuthRuleModel();
        $admin_menu_list = $this->auth_rule_model->order(['sort' => 'DESC', 'id' => 'ASC'])->select();
        $admin_menu_level_list = array2level($admin_menu_list);

        $this->assign('admin_menu_level_list', $admin_menu_level_list);
    }

    /**
     * 后台菜单
     * @return mixed
     */
    public function index()
    {
        return $this->fetch();
    }

    /**
     * 添加菜单
     * @param string $pid
     * @return mixed
     */
    public function add($pid = '')
    {
        return $this->fetch('add', ['pid' => $pid]);
    }

    /**
     * 保存菜单
     */
    public function save()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $validate_result = $this->validate($data, 'Menu');
            if ($validate_result !== true) {
                $this->error($validate_result);
            } else {
                if ($this->auth_rule_model->save($data)) {
                    $this->success('保存成功');
                } else {
                    $this->error('保存失败');
                }
            }
        }
    }

    /**
     * 编辑菜单
     * @param $id
     * @return mixed
     */
    public function edit($id)
    {
        $admin_menu = $this->auth_rule_model->find($id);
        return $this->fetch('edit', ['admin_menu' => $admin_menu]);
    }

    /**
     * 更新菜单
     * @param $id
     */
    public function update($id)
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $validate_result = $this->validate($data, 'Menu');

            if ($validate_result !== true) {
                $this->error($validate_result);
            } else {
                $auth = $this->auth_rule_model->find($id);
                if (!empty($auth)) {
                    if (intval($auth['is_admin']) != intval($data['is_admin'])) {
                        $this->auth_rule_model->update_is_admin($id, intval($data['is_admin']));
                    }
                }
                if ($this->auth_rule_model->save($data, $id) !== false) {
                    $this->success('更新成功');
                } else {
                    $this->error('更新失败');
                }
            }
        }
    }

    /**
     * 删除菜单
     * @param $id
     */
    public function delete($id)
    {
        $sub_menu = $this->auth_rule_model->where(['pid' => $id])->find();
        if (!empty($sub_menu)) {
            $this->error('此菜单下存在子菜单，不可删除');
        }
        if ($this->auth_rule_model->destroy($id)) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }
}