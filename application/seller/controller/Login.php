<?php

namespace app\seller\controller;

use think\Config;
use think\Controller;
use think\Db;
use think\Session;
use app\common\model\Seller as SellerModel;

/**
 * 商家登录
 * Class Login
 * @package app\seller\controller
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
                $seller_model = new SellerModel();
//                * seller_name 商户用户名
//                * login_password 登录密码
                $seller_data = array(
                    'seller_name' => $data['username'],
                    'seller_login_password' => $data['password']
                );
                $out_data = $seller_model->loginSeller($seller_data);
                if (intval($out_data['code']) == 0) {
                    $this->success('登录成功', 'seller/index/index');
                } else {
                    $this->error($out_data['info']);
                }
            }
        }
    }

    /**
     * 退出登录
     */
    public function logout()
    {
        Session::delete('seller_id');
        Session::delete('store_id');
        Session::delete('store_key_id');
        Session::delete('seller_name');
        Session::delete('is_admin');
        Session::delete('seller_data');
        $this->success('退出成功', 'seller/login/index');
    }
}
