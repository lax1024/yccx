<?php

namespace app\api\controller;

use app\common\controller\SellerBase;
use app\common\model\Seller as SellerModel;
use think\Session;

/**
 * 商户操作API
 * Class Seller
 * @package app\api\controller
 */
class Seller extends SellerBase
{
    protected function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 提交入驻申请
     */
    public function add_subjoin()
    {
        $dataout = array(
            'code' => 1,
            'url' => url('index/index/subjoin'),
            'info' => '参数有误',
        );
        if ($this->request->isPost()) {
            $data = $this->request->post();
//            * store_name 单位名称
//            * store_principal 负责人姓名
//            * store_mobile 负责人手机号码
//            * store_tel 单位电话
//            * province_id 省份id
//            * city_id 市级id
//            * area_id 地区id/县区id
//            * street_id 街道id
//            * address 客户地址
//            * location_longitude 经度
//            * location_latitude 纬度
//            * principal_id 负责人身份证号码
//            * principal_id_front_img 负责人身份证正面图片
//            * principal_id_back_img 负责人身份证背面图片
//            * principal_id_handheld_img 获取负责手持人身份证正面图片
//            * business_license 营业执照编号或者组织代码
//            * business_license_img 营业执照图片
//            * protocol_img 入驻协议图片
//            * business_start 开店时间
//            * business_end 关店时间
            if ($this->_verify($data['code'], $data['store_mobile'])) {
                //构造基本数据
                $seller_subjoin_data = array(
                    'seller_id' => $this->seller_info['seller_id'],
                    'store_name' => $data['store_name'],
                    'store_principal' => $data['store_principal'],
                    'store_mobile' => $data['store_mobile'],
                    'store_tel' => $data['store_tel'],
                    'province_id' => $data['province_id'],
                    'city_id' => $data['city_id'],
                    'area_id' => $data['area_id'],
                    'street_id' => $data['street_id'],
                    'address' => $data['address'],
                    'location_longitude' => $data['location_longitude'],
                    'location_latitude' => $data['location_latitude'],
                    'principal_id' => $data['principal_id'],
                    'principal_id_front_img' => $data['principal_id_front_img'],
                    'principal_id_back_img' => $data['principal_id_back_img'],
                    'principal_id_handheld_img' => $data['principal_id_handheld_img'],
                    'business_license' => $data['business_license'],
                    'business_license_img' => $data['business_license_img'],
                    'protocol_img' => $data['protocol_img'],
                    'business_start' => $data['business_start'],
                    'business_end' => $data['business_end']
                );
                $subjoin_out_data = $this->seller_model->addSellerSubjoin($seller_subjoin_data);
                if ($subjoin_out_data['code'] == 0) {
                    $subjoin_out_data['url'] = url('index/index/subjoin');
                } else {
                    $subjoin_out_data['url'] = url('index/index/subjoin');
                }
                out_json_data($subjoin_out_data);
            }
        }
        out_json_data($dataout);
    }

    /**
     * 修改入驻申请
     */
    public function update_subjoin()
    {
        $dataout = array(
            'code' => 1,
            'info' => '参数有误',
        );
        if ($this->request->isPost()) {
            $data = $this->request->post();
//            * store_name 单位名称
//            * store_principal 负责人姓名
//            * store_mobile 负责人手机号码
//            * store_tel 单位电话
//            * province_id 省份id
//            * city_id 市级id
//            * area_id 地区id/县区id
//            * street_id 街道id
//            * address 客户地址
//            * location_longitude 经度
//            * location_latitude 纬度
//            * principal_id 负责人身份证号码
//            * principal_id_front_img 负责人身份证正面图片
//            * principal_id_back_img 负责人身份证背面图片
//            * principal_id_handheld_img 获取负责手持人身份证正面图片
//            * business_license 营业执照编号或者组织代码
//            * business_license_img 营业执照图片
//            * protocol_img 入驻协议图片
//            * business_start 开店时间
//            * business_end 关店时间
            if ($this->_verify($data['code'], $data['store_mobile'])) {
                //构造更新基本数据
                $update_seller_subjoin_data = array(
                    'id' => $data['id'],
                    'store_name' => $data['store_name'],
                    'store_principal' => $data['store_principal'],
                    'store_mobile' => $data['store_mobile'],
                    'store_tel' => $data['store_tel'],
                    'province_id' => $data['province_id'],
                    'city_id' => $data['city_id'],
                    'area_id' => $data['area_id'],
                    'street_id' => $data['street_id'],
                    'address' => $data['address'],
                    'location_longitude' => $data['location_longitude'],
                    'location_latitude' => $data['location_latitude'],
                    'principal_id' => $data['principal_id'],
                    'principal_id_front_img' => $data['principal_id_front_img'],
                    'principal_id_back_img' => $data['principal_id_back_img'],
                    'principal_id_handheld_img' => $data['principal_id_handheld_img'],
                    'business_license' => $data['business_license'],
                    'business_license_img' => $data['business_license_img'],
                    'protocol_img' => $data['protocol_img'],
                    'business_start' => $data['business_start'],
                    'business_end' => $data['business_end']
                );

                $subjoin_out_data = $this->seller_model->updateSellerSubjoin($update_seller_subjoin_data);
                if ($subjoin_out_data['code'] == 0) {
                    $subjoin_out_data['url'] = url('index/index/subjoin');
                } else {
                    $subjoin_out_data['url'] = url('index/index/subjoin');
                }
                out_json_data($subjoin_out_data);
            }
        }
        out_json_data($dataout);
    }

    /**
     * 验证短信验证码
     * @param $code_v 短信验证码
     * @param $mobile_v 验证的手机号码
     * @return bool
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
            out_json_data($dataout);
        }
        if (!check_mobile_number($mobile_v)) {
            $dataout['code'] = 1002;
            $dataout['info'] = '手机号码格式不正确';
            out_json_data($dataout);
        }
        $mobile = Session::get('mobile');//手机号码
        $code = Session::get('verify_code');//验证码
        $sum_v = Session::get('sum_v');//验证次数 /获取验证码时 为0
        $time = Session::get('time');//获取验证码 的时间
        if (time() - $time > 600) {
            Session::set('mobile', '');//手机号码
            Session::set('verify_code', '');//验证码
            Session::set('sum_v', 0);//验证次数 /获取验证码时 为0
            $dataout['code'] = 1006;
            $dataout['info'] = '验证码已过期';
            out_json_data($dataout);
        }
        $sum_v++;
        Session::set('sum_v', $sum_v);//增加一次验证
        if ($mobile == $mobile_v && $code_v == $code && $sum_v < 5) {
            Session::set('mobile', '');//手机号码
            Session::set('verify_code', '');//验证码
            Session::set('sum_v', 0);//验证次数 /获取验证码时 为0
            //验证成功
            return true;
        } else {
            if ($sum_v >= 5) {
                $dataout['code'] = 1007;
                $dataout['info'] = '验证次数太多，请重新获取';
                out_json_data($dataout);
            }
            $dataout['code'] = 1008;
            $dataout['info'] = '验证码不正确';
            out_json_data($dataout);
        }
    }

}