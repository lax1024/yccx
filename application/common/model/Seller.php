<?php

namespace app\common\model;

/**
 * 商家数据模型
 * 实现商家用户的基础数据操作
 * 添加商家用户
 * 修改商家用户信息
 * 商家用户登录
 */

use think\Config;
use think\Model;
use think\Session;

class Seller extends Model
{
    protected $insert = ['create_time'];

    /**
     * 创建时间
     * @return bool|string
     */
    protected function setCreateTimeAttr()
    {
        return date('Y-m-d H:i:s');
    }

    /**
     * 获取商家用户基本信息
     * @param string $seller_id 商家id
     * @param string $seller_name 商家用户名
     * @param bool $is_admin 是否是管理员操作
     * @return mixed
     */
    public function getSeller($seller_id = '', $seller_name = '', $is_admin = false)
    {
        if (!empty($seller_name)) {
            $seller = $this->where(array('seller_name' => $seller_name))->find();
            if (intval($seller['seller_status']) == 2 && $is_admin === false) {
                $out_data['code'] = 404;
                $out_data['info'] = "账号已被锁定";
                return $out_data;//账号已被锁定
            }
            $out_data['code'] = 0;
            $out_data['data'] = $seller;
            $out_data['info'] = "获取成功";
            return $out_data;//获取成功
        } else if (!empty($seller_mobile)) {
            $seller = $this->where(array('seller_mobile' => $seller_mobile))->find();
            if (intval($seller['seller_status']) == 2 && $is_admin === false) {
                $out_data['code'] = 404;
                $out_data['info'] = "账号已被锁定";
                return $out_data;//账号已被锁定
            }
            $out_data['code'] = 0;
            $out_data['data'] = $seller;
            $out_data['info'] = "获取成功";
            return $out_data;//获取成功
        } else if (!empty($seller_id)) {
            $seller = $this->where(array('id' => $seller_id))->find();
            if (intval($seller['seller_status']) == 2 && $is_admin === false) {
                $out_data['code'] = 404;
                $out_data['info'] = "账号已被锁定";
                return $out_data;//账号已被锁定
            }
            $out_data['code'] = 0;
            $out_data['data'] = $seller;
            $out_data['info'] = "获取成功";
            return $out_data;//获取成功
        }
        $out_data['code'] = 405;
        $out_data['info'] = "账号不存在";
        return $out_data;//账号不存在
    }

    /**
     * 添加商家用户
     * @param $seller_data
     * 必须数据
     * seller_name 商家用户名称
     * seller_mobile 商家电话号码
     * login_password 商家登录密码
     * seller_group_id 商家权限分组
     * parent_id 归属商家id
     * store_id 店铺id
     * is_admin 是否管理员(0-不是 1-是)
     * store_key_id 归属总店id
     * @return array
     *  0 用户添加成功
     */
    public function addSeller($seller_data)
    {
        $out_data = array();
        //验证数据合法性
        $seller_name = $seller_data['seller_name'];
        if ($this->_verifySeller($seller_name) === true) {
            $out_data['code'] = 600;
            $out_data['info'] = "账号已被注册";
            return $out_data;//账号已被注册
        }
        //获取手机号码
        $seller_mobile = $seller_data['seller_mobile'];
        if (check_mobile_number($seller_mobile) === false) {
            $out_data['code'] = 601;
            $out_data['info'] = "手机号码格式不正确";
            return $out_data;//手机号码格式不正确
        }
        $seller_login_password = $seller_data['seller_login_password'];
        if (strlen($seller_login_password) < 3) {
            $out_data['code'] = 602;
            $out_data['info'] = "登录密码不符合规范";
            return $out_data;//登录密码不符合规范
        }
        $parent_id = $seller_data['parent_id'];
        if (!is_numeric($parent_id)) {
            $out_data['code'] = 603;
            $out_data['info'] = "父级商家id不合法";
            return $out_data;//父级商家id不合法
        }
        $is_admin = $seller_data['is_admin'];
        if (!is_numeric($is_admin)) {
            $out_data['code'] = 604;
            $out_data['info'] = "管理员类型不合法";
            return $out_data;//管理员类型不合法
        } else if (!(intval($is_admin) >= 0 && intval($is_admin) <= 1)) {
            $out_data['code'] = 604;
            $out_data['info'] = "管理员类型不合法";
            return $out_data;//管理员类型不合法
        }
        if (intval($is_admin) == 1) {
            $seller_group_id = 1;
        } else {
            $seller_group_id = $seller_data['seller_group_id'];
            if (!is_numeric($seller_group_id)) {
                $out_data['code'] = 605;
                $out_data['info'] = "父级商家id不合法";
                return $out_data;//父级商家id不合法
            }
        }
        if (is_numeric($seller_data['store_id'])) {
            $store_id = $seller_data['store_id'];
        } else {
            $store_id = 0;
        }
        if (is_numeric($seller_data['store_key_id'])) {
            $store_key_id = $seller_data['store_key_id'];
        } else {
            $store_key_id = 0;
        }
        if (is_numeric($seller_data['seller_status'])) {
            $seller_status = $seller_data['seller_status'];
        } else {
            $seller_status = 0;
        }
        //验证数据合法性完成
        $datetime = date("Y-m-d H:i:s");
        $in_seller_data = array(
            'seller_name' => $seller_name,
            'seller_mobile' => $seller_mobile,
            'seller_group_id' => $seller_group_id,
            'parent_id' => $parent_id,
            'store_id' => $store_id,
            'store_key_id' => $store_key_id,
            'by_ip' => getIp(),
            'seller_status' => $seller_status,
            'create_time' => $datetime,
            'update_time' => $datetime
        );
        if ($this->save($in_seller_data)) {
            $id = $this->getLastInsID();//获取插入数据的id
            //构造密码记录数据
            $this->_addLoginPassword($id, $seller_login_password);
            //更新权限
            $this->_updateSellerGrouopGroupAccess($id, $seller_group_id);
            $out_data['code'] = 0;
            $out_data['info'] = "商家管理人员添加成功";
            return $out_data;//商家管理人员添加成功
        }
        $out_data['code'] = 622;
        $out_data['info'] = "数据库写失败";
        return $out_data;//数据库写失败
    }

