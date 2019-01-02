<?php

namespace app\pile\controller;

use app\common\controller\MobileBase;
use think\Request;
use think\Session;

class Index extends MobileBase
{

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
    }

    /**
     * 首页
     */
    public function index()
    {
        $jssdk = array();
        $urls = curPageURL();
        $fxurl = '';
        if ($this->is_wxBrowser) {
            get_wxuser($urls);
            $jssdk = get_jssdk($urls);
            $customer_id = Session::get('customer_id');
            $mobile_phone = Session::get('mobile_phone');
            $customer_status = Session::get('customer_status');
            $customer_info = array(
                'customer_id' => $customer_id,
                'mobile_phone' => $mobile_phone,
                'customer_status' => $customer_status,
            );
            $this->assign($customer_info);
            if (strpos($urls, '=') > 0) {
                $fxurl = $urls . "&channel_uid=" . $customer_id;
            } else {
                $fxurl = $urls . "?channel_uid=" . $customer_id;
            }
        }
     //分享渠道链接
        return $this->fetch('index', ['jssdk' => $jssdk, 'fxurl' => $fxurl]);
    }
    /**
     * 充电桩建议
     */
    public function pile_advise()
    {
        return $this->fetch('pile_advise');
    }
    /**
     * 我的订单(订单列表)
     */
    public function order_mine()
    {
        return $this->fetch('order_mine');
    }
    /**
     * 订单详情
     */
    public function order_details()
    {
        return $this->fetch('order_details');
    }
    /**
     * 建一个充电桩
     */
    public function build_pile()
    {
        $jssdk = array();
        $urls = curPageURL();
        $fxurl = '';
        if ($this->is_wxBrowser) {
            get_wxuser($urls);
            $jssdk = get_jssdk($urls);
        }

        return $this->fetch('build_pile', ['jssdk' => $jssdk, 'fxurl' => $fxurl]);
    }
    /**
     * 订单评价
     */
    public function order_comment()
    {
        $jssdk = array();
        $urls = curPageURL();
        $fxurl = '';
        if ($this->is_wxBrowser) {
            get_wxuser($urls);
            $jssdk = get_jssdk($urls);
        }

        return $this->fetch('order_comment', ['jssdk' => $jssdk, 'fxurl' => $fxurl]);
    }
    /**
     * 测试界面
     */
    public function test()
    {
        return $this->fetch('test');
    }
    /**
     * 测试界面
     */
    public function heat()
    {
        return $this->fetch('heat');
    }

}