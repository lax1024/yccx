<?php

namespace app\mobile\controller;

use app\common\controller\MobileBase;
use app\common\model\Article;
use app\common\model\Customer;
use app\common\model\CustomerBalanceLog;
use app\common\model\CustomerCash;
use app\common\model\CustomerCashLog;
use app\common\model\CustomerChannel;
use app\common\model\Order as OrderModel;
use app\common\model\Reserve;
use app\common\model\Slide as SlideModel;
use app\common\model\Store;
use definition\GoodsType;
use definition\OrderStatus;
use definition\TerminalCarType;
use think\Cache;
use think\Config;
use think\Cookie;
use think\Request;
use think\Session;
use tool\CarDeviceTool;

class Index extends MobileBase
{

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $service_phone = Config::get('store_tel');
        $this->assign('service_phone', $service_phone);
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
        $slide_model = new SlideModel();
        $map = array(
            'status' => 1
        );
        $slide_list = $slide_model->where($map)->field('name,link,image')->order('sort DESC')->limit(5)->select();
        //分享渠道链接
        return $this->fetch('index', ['jssdk' => $jssdk, 'fxurl' => $fxurl, 'slide_list' => $slide_list]);
    }

    /**
     * 传统租车的主页
     */
    public function index_tradition()
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
        $article_model = new Article();
        $article = $article_model->field('title,content,publish_time')->find(17);
        $article['content'] = htmlspecialchars_decode($article['content']);
        $article1 = $article_model->field('title,content,publish_time')->find(18);
        $article1['content'] = htmlspecialchars_decode($article1['content']);

        //分享渠道链接
        return $this->fetch('index_tradition', ['jssdk' => $jssdk, 'fxurl' => $fxurl, 'article' => $article, 'article1' => $article1]);
    }


    /**
     * 推荐建一个站点
     */
    public function build_point()
    {
        $jssdk = array();
        $urls = curPageURL();
        $fxurl = '';
        if ($this->is_wxBrowser) {
            get_wxuser($urls);
            $jssdk = get_jssdk($urls);
        }

        return $this->fetch('build_point', ['jssdk' => $jssdk, 'fxurl' => $fxurl]);
    }

    /**
     * 用户中心
     * @return mixed
     */
    public function user_center()
    {
        $jssdk = array();
        $urls = curPageURL();
        $fxurl = '';
        $subscribe = 0;
        if ($this->is_wxBrowser) {
            get_wxuser($urls, true);
            $jssdk = get_jssdk($urls);
            $customer_id = Session::get('customer_id');
            $mobile_phone = Session::get('mobile_phone');
            $customer_status = Session::get('customer_status');
            $subscribe = Session::get('subscribe');
            $fxurl = "http://www.youchedongli.cn/mobile/index/user_center?channel_uid=" . $customer_id;
        } else {
            $customer_id = Session::get('customer_id');
            if (empty($customer_id)) {
                $customer_id = 0;
            }
            $mobile_phone = Session::get('mobile_phone');
            $customer_status = Session::get('customer_status');
            if (empty($customer_status)) {
                $customer_status = 0;
            }
        }
        $customer_info = array(
            'customer_id' => $customer_id,
            'mobile_phone' => $mobile_phone,
            'customer_status' => $customer_status,
        );
        $this->assign($customer_info);
        $customer_model = new Customer();
        $field = "customer_status,customer_name,customer_nickname,customer_balance,id_number,mobile_phone,wechar_headimgurl,wechar_nickname,cash,cash_is,wechar_location_lat,wechar_location_lng";
        $customer_data = $customer_model->getCustomerField($customer_id, $field);
        $customer_data = $customer_data['data'];
        $customer_data['customer_name'] = name_hide($customer_data['customer_name']);
        $customer_data['id_number'] = idcard_hide($customer_data['id_number']);
        return $this->fetch('user_center', ['customer_data' => $customer_data, 'jssdk' => $jssdk, 'fxurl' => $fxurl, 'subscribe' => $subscribe]);
    }

    /**
     * 充值中心
     * @return mixed
     */
    public function user_balance()
    {
        $jssdk = array();
        $urls = curPageURL();
        $fxurl = '';
        if ($this->is_wxBrowser) {
            get_wxuser($urls, true);
            $jssdk = get_jssdk($urls);
            $customer_id = Session::get('customer_id');
            $mobile_phone = Session::get('mobile_phone');
            $customer_status = Session::get('customer_status');
            $fxurl = "http://www.youchedongli.cn/mobile/index/user_center?channel_uid=" . $customer_id;
        } else {
            $customer_id = Session::get('customer_id');
            if (empty($customer_id)) {
                $customer_id = 0;
            }
            $mobile_phone = Session::get('mobile_phone');
            $customer_status = Session::get('customer_status');
            if (empty($customer_status)) {
                $customer_status = 0;
            }
        }
        $customer_info = array(
            'customer_id' => $customer_id,
            'mobile_phone' => $mobile_phone,
            'customer_status' => $customer_status,
        );
        $this->assign($customer_info);
        $customer_model = new Customer();
        $field = "customer_status,customer_nickname,customer_balance,mobile_phone,wechar_headimgurl,wechar_nickname,cash,cash_is,wechar_location_lat,wechar_location_lng";
        $customer_data = $customer_model->getCustomerField($customer_id, $field);
        $customer_data = $customer_data['data'];
        return $this->fetch('user_balance', ['customer_data' => $customer_data, 'jssdk' => $jssdk, 'fxurl' => $fxurl]);
    }

    /**
     * 充值中心
     * @return mixed
     */
    public function ele_return_car()
    {
        $jssdk = array();
        $urls = curPageURL();
        if ($this->is_wxBrowser) {
            get_wxuser($urls);
            $jssdk = get_jssdk($urls);
        }
        //分享渠道链接
        return $this->fetch('ele_return_car', ['jssdk' => $jssdk]);
    }

    /**
     * 余额日志
     * @return mixed
     */
    public function user_balance_log()
    {
        $customer_balance_log_model = new CustomerBalanceLog();
        $customer_id = Session::get('customer_id');
        if (empty($customer_id)) {
            $this->redirect(url('mobile/index/user_center'));
        }
        $map = array(
            'customer_id' => $customer_id,
        );
        $customer_balance_log_data = $customer_balance_log_model->where($map)->select();
        foreach ($customer_balance_log_data as &$v) {
            $v['add_time'] = date('Y-m-d H:i:s', $v['add_time']);
        }
        return $this->fetch('user_balance_log', ['customer_balance_log_data' => $customer_balance_log_data]);
    }

    /**
     * 押金中心
     * @return mixed
     */
    public function user_cash()
    {
        $customer_model = new Customer();
        $customer_id = Session::get('customer_id');
        if (empty($customer_id)) {
            $this->redirect(url('mobile/index/user_center'));
        }
        $this->assign('seo_title', '缴纳押金');
        $field = "customer_status,cash,cash_is";
        $cash = Config::get('cash');
        $customer_data = $customer_model->getCustomerField($customer_id, $field);
        $customer_data = $customer_data['data'];
        $customer_cash_model = new CustomerCash();
        $customer_cash_data = $customer_cash_model->where(array('customer_id' => $customer_id, 'state' => 20))->find();
        $cash_data = array(
            'code' => 30,
            'info' => "未缴纳押金"
        );
        $pay_sn = "";
        if (!empty($customer_cash_data)) {
            $pay_sn = $customer_cash_data['pay_sn'];
            $cash_data = $customer_cash_model->is_return_cash($pay_sn);
            if ($cash_data['code'] == 1300) {
                $time = $cash_data['data'];
                $temp_data = diy_time_tostr($time);
                $temp_data['return_day'] = date("Y-m-d", time() + intval($time));
                $cash_data['data'] = $temp_data;
            }
        }
        return $this->fetch('user_cash', ['customer_data' => $customer_data, 'pay_sn' => $pay_sn, 'cash' => $cash, 'cash_data' => $cash_data]);
    }

    /**
     * 押金日志
     * @return mixed
     */
    public function user_cash_log()
    {
        $customer_cash_log_model = new CustomerCashLog();
        $customer_id = Session::get('customer_id');
        if (empty($customer_id)) {
            $this->redirect(url('mobile/index/user_center'));
        }
        $map = array(
            'customer_id' => $customer_id
        );
        $customer_cash_log_data = $customer_cash_log_model->where($map)->select();
        foreach ($customer_cash_log_data as &$v) {
            $v['add_time'] = date('Y-m-d H:i:s', $v['add_time']);
        }
        return $this->fetch('user_cash_log', ['customer_cash_log_data' => $customer_cash_log_data]);
    }

    /**
     * 用户用车协议
     * @return mixed
     */
    public function user_protocol()
    {
        $article_model = new Article();
        $article = $article_model->field('title,content,publish_time')->find(17);
        $article['content'] = htmlspecialchars_decode($article['content']);
        $article1 = $article_model->field('title,content,publish_time')->find(18);
        $article1['content'] = htmlspecialchars_decode($article1['content']);
        return $this->fetch('user_protocol', ['article' => $article, 'article1' => $article1]);
    }

    /**
     * 我的推广用户
     */
    public function user_spread()
    {
        $jssdk = array();
        $urls = curPageURL();
        $fxurl = '';
        if ($this->is_wxBrowser) {
            get_wxuser($urls);
            $jssdk = get_jssdk($urls);
            $customer_id = Session::get('customer_id');
            $fxurl = "http://www.youchedongli.cn/mobile/index/user_center?channel_uid=" . $customer_id;
        }
        return $this->fetch('user_spread', ['jssdk' => $jssdk, 'fxurl' => $fxurl]);
    }

    /**
     * 推广链接
     * @return mixed
     */
    public function user_generalize()
    {
        $jssdk = array();
        $urls = curPageURL();
        $fxurl = '';
        $this->assign('seo_title', "推广渠道");
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
            $fxurl = "http://www.youchedongli.cn/mobile/index/user_center?channel_uid=" . $customer_id;
            $customer_model = new Customer();
            $field = "customer_status,customer_name,customer_nickname,customer_balance,id_number,mobile_phone,wechar_headimgurl,wechar_nickname,cash,cash_is";
            $customer_data = $customer_model->getCustomerField($customer_id, $field);
            $customer_channel_model = new CustomerChannel();
            $channel = $customer_channel_model->getCustomerChannel($customer_id);
            $generalize = array(
                'name' => $channel['data']['name'],
                'url' => 'http://www.youchedongli.cn' . url('mobile/index/user_center', ['channel_uid' => $customer_id]),
                'icon_url' => "http://www.youchedongli.cn/api/Customer/get_customer_head/customer_id/" . $customer_id . ".png",
                'address1' => $channel['data']['address'][0],
                'address2' => $channel['data']['address'][1]
            );
            $this->assign($generalize);
            $customer_data = $customer_data['data'];
        } else {
            $customer_id = Session::get('customer_id');
            $mobile_phone = Session::get('mobile_phone');
            $customer_status = Session::get('customer_status');
            $customer_info = array(
                'customer_id' => $customer_id,
                'mobile_phone' => $mobile_phone,
                'customer_status' => $customer_status,
            );
            $this->assign($customer_info);
            $customer_model = new Customer();
            $field = "customer_status,customer_name,customer_nickname,customer_balance,id_number,mobile_phone,wechar_headimgurl,wechar_nickname,cash,cash_is";
            $customer_data = $customer_model->getCustomerField($customer_id, $field);
            $customer_channel_model = new CustomerChannel();
            $channel = $customer_channel_model->getCustomerChannel($customer_id);
            $generalize = array(
                'name' => $channel['data']['name'],
                'url' => 'http://www.youchedongli.cn' . url('mobile/index/user_center', ['channel_uid' => $customer_id]),
                'icon_url' => "http://www.youchedongli.cn/api/Customer/get_customer_head/customer_id/" . $customer_id . ".png",
                'address1' => $channel['data']['address'][0],
                'address2' => $channel['data']['address'][1]
            );
            $this->assign($generalize);
        }
        return $this->fetch('user_generalize', ['customer_data' => $customer_data, 'jssdk' => $jssdk, 'fxurl' => $fxurl]);
    }

    /**
     * 用户月驾驶排行榜
     * @return mixed
     */
    public function user_driver_rank()
    {
        $jssdk = array();
        $urls = curPageURL();
        $fxurl = '';
        $this->assign('seo_title', "用车里程排行");
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
            $fxurl = "http://www.youchedongli.cn/mobile/index/user_center?channel_uid=" . $customer_id;
            $rank_list_data_json = Cache::get('user_driver_rank');
            $rank_data = [];
            if (empty($rank_list_data_json)) {
                $customer_model = new Customer();
                $field = "id,customer_balance,wechar_headimgurl,wechar_nickname,drive_month_km";
                $rank_list_data = $customer_model->where(['customer_status' => 1, 'drive_month' => date("Y-m")])->field($field)->order('drive_month_km DESC')->select();
                $rank = 1;
                foreach ($rank_list_data as &$v) {
                    $v['rank'] = $rank;
                    $rank++;
                    if ($v['id'] == $customer_id) {
                        $rank_data = $v;
                    }
                }
                Cache::set('user_driver_rank', json_encode($rank_list_data), 7200);
            } else {
                $rank_list_data = json_decode($rank_list_data_json, true);
            }
            $generalize = array(
                'rank_data' => $rank_data,
                'rank_list_data' => $rank_list_data
            );
            $this->assign($generalize);
        }
        return $this->fetch('user_driver_rank', ['jssdk' => $jssdk, 'fxurl' => $fxurl]);
    }

    /**
     * 车辆搜索(常规车辆)
     * @return mixed
     */
    public function carsearch_tradition()
    {
        return $this->fetch('carsearch_tradition');
    }

    /**
     * 车辆搜索(纯电动车)
     * @return mixed
     */
    public function carsearch_newenergy()
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
        return $this->fetch('carsearch_newenergy', ['jssdk' => $jssdk, 'fxurl' => $fxurl]);
    }

    //测试界面
    public function test()
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
            $reserve_model = new Reserve();
            $r_data = $reserve_model->isReserve($customer_id);
            if ($r_data['code'] == 103) {
                $this->redirect(url('mobile/index/order_fillin_newenergy'));
            }
            $order_model = new OrderModel();

            $map = array(
                'customer_id' => $customer_id,
                'goods_type' => GoodsType::$GoodsTypeElectrocar['code'],
                'order_status' => OrderStatus::$OrderStatusAcquire['code'],
            );
            $order_data = $order_model->where($map)->field('id')->find();
            if (!empty($order_data)) {
                $order_id = $order_data['id'];
                $this->redirect(url('mobile/index/order_ele_key') . "?order_id=" . $order_id);
            }
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
            $customer_model = new Customer();
            $field = "customer_status,customer_name,customer_nickname,customer_balance,id_number,mobile_phone,wechar_headimgurl,wechar_nickname,cash,cash_is,wechar_location_lat,wechar_location_lng";
            $customer_data = $customer_model->getCustomerField($customer_id, $field);
            $customer_data = $customer_data['data'];
        } else {
            $customer_data = [];
        }
        return $this->fetch('test', ['customer_data' => $customer_data, 'jssdk' => $jssdk, 'fxurl' => $fxurl]);
    }

    //取车城市选择
    public function city_takecar()
    {
        return $this->fetch('city_takecar');
    }

    //证件列表
    public function contactlist()
    {
        return $this->fetch('contactlist');
    }

    //添加证件
    public function addcontact()
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
        return $this->fetch('addcontact', ['jssdk' => $jssdk, 'fxurl' => $fxurl]);
    }

    //还车城市选择
    public function city_returncar()
    {
        return $this->fetch('city_returncar');
    }

    //取车城市地区选择
    public function area_takecar()
    {
        return $this->fetch('area_takecar');
    }

    //还车城市地区选择
    public function area_returncar()
    {
        return $this->fetch('area_returncar');
    }

    //地图还车
    public function map_return_car()
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
        return $this->fetch('map_return_car', ['jssdk' => $jssdk, 'fxurl' => $fxurl]);
    }

    /**
     * 填写订单(常规汽车)
     */
    public function order_fillin_tradition()
    {
        return $this->fetch('order_fillin_tradition');
    }


    /**
     * 优惠卷(新手)
     */
    public function user_coupon_newhand()
    {
        return $this->fetch('user_coupon_newhand');
    }


    /**
     * 优惠卷列表
     */
    public function user_coupon_list()
    {
        return $this->fetch('user_coupon_list');
    }


    /**
     * 填写订单(新能源车)
     */
    public function order_fillin_newenergy()
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
        $article_model = new Article();
        $article = $article_model->field('title,content,publish_time')->find(17);
        $article['content'] = htmlspecialchars_decode($article['content']);
        $article1 = $article_model->field('title,content,publish_time')->find(18);
        $article1['content'] = htmlspecialchars_decode($article1['content']);

        //分享渠道链接
        return $this->fetch('order_fillin_newenergy', ['jssdk' => $jssdk, 'fxurl' => $fxurl, 'article' => $article, 'article1' => $article1]);
    }


    /**
     * 地图选车
     */
    public function map_choose_car()
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
            $reserve_model = new Reserve();
            $r_data = $reserve_model->isReserve($customer_id);
            if ($r_data['code'] == 103) {
                $this->redirect(url('mobile/index/order_fillin_newenergy').'?goods_id='.$r_data['goods_id']);
            }
            $order_model = new OrderModel();

            $map = array(
                'customer_id' => $customer_id,
                'goods_type' => GoodsType::$GoodsTypeElectrocar['code'],
                'order_status' => OrderStatus::$OrderStatusAcquire['code'],
            );
            $order_data = $order_model->where($map)->field('id')->find();
            if (!empty($order_data)) {
                $order_id = $order_data['id'];
                $this->redirect(url('mobile/index/order_ele_key') . "?order_id=" . $order_id);
            }
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
            $customer_model = new Customer();
            $field = "customer_status,customer_name,customer_nickname,customer_balance,id_number,mobile_phone,wechar_headimgurl,wechar_nickname,cash,cash_is,wechar_location_lat,wechar_location_lng";
            $customer_data = $customer_model->getCustomerField($customer_id, $field);
            $customer_data = $customer_data['data'];
        } else {
            $customer_data = [];
        }
        //分享渠道链接
        return $this->fetch('map_choose_car', ['customer_data' => $customer_data, 'jssdk' => $jssdk, 'fxurl' => $fxurl]);
    }

    /**
     * 推荐建点
     */
    public function recommend_build_point()
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
        return $this->fetch('recommend_build_point', ['jssdk' => $jssdk, 'fxurl' => $fxurl]);
    }


    /**
     * 地图选门店
     */
    public function map_choose_store()
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
        return $this->fetch('map_choose_store', ['jssdk' => $jssdk, 'fxurl' => $fxurl]);
    }


    /**
     * 订单详情
     */
    public function order_view()
    {
        return $this->fetch('order_view');
    }

    /**
     * 订单列表
     */
    public function order_mine()
    {
        $urls = curPageURL();
        if ($this->is_wxBrowser) {
            get_wxuser($urls);
            $customer_id = Session::get('customer_id');
            $mobile_phone = Session::get('mobile_phone');
            $customer_status = Session::get('customer_status');
            $customer_info = array(
                'customer_id' => $customer_id,
                'mobile_phone' => $mobile_phone,
                'customer_status' => $customer_status,
            );
            $this->assign($customer_info);
        }
        return $this->fetch('order_mine');
    }

    /**
     * 订单付款
     */
    public function order_pay()
    {
        $customer_model = new Customer();
        $customer_id = Session::get('customer_id');
        if (empty($customer_id)) {
            $this->redirect(url('mobile/index/user_center'));
        }
        $field = "customer_status,customer_balance";
        $customer_data = $customer_model->getCustomerField($customer_id, $field);
        $customer_data = $customer_data['data'];
        return $this->fetch('order_pay', ['customer_data' => $customer_data]);
    }

    /**
     * 取车拍照
     */
    public function take_car_pic()
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
        return $this->fetch('take_car_pic', ['jssdk' => $jssdk, 'fxurl' => $fxurl]);
    }

    /**
     * 还车拍照
     */
    public function return_car_pic()
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
        return $this->fetch('return_car_pic', ['jssdk' => $jssdk, 'fxurl' => $fxurl]);
    }

    /**
     * 扫码用车
     */
    public function qrcode_or_license()
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
        return $this->fetch('qrcode_or_license', ['jssdk' => $jssdk, 'fxurl' => $fxurl]);
    }

    /**
     * 新能源车订单详情
     */
    public function order_details_newenergy()
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
        return $this->fetch('order_details_newenergy', ['jssdk' => $jssdk, 'fxurl' => $fxurl]);
    }

    /**
     * 新能源车订单详情
     */
    public function order_details_tradition()
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
        return $this->fetch('order_details_tradition', ['jssdk' => $jssdk, 'fxurl' => $fxurl]);
    }

    /**
     * 新能源车_地图用车
     */
    public function map_usecar_newenergy()
    {
        return $this->fetch('map_usecar_newenergy');
    }

    //车辆控制（电子钥匙）
    public function order_ele_key()
    {
        return $this->fetch('order_ele_key');
    }

    //订单评价
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


    //查看评价
    public function order_comment_show()
    {
        $jssdk = array();
        $urls = curPageURL();
        $fxurl = '';
        if ($this->is_wxBrowser) {
            get_wxuser($urls);
            $jssdk = get_jssdk($urls);
        }

        return $this->fetch('order_comment_show', ['jssdk' => $jssdk, 'fxurl' => $fxurl]);
    }


    //常规租车的取车门店选择
    public function store_take_tradition()
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
        return $this->fetch('store_take_tradition', ['jssdk' => $jssdk, 'fxurl' => $fxurl]);
    }


    //常规租车的取车门店选择(修改版)
    public function store_take()
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
        return $this->fetch('store_take', ['jssdk' => $jssdk, 'fxurl' => $fxurl]);
    }


    //常规租车的取车门店选择
    public function store_return_tradition()
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
        return $this->fetch('store_return_tradition', ['jssdk' => $jssdk, 'fxurl' => $fxurl]);
    }


    //常规租车的还车门店选择(修改版)
    public function store_return()
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
        return $this->fetch('store_return', ['jssdk' => $jssdk, 'fxurl' => $fxurl]);
    }

    /**
     * 用户实名
     */
    public function add_user_info()
    {
        $jssdk = array();
        $urls = curPageURL();
        $customer_data['data'] = array();
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
            $customer_model = new Customer();
            $customer_data = $customer_model->getCustomerField($customer_id, 'customer_name,customer_status,customer_remark,id_number,id_number_time,id_number_image_front,id_number_image_reverse,
        customer_front,driver_license,driver_license_name,driver_license_number,driver_license_time,driver_license_image_front,driver_license_image_attach');
        }
        //分享渠道链接
        return $this->fetch('add_user_info', ['jssdk' => $jssdk, 'customer_data' => $customer_data['data']]);
    }

    public function admin_dispatch($keyword = '')
    {
        $admin_dispatch_id = Config::get('admin_dispatch_id');
        $admin_dispatch_store_id = Config::get('admin_dispatch_store_id');
        $urls = curPageURL();
        $customer_data['data'] = array();
        if ($this->is_wxBrowser) {
            get_wxuser($urls);
            $customer_id = Session::get('customer_id');
            if (in_array($customer_id, $admin_dispatch_id)) {
                $car_device_tool = new CarDeviceTool();
                $page_config = ['page' => 1, 'query' => ['keyword' => $keyword]];
                $car_device_list = $car_device_tool->getPageList($keyword, 'DESC', $page_config, 100);
                $device_type = TerminalCarType::$CARDEVICETYPE_CODE;
                $maps['store_key_id'] = $admin_dispatch_store_id[intval($customer_id)];
                $store_model = new Store();
                $store_site_list = $store_model->getChildList($maps);
                return $this->fetch('admin_dispatch', ['car_device_list' => $car_device_list, 'store_site_list' => $store_site_list, 'device_type' => $device_type]);
            } else {
                $this->redirect(url('mobile/index/user_center'));
            }
        } else {
            $customer_id = Session::get('customer_id');
            if (in_array($customer_id, $admin_dispatch_id)) {
                $car_device_tool = new CarDeviceTool();
                $page_config = ['page' => 1, 'query' => ['keyword' => $keyword]];
                $car_device_list = $car_device_tool->getPageList($keyword, 'DESC', $page_config, 100);
                $device_type = TerminalCarType::$CARDEVICETYPE_CODE;
                $maps['store_key_id'] = $admin_dispatch_store_id[intval($customer_id)];
                $store_model = new Store();
                $store_site_list = $store_model->getChildList($maps);
                return $this->fetch('admin_dispatch', ['car_device_list' => $car_device_list, 'store_site_list' => $store_site_list, 'device_type' => $device_type]);
            } else {
                $this->redirect(url('mobile/index/user_center'));
            }
        }

    }

    //关于我们
    public function ewm()
    {
        $this->redirect("https://mp.weixin.qq.com/s/Srs8YFtiOQC2BH5ySAjX8g");
    }

    /**
     * 验证用户登录情况
     * @return bool
     */
    private function _ver_user()
    {
        $customer_id = Session::get('customer_id');
        if (empty($customer_id)) {
            $urls = curPageURL();
            if ($this->is_wxBrowser) {
                get_wxuser($urls);
                $mobile = Session::get('mobile_phone');
                $customer_status = Session::get('customer_status');
                if (empty($mobile) || !check_mobile_number($mobile)) {
                    //如果没有绑定电话号码 去绑定
                    Session::set('ver_mobile_url', $urls);
                    return true;
                }
                if (intval($customer_status) == 0) {
                    Session::set('ver_status_url', $urls);
                    return true;
                }
            } else {
                $customer_id = Cookie::get('customer_id');
                if (!empty($customer_id)) {
                    $customer_id = Cookie::get('customer_id');
                    $mobile_phone = Cookie::get('mobile_phone');
                    $wechar_headimgurl = Cookie::get('wechar_headimgurl');
                    $wechar_nickname = Cookie::get('wechar_nickname');
                    $wechar_openid = Cookie::get('wechar_openid');
                    $wechar_unionid = Cookie::get('wechar_unionid');

                    Session::set('customer_id', $customer_id);
                    Session::set('mobile_phone', $mobile_phone);
                    Session::set('wechar_headimgurl', $wechar_headimgurl);
                    Session::set('wechar_nickname', $wechar_nickname);
                    Session::set('wechar_openid', $wechar_openid);
                    Session::set('wechar_unionid', $wechar_unionid);
                    return false;
                }
                Session::set('ver_mobile_url', $urls);
                return true;
            }
        }
        return false;
    }
}