    /**
     * 商户状态修
     * @param $seller_id 商户id
     * @param $status 商户状态
     * @param $remark 备注信息
     * @return array
     *  0商户状态修改成功
     *  650 状态参数有误
     *  651 锁定失败
     */
    public function updateStatusSeller($seller_id, $status, $remark)
    {
        $out_data = array();//返回信息
        if (!is_numeric($status)) {
            $out_data['code'] = 650;
            $out_data['info'] = "状态参数有误";
            return $out_data;//客户锁定失败
        } else if (intval($status) < 0 || intval($status) > 2) {
            $out_data['code'] = 650;
            $out_data['info'] = "状态参数有误";
            return $out_data;//客户锁定失败
        }
        $update_seller_data = array(
            'seller_status' => $status,
            'remark' => $remark,
            'update_time' => date("Y-m-d H:i:s")
        );
        if ($this->save($update_seller_data, array('id' => $seller_id))) {
            $out_data['code'] = 0;
            $out_data['info'] = "商户状态修改成功";
            return $out_data;//客户锁定成功
        }
        $out_data['code'] = 651;
        $out_data['info'] = "商户状态修改失败";
        return $out_data;//客户锁定失败
    }

    /**
     * 修改用户基本信息
     * @param $seller_data
     * 必须数据
     * seller_id 客户id
     * 非必须数据
     * login_password 登录密码
     * seller_group_id 权限分组
     * store_id 店铺id
     * @return array
     *  0 客户数据修改成功
     */
    public function updateSeller($seller_data)
    {
        $out_data = array();//返回信息
        $up_seller_data = array();//更新数据数组
        //获取客户id
        $seller_id = $seller_data['seller_id'];
        if (empty($seller_id)) {
            $out_data['code'] = 670;
            $out_data['info'] = "提交数据有误";
            return $out_data;//真实姓名不符合规范
        }
        $is_admin = $this->_verifySellerId($seller_id);//获取商户是否是管理员
        if ($is_admin === false) {
            $out_data['code'] = 671;
            $out_data['info'] = "账号不存在";
            return $out_data;//账号不存在
        }

        $seller_login_password = $seller_data['seller_login_password'];
        $is_login_password = false;
        if (!empty($seller_login_password)) {
            if (strlen($seller_login_password) < 3) {
                $out_data['code'] = 672;
                $out_data['info'] = "登录密码不符合规范";
                return $out_data;//登录密码不符合规范
            }
            if ($this->_addLoginPassword($seller_id, $seller_login_password) === false) {
                $out_data['code'] = 673;
                $out_data['info'] = "登录密码修改失败";
                return $out_data;//登录密码修改失败
            }
            $is_login_password = true;
        }
        if (intval($is_admin) == 0) {
            $seller_group_id = $seller_data['seller_group_id'];
            if (!is_numeric($seller_group_id)) {
                $out_data['code'] = 674;
                $out_data['info'] = "权限分组id不合法";
                return $out_data;//权限分组id不合法
            }
            $up_seller_data['seller_group_id'] = $seller_group_id;
            //获取手机号码
            $seller_mobile = $seller_data['seller_mobile'];
            if (!empty($seller_mobile)) {
                if (check_mobile_number($seller_mobile) === false) {
                    $out_data['code'] = 675;
                    $out_data['info'] = "手机号码不合法";
                    return $out_data;//手机号码不合法
                }
                $up_seller_data['seller_mobile'] = $seller_mobile;
            }
            //获取门店id
            $store_id = $seller_data['store_id'];
            if (is_numeric($store_id)) {
                $up_seller_data['store_id'] = $store_id;
            }
            //获取状态
            $seller_status = $seller_data['seller_status'];
            if (is_numeric($seller_status)) {
                $up_seller_data['seller_status'] = $seller_status;
            }
            //验证数据合法性完成
            $datetime = date("Y-m-d H:i:s");
            $up_seller_data['update_time'] = $datetime;
            if ($this->save($up_seller_data, array('id' => $seller_id))) {
                //更新权限
                $this->_updateSellerGrouopGroupAccess($seller_id, $seller_group_id);
                $out_data['code'] = 0;
                $out_data['info'] = "商户信息修改成功";
                return $out_data;//商户信息修改成功
            } else {
                $out_data['code'] = 675;
                $out_data['info'] = "商户信息修改失败";
                return $out_data;//商户信息修改成功
            }
        }
        if ($is_login_password) {
            $out_data['code'] = 0;
            $out_data['info'] = "商户信息修改成功";
            return $out_data;//商户信息修改成功
        }

        $out_data['code'] = 676;
        $out_data['info'] = "商户信息无数据改变";
        return $out_data;//商户信息无数据改变
    }

