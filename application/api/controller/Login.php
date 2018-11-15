<?php

namespace app\api\controller;

use app\common\controller\HomeBase;
use baidu\baiduSmsClient;
use think\Config;
use app\common\model\Customer as CustomerModel;
use app\common\model\Seller as SellerModel;
use think\Request;
use think\Session;

/**
 * 用户登录注册接口
 * Class Ueditor
 * @package app\api\controller
 */
class Login extends HomeBase
{
    private $customer_model;
    private $seller_model;

    protected function _initialize()
    {
        parent::_initialize();
        $this->customer_model = new CustomerModel();
        $this->seller_model = new SellerModel();
    }

    /**
     * 注册普通客户
     */
    public function register()
    {
        $dataout = array(
            'code' => 1,
            'info' => '参数有误',
        );
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $out_verify = $this->_verify($data['code'], $data['mobile']);
            if ($out_verify['code'] == 0) {
                //构造注册用时的基本数据
                $customer_data = array(
                    'mobile_phone' => $data['mobile'],
                    'login_password' => $data['password'],
                    'customer_name' => $data['customer_name'],
                    'customer_nickname' => $data['customer_nickname'],
                    'customer_sex' => $data['customer_sex'],
                    'region_id' => $data['region_id'],
                    'address' => $data['address'],
                    'id_number' => $data['id_number'],
                    'email' => $data['email'],
                    'regist_type' => $data['type'],
                );
                $register_out_data = $this->customer_model->addCustomer($customer_data);
                if ($register_out_data['code'] == 0) {
                    $login_out_data = $this->customer_model->loginCustomer($customer_data);
                    out_json_data($login_out_data);
                } else {
                    out_json_data($register_out_data);
                }
            }
            out_json_data($out_verify);
        }
        out_json_data($dataout);
    }

    /**
     * 注册商户
     */
    public function register_seller()
    {
        $dataout = array(
            'code' => 1,
            'info' => '参数有误',
            'url' => url('seller/index/index')
        );
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $out_verify = $this->_verify($data['code'], $data['mobile']);
            if ($out_verify['code'] == 0) {
                //构造注册用时的基本数据
                $seller_data = array(
                    'seller_name' => $data['seller_name'],
                    'seller_mobile' => $data['mobile'],
                    'seller_login_password' => $data['password'],
                    'seller_group_id' => 1,
                    'is_admin' => 1,
                    'parent_id' => 0,
                    'seller_status' => 0
                );
                $register_out_data = $this->seller_model->addSeller($seller_data);
                if ($register_out_data['code'] == 0) {
                    $login_out_data = $this->seller_model->loginSeller($seller_data);
                    $login_out_data['url'] = url('index/index/subjoin');
                    if ($login_out_data['code'] == 0) {
                        $login_out_data['info'] = '注册成功';
                    }
                    out_json_data($login_out_data);
                } else {
                    $register_out_data['url'] = url('seller/index/index');
                    out_json_data($register_out_data);
                }
            }
            $out_verify['url'] = url('seller/index/index');
            out_json_data($out_verify);
        }
        out_json_data($dataout);
    }

    /**
     * 重置密码
     */
    public function reset_login_password()
    {
        $dataout = array(
            'code' => 1,
            'info' => '参数有误',
        );
        if ($this->request->isPost()) {
            $data = $this->request->only(['mobile', 'password', 'code', 'session_id']);
            if (!empty($data['session_id'])) {
                Session::set_session_id($data['session_id']);
            }
            $out_verify = $this->_verify($data['code'], $data['mobile']);
            if ($out_verify['code'] == 0) {
                $get_out_data = $this->customer_model->getCustomer($data['mobile'], '');
                if ($get_out_data['code'] == 0) {
                    $customer_data = $get_out_data['data'];
                    if ($this->customer_model->updateCustomerLoginPassword($customer_data['id'], $data['password'])) {
                        $customer_data = array();
                        $customer_data['mobile_phone'] = $data['mobile'];
                        $customer_data['login_password'] = $data['password'];
                        $login_out_data = $this->customer_model->loginCustomer($customer_data);
                        out_json_data($login_out_data);
                    } else {
                        $dataout['code'] = 2;
                        $dataout['info'] = "密码重置失败";
                    }
                    out_json_data($dataout);
                } else {
                    out_json_data($get_out_data);
                }
            }
            out_json_data($out_verify);
        }
        out_json_data($dataout);
    }

    /**
     * 重置商户密码
     */
    public function reset_seller_login_password()
    {
        $dataout = array(
            'code' => 1,
            'info' => '参数有误',
        );
        if ($this->request->isPost()) {
            $data = $this->request->only(['mobile', 'seller_name', 'password']);
            $out_verify = $this->_verify($data['code'], $data['mobile']);
            if ($out_verify['code'] == 0) {
                $get_out_data = $this->getSeller('', $data['seller_name'], true);
                if ($get_out_data['code'] == 0) {
                    $seller_data = $get_out_data['data'];
                    if ($this->customer_model->updateSellerLoginPassword($seller_data['id'], $data['password'])) {
                        $dataout['code'] = 0;
                        $dataout['info'] = "密码重置成功";
                    } else {
                        $dataout['code'] = 2;
                        $dataout['info'] = "密码重置失败";
                    }
                    out_json_data($dataout);
                } else {
                    out_json_data($get_out_data);
                }
            }
            out_json_data($out_verify);
        }
        out_json_data($dataout);
    }

    /**
     * 验证短信验证码
     * @param $code_v 短信验证码
     * @param $mobile_v 验证的手机号码
     * @return array
     * 1006 验证码已过期
     * 1007 验证次数太多，请重新获取
     * 1008 验证码不正确
     */
    private function _verify($code_v, $mobile_v)
    {
        $dataout = array(
            'code' => 1001,
            'info' => '参数有误'
        );
        if (empty($code_v) || empty($mobile_v)) {
            return $dataout;
        }
        if (!check_mobile_number($mobile_v)) {
            $dataout['code'] = 1002;
            $dataout['info'] = '手机号码格式不正确';
            return $dataout;
        }
        $mobile = Session::get('mobile');//手机号码
        $code = Session::get('verify_code');//验证码
        $sum_v = Session::get('sum_v');//验证次数 /获取验证码时 为0
        $time = Session::get('time');//获取验证码 的时间
        if (empty($mobile) || empty($code) || empty($time)) {
            $dataout['code'] = 1009;
            $dataout['data'] = Request::instance();
            $dataout['info'] = '非法验证';
            return $dataout;
        }
        if (time() - $time > 600) {
            Session::set('mobile', '');//手机号码
            Session::set('verify_code', '');//验证码
            Session::set('sum_v', 0);//验证次数 /获取验证码时 为0
            $dataout['code'] = 1006;
            $dataout['info'] = '验证码已过期';
            return $dataout;
        }
        $sum_v++;
        Session::set('sum_v', $sum_v);//增加一次验证
        if ($mobile == $mobile_v && $code_v == $code && $sum_v < 5) {
            Session::set('mobile', '');//手机号码
            Session::set('verify_code', '');//验证码
            Session::set('sum_v', 0);//验证次数 /获取验证码时 为0
            //验证成功
            $dataout['code'] = 0;
            $dataout['info'] = "验证成功";
            return $dataout;
        } else {
            if ($sum_v >= 5) {
                $dataout['code'] = 1007;
                $dataout['info'] = '验证次数太多，请重新获取';
                return $dataout;
            }
            $dataout['code'] = 1008;
            $dataout['info'] = '验证码不正确';
            return $dataout;
        }
    }

    /**
     * 获取验证码
     * @param string $mobile
     * @param string $condition ver 表示用户不存在时才能获取。yes_ver表示用户存在时才能获取
     * 0 参数有误
     * 1001 参数有误
     * 1002 手机号码格式不对
     * 1003 用户已存在
     * 1004 用户不存在
     * 1005 特殊错误（）
     */
    public function get_verify_code($mobile = '', $condition = 'ver')
    {
        $dataout = array(
            'code' => 1001,
            'info' => '参数有误'
        );
        $get_verify_code_time = Session::get('get_verify_code_time');
        $now_time = time();
        //如果 登录次数超过5次 并在1分钟以内 则锁定当前登录 60分钟之后失效
        if ($now_time - $get_verify_code_time < 60) {
            $dataout = array(
                'code' => 1001,
                'info' => '验证码获取过于频繁，请稍后重试',
            );
            out_json_data($dataout);
        }
        Session::set('get_verify_code_time', $now_time);
        if (empty($mobile)) {
            out_json_data($dataout);
        }
        if (!check_mobile_number($mobile)) {
            $dataout['code'] = 1002;
            $dataout['info'] = '手机号码格式不对';
            out_json_data($dataout);
        }
        if ($condition == 'ver') {
            //判断客户是否存在
            if ($this->customer_model->verifyCustomer($mobile) === true) {
                $dataout['code'] = 1003;
                $dataout['info'] = '用户已存在';
                out_json_data($dataout);
            }
        } else if ($condition == 'yes_ver') {
            //判断客户是否存在
            if ($this->customer_model->verifyCustomer($mobile) === false) {
                $dataout['code'] = 1004;
                $dataout['info'] = '用户不存在';
                out_json_data($dataout);
            }
        }
        $code = rand(1000, 9999);
        //发送验证码 代码
        $baidusms = new  baiduSmsClient();
        $config = Config::get('baidusms');
        $baidusms->set_config($config);
        $message = array(
            "invokeId" => "GQou3Krg-WP40-GVNh",
            "phoneNumber" => $mobile . "",
            "templateCode" => "smsTpl:e7476122a1c24e37b3b0de19d04ae900",
            "contentVar" => array(
                "code" => $code . "",
            ),
        );
        $ret = $baidusms->sendMessage($message);
        if (intval($ret->code) == 1000) {
            Session::set('mobile', $mobile);//手机号码
            Session::set('verify_code', $code);//验证码
            Session::set('sum_v', 0);//验证次数 /获取验证码时 为0
            Session::set('time', time());//验证次数 /获取验证码时 为0
            $dataout['code'] = 0;
            $dataout['data'] = Request::instance();
            $dataout['info'] = '验证码已发送到手机';
            out_json_data($dataout);
        } else {
            $dataout['code'] = 1005;
            $dataout['info'] = $ret->message;
            out_json_data($dataout);
        }
    }

    /**
     * 登录验证
     * @return string
     */
    public function login()
    {
        $dataout = array(
            'code' => 21,
            'info' => '数据参数有误',
        );
        $login_count = Session::get('login_count');
        if (empty($login_count)) {
            $login_count = 1;
        } else if ($login_count > 5) {
            $login_time = Session::get('login_time');
            $now_time = time();
            //如果 登录次数超过5次 并在20分钟以内 则锁定当前登录 20分钟之后失效
            if ($now_time - $login_time < 1200) {
                $dataout = array(
                    'code' => 15,
                    'info' => '登录过于频繁，请稍后重试' . $login_time,
                );
                out_json_data($dataout);
            } else {
                $login_count = 1;
                Session::set('login_count', $login_count);
            }
        }
        if ($this->request->isPost()) {
            $data = $this->request->only(['mobile', 'password', 'verify']);
            if (empty($data['mobile']) || empty($data['password'])) {
                out_json_data($dataout);
            }
            if (!check_mobile_number($data['mobile'])) {
                $dataout['code'] = 16;
                $dataout['info'] = '手机号码格式不对';
                out_json_data($dataout);
            }
            if (strlen($data['password']) > 50 || strlen($data['password']) < 8) {
                $dataout['code'] = 16;
                $dataout['info'] = '登录密码格式不正确';
                out_json_data($dataout);
            }
            $customer_data = array();
            $customer_data['mobile_phone'] = $data['mobile'];
            $customer_data['login_password'] = $data['password'];
//            $cap = new Captcha();
//            if (!$cap->check($data['verify'])) {
//                $dataout['code'] = 5;
//                $dataout['info'] = "验证码不正确";
//                out_json_data($dataout);
//            }
            $login_out_data = $this->customer_model->loginCustomer($customer_data);
            $login_count++;
            Session::set('login_count', $login_count);
            Session::set('login_time', time());
            out_json_data($login_out_data);
        }
        out_json_data($dataout);
    }

    /**
     * 注销账号
     * @param $token
     */
    public function logout($token)
    {
        $dataout = array(
            'code' => 21,
            'info' => '数据参数有误',
        );
        if (empty($token)) {
            out_json_data($dataout);
        }
        if ($this->customer_model->logoutCustomer($token)) {
            $dataout['code'] = 0;
            $dataout['info'] = "注销成功";
            out_json_data($dataout);
        }
        $dataout['code'] = 22;
        $dataout['info'] = "注销失败";
        out_json_data($dataout);
    }

    /**
     * 手机验证登录
     * 输入方式 POST
     * 参数 手机号码 mobile
     * 参数 验证码 code
     */
    public function mobile_login()
    {
        $dataout = array(
            'code' => 1,
            'info' => '参数有误',
        );
        if ($this->request->isPost()) {
            $data = $this->request->only(['mobile', 'code']);
            $out_verify = $this->_verify($data['code'], $data['mobile']);
            if ($out_verify['code'] == 0) {
                $customer_id = Session::get('customer_id');
                $out_user = $this->customer_model->loginMobileCustomer($data['mobile'], $customer_id);
                out_json_data($out_user);
            } else {
                out_json_data($out_verify);
            }
        }
        out_json_data($dataout);
    }

    /**
     * 微端验证注销
     * 输入方式 POST
     * 参数 手机号码 mobile
     * 参数 验证码 code
     */
    public function mobile_logout()
    {
        $dataout = array(
            'code' => 21,
            'info' => '数据参数有误'
        );
        $customer_data = [
            'id' => Session::get('customer_id'),
            'mobile_phone' => Session::get('mobile_phone'),
            'customer_status' => Session::get('customer_status'),
            'wechar_headimgurl' => Session::get('wechar_headimgurl'),
            'wechar_nickname' => Session::get('wechar_nickname'),
            'wechar_openid' => Session::get('wechar_openid'),
            'wechar_unionid' => Session::get('wechar_unionid')
        ];
        if ($this->customer_model->logoutMobileCustomer()) {
            $dataout['code'] = 0;
            $dataout['data'] = $customer_data;
            $dataout['info'] = "注销成功";
            out_json_data($dataout);
        }
        $dataout['code'] = 22;
        $dataout['data'] = $customer_data;
        $dataout['info'] = "注销失败";
        out_json_data($dataout);
    }

    /**
     * 商户登录验证
     * @return string
     */
    public function login_seller()
    {
        $dataout = array(
            'code' => 21,
            'info' => '数据参数有误',
        );
        $login_count = Session::get('login_count');
        if (empty($login_count)) {
            $login_count = 1;
        } else if ($login_count > 5) {
            $login_time = Session::get('login_time');
            $now_time = time();
            //如果 登录次数超过5次 并在20分钟以内 则锁定当前登录 20分钟之后失效
            if ($now_time - $login_time < 1200) {
                $dataout = array(
                    'code' => 15,
                    'info' => '登录过于频繁，请稍后重试',
                );
                out_json_data($dataout);
            } else {
                $login_count = 1;
                Session::set('login_count', $login_count);
            }
        }
        if ($this->request->isPost()) {
            $data = $this->request->only(['seller_name', 'password', 'verify']);
            if (empty($data['seller_name'])) {
                out_json_data($dataout);
            }
            if (!empty($data['verify'])) {
                $validate_result = $this->validate($data, 'Login');
                if ($validate_result !== true) {
                    $dataout['code'] = 100;
                    $dataout['info'] = $validate_result;
                    out_json_data($dataout);
                }
            }
            if (empty($data['password'])) {
                out_json_data($dataout);
            }
            if (strlen($data['password']) > 50 || strlen($data['password']) < 3) {
                $dataout['code'] = 16;
                $dataout['info'] = '登录密码格式不正确';
                out_json_data($dataout);
            }
            $seller_data = array();
            if (check_mobile_number($data['seller_name'])) {
                $seller_data['seller_mobile'] = $data['mobile'];
                $seller_data['seller_login_password'] = $data['password'];
                $login_out_data = $this->seller_model->loginSellerMobile($seller_data);
            } else {
                $seller_data['seller_name'] = $data['seller_name'];
                $seller_data['seller_login_password'] = $data['password'];
                $login_out_data = $this->seller_model->loginSeller($seller_data);
            }
            $login_out_data['url'] = url('seller/index/index');
            $login_count++;
            Session::set('login_count', $login_count);
            Session::set('login_time', time());
            out_json_data($login_out_data);
        }
        out_json_data($dataout);
    }

}