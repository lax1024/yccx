<?php

namespace app\manage\controller;

use think\Config;
use think\Controller;
use think\Db;
use think\Session;

/**
 * 后台登录
 * Class Login
 * @package app\manage\controller
 */
class Login extends Controller
{
    /**
     * 后台登录
     * @return mixed
     */
    public function index()
    {
        return $this->fetch();
    }

    /**
     * 登录验证
     * @return string
     */
    public function login()
    {
        if ($this->request->isPost()) {
            $data = $this->request->only(['username', 'password', 'verify']);
            $validate_result = $this->validate($data, 'Login');
            if ($validate_result !== true) {
                $this->error($validate_result);
            } else {
                $where['username'] = $data['username'];
                $where['password'] = md5(md5($data['password']));
                $admin_user = Db::name('admin_user')->field('id,username,status')->where($where)->find();
                if (!empty($admin_user)) {
                    if ($admin_user['status'] != 1) {
                        $this->error('当前用户已禁用');
                    } else {
                        Session::set('manage_id', $admin_user['id']);
                        Session::set('manage_name', $admin_user['username']);
                        Db::name('admin_user')->update(
                            [
                                'last_login_time' => date('Y-m-d H:i:s', time()),
                                'last_login_ip' => $this->request->ip(),
                                'id' => $admin_user['id']
                            ]
                        );
                        Db::name('admin_log')->insert(
                            [
                                'create_time' => date('Y-m-d H:i:s', time()),
                                'visit_ip' => $this->request->ip(),
                                'admin_id' => $admin_user['id'],
                                'status' => 1,
                                'admin_name' => $admin_user['username']
                            ]
                        );
                        $this->success('登录成功', 'manage/index/index');
                    }
                } else {
                    $this->error('用户名或密码错误');
                }
            }
        }
    }

    /**
     * 退出登录
     */
    public function logout()
    {
        Session::delete('manage_id');
        Session::delete('manage_name');
        $this->success('退出成功', 'manage/login/index');
    }
}