    /**
     * 商户申请入驻信息
     * @param $seller_subjoin_data
     * 必须数据
     * seller_id 客户id
     * 非必须数据
     * store_name 单位名称
     * store_principal 负责人姓名
     * store_mobile 负责人手机号码
     * store_tel 单位电话
     * province_id 省份id
     * city_id 市级id
     * area_id 地区id/县区id
     * street_id 街道id
     * address 地址
     * location_longitude 经度
     * location_latitude 纬度
     * principal_id 负责人身份证号码
     * principal_id_front_img 负责人身份证正面图片
     * principal_id_back_img 负责人身份证背面图片
     * principal_id_handheld_img 获取负责手持人身份证正面图片
     * business_license 营业执照编号或者组织代码
     * business_license_img 营业执照图片
     * protocol_img 入驻协议图片
     * business_start 营业开始时间
     * business_end 营业关店时间
     * @return array
     *  0 客户数据修改成功
     */
    public function addSellerSubjoin($seller_subjoin_data)
    {
        $out_data = array();//返回信息
        $in_seller_subjoin_data = array();//更新数据数组
        $in_seller_subjoin_data['seller_id'] = $seller_subjoin_data['seller_id'];
        //获取单位名称
        $store_name = $seller_subjoin_data['store_name'];
        //验证单位名称是否符合规范
        if (strlen($store_name) < 2) {
            $out_data['code'] = 606;
            $out_data['info'] = "单位名称不符合规范";
            return $out_data;//单位名称不符合规范
        }
        $in_seller_subjoin_data['store_name'] = $store_name;
        //获取负责人姓名
        $store_principal = $seller_subjoin_data['store_principal'];
        //验证负责人姓名是否符合规范
        if (strlen($store_principal) < 2) {
            $out_data['code'] = 607;
            $out_data['info'] = "负责人姓名不符合规范";
            return $out_data;//负责人姓名不符合规范
        }
        $in_seller_subjoin_data['store_principal'] = $store_principal;

        //获取负责人手机号码
        $store_mobile = $seller_subjoin_data['store_mobile'];
        //验证负责人手机号码是否符合规范
        if (!check_mobile_number($store_mobile)) {
            $out_data['code'] = 608;
            $out_data['info'] = "负责人手机号码不符合规范";
            return $out_data;//负责人手机号码不符合规范
        }
        $in_seller_subjoin_data['store_mobile'] = $store_mobile;

        //获取单位电话
        $store_tel = $seller_subjoin_data['store_tel'];
        //验证单位电话是否符合规范
        if (strlen($store_tel) < 6 || strlen($store_tel) > 20) {
            $out_data['code'] = 609;
            $out_data['info'] = "单位电话不符合规范";
            return $out_data;//单位电话不符合规范
        }
        $in_seller_subjoin_data['store_tel'] = $store_tel;

        //获取单位地址省级id
        $province_id = $seller_subjoin_data['province_id'];
        //验证单位地址省级id是否符合规范
        if (!is_numeric($province_id)) {
            $out_data['code'] = 610;
            $out_data['info'] = "单位地址省级id不符合规范";
            return $out_data;//单位地址省级id不符合规范
        }
        $in_seller_subjoin_data['province_id'] = $province_id;
        //获取单位地址市级id
        $city_id = $seller_subjoin_data['city_id'];
        //验证单位地址市级id是否符合规范
        if (!is_numeric($city_id)) {
            $out_data['code'] = 611;
            $out_data['info'] = "单位地址市级id不符合规范";
            return $out_data;//单位地址市级id不符合规范
        }
        $in_seller_subjoin_data['city_id'] = $city_id;

        //获取单位地址区/县级id
        $area_id = $seller_subjoin_data['area_id'];
        //验证区/县级id是否符合规范
        if (!is_numeric($area_id)) {
            $out_data['code'] = 612;
            $out_data['info'] = "单位地址区县级id不符合规范";
            return $out_data;//单位地址区县级id不符合规范
        }
        $in_seller_subjoin_data['area_id'] = $area_id;
        //街道id
        $street_id = $seller_subjoin_data['street_id'];
        if (is_numeric($street_id)) {
            $in_seller_subjoin_data['street_id'] = $street_id;
        }
        //获取单位地址
        $address = $seller_subjoin_data['address'];
        if (strlen($address) > 255 || strlen($address) < 2) {
            $out_data['code'] = 613;
            $out_data['info'] = "单位地址长度不符合规范";
            return $out_data;//单位地址长度不符合规范
        }
        $in_seller_subjoin_data['address'] = $address;
        //获取负责人身份证号码
        $principal_id = $seller_subjoin_data['principal_id'];
        if (idcard_checksum18($principal_id) === false) {
            $out_data['code'] = 614;
            $out_data['info'] = "负责人身份证号码不符合规范";
            return $out_data;//负责人身份证号码不符合规范
        }
        $in_seller_subjoin_data['principal_id'] = $principal_id;

        //获取负责人身份证正面照
        $principal_id_front_img = $seller_subjoin_data['principal_id_front_img'];
        if (empty($principal_id_front_img)) {
            $out_data['code'] = 615;
            $out_data['info'] = "负责人身份证正面照不能为空";
            return $out_data;//负责人身份证正面照不能为空
        }
        $in_seller_subjoin_data['principal_id_front_img'] = $principal_id_front_img;

        //获取负责人身份证背面照
        $principal_id_back_img = $seller_subjoin_data['principal_id_back_img'];
        if (empty($principal_id_back_img)) {
            $out_data['code'] = 616;
            $out_data['info'] = "负责人身份证背面照不能为空";
            return $out_data;//负责人身份证背面照不能为空
        }
        $in_seller_subjoin_data['principal_id_back_img'] = $principal_id_back_img;

        //获取负责手持人身份证正面图片
        $principal_id_handheld_img = $seller_subjoin_data['principal_id_handheld_img'];
        if (empty($principal_id_handheld_img)) {
            $out_data['code'] = 617;
            $out_data['info'] = "负责手持人身份证正面照不能为空";
            return $out_data;//负责手持人身份证正面照不能为空
        }
        $in_seller_subjoin_data['principal_id_handheld_img'] = $principal_id_handheld_img;

        //获取营业执照编号或者组织代码
        $business_license = $seller_subjoin_data['business_license'];
        if (empty($business_license)) {
            $out_data['code'] = 618;
            $out_data['info'] = "营业执照编号或者组织代码不能为空";
            return $out_data;//营业执照编号或者组织代码不能为空
        }
        $in_seller_subjoin_data['business_license'] = $business_license;

        //获取营业执照图片
        $business_license_img = $seller_subjoin_data['business_license_img'];
        if (empty($business_license_img)) {
            $out_data['code'] = 619;
            $out_data['info'] = "营业执照图片不能为空";
            return $out_data;//营业执照图片不能为空
        }
        $in_seller_subjoin_data['business_license_img'] = $business_license_img;

        //获取入驻协议图片
        $protocol_img = $seller_subjoin_data['protocol_img'];
        if (empty($protocol_img)) {
            $out_data['code'] = 620;
            $out_data['info'] = "入驻协议图片不能为空";
            return $out_data;//入驻协议图片不能为空
        }
        $location_longitude = $seller_subjoin_data['location_longitude'];
        if (is_numeric($location_longitude)) {
            $in_seller_subjoin_data['location_longitude'] = $location_longitude;
        }
        $location_latitude = $seller_subjoin_data['location_latitude'];
        if (is_numeric($location_latitude)) {
            $in_seller_subjoin_data['location_latitude'] = $location_latitude;
        }
        $in_seller_subjoin_data['protocol_img'] = $protocol_img;

        $business_start = $seller_subjoin_data['business_start'];
        if (!empty($business_start)) {
            $in_seller_subjoin_data['business_start'] = $business_start;
        }
        $business_end = $seller_subjoin_data['business_end'];
        if (!empty($business_end)) {
            $in_seller_subjoin_data['business_end'] = $business_end;
        }
        $datetime = date("Y-m-d H:i:s");
        $in_seller_subjoin_data['store_status'] = 0;
        $in_seller_subjoin_data['create_time'] = $datetime;
        $in_seller_subjoin_data['update_time'] = $datetime;
        $seller_subjoin_model = new SellerSubjoin();
        if ($seller_subjoin_model->save($in_seller_subjoin_data)) {
            $out_data['code'] = 0;
            $out_data['info'] = "商家入驻申请成功";
            return $out_data;//商家申请成功
        }
        $out_data['code'] = 621;
        $out_data['info'] = "商家入驻申请失败";
        return $out_data;//商家申请成功
    }

