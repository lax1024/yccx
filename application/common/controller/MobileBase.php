<?php

namespace app\common\controller;

use app\common\model\Customer;
use think\Cache;
use think\Controller;
use think\Db;
use think\Session;

/**
 * 移动端规则
 * Class MobileBase
 * @package app\common\controller
 */
class MobileBase extends Controller
{
    protected $is_wxBrowser = false;
    protected $is_pcBrowser = false;

    protected function _initialize()
    {
        parent::_initialize();
        $this->getSystem();
        $this->getBrowser();
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
        $customer_id = Session::get('customer_id');
        $mobile_phone = Session::get('mobile_phone');
        $customer_status = Session::get('customer_status');
        $customer_info = array(
            'customer_id' => $customer_id,
            'mobile_phone' => $mobile_phone,
            'customer_status' => $customer_status,
        );
        if (intval($customer_status) != 1) {
            $customer_id = Session::get('customer_id');
            $customer_model = new Customer();
            $customer_data = $customer_model->getCustomer('', $customer_id);
            $customer_data = $customer_data['data'];
            Session::set('customer_status', $customer_data['customer_status']);
            $customer_info = array(
                'customer_id' => $customer_data['id'],
                'mobile_phone' => $customer_data['mobile_phone'],
                'customer_status' => $customer_data['customer_status']
            );
        }
        $this->assign($customer_info);
        $js_css_rand = array(
            'js_rand' => '2018111191',
            'css_rand' => '201811191',
            'm_rand' => date("YmdHis")
        );
        $this->assign($js_css_rand);
    }

    /**
     * 获取浏览器信息
     */
    protected function getBrowser()
    {
        $wrowser_config = array(
            'is_wxBrowser' => 'no',
            'is_pcBrowser' => 'no'
        );
        //判断是否是微信浏览器
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') == true) {
            $this->is_wxBrowser = true;
            $wrowser_config['is_wxBrowser'] = 'yes';
        }
        //判断是否是手机浏览器
        if (!is_mobile()) {
            $this->is_pcBrowser = true;
            $wrowser_config['is_pcBrowser'] = 'yes';
        }
        $this->assign($wrowser_config);
    }
}