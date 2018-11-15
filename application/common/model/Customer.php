<?php

namespace app\common\model;

/**
 * 客户数据模型
 * 实现用户的基础数据操作
 * 添加用户
 * 修改用户信息
 * 用户登录
 */

use chinadatapay\ChinaDataApi;
use definition\CustomerStatus;
use definition\RegistType;
use think\Config;
use think\Cookie;
use think\Model;
use think\Session;
use tool\OpensslAES;

class Customer extends Model
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
     *
     * @param $data
     */
    public function formatx(&$data)
    {
        if (isset($data['customer_balance'])) {
            $data['customer_balance'] = OpensslAES::decryptWithOpenssl($data['customer_balance']);
        }
        $data['customer_status_str'] = CustomerStatus::$CUSTOMERSTATUS_CODE[intval($data['customer_status'])];
    }

    /**
     * 获取用户基本信息
     * @param string $mobile_phone 电话号码 电话号码优先使用
     * @param string $customer_id 客户id
     * @param bool $is_admin 是否是管理员操作
     * @return mixed
     */
    public function getCustomer($mobile_phone = '', $customer_id = '', $is_admin = false)
    {
        if (!empty($mobile_phone)) {
            $customer = $this->where(array('mobile_phone' => $mobile_phone))->find();
            if (empty($customer)) {
                $out_data['code'] = 405;
                $out_data['info'] = "账号不存在";
                return $out_data;//账号不存在
            }
            if (intval($customer['customer_status']) == 2 && $is_admin === false) {
                $out_data['code'] = 404;
                $out_data['info'] = "账号已被锁定";
                return $out_data;//账号已被锁定
            }
            $customer['customer_balance'] = OpensslAES::decryptWithOpenssl($customer['customer_balance']);
            $out_data['code'] = 0;
            $out_data['data'] = $customer;
            $address_config = array(
                'default' => false,
                'type' => 1,
                'province' => "",
                'city' => "",
                'area' => "",
                'street' => "",
            );
            if (!empty($customer['province_id']) && !empty($customer['city_id']) && !empty($customer['area_id'])) {
                $address_config['default'] = true;
                $address_config['type'] = 4;
                $address_config['province'] = $customer['province_id'] . "";
                $address_config['city'] = $customer['city_id'] . "";
                $address_config['area'] = $customer['area_id'] . "";
                $address_config['street'] = $customer['street_id'] . "";
            }
            $out_data['address_config'] = $address_config;
            $out_data['info'] = "获取成功";
            return $out_data;//获取成功
        } else if (!empty($customer_id)) {
            $customer = $this->where(array('id' => $customer_id))->find();
            if (empty($customer)) {
                $out_data['code'] = 405;
                $out_data['info'] = "账号不存在";
                return $out_data;//账号不存在
            }
            if (intval($customer['customer_status']) == 2 && $is_admin === false) {
                $out_data['code'] = 404;
                $out_data['info'] = "账号已被锁定";
                return $out_data;//账号已被锁定
            }
            $customer['customer_balance'] = OpensslAES::decryptWithOpenssl($customer['customer_balance']);
            $out_data['code'] = 0;
            $out_data['data'] = $customer;
            $address_config = array(
                'default' => false,
                'type' => 1,
                'province' => "",
                'city' => "",
                'area' => "",
                'street' => "",
            );
            if (!empty($customer['province_id']) && !empty($customer['city_id']) && !empty($customer['area_id'])) {
                $address_config['default'] = true;
                $address_config['type'] = 4;
                $address_config['province'] = $customer['province_id'] . "";
                $address_config['city'] = $customer['city_id'] . "";
                $address_config['area'] = $customer['area_id'] . "";
                $address_config['street'] = $customer['street_id'] . "";
            }
            $out_data['address_config'] = $address_config;
            $out_data['info'] = "获取成功";
            return $out_data;//获取成功
        }
        $out_data['code'] = 405;
        $out_data['info'] = "账号不存在";
        return $out_data;//账号不存在
    }

    /**
     * 获取用户指定字段数据
     * @param string $customer_id
     * @param $field
     * @return mixed
     */
    public function getCustomerField($customer_id = '', $field)
    {
        $customer = $this->where(array('id' => $customer_id))->field($field)->find();
        if (empty($customer)) {
            $out_data['code'] = 405;
            $out_data['info'] = "账号不存在";
            return $out_data;//账号不存在
        }
        if (isset($customer['customer_balance'])) {
            $customer['customer_balance'] = OpensslAES::decryptWithOpenssl($customer['customer_balance']);
        }
        $out_data['code'] = 0;
        $out_data['data'] = $customer;
        $out_data['info'] = "获取成功";
        return $out_data;//获取成功
    }

    /**
     * 获取用户基本信息
     * @param string $wechar_openid 微信唯一标识
     * @return mixed
     */
    public function getWecharCustomer($wechar_openid = '')
    {
        $customer = $this->where(array('wechar_openid' => $wechar_openid))->find();
        if (empty($customer)) {
            $out_data['code'] = 405;
            $out_data['info'] = "账号不存在";
            return $out_data;//账号不存在
        }
        $customer['customer_balance'] = OpensslAES::decryptWithOpenssl($customer['customer_balance']);
        $out_data['code'] = 0;
        $out_data['data'] = $customer;
        $address_config = array(
            'default' => false,
            'type' => 1,
            'province' => "",
            'city' => "",
            'area' => "",
            'street' => "",
        );
        if (!empty($customer['province_id']) && !empty($customer['city_id']) && !empty($customer['area_id'])) {
            $address_config['default'] = true;
            $address_config['type'] = 4;
            $address_config['province'] = $customer['province_id'] . "";
            $address_config['city'] = $customer['city_id'] . "";
            $address_config['area'] = $customer['area_id'] . "";
            $address_config['street'] = $customer['street_id'] . "";
        }
        $out_data['address_config'] = $address_config;
        $out_data['info'] = "获取成功";
        return $out_data;//获取成功
    }

    /**
     * 添加用户
     * @param $customer_data
     * 必须数据
     * mobile_phone 手机号码
     * login_password 登录密码
     * regist_type 注册渠道
     * customer_status 客户状态
     * 非必须数据
     * customer_name 客户真实姓名
     * customer_nickname 客户昵称
     * customer_sex 客户性别
     * province_id 省份id
     * city_id 市级id
     * area_id 区、县id
     * street_id 街道id
     * address 客户地址
     * id_number 身份证号码
     * id_number_image_front 身份证照片正面
     * id_number_image_reverse 身份证照片反面
     * driver_license 驾驶证档案编号
     * driver_license_image_front 驾驶证档案照片_正面
     * driver_license_image_attach 驾驶证档案照片_副页
     * channel_uid 用户来源渠道id
     * qq 客户qq号
     * wechar 微信号
     * @return array
     *  0 用户添加成功
     */
    public function addCustomer($customer_data)
    {
        $out_data = array();
        //验证数据合法性
        $mobile_phone = $customer_data['mobile_phone'];
        if ($this->verifyCustomer($mobile_phone) === true) {
            $out_data['code'] = 101;
            $out_data['info'] = "账号已被注册";
            return $out_data;//账号已被注册
        }
        if (check_mobile_number($mobile_phone) === false) {
            $out_data['code'] = 102;
            $out_data['info'] = "手机号码不正确";
            return $out_data;//手机号码不正确
        }
        $login_password = $customer_data['login_password'];
        if (strlen($login_password) < 8) {
            $out_data['code'] = 103;
            $out_data['info'] = "登录密码不符合规范";
            return $out_data;//登录密码不符合规范
        }
        $regist_type = $customer_data['regist_type'];
        if (intval($regist_type) < 1 || intval($regist_type) > count(RegistType::$REGISTTYPE_CODE) + 1) {
            $out_data['code'] = 104;
            $out_data['info'] = "注册渠道不合法";
            return $out_data;//注册渠道不合法
        }
        //验证数据合法性完成
        $datetime = date("Y-m-d H:i:s");
        $in_customer_data = array(
            'mobile_phone' => $mobile_phone,
            'regist_type' => $regist_type,
            'create_time' => $datetime,
            'on_modifie' => $datetime
        );
        //获取真实姓名
        $customer_name = $customer_data['customer_name'];
        if (!empty($customer_name)) {
            //验证真实姓名是否符合规范
            if (strlen($customer_name) < 2) {
                $out_data['code'] = 105;
                $out_data['info'] = "真实姓名不符合规范";
                return $out_data;//真实姓名不符合规范
            }
            $in_customer_data['customer_name'] = $customer_name;
        }
        //获取客户昵称
        $customer_nickname = $customer_data['customer_nickname'];
        if (!empty($customer_nickname)) {
            //验证客户昵称是否符合规范
            if (strlen($customer_nickname) > 50) {
                $out_data['code'] = 106;
                $out_data['info'] = "客户昵称不符合规范";
                return $out_data;//客户昵称不符合规范
            }
            $in_customer_data['customer_nickname'] = $customer_nickname;
        }
        //获取客户性别
        $customer_sex = $customer_data['customer_sex'];
        if (is_numeric($customer_sex)) {
            if (intval($customer_sex) > 1 || intval($customer_sex) < 0) {
                $out_data['code'] = 107;
                $out_data['info'] = "性别格式不正确";
                return $out_data;//性别格式不正确
            }
            $in_customer_data['customer_sex'] = $customer_sex;
        } else if (!empty($customer_sex)) {
            $out_data['code'] = 107;
            $out_data['info'] = "性别格式不正确";
            return $out_data;//性别格式不正确
        }
        //获取客户省份id
        $province_id = $customer_data['province_id'];
        if (is_numeric($province_id)) {
            $in_customer_data['province_id'] = $province_id;
        } else if (!empty($province_id)) {
            $in_customer_data['province_id'] = '152964';
        }
        //获取客户市id
        $city_id = $customer_data['city_id'];
        if (is_numeric($city_id)) {
            $in_customer_data['city_id'] = $city_id;
        } else if (!empty($city_id)) {
            $in_customer_data['city_id'] = '152965';
        }
        //获取客户县、区id
        $area_id = $customer_data['area_id'];
        if (is_numeric($area_id)) {
            $in_customer_data['area_id'] = $area_id;
        } else if (!empty($area_id)) {
            $in_customer_data['area_id'] = '153131';
        }
        //获取客户街道id
        $street_id = $customer_data['street_id'];
        if (is_numeric($street_id)) {
            $in_customer_data['street_id'] = $street_id;
        } else if (!empty($street_id)) {
            $in_customer_data['street_id'] = '153240';
        }
        //获取客户地址
        $address = $customer_data['address'];
        if (!empty($address)) {
            if (strlen($address) > 255) {
                $out_data['code'] = 113;
                $out_data['info'] = "用户地址格式有误";
                return $out_data;//用户地址格式有误
            }
            $in_customer_data['address'] = $address;
        }
        //获取身份证号码
        $id_number = $customer_data['id_number'];
        if (!empty($id_number)) {
            //当前身份证号码存在时 验证身份证号码是否符合规范
            if (idcard_checksum18($id_number) === false) {
                $out_data['code'] = 114;
                $out_data['info'] = "身份证号码不符合规范";
                return $out_data;//身份证号码不符合规范
            }
            $in_customer_data['id_number'] = $id_number;
        }
        //获取身份证图片地址正面
        $id_number_image_front = $customer_data['id_number_image_front'];
        if (!empty($id_number_image_front)) {
            $in_customer_data['id_number_image_front'] = $id_number_image_front;
        }
        //获取身份证图片地址反面
        $id_number_image_reverse = $customer_data['id_number_image_reverse'];
        if (!empty($id_number_image_reverse)) {
            $in_customer_data['id_number_image_reverse'] = $id_number_image_reverse;
        }
        //获取邮箱
        $email = $customer_data['email'];
        if (!empty($email)) {
            //当前身份证号码存在时 验证身份证号码是否符合规范
            if (check_email($email) === false) {
                $out_data['code'] = 115;
                $out_data['info'] = "邮箱格式不符合规范";
                return $out_data;//邮箱格式不符合规范
            }
            $in_customer_data['email'] = $email;
        }
        //获取客户驾驶证档案编号
        $driver_license = $customer_data['driver_license'];
        if (!empty($driver_license)) {
            if (check_driver_license($driver_license) === false) {
                $out_data['code'] = 116;
                $out_data['info'] = "用户驾驶证编号有误";
                return $out_data;//用户驾驶证编号有误
            }
            $in_customer_data['driver_license'] = $driver_license;
        }
        //获取客户驾驶证档案编号图片地址正面
        $driver_license_image_front = $customer_data['driver_license_image_front'];
        if (!empty($driver_license_image_front)) {
            $in_customer_data['driver_license_image_front'] = $driver_license_image_front;
        }
        //获取客户驾驶证档案编号图片地址副页
        $driver_license_image_attach = $customer_data['driver_license_image_attach'];
        if (!empty($driver_license_image_attach)) {
            $in_customer_data['driver_license_image_attach'] = $driver_license_image_attach;
        }

        //获取qq
        $qq = $customer_data['qq'];
        if (!empty($qq)) {
            //当前qq存在时 验证qq是否符合规范
            if (check_qq($qq) === false) {
                $out_data['code'] = 117;
                $out_data['info'] = "qq格式不符合规范";
                return $out_data;//qq格式不符合规范
            }
            $in_customer_data['qq'] = $qq;
        }

        //获取微信账号
        $wechar = $customer_data['wechar'];
        if (!empty($wechar)) {
            //当前wechar存在时 验证wechar是否符合规范
            if (check_wechar($wechar) === false) {
                $out_data['code'] = 118;
                $out_data['info'] = "微信账号格式不符合规范";
                return $out_data;//微信账号格式不符合规范
            }
            $in_customer_data['wechar'] = $wechar;
        }

        //获取客户状态
        $customer_status = $customer_data['customer_status'];
        if (is_numeric($customer_status)) {
            $in_customer_data['customer_status'] = $customer_status;
        } else if (!empty($customer_status)) {
            $out_data['code'] = 120;
            $out_data['info'] = "客户状态格式有误";
            return $out_data;//客户状态格式有误
        }
        $in_customer_data['customer_balance'] = OpensslAES::encryptWithOpenssl('0.00');
        //获取客户来源渠道id
        $channel_uid = $customer_data['channel_uid'];
        if (!empty($channel_uid)) {
            $in_customer_data['channel_uid'] = $channel_uid;
        }
        //插入客户表
        if ($this->save($in_customer_data)) {
            $id = $this->getLastInsID();//获取插入数据的id
            //构造密码记录数据
            $this->_addLoginPassword($id, $login_password);
            $out_data['code'] = 0;
            $out_data['info'] = "用户添加成功";
            return $out_data;//用户添加成功
        }
        $out_data['code'] = 121;
        $out_data['info'] = "数据库写失败";
        return $out_data;//数据库写失败
    }

    /**
     * 添加/更新微信用户
     * @param $customer_data
     * 必须数据
     * wechar_openid 微信账号授权 唯一标识
     * wechar_headimgurl 微信头像
     * wechar_nickname 微信昵称
     * customer_status 客户状态
     * 非必须数据
     * customer_nickname 客户昵称
     * channel_uid 用户来源渠道id
     * @return array
     *  0 用户添加成功
     */
    public function addWecharCustomer($customer_data)
    {
        $out_data = array();
        //验证数据合法性
        //获取客户微信 唯一标识
        $wechar_openid = $customer_data['wechar_openid'];
        if (empty($wechar_openid)) {
            $out_data['code'] = 110;
            $out_data['info'] = "唯一标识 不能为空";
            return $out_data;//唯一标识 不能为空
        }
        $in_customer_data['wechar_openid'] = $wechar_openid;
        //获取客户微信开放平台 唯一标识
        $wechar_unionid = $customer_data['wechar_unionid'];
        if (!empty($wechar_openid)) {
            $in_customer_data['wechar_unionid'] = $wechar_unionid;
        }
        //获取客户微信头像
        $wechar_headimgurl = $customer_data['wechar_headimgurl'];
        if (!empty($wechar_headimgurl)) {
            $in_customer_data['wechar_headimgurl'] = $wechar_headimgurl;
        } else {
            $in_customer_data['wechar_headimgurl'] = 'http://img.youchedongli.cn/public/static/mobile/images/logo.png';
        }
        //获取客户微信头像
        $wechar_nickname = $customer_data['wechar_nickname'];
        if (empty($wechar_nickname)) {
            $out_data['code'] = 111;
            $out_data['info'] = "用户未授权用户信息";
            return $out_data;//唯一标识 不能为空
        }
        $in_customer_data['wechar_nickname'] = $wechar_nickname;
        $in_customer_data['customer_nickname'] = $wechar_nickname;
        /**
         * 查询用户是否存在
         */
        $customer = $this->where(array('wechar_openid' => $wechar_openid))->find();
        if (!empty($customer)) {
            //如果存在更新客户信息
            $up_customer_data = array(
                'customer_id' => $customer['id'],
                'wechar_openid' => $wechar_openid,
                'wechar_unionid' => $wechar_unionid,
                'wechar_headimgurl' => $wechar_headimgurl,
                'wechar_nickname' => $wechar_nickname,
                'customer_nickname' => $wechar_nickname
            );
            $this->updateCustomer($up_customer_data);
            $token = $this->_addCustomerToken($customer['id']);
            if (empty($token)) {
                $out_data['code'] = 306;
                $out_data['info'] = "登录失败";
                return $out_data;//登录失败
            }
            //添加登录日志
            $this->_addCustomerLog($customer['id']);
            //将用户登录数据存储（Session中）
            $this->_cannedCustomerData($customer);
            $out_data['code'] = 0;
            $out_data['id'] = $customer['id'];
            $out_data['time'] = strtotime($customer['create_time']);
            $out_data['info'] = "更新成功";
            return $out_data;//用户添加成功
        }
        //获取客户状态
        $customer_status = $customer_data['customer_status'];
        if (is_numeric($customer_status)) {
            $in_customer_data['customer_status'] = $customer_status;
        } else if (!empty($customer_status)) {
            $out_data['code'] = 120;
            $out_data['info'] = "客户状态格式有误";
            return $out_data;//客户状态格式有误
        }
        $in_customer_data['customer_balance'] = OpensslAES::encryptWithOpenssl('0.00');
        //获取客户来源渠道id
        $channel_uid = $customer_data['channel_uid'];
        if (!empty($channel_uid)) {
            $in_customer_data['channel_uid'] = $channel_uid;
        }
        //注册渠道
        $in_customer_data['regist_type'] = RegistType::$RegistTypeWeChar['code'];
        $datetime = date("Y-m-d H:i:s");
        $in_customer_data['create_time'] = $datetime;
        $in_customer_data['on_modifie'] = $datetime;
        //插入客户表
        if ($this->save($in_customer_data)) {
            $id = $this->getLastInsID();//获取插入数据的id
            //构造密码记录数据
            $login_password = random_char(16);
            $this->_addLoginPassword($id, $login_password);
            $out_data = $this->getCustomer('', $id, true);
            $customer = $out_data['data'];
            $token = $this->_addCustomerToken($customer['id']);
            if (empty($token)) {
                $out_data['code'] = 306;
                $out_data['info'] = "登录失败";
                return $out_data;//登录失败
            }
            //添加登录日志
            $this->_addCustomerLog($customer['id']);
            //将用户登录数据存储（Session中）
            $this->_cannedCustomerData($customer);
            $out_data['code'] = 0;
            $out_data['id'] = $id;
            $out_data['time'] = time();
            $out_data['info'] = "用户添加成功";
            return $out_data;//用户添加成功
        }
        $out_data['code'] = 121;
        $out_data['info'] = "数据库写失败";
        return $out_data;//数据库写失败
    }

    /**
     * 更新渠道
     * @param $customer_id
     * @param $channel_uid
     * @param $channel_text
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function updateChannel($customer_id, $channel_uid, $channel_text)
    {
        $customer_data = $this->where(['id' => $customer_id])->find();
        $c_time = strtotime($customer_data['create_time']);
        if (time() - $c_time < 3600 * 12) {
            if (empty($customer_data['channel_uid'])) {
                $customer_data->channel_uid = $channel_uid;
            }
            $customer_data->channel_text = $channel_text;
            $ret = $customer_data->save();
            if ($ret !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * 锁定用户
     * @param $customer_id
     * @return bool
     *  0锁定成功
     *  150 锁定失败
     */
    public function addLockCustomer($customer_id)
    {
        $lock_customer_data = array(
            'customer_status' => 2
        );

        if ($this->save($lock_customer_data, array('id' => $customer_id))) {
            return true;//客户锁定成功
        }
        return false;//客户锁定失败
    }

    /**
     * 解除锁定用户
     * @param $customer_id
     * @return bool
     *  0锁定成功
     *  150 锁定失败
     */
    public function delLockCustomer($customer_id)
    {
        $lock_customer_data = array(
            'customer_status' => 1
        );
        if ($this->save($lock_customer_data, array('id' => $customer_id))) {
            return true;//客户锁定成功
        }
        return false;//客户锁定失败
    }

    /**
     * 修改用户基本信息
     * @param $customer_data
     * 必须数据
     * customer_id 客户id
     * 非必须数据
     * customer_name 真实姓名
     * mobile_phone 手机号码
     * customer_nickname 客户昵称
     * customer_sex 客户性别
     * province_id 省份id
     * city_id 市级id
     * area_id 区、县id
     * street_id 街道id
     * address 客户地址
     * id_number 身份证号码
     * id_number_image_front 身份证照片正面
     * id_number_image_reverse 身份证照片反面
     * driver_license 驾驶证档案编号
     * driver_license_image_front 驾驶证档案照片_正面
     * driver_license_image_attach 驾驶证档案照片_副页
     * email 客户邮箱
     * qq 客户qq
     * qq_headimgurl 客户QQ头像
     * qq_nickname 客户QQ昵称
     * qq_openid 客户腾讯身份标识
     * wechar 客户微信号
     * wechar_headimgurl 客户微信头像
     * wechar_nickname 客户微信昵称
     * wechar_openid 客户微信opendid
     * @return array
     *  0 客户数据修改成功
     */
    public function updateCustomer($customer_data)
    {
        $out_data = array();//返回信息
        $up_customer_data = array();//更新数据数组
        //获取客户id
        $customer_id = $customer_data['customer_id'];
        if (empty($customer_id)) {
            $out_data['code'] = 217;
            $out_data['info'] = "提交数据有误";
            return $out_data;//真实姓名不符合规范
        }

        //获取手机号码
        $mobile_phone = $customer_data['mobile_phone'];
        if (!empty($mobile_phone)) {
            if (check_mobile_number($mobile_phone) === false) {
                $out_data['code'] = 218;
                $out_data['info'] = "手机号码不正确";
                return $out_data;//手机号码不正确
            }
            $up_customer_data['mobile_phone'] = $mobile_phone;
        }

        //获取真实姓名
        $customer_name = $customer_data['customer_name'];
        if (!empty($customer_name)) {
            //验证真实姓名是否符合规范
            if (strlen($customer_name) < 2) {
                $out_data['code'] = 201;
                $out_data['info'] = "真实姓名不符合规范";
                return $out_data;//真实姓名不符合规范
            }
            $up_customer_data['customer_name'] = $customer_name;
        }
        //获取客户昵称
        $customer_nickname = $customer_data['customer_nickname'];
        if (!empty($customer_nickname)) {
            //验证客户昵称是否符合规范
            if (strlen($customer_nickname) > 50) {
                $out_data['code'] = 202;
                $out_data['info'] = "客户昵称不符合规范";
                return $out_data;//客户昵称不符合规范
            }
            $up_customer_data['customer_nickname'] = $customer_nickname;
        }
        //获取客户性别
        $customer_sex = $customer_data['customer_sex'];
        if (is_numeric($customer_sex)) {
            if (intval($customer_sex) > 1 || intval($customer_sex) < 0) {
                $out_data['code'] = 203;
                $out_data['info'] = "性别格式不正确";
                return $out_data;//性别格式不正确
            }
            $up_customer_data['customer_sex'] = $customer_sex;
        } else if (!empty($customer_sex)) {
            $out_data['code'] = 203;
            $out_data['info'] = "性别格式不正确";
            return $out_data;//性别格式不正确
        }

        //获取客户省份id
        $province_id = $customer_data['province_id'];
        if (!empty($province_id)) {
            if (!is_numeric($province_id)) {
                $out_data['code'] = 204;
                $out_data['info'] = "省份id数据不正确";
                return $out_data;//省份id数据不正确
            } else {
                $up_customer_data['province_id'] = $province_id;
            }
        }
        //获取客户市级id
        $city_id = $customer_data['city_id'];
        if (!empty($city_id)) {
            if (!is_numeric($city_id)) {
                $out_data['code'] = 205;
                $out_data['info'] = "省份id数据不正确";
                return $out_data;//省份id数据不正确
            } else {
                $up_customer_data['city_id'] = $city_id;
            }
        }
        //获取客户地区/县id
        $area_id = $customer_data['area_id'];
        if (!empty($area_id)) {
            if (!is_numeric($area_id)) {
                $out_data['code'] = 206;
                $out_data['info'] = "地区、县id数据不正确";
                return $out_data;//地区、县id数据不正确
            } else {
                $up_customer_data['area_id'] = $area_id;
            }
        }
        //获取客户街道id
        $street_id = $customer_data['street_id'];
        if (!empty($street_id)) {
            if (!is_numeric($street_id)) {
                $out_data['code'] = 207;
                $out_data['info'] = "街道id数据不正确";
                return $out_data;//街道id数据不正确
            } else {
                $up_customer_data['street_id'] = $street_id;
            }
        }
        //获取客户地址
        $address = $customer_data['address'];
        if (!empty($address)) {
            if (strlen($address) > 255) {
                $out_data['code'] = 208;
                $out_data['info'] = "用户地址格式有误";
                return $out_data;//用户地址格式有误
            }
            $up_customer_data['address'] = $address;
        }
        //获取身份证号码
        $id_number = $customer_data['id_number'];
        if (!empty($id_number)) {
            //当前身份证号码存在时 验证身份证号码是否符合规范
            if (idcard_checksum18($id_number) === false) {
                $out_data['code'] = 209;
                $out_data['info'] = "身份证号码不符合规范";
                return $out_data;//身份证号码不符合规范
            }
            $up_customer_data['id_number'] = $id_number;
        }
        //获取身份证图片地址正面
        $id_number_image_front = $customer_data['id_number_image_front'];
        if (!empty($id_number_image_front)) {
            $up_customer_data['id_number_image_front'] = $id_number_image_front;
        }
        //获取身份证图片地址反面
        $id_number_image_reverse = $customer_data['id_number_image_reverse'];
        if (!empty($id_number_image_reverse)) {
            $up_customer_data['id_number_image_reverse'] = $id_number_image_reverse;
        }
        //获取客户驾驶证档案编号
        $driver_license = $customer_data['driver_license'];
        if (!empty($driver_license)) {
            if (check_driver_license($driver_license) === false) {
                $out_data['code'] = 210;
                $out_data['info'] = "用户驾驶证编号有误";
                return $out_data;//用户驾驶证编号有误
            }
            $up_customer_data['driver_license'] = $driver_license;
        }
        //获取客户驾驶证档案编号图片地址正面
        $driver_license_image_front = $customer_data['driver_license_image_front'];
        if (!empty($driver_license_image_front)) {
            $up_customer_data['driver_license_image_front'] = $driver_license_image_front;
        }
        //获取客户驾驶证档案编号图片地址副页
        $driver_license_image_attach = $customer_data['driver_license_image_attach'];
        if (!empty($driver_license_image_attach)) {
            $up_customer_data['driver_license_image_attach'] = $driver_license_image_attach;
        }
        //获取邮箱
        $email = $customer_data['email'];
        if (!empty($email)) {
            //当前邮箱存在时 验证邮箱是否符合规范
            if (check_email($email) === false) {
                $out_data['code'] = 211;
                $out_data['info'] = "邮箱格式不符合规范";
                return $out_data;//邮箱格式不符合规范
            }
            $up_customer_data['email'] = $email;
        }
        //获取qq
        $qq = $customer_data['qq'];
        if (!empty($qq)) {
            //当前qq存在时 验证qq是否符合规范
            if (check_qq($qq) === false) {
                $out_data['code'] = 212;
                $out_data['info'] = "qq格式不符合规范";
                return $out_data;//qq格式不符合规范
            }
            $up_customer_data['qq'] = $qq;
        }
        //获取qq头像
        $qq_headimgurl = $customer_data['qq_headimgurl'];
        if (!empty($qq_headimgurl)) {
            //当前qq昵称存在时 验证qq昵称是否符合规范
            $up_customer_data['qq_headimgurl'] = $qq_headimgurl;
        }
        //获取qq昵称
        $qq_nickname = $customer_data['qq_nickname'];
        if (!empty($qq_nickname)) {
            //当前qq昵称存在时 验证qq昵称是否符合规范
            if (strlen($qq_nickname) > 100 || strlen($qq_nickname) < 0) {
                $out_data['code'] = 213;
                $out_data['info'] = "qq昵称格式不符合规范";
                return $out_data;//qq昵称格式不符合规范
            }
            $up_customer_data['qq_nickname'] = $qq_nickname;
        }
        //获取腾讯开放平台 唯一对应用户身份的标识
        $qq_openid = $customer_data['qq_openid'];
        if (!empty($qq_openid)) {
            //当前qq openid存在时 验证qq openid 是否符合规范
            if (strlen($qq_openid) > 50 || strlen($qq_nickname) < 0) {
                $out_data['code'] = 214;
                $out_data['info'] = "腾讯身份标识格式不符合规范";
                return $out_data;//腾讯身份标识格式不符合规范
            }
            $up_customer_data['qq_openid'] = $qq_openid;
        }
        //获取微信账号
        $wechar = $customer_data['wechar'];
        if (!empty($wechar)) {
            //当前wechar存在时 验证wechar是否符合规范
            if (check_wechar($wechar) === false) {
                $out_data['code'] = 215;
                $out_data['info'] = "微信账号格式不符合规范";
                return $out_data;//微信账号格式不符合规范
            }
            $up_customer_data['wechar'] = $wechar;
        }
        //获取微信头像
        $wechar_headimgurl = $customer_data['wechar_headimgurl'];
        if (!empty($wechar_headimgurl)) {
            $up_customer_data['wechar_headimgurl'] = $wechar_headimgurl;
        }
        //获取微信昵称
        $wechar_nickname = $customer_data['wechar_nickname'];
        if (!empty($wechar_nickname)) {
            //当前微信昵称存在时 验证微信昵称是否符合规范
            if (strlen($wechar_nickname) > 100 || strlen($wechar_nickname) < 0) {
                $out_data['code'] = 216;
                $out_data['info'] = "微信昵称格式不符合规范";
                return $out_data;//微信昵称格式不符合规范
            }
            $up_customer_data['wechar_nickname'] = $wechar_nickname;
        }
        //获取微信开放平台 唯一对应用户身份的标识
        $wechar_openid = $customer_data['wechar_openid'];
        if (!empty($wechar_openid)) {
            //当前微信用户身份的标识存在时 验证微信用户身份的标识是否符合规范
            if (strlen($wechar_openid) > 50 || strlen($wechar_openid) < 0) {
                $out_data['code'] = 217;
                $out_data['info'] = "微信openid格式不符合规范";
                return $out_data;//微信openid格式不符合规范
            }
            $up_customer_data['wechar_openid'] = $wechar_openid;
        }
        //获取客户状态
        $customer_status = $customer_data['customer_status'];
        if (is_numeric($customer_status)) {
            if (intval($customer_status) > 3 || intval($customer_status) < 0) {
                $out_data['code'] = 218;
                $out_data['info'] = "客户状态格式有误";
                return $out_data;//客户状态格式有误
            }
            if (intval($customer_status) == 1) {
                $customer_data_temp = $this->where(['id' => $customer_id])->field('customer_status,channel_uid')->find();
                if (intval($customer_data_temp['customer_status']) == 4) {
                    $customer_coupon_model = new CustomerCoupon();
                    $customer_coupon_model->register_coupon($customer_data_temp['channel_uid'], $customer_id);
                    $up_customer_data['customer_end_time'] = date("Y-m-d H:i:s");
                }
            }
            $up_customer_data['customer_status'] = $customer_status;
        } else if (!empty($customer_status)) {
            $out_data['code'] = 218;
            $out_data['info'] = "客户状态格式有误";
            return $out_data;//客户状态格式有误
        }
        $customer_remark = $customer_data['customer_remark'];
        if (!empty($customer_remark)) {
            $up_customer_data['customer_remark'] = $customer_remark;
        }
        $datetime = date("Y-m-d H:i:s");
        $up_customer_data['on_modifie'] = $datetime;
        if ($this->save($up_customer_data, array('id' => $customer_id))) {
            $out_data['code'] = 0;
            $out_data['info'] = "客户数据修改成功";
            return $out_data;//客户数据修改成功
        }
        $out_data['code'] = 219;
        $out_data['info'] = "客户数据修改失败";
        return $out_data;//客户数据修改失败
    }

    /**
     *
     * @param $map
     * @param $data 键值对
     * @return bool
     */
    public function updateCustomerField($map, $data)
    {
        if ($this->where($map)->setField($data)) {
            return true;
        }
        return false;
    }

    /**
     * 更改电话号码
     * @param $customer_data
     * customer_id 客户id
     * mobile_phone 手机号码
     * @param bool $is_clear 是否是解绑手机号码
     * @return array
     */
    public function updateCustomerMobile($customer_data, $is_clear = false)
    {
        $out_data = array();//返回信息
        $customer_id = $customer_data['customer_id'];
        if (!is_numeric($customer_id)) {
            $out_data['code'] = 280;
            $out_data['info'] = '客户id格式不合法';
            return $out_data;
        }
        $mobile_phone = $customer_data['mobile_phone'];
        if (!empty($mobile_phone)) {
            if (check_mobile_number($mobile_phone) === false) {
                $out_data['code'] = 281;
                $out_data['info'] = "手机号码不正确";
                return $out_data;
            }
        } else {
            if ($is_clear) {
                $mobile_phone = "";
            } else {
                $out_data['code'] = 282;
                $out_data['info'] = "手机号码不能为空";
                return $out_data;
            }
        }
        $customer_data = $this->where(array('id' => $customer_id))->find();
        $customer_data->mobile_phone = $mobile_phone;
        if ($customer_data->save()) {
            $out_data['code'] = 0;
            $out_data['info'] = "更新成功";
            return $out_data;
        }
        $out_data['code'] = 283;
        $out_data['info'] = "更新失败";
        return $out_data;
    }

    /**
     * 用户登录
     * @param $customer_data
     * 必须数据
     * mobile_phone 客户手机号码
     * login_password 登录密码
     * @return array
     *  0 登录成功
     *  301 手机号码不正确
     *  302 登录密码不符合规范
     *  303 账号不存在
     *  304 账号已被锁定
     *  305 账号密码不匹配
     *  306 登录失败
     */
    public function loginCustomer($customer_data)
    {
        //返回参数
        $out_data = array();
        //验证数据合法性
        $mobile_phone = $customer_data['mobile_phone'];
        if (check_mobile_number($mobile_phone) === false) {
            $out_data['code'] = 301;
            $out_data['info'] = "手机号码不正确";
            return $out_data;//手机号码不正确
        }
        $login_password = $customer_data['login_password'];
        if (strlen($login_password) < 8) {
            $out_data['code'] = 302;
            $out_data['info'] = "登录密码不符合规范";
            return $out_data;//登录密码不符合规范
        }
        //获取登录客户信息
        $customer = $this->where(array('mobile_phone' => $mobile_phone))->find();
        if (empty($customer)) {
            $out_data['code'] = 303;
            $out_data['info'] = "账号不存在";
            return $out_data;//账号不存在
        }
        if (intval($customer['customer_status']) == 2) {
            $out_data['code'] = 304;
            $out_data['info'] = "账号已被锁定";
            return $out_data;//账号不存在
        }
        //验证登录客户登录密码
        if ($this->_verifyLoginPassword($customer['id'], $login_password) === false) {
            $out_data['code'] = 305;
            $out_data['info'] = "账号密码不匹配";
            return $out_data;//账号密码不匹配
        }
        //添加客户对话随机字符串
        $token = $this->_addCustomerToken($customer['id']);
        if (empty($token)) {
            $out_data['code'] = 306;
            $out_data['info'] = "登录失败";
            return $out_data;//登录失败
        }
        //添加登录日志
        $this->_addCustomerLog($customer['id']);
        //将用户登录数据存储（Session中）
        $this->_cannedCustomerData($customer);
        $out_data['code'] = 0;
        //登录成功之后 返回客户数据
        $data_token = array(
            'customer_id' => $customer['id'],
            'mobile_phone' => $customer['mobile_phone'],
            'customer_name' => $customer['customer_name'],
            'customer_nickname' => $customer['customer_nickname'],
            'customer_sex' => $customer['customer_sex'],
            'customer_token' => $token
        );
        $out_data['data'] = $data_token;
        $out_data['info'] = "登录成功";
        return $out_data;//登录成功
    }

    /**
     * 手机号码登录 （必输验证码通过之后才能调用）
     * 如果账户不存在 自动生成账号
     * @param $mobile_phone
     * @param $customer_id
     * @return array
     */
    public function loginMobileCustomer($mobile_phone, $customer_id = 0)
    {
        //返回参数
        $out_data = array();
        //验证数据合法性
        if (check_mobile_number($mobile_phone) === false) {
            $out_data['code'] = 301;
            $out_data['info'] = "手机号码不正确";
            return $out_data;//手机号码不正确
        }
        if (!empty($customer_id)) {
            $customer_temp = $this->where(array('mobile_phone' => $mobile_phone))->find();
            if (!empty($customer_temp['id'])) {
                $customer_temp->mobile_phone = '';
                $customer_temp->save();

            }
            $customer = $this->where(array('id' => $customer_id))->find();
            $customer->mobile_phone = $mobile_phone;
            if ($customer->save()) {
                $token = $this->_addCustomerToken($customer_id);
                if (empty($token)) {
                    $out_data['code'] = 306;
                    $out_data['info'] = "登录失败";
                    return $out_data;//登录失败
                }
                //添加登录日志
                $this->_addCustomerLog($customer_id);
                //将用户登录数据存储（Session中）
                $this->_cannedCustomerData($customer);
                $out_data['code'] = 0;
                //登录成功之后 返回客户数据
                $data_token = array(
                    'customer_id' => $customer['id'],
                    'mobile_phone' => $customer['mobile_phone'],
                    'customer_name' => $customer['customer_name'],
                    'customer_nickname' => $customer['customer_nickname'],
                    'customer_sex' => $customer['customer_sex'],
                    'customer_token' => $token
                );
                $out_data['data'] = $data_token;
                $out_data['info'] = "登录成功";
                return $out_data;//登录成功
            }
        }
        $customer = $this->where(array('mobile_phone' => $mobile_phone))->find();
        if (empty($customer)) {
            $in_customer_data = array(
                'mobile_phone' => $mobile_phone,
                'login_password' => random_char(16),
                'regist_type' => RegistType::$RegistTypePC['code'],//后台添加
            );
            $out_add_data = $this->addCustomer($in_customer_data);
            if ($out_add_data['code'] != 0) {
                return $out_add_data;//添加失败
            }
            $customer = $this->where(array('mobile_phone' => $mobile_phone))->find();
        } else {
            if (intval($customer['customer_status']) == 2) {
                $out_data['code'] = 304;
                $out_data['info'] = "账号已被锁定";
                return $out_data;//账号不存在
            }
            //添加客户对话随机字符串
        }
        $token = $this->_addCustomerToken($customer['id']);
        if (empty($token)) {
            $out_data['code'] = 306;
            $out_data['info'] = "登录失败";
            return $out_data;//登录失败
        }
        //添加登录日志
        $this->_addCustomerLog($customer['id']);
        //将用户登录数据存储（Session中）
        $this->_cannedCustomerData($customer);
        $out_data['code'] = 0;
        //登录成功之后 返回客户数据
        $data_token = array(
            'customer_id' => $customer['id'],
            'mobile_phone' => $customer['mobile_phone'],
            'customer_name' => $customer['customer_name'],
            'customer_nickname' => $customer['customer_nickname'],
            'customer_sex' => $customer['customer_sex'],
            'customer_token' => $token
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
    public function verifyCustomerToken($token)
    {
        //如果缓存中对话中无用户信息
//        if (!$this->_verifyCacheCustomerData()) {
        //验证客户对话
        $customer_id = $this->_verifyCustomerToken($token);
        if (!empty($customer_id)) {
            //验证成功之后
            $customer = $this->where(array('id' => $customer_id))->find();
            //将用户登录数据存储（Session中）
            $this->_cannedCustomerData($customer);
            return true;
        }
        return false;
//        }
//        return true;
    }

    /**
     * 注销用户登录
     * @param $token
     * @return bool
     */
    public function logoutCustomer($token)
    {
        if (!$this->_verifyCacheCustomerData()) {
            //验证客户对话
            $customer_id = $this->_verifyCustomerToken($token);
            if (!empty($customer_id)) {
                $customer_token_model = new CustomerToken();
                $w = array(
                    'token' => $token
                );
                $customer_token_model->where($w)->delete();
                //清除用户登录数据存储（Session中）
                $this->_clearCustomerData();
                return true;
            }
        }
        return false;
    }

    /**
     * 注销账号
     * @return bool
     */
    public function logoutMobileCustomer()
    {
        $this->_clearCustomerData();
        $customer_id = Session::get('customer_id');
        if (empty($customer_id)) {
            return true;
        }
        return false;
    }

    /**
     *修改用户登录密码
     * @param $id 修改的用户id
     * @param $login_password 登录密码
     * @return bool
     */
    public function updateCustomerLoginPassword($id, $login_password)
    {
        if ($this->_addLoginPassword($id, $login_password)) {
            return true;
        }
        return false;
    }

    /**
     *修改用户支付密码
     * @param $id 修改的用户id
     * @param $pay_password 支付密码
     * @return bool
     */
    public function updateCustomerPayPassword($id, $pay_password)
    {
        if ($this->_addPayPassword($id, $pay_password)) {
            return true;
        }
        return false;
    }

    /**
     *验证用户支付密码
     * @param $id 验证的用户id
     * @param $pay_password 支付密码
     * @return bool 成功 true 失败 false
     */
    public function verifyCustomerPayPassword($id, $pay_password)
    {
        if ($this->_verifyPayPassword($id, $pay_password)) {
            return true; //成功
        }
        return false;//失败
    }

    /**
     * 验证账号是否存在
     * @param $mobile_phone 手机号码
     * @return bool
     */
    public function verifyCustomer($mobile_phone)
    {
        //检查电话号码是否符合格式
        if (check_mobile_number($mobile_phone)) {
            //验证账号是否已经注册
            $customer = $this->where(array('mobile_phone' => $mobile_phone))->find();
            if (!empty($customer)) {
                return true;//账号已被注册
            }
        }
        return false;//账号不存在 电话号码不正确
    }

    /**
     * 更新用户余额
     * @param $money
     * customer_id 客户id
     * balance 变动金额
     * pay_sn 支付单号
     * type 状态 ADD增加，SUB减少，PAY支付，REFUND退款
     * remark 备注信息
     * @return bool
     */
    public function updateCustomerBalance($money)
    {
//        $money = array(
//            'balance' => 150,// 元
//            'pay_sn' => '201709191654137553775',// 支付单号
//            'type' => 'ADD',// 变动类型
//            'customer_id' => '3',//客户id
//            'remark' => '支付订单:201709191654137553775',// 备注
//        );
        if (empty($money['customer_id']) || empty($money['type']) || empty($money['balance'])) {
            $out_data['code'] = 450;
            $out_data['info'] = "参数有误";
            return $out_data;//参数有误
        }
        $customer = $this->where(array('id' => $money['customer_id']))->find();
        if (empty($customer)) {
            $out_data['code'] = 451;
            $out_data['info'] = "账号不存在";
            return $out_data;//账号不存在
        }
        if (intval($customer['customer_status']) == 2) {
            $out_data['code'] = 452;
            $out_data['info'] = "账号已被锁定";
            return $out_data;//账号已被锁定
        }
        $customer_balance = OpensslAES::decryptWithOpenssl($customer['customer_balance']);
        $money['balance_last'] = $customer_balance;
        $this->_addCustomerBalanceLog($money);
        switch ($money['type']) {
            case "ADD":
                $customer_balance = floatval($customer_balance) + floatval($money['balance']);
                $customer->customer_balance = OpensslAES::encryptWithOpenssl($customer_balance);
                if ($customer->save()) {
                    $out_data['code'] = 0;
                    $out_data['info'] = "客户余额增加成功";
                    return $out_data;//客户余额增加成功
                }
                $out_data['code'] = 453;
                $out_data['info'] = "客户余额增加失败";
                return $out_data;//客户余额增加失败
                break;
            case "SUB":
                $customer_balance = floatval($customer_balance) - floatval($money['balance']);
                if ($customer_balance < 0) {
                    $out_data['code'] = 454;
                    $out_data['info'] = "客户余额不足";
                    return $out_data;//客户余额不足
                }
                $customer->customer_balance = OpensslAES::encryptWithOpenssl($customer_balance);
                if ($customer->save()) {
                    $out_data['code'] = 0;
                    $out_data['info'] = "客户余额减少成功";
                    return $out_data;//客户余额增加成功
                }
                $out_data['code'] = 455;
                $out_data['info'] = "客户余额减少失败";
                return $out_data;//客户余额减少失败
                break;
            case "PAY":
                $customer_balance = floatval($customer_balance) - floatval($money['balance']);
                if ($customer_balance < 0) {
                    $out_data['code'] = 456;
                    $out_data['info'] = "客户余额不足";
                    return $out_data;//客户余额不足
                }
                $customer->customer_balance = OpensslAES::encryptWithOpenssl($customer_balance);
                if ($customer->save()) {
                    $out_data['code'] = 0;
                    $out_data['info'] = "客户支付成功";
                    return $out_data;//客户支付成功
                }
                $out_data['code'] = 457;
                $out_data['info'] = "客户支付失败";
                return $out_data;//客户支付失败
                break;
            case "REFUND":
                $customer_balance = floatval($customer_balance) + floatval($money['balance']);
                $customer->customer_balance = OpensslAES::encryptWithOpenssl($customer_balance);
                if ($customer->save()) {
                    $out_data['code'] = 0;
                    $out_data['info'] = "客户退款成功";
                    return $out_data;//客户退款成功
                }
                $out_data['code'] = 458;
                $out_data['info'] = "客户退款失败";
                return $out_data;//客户退款失败
                break;
        }
        $out_data['code'] = 459;
        $out_data['info'] = "未定义参数";
        return $out_data;//未定义参数
    }

    /**
     * 缴纳押金
     * @param  array $cash_data 押金数据
     * @return bool 缴纳的押金状态
     */
    public function addCash($cash_data)
    {
        $customer_data = $this->where(array('id' => $cash_data['customer_id']))->field('cash,cash_is')->find();
        $cash_log = new CustomerCashLog();
        $cash_data['add_time'] = time();
        $MONEY_LOG_TYPE = Config::get('MONEY_LOG_TYPE');
        $cash_data['type'] = $MONEY_LOG_TYPE['ADD']['code'];
        if ($cash_log->save($cash_data)) {
            if (intval($customer_data['cash_is']) == 0) {
                //如果未缴纳押金
                $customer_data->cash = floatval($cash_data['cash']);
                $customer_data->cash_is = 1;
                if ($customer_data->save()) {
                    //保存押金日志
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * 退还押金
     * @param array $cash_data 押金数据
     * @return int 已缴纳的押金
     */
    public function subCash($cash_data)
    {
        $cash = 0;
        $customer_data = $this->where(array('id' => $cash_data['customer_id']))->field('cash,cash_is')->find();
        if (intval($customer_data['cash_is']) == 1) {
            //如果已缴纳押金
            $cash = $customer_data['cash'];
            $customer_data->cash = 0;
            $customer_data->cash_is = 0;
            if ($customer_data->save()) {
                $cash_log = new CustomerCashLog();
                $cash_data['add_time'] = time();
                $MONEY_LOG_TYPE = Config::get('MONEY_LOG_TYPE');
                $cash_data['type'] = $MONEY_LOG_TYPE['SUB']['code'];
                $cash_log->save($cash_data);
                return $cash;
            }
        }
        return $cash;
    }

    /**
     * 退还押金
     * @param array $cash_data 押金数据
     * @return int 已缴纳的押金
     */
    public function deductCash($cash_data)
    {
        $cash = 0;
        $customer_data = $this->where(array('id' => $cash_data['customer_id']))->field('cash,cash_is')->find();
        if (intval($customer_data['cash_is']) == 1) {
            //如果已缴纳押金
            $cash = $customer_data['cash'];
            $customer_data->cash = 0;
            $customer_data->cash_is = 0;
            if ($customer_data->save()) {
                $cash_log = new CustomerCashLog();
                $cash_data['add_time'] = time();
                $MONEY_LOG_TYPE = Config::get('MONEY_LOG_TYPE');
                $cash_data['type'] = $MONEY_LOG_TYPE['DED']['code'];
                $cash_log->save($cash_data);
                return $cash;
            }
        }
        return $cash;
    }

    /**
     * 增加余额
     * @param $balance_data
     * @return bool
     */
    public function addBalance($balance_data)
    {
//        $balance_data = array(
//            'balance' => $pay_info['money'],// 单位 元
//            'pay_sn' => $pay_info['pay_sn'],// 订单号
//            'customer_id' => $order['customer_id'],//用户id
//            'remark' => $pay_info['remark']//备注信息
//        );
        $customer_data = $this->where(array('id' => $balance_data['customer_id']))->field('customer_balance')->find();
        $balance = floatval($balance_data['balance']);
        $customer_balance = OpensslAES::decryptWithOpenssl($customer_data['customer_balance']);
        if (!is_numeric($customer_balance)) {
            $customer_balance = 0;
        }
        $customer_data->customer_balance = OpensslAES::encryptWithOpenssl(round(floatval($customer_balance) + floatval($balance), 2));
        if ($customer_data->save()) {
            $balance_log = new CustomerBalanceLog();
            $balance_data['add_time'] = time();
            $MONEY_LOG_TYPE = Config::get('MONEY_LOG_TYPE');
            $balance_data['type'] = $MONEY_LOG_TYPE['ADD']['code'];
            $balance_data['balance_last'] = $customer_balance;
            $balance_log->save($balance_data);
            return true;
        }
        return false;
        //获取客户来源渠道id

    }

    /**
     * 退还余额
     * @param $balance_data
     * @return bool
     */
    public function refundBalance($balance_data)
    {
//        $balance_data = array(
//            'balance' => $pay_info['money'],// 单位 元
//            'pay_sn' => $pay_info['pay_sn'],// 订单号
//            'customer_id' => $order['customer_id'],//用户id
//            'remark' => $pay_info['remark']//备注信息
//        );
        $customer_data = $this->where(array('id' => $balance_data['customer_id']))->field('customer_balance')->find();
        $balance = floatval($balance_data['balance']);
        $customer_balance = OpensslAES::decryptWithOpenssl($customer_data['customer_balance']);
        if (!is_numeric($customer_balance)) {
            $customer_balance = 0;
        }
        $customer_data->customer_balance = OpensslAES::encryptWithOpenssl(round(floatval($customer_balance) + floatval($balance), 2));
        if ($customer_data->save()) {
            $balance_log = new CustomerBalanceLog();
            $balance_data['add_time'] = time();
            $MONEY_LOG_TYPE = Config::get('MONEY_LOG_TYPE');
            $balance_data['type'] = $MONEY_LOG_TYPE['REFUND']['code'];
            $balance_data['balance_last'] = $customer_balance;
            $balance_log->save($balance_data);
            return true;
        }
        return false;
        //获取客户来源渠道id

    }

    /**
     * 减少余额
     * @param $balance_data
     * @return bool
     */
    public function subBalance($balance_data)
    {
//        $balance_data = array(
//            'balance' => $pay_info['money'],// 单位 元
//            'pay_sn' => $pay_info['pay_sn'],// 订单号
//            'customer_id' => $order['customer_id'],//用户id
//            'remark' => $pay_info['remark']//备注信息
//        );
        $customer_data = $this->where(array('id' => $balance_data['customer_id']))->field('customer_balance')->find();
        $balance = floatval($balance_data['balance']);
        $customer_balance = OpensslAES::decryptWithOpenssl($customer_data['customer_balance']);
        if (!is_numeric($customer_balance)) {
            $customer_balance = 0;
        }
        if (floatval($customer_balance) < $balance) {
            return false;
        }
        $customer_data->customer_balance = OpensslAES::encryptWithOpenssl(round(floatval($customer_balance) - floatval($balance), 2));
        if ($customer_data->save()) {
            $balance_log = new CustomerBalanceLog();
            $balance_data['add_time'] = time();
            $MONEY_LOG_TYPE = Config::get('MONEY_LOG_TYPE');
            $balance_data['type'] = $MONEY_LOG_TYPE['SUB']['code'];
            $balance_data['balance_last'] = $customer_balance;
            $balance_log->save($balance_data);
            return true;
        }
        return false;
        //获取客户来源渠道id
    }

    /**
     * 自动审核用户信息
     * @param $customer_id
     * @return array
     */
    public function checkPersonalFace($customer_id)
    {
        $out_data = array(
            'code' => 100,
            'info' => '数据参数有误'
        );
        $aliyun_oss = Config::get('aliyun_oss');
        $img_url = $aliyun_oss['weburl'];

        $customer_data = $this->where(['id' => $customer_id])->field('customer_name,customer_front,id_number,driver_license,driver_license_image_front,id_number_time,driver_license_time,channel_uid')->find();
//        if (empty($customer_data['customer_name']) || empty($customer_data['id_number']) ||
//            empty($customer_data['customer_front']) || empty($customer_data['driver_license']) ||
//            empty($customer_data['driver_license_image_front'])
//        ){

        file_put_contents("public/verify/" . $customer_data['id_number'] . ".txt", json_encode($customer_data));
        if (empty($customer_data['customer_name']) || empty($customer_data['id_number'])) {
            return $out_data;
        }
        if (!validity_time($customer_data['id_number_time'], 0)) {
            $out_data['code'] = 24;
            $out_data['info'] = "身份证有效期不满足要求";
            $this->where(['id' => $customer_id])->setField('customer_remark', $out_data['info']);
            return $out_data;
        }
        if (!validity_time($customer_data['driver_license_time'], 0)) {
            $out_data['code'] = 25;
            $out_data['info'] = "驾驶证件有效期不满足要求";
            $this->where(['id' => $customer_id])->setField('customer_remark', $out_data['info']);
            return $out_data;
        }
        /**
         * 验证身份证人脸信息是否一致
         */
        $name = $customer_data['customer_name'];
        $idcard = $customer_data['id_number'];
//        print_r($img_url . $customer_data['customer_front']);
        $image = file_get_contents($img_url . $customer_data['customer_front']);
        $data = ChinaDataApi::personal_face($name, $idcard, base64_encode($image));
//        print_r($data);
        file_put_contents("public/verify/" . $customer_id . '_face.txt', json_encode($data));
        if ($data['code'] != "10000") {
            $out_data['code'] = 101;
            $out_data['info'] = "用户照片:" . $data['message'];
            $set_data = [
                'customer_remark' => $out_data['info'],
                'customer_check_time' => time()
            ];
            $this->where(['id' => $customer_id])->setField($set_data);
            $this->where(['id' => $customer_id])->setInc('customer_check_count', 1);
            return $out_data;
        } else {
            $out_data['code'] = 0;
            $out_data['info'] = "用户照片:" . $data['message'];
        }
        $driver_license = $customer_data['driver_license'];//驾驶证档案编号
        $data = ChinaDataApi::driving($name, $idcard, $driver_license);
        file_put_contents("public/verify/" . $customer_id . '_driving.txt', json_encode($data));
        if ($data['code'] != "10000") {
            $out_data['code'] = 102;
            $out_data['info'] = "驾驶证:" . $data['message'];
            $set_data = [
                'customer_remark' => $out_data['info'],
                'customer_check_time' => time()
            ];
            $this->where(['id' => $customer_id])->setField($set_data);
            $this->where(['id' => $customer_id])->setInc('customer_check_count', 1);
            return $out_data;
        } else {
            $result = $data['data']['result'];
            $set_data = [
                'customer_check_time' => time()
            ];
            switch (intval($result)) {
                case 1:
                    $socre = intval($data['data']['socre']);
                    if ($socre >= 9) {
                        if ($socre >= 12) {
                            $out_data['code'] = 103;
                            $out_data['info'] = "驾照分已被扣完，请一个月之后再次提交";
                            $set_data['customer_status'] = 5;
                        } else {
                            $out_data['code'] = 200;
                            $out_data['info'] = "驾照剩余分数不足3分,请谨慎驾驶";
                            $set_data['customer_status'] = 1;
                            $set_data['customer_end_time'] = date("Y-m-d H:i:s");
                            $customer_coupon_model = new CustomerCoupon();
                            $customer_coupon_model->register_coupon($customer_data['channel_uid'], $customer_id);
                        }
                    } else {
                        $out_data['code'] = 0;
                        $out_data['info'] = "客户信息校验通过";
                        $set_data['customer_status'] = 1;
                        $set_data['customer_end_time'] = date("Y-m-d H:i:s");
                        $customer_coupon_model = new CustomerCoupon();
                        $customer_coupon_model->register_coupon($customer_data['channel_uid'], $customer_id);
                    }
                    break;
                case 2:
                    $out_data['code'] = 104;
                    $out_data['info'] = "驾驶证号与档案编号不一致";
                    break;
                case 3:
                    $out_data['code'] = 105;
                    $out_data['info'] = "姓名与驾驶证号不一致";
                    break;
                default:
                    $out_data['code'] = 106;
                    $out_data['info'] = "驾照接口异常";
                    break;
            }
        }
        $set_data['customer_remark'] = $out_data['info'] . $out_data['code'];
        $this->where(['id' => $customer_id])->setField($set_data);
        $this->where(['id' => $customer_id])->setInc('customer_check_count', 1);
        return $out_data;
    }

    /**
     * 驾照扣分情况查询
     * @param $customer_id
     * @return array
     */
    public function checkDriving($customer_id)
    {
        $out_data = array(
            'code' => 100,
            'info' => '数据参数有误'
        );
        $customer_data = $this->where(['id' => $customer_id])->field('customer_name,id_number,driver_license')->find();
        if (empty($customer_data['customer_name']) || empty($customer_data['id_number']) || empty($customer_data['driver_license'])) {
            return $out_data;
        }
        /**
         * 查询驾照三要素信息
         */
        $name = $customer_data['customer_name'];
        $idcard = $customer_data['id_number'];
        $driver_license = $customer_data['driver_license'];//驾驶证档案编号
        $data = ChinaDataApi::driving($name, $idcard, $driver_license);
        if ($data['code'] != "10000") {
            $out_data['code'] = 102;
            $out_data['info'] = $data['message'];
            return $out_data;
        } else {
            $result = $data['data']['result'];
            switch (intval($result)) {
                case 1:
                    $socre = intval($data['data']['socre']);
                    if ($socre > 9) {
                        $out_data['code'] = 103;
                        $out_data['info'] = "驾照剩余分数不足3分";
                        return $out_data;
                    } else {
                        $out_data['code'] = 0;
                        $out_data['info'] = "客户信息校验通过";
                        return $out_data;
                    }
                    break;
                case 2:
                    $out_data['code'] = 104;
                    $out_data['info'] = "驾驶证号与档案编号不一致";
                    break;
                case 3:
                    $out_data['code'] = 105;
                    $out_data['info'] = "姓名与驾驶证号不一致";
                    break;
            }
        }
        return $out_data;
    }

    /**
     * 更新用户首次登陆位置
     * @param $customer_id
     * @param $lng
     * @param $lat
     * @return bool
     */
    public function update_customer_wechar($customer_id, $lng, $lat)
    {
//        $customer_data = $this->getCustomerField($customer_id, "wechar_location_lat,wechar_location_lng");
//        $data = $customer_data;
//        if (floatval($data['wechar_location_lat']) != 0 && floatval($data['wechar_location_lng']) != 0) {
//            return false;
//        }
        $up_localhost = [
            'wechar_location_lat' => $lat,
            'wechar_location_lng' => $lng
        ];
        $ret = $this->where(['id' => $customer_id, 'wechar_location_lat' => '0', 'wechar_location_lng' => '0'])->setField($up_localhost);
        if ($ret !== false) {
            return true;
        }
        return false;
    }

    /**
     * 判断是否可以立即充电
     * @param $customer_id
     * @return array
     */
    public function isCharging($customer_id)
    {
        $return_data = [
            'code' => 100,
            'info' => "用户数据有误"
        ];
        if (empty($customer_id)) {
            return $return_data;
        }
        $customer_data_out = $this->getCustomerField($customer_id, "mobile_phone,customer_balance");
        if (!empty($customer_data_out['code'])) {
            return $return_data;
        }
        $customer_data = $customer_data_out['data'];
        if (empty($customer_data['mobile_phone'])) {
            $return_data['code'] = 101;
            $return_data['info'] = "手机号码未绑定";
            return $return_data;
        }
        $charging_min = Config::get('charging_min');
        if (floatval($customer_data['customer_balance']) < $charging_min) {
            $return_data['code'] = 102;
            $return_data['info'] = "余额不足30,请先充值";
            return $return_data;
        }
        $return_data['code'] = 0;
        $return_data['info'] = "条件满足";
        return $return_data;
    }

    /**
     * 更新驾驶里程
     * @param $customer_id
     * @param $drive_km
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function update_drive_km($customer_id, $drive_km)
    {
        $return_data = [
            'code' => 100,
            'info' => "用户id有误"
        ];
        if (empty($customer_id)) {
            return $return_data;
        }
        $customer_data = $this->where(['id' => $customer_id])->field('id,drive_all_km,drive_month_km,drive_month')->find();
        if (empty($customer_data)) {
            $return_data['code'] = 101;
            $return_data['info'] = "用户信息有误";
            return $return_data;
        }
        $drive_all_km = $customer_data['drive_all_km'];
        $drive_month_km = $customer_data['drive_month_km'];
        $month = date("Y-m");
        $drive_month = $customer_data['drive_month'];
        if ($month == $drive_month) {
            $drive_month_km = 0;
        }
        $set_data = [
            'drive_all_km' => intval($drive_all_km) + $drive_km,
            'drive_month_km' => intval($drive_month_km) + $drive_km,
            'drive_month' => date("Y-m"),
        ];
        $ret = $this->where(['id' => $customer_id])->setField($set_data);
        if ($ret !== false) {
            $return_data['code'] = 0;
            $return_data['info'] = "更新成功";
            return $return_data;
        }
        $return_data['code'] = 102;
        $return_data['info'] = "更新失败";
        return $return_data;
    }

    /**
     * 添加登录密码
     * 登录密码为最近一次值有效
     * @param $id 客户id
     * @param $login_password 登录密码
     * @return bool
     */
    private function _addLoginPassword($id, $login_password)
    {
        $in_login_password_data = array(
            'customer_id' => $id,
            'login_password' => md5(md5($login_password)),
            'on_set' => time(),
            'by_ip' => getIp()
        );
        $login_password_model = new LoginPassword();
        if ($login_password_model->save($in_login_password_data)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 验证登录密码
     * 登录密码为最近一次值有效
     * @param $id 客户id
     * @param $login_password 登录密码
     * @return bool
     */
    private function _verifyLoginPassword($id, $login_password)
    {
        $login_password_model = new LoginPassword();
        $customer_password = $login_password_model->where(array('customer_id' => $id))->order('on_set DESC')->find();
        //判断密码是否正确
        if ($customer_password['login_password'] === md5(md5($login_password))) {
            return true;//账号密码匹配成功
        }
        return false;
    }

    /**
     * 添加支付密码
     * 支付密码为最近一次值有效
     * @param $id 客户id
     * @param $pay_password 支付密码
     * @return bool
     */
    private function _addPayPassword($id, $pay_password)
    {
        $in_pay_password_data = array(
            'customer_id' => $id,
            'pay_password' => md5(md5($pay_password)),
            'on_set' => time(),
            'by_ip' => getIp()
        );
        $pay_password_model = new PayPassword();
        if ($pay_password_model->save($in_pay_password_data)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 验证支付密码
     * 登录密码为最近一次值有效
     * @param $id 客户id
     * @param $pay_password 登录密码
     * @return bool
     */
    private function _verifyPayPassword($id, $pay_password)
    {
        $pay_password_model = new PayPassword();
        $customer_password = $pay_password_model->where(array('customer_id' => $id))->order('on_set DESC')->find();
        //判断密码是否正确
        if ($customer_password['pay_password'] === md5(md5($pay_password))) {
            return true;//账号密码匹配成功
        }
        return false;
    }

    /**
     * 添加登录日志
     * @param $id
     * @return bool
     */
    private function _addCustomerLog($id)
    {
        $in_customer_log_data = array(
            'customer_id' => $id,
            'on_login' => date("Y-m-d H:i:s"),
            'by_ip' => getIp()
        );
        $customer_log_model = new CustomerLog();
        if ($customer_log_model->save($in_customer_log_data)) {
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
    private function _addCustomerToken($id)
    {
        $customer_token_model = new CustomerToken();
        //清除之前的对话
        $customer_token_model->where(array('customer_id' => $id))->delete();
        //构造对话数据
        $token = $this->_creatToken();
        $in_customer_token_data = array(
            'customer_id' => $id,
            'token' => $token,
            'on_set' => time(),
            'by_ip' => getIp()
        );
        //插入对话数据
        if ($customer_token_model->save($in_customer_token_data)) {
            return $token;
        } else {
            return false;
        }
    }

    /**
     * 验证客户对话随机字符串
     * @param $token
     * @return bool|mixed
     */
    private function _verifyCustomerToken($token)
    {
        $customer_token_model = new CustomerToken();
        $w = array(
            'token' => $token
        );
        $customer_token = $customer_token_model->where($w)->find();
        if (empty($customer_token)) {
            return 0;//对话过期
        }
        $token_out_time = Config::get('token_out_time');
        if ((time() - $customer_token['on_set']) > $token_out_time) {
            //如果对话过期 删除无用信息
            $customer_token_model->where($w)->delete();
            return 0;//对话过期
        }
        return $customer_token['customer_id'];
    }

    //产生随机对话加密字符串
    private function _creatToken()
    {
        $token = MD5(random_char(16) . date("YmdHis") . random_char(16) . time());
        return $token;
    }

    /**
     * 将客户信息存储到 Session中
     * @param $customer_data
     */
    private function _cannedCustomerData($customer_data)
    {
        //如果用户数据存在
        if (!empty($customer_data)) {
            //将数据存储到Session
            Session::set('customer_id', $customer_data['id']);
            Session::set('mobile_phone', $customer_data['mobile_phone']);
            Session::set('customer_status', $customer_data['customer_status']);
            Session::set('wechar_headimgurl', $customer_data['wechar_headimgurl']);
            Session::set('wechar_nickname', $customer_data['wechar_nickname']);
            Session::set('wechar_openid', $customer_data['wechar_openid']);
            Session::set('wechar_unionid', $customer_data['wechar_unionid']);
            Session::set('customer_data', json_encode($customer_data));

            Cookie::set('customer_id', $customer_data['id']);
            Cookie::set('mobile_phone', $customer_data['mobile_phone']);
            Cookie::set('customer_status', $customer_data['customer_status']);
            Cookie::set('wechar_headimgurl', $customer_data['wechar_headimgurl']);
            Cookie::set('wechar_nickname', $customer_data['wechar_nickname']);
            Cookie::set('wechar_openid', $customer_data['wechar_openid']);
            Cookie::set('wechar_unionid', $customer_data['wechar_unionid']);
        }
    }

    /**
     * 验证对话有效性
     * @return bool
     */
    private function _verifyCacheCustomerData()
    {
        $customer_id = Session::get('customer_id');
        //如果用户数据存在
        if (!empty($customer_id)) {
            return true;
        }
        return false;
    }

    /**
     * 将客户信息从Session中清除掉
     */
    private function _clearCustomerData()
    {
        Session::delete('customer_id');
        Session::delete('mobile_phone');
        Session::delete('customer_status');
        Session::delete('wechar_headimgurl');
        Session::delete('wechar_nickname');
        Session::delete('wechar_openid');
        Session::delete('wechar_unionid');
        Session::delete('customer_data');
        Session::clear();

        Cookie::delete('customer_id');
        Cookie::delete('mobile_phone');
        Cookie::delete('customer_status');
        Cookie::delete('wechar_headimgurl');
        Cookie::delete('wechar_nickname');
        Cookie::delete('wechar_openid');
        Cookie::delete('wechar_unionid');
        Cookie::clear();
    }

    //添加用户余额日志
    private function _addCustomerBalanceLog($money)
    {
        $money['add_time'] = time();
        $customerbalancelog_model = new CustomerBalanceLog();
        $customerbalancelog_model->save($money);
    }

}