    /**
     * 修改商户申请入驻信息
     * @param $seller_subjoin_data
     * 必须数据
     * id 数据id
     * seller_id 商户id
     * 非必须数据
     * store_name 单位名称
     * store_principal 负责人姓名
     * store_mobile 负责人手机号码
     * store_tel 单位电话
     * province_id 省份id
     * city_id 市级id
     * area_id 地区id/县区id
     * street_id 街道id
     * address 客户地址
     * location_longitude 经度
     * location_latitude 纬度
     * principal_id 负责人身份证号码
     * principal_id_front_img 负责人身份证正面图片
     * principal_id_back_img 负责人身份证背面图片
     * principal_id_handheld_img 获取负责手持人身份证正面图片
     * business_license 营业执照编号或者组织代码
     * business_license_img 营业执照图片
     * protocol_img 入驻协议图片
     * business_start 营业开始时间
     * business_end 营业关店时间
     * @return array
     *  0 客户数据修改成功
     */
    public function updateSellerSubjoin($seller_subjoin_data)
    {
        $out_data = array();//返回信息
        $up_seller_subjoin_data = array();//更新数据数组
        //数据id
        $id = $seller_subjoin_data['id'];
        if (empty($id)) {
            $out_data['code'] = 680;
            $out_data['info'] = "提交数据有误";
            return $out_data;//提交数据有误
        }
        $seller_id = $this->_verifySellerSubjoinId($id);//获取商户id
        if ($seller_id === false) {
            $out_data['code'] = 681;
            $out_data['info'] = "申请入驻信息不存在";
            return $out_data;//申请入驻信息不存在
        }
        if (intval($seller_id) == $seller_subjoin_data['seller_id']) {
            $out_data['code'] = 682;
            $out_data['info'] = "不是管理员无权操作此数据";
            return $out_data;//不是管理员无权操作此数据
        }
        //获取单位名称
        $store_name = $seller_subjoin_data['store_name'];
        if (!empty($store_name)) {
            //验证单位名称是否符合规范
            if (strlen($store_name) < 2) {
                $out_data['code'] = 683;
                $out_data['info'] = "单位名称不符合规范";
                return $out_data;//单位名称不符合规范
            }
            $up_seller_subjoin_data['store_name'] = $store_name;
        }
        //获取负责人姓名
        $store_principal = $seller_subjoin_data['store_principal'];
        if (!empty($store_principal)) {
            //验证负责人姓名是否符合规范
            if (strlen($store_principal) < 2) {
                $out_data['code'] = 684;
                $out_data['info'] = "负责人姓名不符合规范";
                return $out_data;//负责人姓名不符合规范
            }
            $up_seller_subjoin_data['store_principal'] = $store_principal;
        }
        //获取负责人手机号码
        $store_mobile = $seller_subjoin_data['store_mobile'];
        if (!empty($store_mobile)) {
            //验证负责人手机号码是否符合规范
            if (!check_mobile_number($store_mobile)) {
                $out_data['code'] = 685;
                $out_data['info'] = "负责人手机号码不符合规范";
                return $out_data;//负责人手机号码不符合规范
            }
            $up_seller_subjoin_data['store_mobile'] = $store_mobile;
        }
        //获取单位电话
        $store_tel = $seller_subjoin_data['store_tel'];
        if (!empty($store_tel)) {
            //验证单位电话是否符合规范
            if (strlen($store_tel) < 6 || strlen($store_tel) > 20) {
                $out_data['code'] = 686;
                $out_data['info'] = "单位电话不符合规范";
                return $out_data;//单位电话不符合规范
            }
            $up_seller_subjoin_data['store_tel'] = $store_tel;
        }
        //获取单位地址省级id
        $province_id = $seller_subjoin_data['province_id'];
        //验证单位地址省级id是否符合规范
        if (!is_numeric($province_id)) {
            if (!empty($province_id)) {
                $out_data['code'] = 687;
                $out_data['info'] = "单位地址省级id不符合规范";
                return $out_data;//单位地址省级id不符合规范
            }
        } else {
            $up_seller_subjoin_data['province_id'] = $province_id;
        }
        //获取单位地址市级id
        $city_id = $seller_subjoin_data['city_id'];
        //验证单位地址市级id是否符合规范
        if (!is_numeric($city_id)) {
            if (!empty($city_id)) {
                $out_data['code'] = 688;
                $out_data['info'] = "单位地址市级id不符合规范";
                return $out_data;//单位地址市级id不符合规范
            }
        } else {
            $up_seller_subjoin_data['city_id'] = $city_id;
        }
        //获取单位地址区/县级id
        $area_id = $seller_subjoin_data['area_id'];
        //验证区/县级id是否符合规范
        if (!is_numeric($area_id)) {
            if (!empty($area_id)) {
                $out_data['code'] = 689;
                $out_data['info'] = "单位地址区县级id不符合规范";
                return $out_data;//单位地址区县级id不符合规范
            }
        } else {
            $up_seller_subjoin_data['area_id'] = $area_id;
        }
        //街道id
        $street_id = $seller_subjoin_data['street_id'];
        if (is_numeric($street_id)) {
            $up_seller_subjoin_data['street_id'] = $street_id;
        }
        //获取单位地址
        $address = $seller_subjoin_data['address'];
        if (!empty($address)) {
            if (strlen($address) > 255 || strlen($address) < 2) {
                $out_data['code'] = 690;
                $out_data['info'] = "单位地址长度不符合规范";
                return $out_data;//单位地址长度不符合规范
            }
            $up_seller_subjoin_data['address'] = $address;
        }
        //获取负责人身份证号码
        $principal_id = $seller_subjoin_data['principal_id'];
        if (!empty($principal_id)) {
            if (idcard_checksum18($principal_id) === false) {
                $out_data['code'] = 691;
                $out_data['info'] = "负责人身份证号码不符合规范";
                return $out_data;//负责人身份证号码不符合规范
            }
            $up_seller_subjoin_data['principal_id'] = $principal_id;
        }
        //获取负责人身份证正面照
        $principal_id_front_img = $seller_subjoin_data['principal_id_front_img'];
        if (!empty($principal_id_front_img)) {
            $up_seller_subjoin_data['principal_id_front_img'] = $principal_id_front_img;
        }
        //获取负责人身份证背面照
        $principal_id_back_img = $seller_subjoin_data['principal_id_back_img'];
        if (!empty($principal_id_back_img)) {
            $up_seller_subjoin_data['principal_id_back_img'] = $principal_id_back_img;
        }
        //获取负责手持人身份证正面图片
        $principal_id_handheld_img = $seller_subjoin_data['principal_id_handheld_img'];
        if (!empty($principal_id_handheld_img)) {
            $up_seller_subjoin_data['principal_id_handheld_img'] = $principal_id_handheld_img;
        }
        //获取营业执照编号或者组织代码
        $business_license = $seller_subjoin_data['business_license'];
        if (!empty($business_license)) {
            $up_seller_subjoin_data['business_license'] = $business_license;
        }
        //获取营业执照图片
        $business_license_img = $seller_subjoin_data['business_license_img'];
        if (!empty($business_license_img)) {
            $up_seller_subjoin_data['business_license_img'] = $business_license_img;
        }
        //获取入驻协议图片
        $protocol_img = $seller_subjoin_data['protocol_img'];
        if (!empty($protocol_img)) {
            $up_seller_subjoin_data['protocol_img'] = $protocol_img;
        }
        $location_longitude = $seller_subjoin_data['location_longitude'];
        if (is_numeric($location_longitude)) {
            $up_seller_subjoin_data['location_longitude'] = $location_longitude;
        }
        $location_latitude = $seller_subjoin_data['location_latitude'];
        if (is_numeric($location_latitude)) {
            $up_seller_subjoin_data['location_latitude'] = $location_latitude;
        }
        $up_seller_subjoin_data['update_time'] = date("Y-m-d H:i:s");

        $business_start = $seller_subjoin_data['business_start'];
        if (!empty($business_start)) {
            $up_seller_subjoin_data['business_start'] = $business_start;
        }
        $business_end = $seller_subjoin_data['business_end'];
        if (!empty($business_end)) {
            $up_seller_subjoin_data['business_end'] = $business_end;
        }
        $up_seller_subjoin_data['store_status'] = 0;
        $sellersubjoin_model = new SellerSubjoin();
        if ($sellersubjoin_model->save($up_seller_subjoin_data, array('id' => $id))) {
            $out_data['code'] = 0;
            $out_data['info'] = "商户申请入驻信息修改成功";
            return $out_data;//商户申请入驻信息修改成功
        }
        $out_data['code'] = 693;
        $out_data['info'] = "商户申请入驻信息修改失败";
        return $out_data;//商户申请入驻信息修改失败
    }

