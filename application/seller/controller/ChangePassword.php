<?php

namespace app\seller\controller;

use app\common\controller\SellerAdminBase;
use think\Db;
use think\Session;

/**
 * 修改密码(登录密码与支付密码)
 * Class ChangePassword
 * @package app\manage\controller
 */
class ChangePassword extends SellerAdminBase
{
    /**
     * 修改密码
     * @return mixed
     */
    public function index()
    {
        return $this->fetch('system/change_password');
    }

    /**
     * 更新密码
     */
    public function updatePassword()
    {
        if ($this->request->isPost()) {
            $seller_id = $this->seller_info['seller_id'];
            $data = $this->request->post();
            if ($data['confirm_password'] == $data['password']) {
                $res = $this->seller_model->updateSellerLoginPassword($seller_id, $data['password'], $data['old_password'], true);
                if ($res !== false) {
                    Session::clear();
                    $this->success('修改成功','seller/index/index');
                } else {
                    $this->error('修改失败');
                }
            } else {
                $this->error('两次密码输入不一致');
            }
        }
    }
}