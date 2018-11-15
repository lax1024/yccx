<?php

namespace app\manage\controller;

use app\common\controller\AdminBase;
use think\Config;
use think\Db;
use think\Session;

/**
 * 修改密码(登录密码与支付密码)
 * Class ChangePassword
 * @package app\manage\controller
 */
class ChangePassword extends AdminBase
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
            $manage_id = Session::get('manage_id');
            $data = $this->request->param();
            $result = Db::name('admin_user')->find($manage_id);

            if ($result['password'] == md5(md5($data['old_password']))) {
                if ($data['password'] == $data['confirm_password']) {
                    $new_password = md5(md5($data['password']));
                    $res = Db::name('admin_user')->where(['id' => $manage_id])->setField('password', $new_password);

                    if ($res !== false) {
                        $this->success('修改成功');
                    } else {
                        $this->error('修改失败');
                    }
                } else {
                    $this->error('两次密码输入不一致');
                }
            } else {
                $this->error('原密码不正确');
            }
        }
    }
}