    /**
     * 审核 商户申请入驻信息
     * @param $id
     * @param $remark
     * @param $check_manage_id
     * @return array
     */
    public function checkSellerSubjoin($id, $remark, $check_manage_id)
    {
        $out_data = array();//返回信息
        $up_seller_subjoin_data = array();//更新数据数组
        //数据id
        if (empty($id)) {
            $out_data['code'] = 700;
            $out_data['info'] = "提交数据有误";
            return $out_data;//提交数据有误
        }
        //获取申请信息
        $sellersubjoin = $this->_verifySellerSubjoinStatus($id);
        if ($sellersubjoin === false) {
            $out_data['code'] = 701;
            $out_data['info'] = "商户申请入驻信息有误";
            return $out_data;//商户申请入驻信息有误
        } else if (intval($sellersubjoin['store_status']) == 2) {
            $out_data['code'] = 702;
            $out_data['info'] = "已审核过,无法再次审核";
            return $out_data;//提交数据有误
        }
        $up_seller_subjoin_data['store_status'] = 2;
        //获取更改状态
        //备注信息不能为空
        if (empty($remark)) {
            $out_data['code'] = 703;
            $out_data['info'] = "备注信息不能为空";
            return $out_data;//备注信息不能为空
        }
        $up_seller_subjoin_data['remark'] = $remark;
        $up_seller_subjoin_data['check_manage_id'] = $check_manage_id;
        $sellersubjoin_model = new SellerSubjoin();
        if ($sellersubjoin_model->save($up_seller_subjoin_data, array('id' => $id))) {
            $in_store_data = array(
                'seller_id' => $sellersubjoin['seller_id'],
                'store_pid' => 0,
                'store_name' => $sellersubjoin['store_name'],
                'store_principal' => $sellersubjoin['store_principal'],
                'store_tel' => $sellersubjoin['store_tel'],
                'province_id' => $sellersubjoin['province_id'],
                'city_id' => $sellersubjoin['city_id'],
                'area_id' => $sellersubjoin['area_id'],
                'street_id' => $sellersubjoin['street_id'],
                'address' => $sellersubjoin['address'],
                'store_charging_is' => 0,
                'store_scope' => 200,
                'location_longitude' => $sellersubjoin['location_longitude'],
                'location_latitude' => $sellersubjoin['location_latitude'],
                'business_start' => $sellersubjoin['business_start'],
                'business_end' => $sellersubjoin['business_end'],
                'is_area' => 0,
                'store_key_id' => 0,
            );
            $store_model = new Store();
            $store_out_data = $store_model->addStore($in_store_data);
            if ($store_out_data['code'] == 0) {
                //更新商户店铺id
                if ($this->_updateSellerStore($sellersubjoin['seller_id'], $store_out_data['store_id'])) {
                    $out_data['code'] = 0;
                    $out_data['info'] = "商户申请入驻信息审核成功";
                    return $out_data;//商户申请入驻信息审核成功
                } else {
                    $out_data['code'] = 704;
                    $out_data['info'] = "商户店铺id绑定失败";
                    return $out_data;//商户店铺id绑定失败
                }
            }
            return $store_out_data;
        }
        $out_data['code'] = 705;
        $out_data['info'] = "商户申请入驻信息审核失败";
        return $out_data;//商户申请入驻信息审核失败
    }

