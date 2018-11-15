<?php

namespace app\common\controller;

use think\Cache;
use think\Controller;
use think\Db;
use app\common\model\Customer as CustomerModel;
use think\Request;
use think\Session;

class UserBase extends Controller
{
    protected $customer_info;//用户信息
    protected $customer_model;//用户验证模型

    protected function _initialize()
    {
        parent::_initialize();
        $this->customer_model = new CustomerModel();
        //Session::set('customer_id','2');
        $token = Request::instance()->param('token');
        if (!empty($token)) {
            if ($this->customer_model->verifyCustomerToken($token)) {
                $customer_id = Session::get('customer_id');
                $mobile_phone = Session::get('mobile_phone');
                $customer_data = Session::get('customer_data');
                $this->customer_info = array(
                    'customer_id' => $customer_id,
                    'mobile_phone' => $mobile_phone,
                    'customer_data' => $customer_data
                );
            } else {
                $dataout = array(
                    'code' => 12,
                    'info' => '登录已过期'
                );
                out_json_data($dataout);
            }
        } else {
            $customer_id = Session::get('customer_id');
            $mobile_phone = Session::get('mobile_phone');
            $customer_data = Session::get('customer_data');
            $this->customer_info = array(
                'customer_id' => $customer_id,
                'mobile_phone' => $mobile_phone,
                'customer_data' => $customer_data
            );
        }
        if (empty($this->customer_info['customer_id'])) {
            $module = $this->request->module();
            $controller = $this->request->controller();
            $action = $this->request->action();
            if ($module . "/" . $controller . "/" . $action !== "api/Pay/wxpayrefund") {
                $manage_id = Session::get('manage_id');
                if (!empty($manage_id)) {

                }
                $dataout = array(
                    'code' => 11,
                    'info' => '非法用户无法操作'
                );
                out_json_data($dataout);
            }
        }
        $this->getSystem();
    }

    /**
     * 获取站点信息
     */
    protected function getSystem()
    {
        //获取渠道信息
        $channel_uid = input('channel_uid');
        if (!empty($channel_uid)) {
            //将渠道信息保存到Session
            Session::set('channel_uid', $channel_uid);
        }
        if (Cache::has('site_config')) {
            $site_config = Cache::get('site_config');
        } else {
            $site_config = Db::name('system')->field('value')->where('name', 'site_config')->find();
            $site_config = unserialize($site_config['value']);
            Cache::set('site_config', $site_config);
        }
        $this->assign($site_config);
    }
}