<?php

namespace app\index\controller;

use app\common\controller\HomeBase;
use app\common\model\SellerSubjoin;
use think\Request;
use think\Session;

class Index extends HomeBase
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
        $seller_id = Session::get('seller_id');
        if (!empty($seller_id)) {
            $this->redirect('seller/index/index');
        }
        return $this->fetch('index', []);
    }

    /**
     * 注册商户
     */
    public function seller()
    {
        return $this->fetch('seller', []);
    }

    /**
     * 申请入驻
     */
    public function subjoin()
    {
        $seller_subjoin_model = new SellerSubjoin();
        $seller_id = Session::get('seller_id');
        $subjoin_data = array();
        $address_config = array(
            'default' => false,
            'type' => 1,
            'province' => "",
            'city' => "",
            'area' => "",
            'street' => "",
        );
        $business_start = get_time_hours(8);
        $business_end = get_time_hours(20);
        if (!empty($seller_id)) {
            $subjoin_data = $seller_subjoin_model->where(array('seller_id' => $seller_id))->find();
            if (empty($subjoin_data)) {
                $subjoin_data = array();
            } else {
                if (intval($subjoin_data['store_status']) == 0) {
                    $this->redirect('index/index/subjoining');
                } else if (intval($subjoin_data['store_status']) == 2) {
                    $this->redirect('seller/login/index');
                }
                if (!empty($subjoin_data['province_id']) && !empty($subjoin_data['city_id']) && !empty($subjoin_data['area_id'])) {
                    $address_config['default'] = true;
                    $address_config['type'] = 4;
                    $address_config['province'] = $subjoin_data['province_id'] . "";
                    $address_config['city'] = $subjoin_data['city_id'] . "";
                    $address_config['area'] = $subjoin_data['area_id'] . "";
                    $address_config['street'] = $subjoin_data['street_id'] . "";
                    if (!empty($subjoin_data['business_start'])) {
                        $business_start = intval(substr($subjoin_data['business_start'], 0, 2));
                    } else {
                        $business_start = 8;
                    }
                    if (!empty($subjoin_data['business_end'])) {
                        $business_end = intval(substr($subjoin_data['business_end'], 0, 2));
                    } else {
                        $business_end = 22;
                    }
                    $business_start = get_time_hours($business_start);
                    $business_end = get_time_hours($business_end);
                }
            }
        } else {
            $this->redirect('seller/login/index');
        }
        return $this->fetch('subjoin', ['subjoin' => $subjoin_data, 'business_start' => $business_start, 'business_end' => $business_end, 'address_config' => json_encode($address_config)]);
    }

    /**
     * 申请审核中
     * @return mixed
     */
    public function subjoining()
    {
        $seller_subjoin_model = new SellerSubjoin();
        $seller_id = Session::get('seller_id');
        if (empty($seller_id)) {
            $this->redirect('seller/login/index');
        }
        $subjoin_data = $seller_subjoin_model->where(array('seller_id' => $seller_id))->find();
        return $this->fetch('subjoining', ['subjoin' => $subjoin_data]);
    }


}