    /**
     * 商户登录
     * @param $seller_data
     * 必须数据
     * seller_name 商户用户名
     * login_password 登录密码
     * @return array
     *  0 登录成功
     */
    public function loginSeller($seller_data)
    {
        //返回参数
        $out_data = array();
        //验证数据合法性
        $seller_name = $seller_data['seller_name'];
        if (strlen($seller_name) < 2) {
            $out_data['code'] = 301;
            $out_data['info'] = "商户用户名不合法";
            return $out_data;//商户用户名不合法
        }
        $login_password = $seller_data['seller_login_password'];
        if (strlen($login_password) < 3) {
            $out_data['code'] = 302;
            $out_data['info'] = "登录密码不符合规范";
            return $out_data;//登录密码不符合规范
        }
        //获取登录客户信息
        $seller = $this->where(array('seller_name' => $seller_name))->find();
        if (empty($seller)) {
            $out_data['code'] = 303;
            $out_data['info'] = "账号不存在";
            return $out_data;//账号不存在
        }
        if (intval($seller['seller_status']) == 2) {
            $out_data['code'] = 304;
            $out_data['info'] = "账号已被锁定";
            return $out_data;//账号不存在
        }
        //验证登录客户登录密码
        if ($this->_verifyLoginPassword($seller['id'], $login_password) === false) {
            $out_data['code'] = 305;
            $out_data['info'] = "账号密码不匹配";
            return $out_data;//账号密码不匹配
        }
        //添加客户对话随机字符串
        $token = $this->_addSellerToken($seller['id']);
        if (empty($token)) {
            $out_data['code'] = 306;
            $out_data['info'] = "登录失败";
            return $out_data;//登录失败
        }
        //添加登录日志
        $this->_addSellerLog($seller['id'], $seller['seller_name']);
        //将用户登录数据存储（Session中）
        $this->_cannedSellerData($seller);
        $out_data['code'] = 0;
        //登录成功之后 返回客户数据
        $data_token = array(
            'seller_id' => $seller['id'],
            'seller_name' => $seller['seller_name'],
            'seller_group_id' => $seller['seller_group_id'],
            'parent_id' => $seller['parent_id'],
            'store_id' => $seller['store_id'],
            'is_admin' => $seller['is_admin'],
            'seller_token' => $token
        );
        $out_data['data'] = $data_token;
        $out_data['info'] = "登录成功";
        return $out_data;//登录成功
    }

    /**
     * 商户登录
     * @param $seller_data
     * 必须数据
     * seller_name 商户用户名
     * login_password 登录密码
     * @return array
     *  0 登录成功
     */
    public function loginSellerMobile($seller_data)
    {
        //返回参数
        $out_data = array();
        //验证数据合法性
        $seller_mobile = $seller_data['seller_mobile'];
        if (check_mobile_number($seller_mobile) === false) {
            $out_data['code'] = 301;
            $out_data['info'] = "商户电话号码不合法";
            return $out_data;//商户用户名不合法
        }
        $login_password = $seller_data['seller_login_password'];
        if (strlen($login_password) < 3) {
            $out_data['code'] = 302;
            $out_data['info'] = "登录密码不符合规范";
            return $out_data;//登录密码不符合规范
        }
        //获取登录客户信息
        $seller = $this->where(array('seller_mobile' => $seller_mobile))->find();
        if (empty($seller)) {
            $out_data['code'] = 303;
            $out_data['info'] = "账号不存在";
            return $out_data;//账号不存在
        }
        if (intval($seller['seller_status']) == 2) {
            $out_data['code'] = 304;
            $out_data['info'] = "账号已被锁定";
            return $out_data;//账号不存在
        }
        //验证登录客户登录密码
        if ($this->_verifyLoginPassword($seller['id'], $login_password) === false) {
            $out_data['code'] = 305;
            $out_data['info'] = "账号密码不匹配";
            return $out_data;//账号密码不匹配
        }
        //添加客户对话随机字符串
        $token = $this->_addSellerToken($seller['id']);
        if (empty($token)) {
            $out_data['code'] = 306;
            $out_data['info'] = "登录失败";
            return $out_data;//登录失败
        }
        //添加登录日志
        $this->_addSellerLog($seller['id'], $seller['seller_name']);
        //将用户登录数据存储（Session中）
        $this->_cannedSellerData($seller);
        $out_data['code'] = 0;
        //登录成功之后 返回客户数据
        $data_token = array(
            'seller_id' => $seller['id'],
            'seller_name' => $seller['seller_name'],
            'seller_group_id' => $seller['seller_group_id'],
            'parent_id' => $seller['parent_id'],
            'store_id' => $seller['store_id'],
            'is_admin' => $seller['is_admin'],
            'seller_token' => $token
        );
        $out_data['data'] = $data_token;
        $out_data['info'] = "登录成功";
        return $out_data;//登录成功
    }

    /**
     * 验证 客户对话
     * @param $token
     * @return bool
     */
    public function verifySellerToken($token)
    {
        //如果缓存中对话中无用户信息
//        if (!$this->_verifyCacheSellerData()) {
        //验证客户对话
        $seller_id = $this->_verifySellerToken($token);
        if (!empty($seller_id)) {
            //验证成功之后
            $seller = $this->where(array('id' => $seller_id))->find();
            //将用户登录数据存储（Session中）
            $this->_cannedSellerData($seller);
            return true;
        }
        return false;
//        }
//        return true;
    }

