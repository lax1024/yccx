<?php

namespace app\common\controller;

use think\Controller;
use app\common\model\Seller as SellerModel;
use think\Request;
use think\Session;

class SellerBase extends Controller
{
    protected $seller_info;//商户信息
    protected $seller_model;//商户信息模型

    protected function _initialize()
    {
        parent::_initialize();
        $this->seller_model = new SellerModel();
        $token = Request::instance()->param('token');
        if (!empty($token)) {
            if ($this->seller_model->verifySellerToken($token)) {
                $this->seller_info = array(
                    'seller_id' => Session::get('seller_id'),
                    'store_key_id' => Session::get('store_key_id'),
                    'store_id' => Session::get('store_id'),
                    'seller_name' => Session::get('seller_name'),
                    'is_admin' => Session::get('is_admin'),
                    'seller_data' => json_decode(Session::get('seller_data'))
                );
            } else {
                $dataout = array(
                    'code' => 12,
                    'info' => '登录已过期'
                );
                out_json_data($dataout);
            }
        } else {
            $this->seller_info = array(
                'seller_id' => Session::get('seller_id'),
                'store_key_id' => Session::get('store_key_id'),
                'store_id' => Session::get('store_id'),
                'seller_name' => Session::get('seller_name'),
                'is_admin' => Session::get('is_admin'),
                'seller_data' => json_decode(Session::get('seller_data'))
            );
        }
        if (empty($this->seller_info['seller_id'])) {
            $dataout = array(
                'code' => 11,
                'info' => '非法用户无法操作'
            );
            out_json_data($dataout);
        }
        //获取渠道信息
        $channel_uid = input('channel_uid');
        if (is_numeric($channel_uid)) {
            //将渠道信息保存到Session
            Session::set('channel_uid', $channel_uid);
        }
    }
}