    /**
     * 注销用户登录
     * @param $token
     */
    public function logoutSeller($token)
    {
//        if (!$this->_verifyCacheSellerData()) {
        //验证客户对话
        $seller_id = $this->_verifySellerToken($token);
        if (!empty($seller_id)) {
            $seller_token_model = new SellerToken();
            $w = array(
                'token' => $token
            );
            $seller_token_model->where($w)->delete();
            //清除用户登录数据存储（Session中）
            $this->_clearSellerData();
        }
//        }
    }

    /**
     *修改用户登录密码
     * @param $id 修改的用户id
     * @param $login_password 登录密码
     * @return bool
     */
    public function updateSellerLoginPassword($id, $login_password, $old_password = '', $verify = true)
    {
        if ($verify) {
            if ($this->_verifyLoginPassword($id, $old_password)) {
                if ($this->_addLoginPassword($id, $login_password)) {
                    return true;
                }
            }
        } else {
            if ($this->_addLoginPassword($id, $login_password)) {
                return true;
            }
        }
        return false;
    }

    /**
     *修改用户支付密码
     * @param $id 修改的用户id
     * @param $pay_password 支付密码
     * @return bool
     */
    public function updateSellerPayPassword($id, $pay_password, $old_password = '', $verify = false)
    {
        if ($verify) {
            if ($this->_verifyPayPassword($id, $old_password)) {
                if ($this->_addPayPassword($id, $pay_password)) {
                    return true;
                }
            }
        } else {
            if ($this->_addPayPassword($id, $pay_password)) {
                return true;
            }
        }
        return false;
    }

    /**
     *验证用户支付密码
     * @param $id 验证的用户id
     * @param $pay_password 支付密码
     * @return bool 成功 true 失败 false
     */
    public function verifySellerPayPassword($id, $pay_password)
    {
        if ($this->_verifyPayPassword($id, $pay_password)) {
            return true; //成功
        }
        return false;//失败
    }

    /**
     * 锁定管理员
     * @param $id 数据id
     * @param bool $is_admin 是否是管理员
     * @param string $store_key_id 总店id
     * @return bool
     */
    public function addLockSeller($id, $is_admin = false, $store_key_id = '')
    {
        if ($is_admin) {
            $lock_seller_data = array(
                'seller_status' => 2
            );
            if ($this->save($lock_seller_data, array('id' => $id))) {
                return true;//锁定管理员成功
            }
            return false;//锁定管理员失败
        } else {
            $lock_seller_data = array(
                'seller_status' => 2
            );
            if ($this->save($lock_seller_data, array('id' => $id, 'store_key_id' => $store_key_id))) {
                return true;//锁定管理员成功
            }
            return false;//锁定管理员失败
        }
    }

    /**
     * 解除锁定管理员
     * @param $id 数据id
     * @param bool $is_admin 是否是管理员
     * @param string $store_key_id 总店id
     * @return bool
     */
    public function delLockSeller($id, $is_admin = false, $store_key_id = '')
    {
        if ($is_admin) {
            $lock_seller_data = array(
                'seller_status' => 1
            );
            if ($this->save($lock_seller_data, array('id' => $id))) {
                return true;//解除锁定管理员成功
            }
            return false;//解除锁定管理员失败
        } else {
            $lock_seller_data = array(
                'seller_status' => 1
            );
            if ($this->save($lock_seller_data, array('id' => $id, 'store_key_id' => $store_key_id))) {
                return true;//解除锁定管理员成功
            }
            return false;//解除锁定管理员失败
        }

    }

    /**
     * 更新用户权限绑定关系
     * @param int $id 管理员id
     * @param int $seller_group_id 权限组id
     * @return bool
     */
    private function _updateSellerGrouopGroupAccess($id, $seller_group_id)
    {
        $groupaccess_model = new SellerGroupAccess();
        $groupaccess = $groupaccess_model->where(array('seller_id' => $id))->find();
        if (empty($groupaccess)) {
            $in_group_access = array(
                'seller_id' => $id,
                'group_id' => $seller_group_id,
            );
            if ($groupaccess_model->save($in_group_access)) {
                return true;
            }
            return false;
        } else {
            $groupaccess->group_id = $seller_group_id;
            if ($groupaccess->save()) {
                return true;
            }
            return false;
        }
    }

    /**
     * 更新商户店铺id
     * @param $seller_id
     * @param $store_id
     * @return bool
     */
    private function _updateSellerStore($seller_id, $store_id)
    {
        if ($this->where(array('id' => $seller_id))->setField(array('is_admin' => 1, 'store_id' => $store_id, 'store_key_id' => $store_id, 'seller_status' => 1))) {
            return true;
        }
        return false;
    }

    /**
     * 验证账号是否存在
     * @param $seller_name 商家用户名称
     * @return bool
     */
    private function _verifySeller($seller_name)
    {
        //检查电话号码是否符合格式
        if (!empty($seller_name)) {
            //验证账号是否已经注册
            $seller = $this->where(array('seller_name' => $seller_name))->find();
            if (!empty($seller)) {
                return true;//账号已被注册
            }
        }
        return false;//账号不存在 用户名格式有误
    }

    /**
     * 验证商家是否存在 返回商家是否是管理员
     * @param $seller_id
     * @return bool|mixed
     */
    private function _verifySellerId($seller_id)
    {
        //检查电话号码是否符合格式
        if (!empty($seller_id)) {
            //验证账号是否已经注册
            $seller = $this->where(array('id' => $seller_id))->find();
            if (!empty($seller)) {
                return $seller['is_admin'];//账号已被注册
            }
        }
        return false;//账号不存在 用户名格式有误
    }

    /**
     * 验证商家申请信息是否存在 返回商家id
     * @param $id
     * @return bool|mixed
     */
    private function _verifySellerSubjoinId($id)
    {
        if (!empty($id)) {
            $sellersubjoin_model = new SellerSubjoin();
            //验证商家申请信息是否存在
            $sellersubjoin = $sellersubjoin_model->where(array('id' => $id, 'store_status' => 1))->find();
            if (!empty($sellersubjoin)) {
                return $sellersubjoin['seller_id'];//商户id
            }
        }
        return false;//商家申请信息不存在
    }

    /**
     * 验证商家申请信息是否存在 返回审核状态
     * @param $id
     * @return bool|mixed
     */
    private function _verifySellerSubjoinStatus($id)
    {
        if (!empty($id)) {
            $sellersubjoin_model = new SellerSubjoin();
            //验证商家申请信息是否存在
            $sellersubjoin = $sellersubjoin_model->where(array('id' => $id))->find();
            if (!empty($sellersubjoin)) {
                return $sellersubjoin;//商户申请信息
            }
        }
        return false;//商家申请信息不存在
    }

    /**
     * 添加商家登录密码
     * 登录密码为最近一次值有效
     * @param int $id 商家id
     * @param string $login_password 登录密码
     * @return bool
     */
    private function _addLoginPassword($id, $login_password)
    {
        $in_login_password_data = array(
            'seller_id' => $id,
            'login_password' => md5(md5($login_password)),
            'on_set' => time(),
            'by_ip' => getIp()
        );
        $login_password_model = new SellerLoginPassword();
        if ($login_password_model->save($in_login_password_data)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 验证登录密码
     * 登录密码为最近一次值有效
     * @param $id 商家id
     * @param $login_password 登录密码
     * @return bool
     */
    private function _verifyLoginPassword($id, $login_password)
    {
        $login_password_model = new SellerLoginPassword();
        $seller_password = $login_password_model->where(array('seller_id' => $id))->order('on_set DESC')->find();
        //判断密码是否正确
        if ($seller_password['login_password'] === md5(md5($login_password))) {
            return true;//账号密码匹配成功
        }
        return false;
    }

    /**
     * 添加商家支付密码
     * 支付密码为最近一次值有效
     * @param $id 商家id
     * @param $pay_password 支付密码
     * @return bool
     */
    private function _addPayPassword($id, $pay_password)
    {
        $in_pay_password_data = array(
            'seller_id' => $id,
            'pay_password' => md5(md5($pay_password)),
            'on_set' => time(),
            'by_ip' => getIp()
        );
        $pay_password_model = new SellerPayPassword();
        if ($pay_password_model->save($in_pay_password_data)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 验证支付密码
     * 登录密码为最近一次值有效
     * @param $id 商家id
     * @param $pay_password 登录密码
     * @return bool
     */
    private function _verifyPayPassword($id, $pay_password)
    {
        $pay_password_model = new SellerPayPassword();
        $seller_password = $pay_password_model->where(array('seller_id' => $id))->order('on_set DESC')->find();
        //判断密码是否正确
        if ($seller_password['pay_password'] === md5(md5($pay_password))) {
            return true;//账号密码匹配成功
        }
        return false;
    }

    /**
     * 添加商家操作日志
     * @param $id
     * @param $seller_name
     * @param string $content
     * @return bool
     */
    private function _addSellerLog($id, $seller_name = '', $content = "登录操作")
    {
        $in_seller_log_data = array(
            'seller_id' => $id,
            'seller_name' => $seller_name,
            'content' => $content,
            'create_time' => time(),
            'by_ip' => getIp()
        );
        $seller_log_model = new SellerLog();
        if ($seller_log_model->save($in_seller_log_data)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 添加客户对话随机字符串
     * @param $id
     * @return bool
     */
    private function _addSellerToken($id)
    {
        $seller_token_model = new SellerToken();
        //清除之前的对话
        $seller_token_model->where(array('seller_id' => $id))->delete();
        //构造对话数据
        $token = $this->_creatToken();
        $in_seller_token_data = array(
            'seller_id' => $id,
            'token' => $token,
            'on_set' => time(),
            'by_ip' => getIp()
        );
        //插入对话数据
        if ($seller_token_model->save($in_seller_token_data)) {
            return $token;
        } else {
            return false;
        }
    }

    /**
     * 验证商家对话随机字符串
     * @param $token
     * @return bool|mixed
     */
    private function _verifySellerToken($token)
    {
        $seller_token_model = new SellerToken();
        $w = array(
            'token' => $token
        );
        $seller_token = $seller_token_model->where($w)->find();
        if (empty($seller_token)) {
            return 0;//对话过期
        }
        $token_out_time = Config::get('token_out_time');
        if ((time() - $seller_token['on_set']) > $token_out_time) {
            //如果对话过期 删除无用信息
            $seller_token_model->where($w)->delete();
            return 0;//对话过期
        }
        return $seller_token['seller_id'];
    }

    //产生随机对话加密字符串
    private function _creatToken()
    {
        $token = MD5(random_char(17) . date("YmdHis") . random_char(17) . time());
        return $token;
    }

    /**
     * 将商家信息存储到 Session中
     * @param $seller_data
     */
    private function _cannedSellerData($seller_data)
    {
        //如果用户数据存在
        if (!empty($seller_data)) {
            $store_model = new Store();
            $store_key_name = $store_model->getStoreName($seller_data['store_key_id']);
            //将数据存储到Session
            Session::set('seller_id', $seller_data['id']);
            Session::set('store_id', $seller_data['store_id']);
            Session::set('store_key_id', $seller_data['store_key_id']);
            Session::set('store_key_name', $store_key_name);
            Session::set('seller_name', $seller_data['seller_name']);
            Session::set('is_admin', $seller_data['is_admin']);
            Session::set('seller_status', $seller_data['seller_status']);
            Session::set('seller_data', json_encode($seller_data));
        }
    }

    /**
     * 验证对话有效性
     * @return bool
     */
    private function _verifyCacheSellerData()
    {
        $seller_id = Session::get('seller_id');
        //如果用户数据存在
        if (!empty($seller_id)) {
            return true;
        }
        return false;
    }

    /**
     * 将客户信息从Session中清除掉
     */
    private function _clearSellerData()
    {
        Session::delete('seller_id');
        Session::delete('store_id');
        Session::delete('seller_name');
        Session::delete('store_key_id');
        Session::delete('store_key_name');
        Session::delete('is_admin');
        Session::delete('seller_status');
        Session::delete('seller_data');
    }


}