<?php

namespace app\api\controller;

use app\common\controller\UserBase;
use app\common\model\CustomerBalance;
use app\common\model\CustomerCash;
use app\common\model\Order;
use app\common\model\CustomerCoupon;
use app\common\model\Reserve as ReserveModel;
use app\common\model\CustomerDrive as CustomerDriveModel;
use app\common\model\CustomerSignLog as CustomerSignLogModel;
use baidu\aip\AipOcr;
use chinadatapay\ChinaDataApi;
use definition\CustomerStatus;
use definition\GoodsType;
use think\Config;
use think\Session;

/**
 * 用户基本操作接口
 * Class Customer
 * @package app\api\controller
 */
class Customer extends UserBase
{
    private $customer_drive_model;
    private $sign_log_model;

    protected function _initialize()
    {
        parent::_initialize();
        $this->customer_drive_model = new CustomerDriveModel();
    }

    //-------------客户基本信息操作开始----------------

    /**
     * 获取用户头像
     * @param string $customer_id
     */
    public function get_customer_head($customer_id = '')
    {
        $customer_data = $this->customer_model->where(['id' => $customer_id])->field('wechar_headimgurl')->find();
        if (empty($customer_id)) {
            exit();
        }
        if (empty($customer_data['wechar_headimgurl'])) {
            $out_data = https_get('http://img.youchedongli.cn/public/static/mobile/images/logo.png?x-oss-process=image/resize,w_90');
            header("Content-Type: image/jpeg;text/html; charset=utf-8");
            exit($out_data);
        }
        $out_data = https_get($customer_data['wechar_headimgurl']);
        header("Content-Type: image/jpeg;text/html; charset=utf-8");
        exit($out_data);
    }

    /**
     * 更改手机号码
     * 输入方式 POST
     * 输入参数
     * mobile 手机号码
     * code 验证码
     * relieve 解除/绑定 为relieve 表示解除绑定，为空或其他表示绑定
     */
    public function change_mobile_phone()
    {
        $out_data = array(
            'code' => 20,
            'info' => "参数有误"
        );
        if ($this->request->isPost()) {
            $data = $this->request->only(['mobile', 'code', 'relieve']);
            $out_verify = $this->_verify($data['code'], $data['mobile']);
            if ($out_verify['code'] == 0) {
//         * @param $customer_data
//                * customer_id 客户id
//                * mobile_phone 手机号码
//         * @param bool $is_clear 是否是解绑手机号码
                $is_clear = false;
                $relieve = $data['relieve'];
                if (!empty($relieve) && $relieve == 'relieve') {
                    $is_clear = true;
                }
                $out_mobile = $this->customer_model->updateCustomerMobile($this->customer_info['customer_id'], $data['mobile'], $is_clear);
                out_json_data($out_mobile);
            } else {
                out_json_data($out_verify);
            }
        }
        out_json_data($out_data);
    }

    /**
     * 获取个人信息
     * 输入方式 POST
     * 输入参数 无
     */
    public function get_customer_data()
    {
        $out_customer = $this->customer_model->getCustomer('', $this->customer_info['customer_id'], false);
        out_json_data($out_customer);
    }

    /**
     * 缴纳押金
     * 返回支付信息
     *  'code' => 0,
     *  info' => "提交成功",
     *  out_trade_no' => 支付单号,
     *  body' => "优车出行 押金缴纳",
     *  total_price' => 押金金额
     */
    public function add_deposit()
    {
        $customer_cash = new CustomerCash();
        $cash = Config::get('cash');
        $cash_info = array(
            'customer_id' => $this->customer_info['customer_id'],
            'mobile_phone' => $this->customer_info['mobile_phone'],
            'cash' => $cash
        );
        $out_data = $customer_cash->add_cash($cash_info);
        out_json_data($out_data);
    }

    /**
     * 充值余额
     * @param int $balance
     */
    public function add_balance($balance = 1)
    {
        $customer_balance = new CustomerBalance();
        $balance_info = array(
            'balance' => $balance,// 单位 元
            'customer_id' => $this->customer_info['customer_id'],//用户id
        );
        $out_data = $customer_balance->add_balance($balance_info);
        out_json_data($out_data);
    }

    /**
     * 更新实名制数据
     */
    public function update_customer_data()
    {
        $out_data = array(
            'code' => 20,
            'info' => "参数有误"
        );
        if ($this->request->isPost()) {
            $data = $this->request->only(['customer_name', 'id_number', 'id_number_time', 'driver_license', 'driver_license_time']);
            if (strlen($data['customer_name']) < 2) {
                $out_data['code'] = 21;
                $out_data['info'] = "姓名不符合规范";
                out_json_data($out_data);
            }
            if (idcard_checksum18($data['id_number']) === false) {
                $out_data['code'] = 22;
                $out_data['info'] = "身份证号码不符合规范";
                out_json_data($out_data);
            }
            $car_customer = $this->customer_model->where(['id_number' => $data['id_number']])->field('id')->find();
            if (!empty($car_customer)) {
                if (intval($car_customer['id']) != intval($this->customer_info['customer_id'])) {
                    $out_data['code'] = 29;
                    $out_data['info'] = "身份证号码已绑定其它账号";
                    out_json_data($out_data);
                }
            }
            if (empty($data['driver_license'])) {
                $out_data['code'] = 24;
                $out_data['info'] = "驾驶证档案号不符合规范";
                out_json_data($out_data);
            }
            if (!validity_time($data['id_number_time'], 0)) {
                $out_data['code'] = 27;
                $out_data['info'] = "身份证有效期不足两个月";
                out_json_data($out_data);
            }
            if (!validity_time($data['driver_license_time'], 0)) {
                $out_data['code'] = 28;
                $out_data['info'] = "驾驶证有效期不足两个月";
                out_json_data($out_data);
            }

            $up_customer_data = array(
                'id' => $this->customer_info['customer_id']
            );
            $customer_data = $this->customer_model->where($up_customer_data)->field('customer_name,id_number,customer_status,driver_license_name,driver_license,customer_front,id_number_image_front,id_number_image_reverse,driver_license_image_front,driver_license_image_attach,customer_check_count,customer_check_time')->find();
            if (intval($customer_data['customer_check_count']) >= 2) {
                $out_data['code'] = 26;
                $out_data['info'] = "自动审核次数已用完，请联系客服";
                out_json_data($out_data);
            }
            if ($customer_data['customer_name'] != $customer_data['driver_license_name']) {
                $out_data['code'] = 26;
                $out_data['info'] = $customer_data['customer_name'] . "身份证信息与驾驶证信息不符，请重新上传" . $customer_data['driver_license_name'];
                out_json_data($out_data);
            }
            if (empty($customer_data['id_number_image_front'])) {
                $out_data['code'] = 26;
                $out_data['info'] = "身份证人脸页请重新上传";
                out_json_data($out_data);
            }
            if (empty($customer_data['id_number_image_reverse'])) {
                $out_data['code'] = 27;
                $out_data['info'] = "身份证国徽页请重新上传";
                out_json_data($out_data);
            }
            if (empty($customer_data['driver_license_image_front'])) {
                $out_data['code'] = 28;
                $out_data['info'] = "驾驶证主页请重新上传";
                out_json_data($out_data);
            }
            if (empty($customer_data['driver_license_image_attach'])) {
                $out_data['code'] = 29;
                $out_data['info'] = "驾驶证副页请重新上传";
                out_json_data($out_data);
            }
            if (empty($customer_data['customer_front'])) {
                $out_data['code'] = 30;
                $out_data['info'] = "用户正面照请重新上传";
                out_json_data($out_data);
            }
            if (!empty($customer_data)) {
                if (intval($customer_data['customer_status']) == 0 || intval($customer_data['customer_status']) == 3) {
                    $this->customer_model->where($up_customer_data)->setField(['customer_name' => $data['customer_name'], 'id_number' => $data['id_number'], 'driver_license' => $data['driver_license'], 'customer_status' => 4]);
                    $out_data = $this->customer_model->checkPersonalFace($this->customer_info['customer_id']);
                    out_json_data($out_data);
                } else if (intval($customer_data['customer_status']) == 4) {
                    $out_data['code'] = 24;
                    $out_data['info'] = "当前账号正在审核中,不能提交数据";
                    out_json_data($out_data);
                } else if (intval($customer_data['customer_status']) == 2) {
                    $out_data['code'] = 24;
                    $out_data['info'] = "当前账号已被锁定,不能提交数据";
                    out_json_data($out_data);
                } else if (intval($customer_data['customer_status']) == 1) {
                    $out_data['code'] = 25;
                    $out_data['info'] = "当前账号已审核通过,无需再次提交";
                    out_json_data($out_data);
                } else if (intval($customer_data['customer_status']) == 5) {
                    $out_data['code'] = 27;
                    $out_data['info'] = "驾驶证累计扣分已达到12分,请一个月之后再次提交申请";
                    out_json_data($out_data);
                }
            }
        }
        out_json_data($out_data);
    }

    /**
     * 更新用户首次进入 微信位置
     */
    public function update_customer_wechar()
    {
        $out_data = array(
            'code' => 20,
            'info' => "参数有误"
        );
        if ($this->request->isPost()) {
            $data = $this->request->only(['lat', 'lng']);
            if (floatval($data['lat']) == 0 || floatval($data['lng']) == 0) {
                $out_data['code'] = 21;
                $out_data['info'] = "定位失败";
                out_json_data($out_data);
            }
            $wechar_location_lat = $data['lat'];
            $wechar_location_lng = $data['lng'];
            if ($this->customer_model->update_customer_wechar($this->customer_info['customer_id'], $wechar_location_lng, $wechar_location_lat)) {
                $out_data['code'] = 0;
                $out_data['info'] = "更新成功";
                out_json_data($out_data);
            }
            $out_data['code'] = 23;
            $out_data['info'] = "更新失败";
            out_json_data($out_data);
        }
        out_json_data($out_data);
    }
    //-------------客户基本信息操作结束----------------

    //---------------驾驶信息开始-------------
    /**
     * 添加驾驶人信息
     * 输入方式 POST
     * 输入参数
     * vehicle_drivers 驾驶人姓名
     * mobile_phone 手机号码
     * id_number 身份证号码
     */
    public function add_customer_drive()
    {
        $out_data = array(
            'code' => 1,
            'info' => "参数有误"
        );
        if ($this->request->isPost()) {
            $data = $this->request->only(['vehicle_drivers', 'mobile_phone', 'id_number',]);
            $vehicle_drivers = $data['vehicle_drivers'];
            $mobile_phone = $data['mobile_phone'];
            $id_number = $data['id_number'];
            if (strlen($vehicle_drivers) < 2) {
                $out_data['code'] = 2;
                $out_data['info'] = "驾驶员姓名有误";
                out_json_data($out_data);
            }
            if (check_mobile_number($mobile_phone) === false) {
                $out_data['code'] = 3;
                $out_data['info'] = "电话号码格式有误";
                out_json_data($out_data);
            }
            if (idcard_checksum18($id_number) === false) {
                $out_data['code'] = 4;
                $out_data['info'] = "身份证号码不符合规范";
                out_json_data($out_data);
            }
            $in_customer_drive = array(
                'customer_id' => $this->customer_info['customer_id'],
                'vehicle_drivers' => $vehicle_drivers,
                'mobile_phone' => $mobile_phone,
                'id_number' => $id_number,
                'update_time' => date("Y-m-d H:i:s")
            );
            $out_drive = $this->customer_drive_model->addCustomerDrive($in_customer_drive);
            out_json_data($out_drive);
        }
        out_json_data($out_data);
    }

    /**
     * 设置为默认驾驶人信息
     * 输入方式 POST
     * 输入参数
     * drive_id 数据id
     * vehicle_drivers 驾驶人姓名
     * mobile_phone 手机号码
     * id_number 身份证号码
     */
    public function default_customer_drive()
    {
        $out_data = array(
            'code' => 1,
            'info' => "参数有误"
        );
        if ($this->request->isPost()) {
            $data = $this->request->only(['drive_id']);
            $drive_id = $data['drive_id'];
            if (!is_numeric($drive_id)) {
                $out_data['info'] = "数据id不合法";
                out_json_data($out_data);
            }
            $out_drive = $this->customer_drive_model->defaultCustomerDrive($drive_id, $this->customer_info['customer_id']);
            out_json_data($out_drive);
        }
        out_json_data($out_data);
    }

    /**
     * 更新驾驶人信息
     * 输入方式 POST
     * 输入参数
     * drive_id 数据id
     * vehicle_drivers 驾驶人姓名
     * mobile_phone 手机号码
     * id_number 身份证号码
     */
    public function update_customer_drive()
    {
        $out_data = array(
            'code' => 1,
            'info' => "参数有误"
        );
        if ($this->request->isPost()) {
            $data = $this->request->only(['drive_id', 'vehicle_drivers', 'mobile_phone', 'id_number']);
            $drive_id = $data['drive_id'];
            if (!is_numeric($drive_id)) {
                $out_data['info'] = "数据id不合法";
                out_json_data($out_data);
            }
            $vehicle_drivers = $data['vehicle_drivers'];
            $mobile_phone = $data['mobile_phone'];
            $id_number = $data['id_number'];
            if (strlen($vehicle_drivers) < 2) {
                $out_data['code'] = 2;
                $out_data['info'] = "驾驶员姓名有误";
                out_json_data($out_data);
            }
            if (check_mobile_number($mobile_phone) === false) {
                $out_data['code'] = 3;
                $out_data['info'] = "电话号码格式有误";
                out_json_data($out_data);
            }
            if (idcard_checksum18($id_number) === false) {
                $out_data['code'] = 4;
                $out_data['info'] = "身份证号码不符合规范";
                out_json_data($out_data);
            }
            $up_customer_drive = array(
                'drive_id' => $drive_id,
                'customer_id' => $this->customer_info['customer_id'],
                'vehicle_drivers' => $vehicle_drivers,
                'mobile_phone' => $mobile_phone,
                'id_number' => $id_number,
                'update_time' => date("Y-m-d H:i:s")
            );
            $out_drive = $this->customer_drive_model->updateCustomerDrive($up_customer_drive);
            out_json_data($out_drive);
        }
        out_json_data($out_data);
    }

    /**
     * 删除驾驶人信息
     * 输入方式 POST
     * 输入参数
     * drive_id 数据id
     */
    public function delete_customer_drive()
    {
        $out_data = array(
            'code' => 1,
            'info' => "参数有误"
        );
        if ($this->request->isPost()) {
            $data = $this->request->only(['drive_id']);
            $drive_id = $data['drive_id'];
            if (!is_numeric($drive_id)) {
                $out_data['info'] = "数据id不合法";
                out_json_data($out_data);
            }
            $delete_customer_drive = array(
                'drive_id' => $drive_id,
                'customer_id' => $this->customer_info['customer_id']
            );
            $out_drive = $this->customer_drive_model->deleteCustomerDrive($delete_customer_drive);
            out_json_data($out_drive);
        }
        out_json_data($out_data);
    }

    /**
     * 获取驾驶人信息
     * 输入方式 POST
     * 输入参数
     * drive_id 数据id 为空默认第一条
     */
    public function get_customer_drive()
    {
        $data = $this->request->only(['drive_id', 'goods_type']);
        $drive_id = $data['drive_id'];
        $goods_type = $data['goods_type'];
        if (empty($drive_id)) {
            $drive_id = 0;
        }
        if (empty($goods_type)) {
            $out_data['code'] = 1;
            $out_data['info'] = "数据不存在";
            out_json_data($out_data);
        }
        if (intval($goods_type) == GoodsType::$GoodsTypeElectrocar['code']) {
            $out_customer = $this->customer_model->getCustomer('', $this->customer_info['customer_id'], false);
            $out_customer = $out_customer['data'];
            if (!empty($out_customer)) {
                $out_data['code'] = 2;
                $out_data['info'] = "审核中";
                switch (intval($out_customer['customer_status'])) {
                    case 0:
                        $out_data['code'] = 3;
                        $out_data['info'] = CustomerStatus::$CUSTOMERSTATUS_CODE[intval($out_customer['customer_status'])];
                        break;
                    case 1:
                        $out_data['code'] = 0;
                        $out_data['info'] = "获取成功";
                        $out_data['data'] = array(
                            'vehicle_drivers' => $out_customer['customer_name'],//驾驶人姓名
                            'mobile_phone' => $out_customer['mobile_phone'],
                            'id_number' => $out_customer['id_number']
                        );
                        break;
                    case 2:
                        $out_data['code'] = 2;
                        $out_data['info'] = CustomerStatus::$CUSTOMERSTATUS_CODE[intval($out_customer['customer_status'])];;
                        break;
                    case 3:
                        $out_data['code'] = 3;
                        $out_data['info'] = CustomerStatus::$CUSTOMERSTATUS_CODE[intval($out_customer['customer_status'])];;
                        break;
                    case 4:
                        $out_data['code'] = 2;
                        $out_data['info'] = CustomerStatus::$CUSTOMERSTATUS_CODE[intval($out_customer['customer_status'])];;
                        break;
                }
                out_json_data($out_data);
            } else {
                $out_data['code'] = 110;
                $out_data['info'] = "数据不存在";
                out_json_data($out_data);
            }
        }
        $out_drive = $this->customer_drive_model->getCustomerDrive($drive_id, $this->customer_info['customer_id'], true);
        $out_drive['data'] = array(
            'vehicle_drivers' => $out_drive['data']['vehicle_drivers'],//驾驶人姓名
            'mobile_phone' => $out_drive['data']['mobile_phone'],
            'id_number' => $out_drive['data']['id_number']
        );
        out_json_data($out_drive);
    }

    /**
     * 获取驾驶人信息列表
     * 输入方式 POST
     * 输入参数 无
     */
    public function get_list_customer_drive()
    {
        $out_drive = $this->customer_drive_model->getListCustomerDrive($this->customer_info['customer_id']);
        out_json_data($out_drive);
    }

//---------------驾驶信息结束--------------

//---------------标记点-------------------
    /**
     * 添加用户充电桩标记
     */
    public function add_customer_sign()
    {
        $out_data = array(
            'code' => 1200,
            'info' => '参数有误'
        );
        $this->sign_log_model = new CustomerSignLogModel();
        if ($this->request->isPost()) {
            $data = $this->request->only(['longitude', 'latitude', 'type', 'remark', 'address']);
            $data['customer_id'] = $this->customer_info['customer_id'];
            $out_data = $this->sign_log_model->addSign($data);
        }
        out_json_data($out_data);
    }

    /**
     * 删除用户充电桩标记
     */
    public function del_customer_sign()
    {
        $out_data = array(
            'code' => 1200,
            'info' => '参数有误'
        );
        $this->sign_log_model = new CustomerSignLogModel();
        if ($this->request->isPost()) {
            $data = $this->request->only(['id']);
            $data['customer_id'] = $this->customer_info['customer_id'];
            $out_data = $this->sign_log_model->delSign($data);
        }
        out_json_data($out_data);
    }

    /**
     * 获取用户标记的点
     * @param $page 0开始
     * @param int $type 类型
     */
    public function get_customer_sign_list($page = 0, $type = 1)
    {
        $out_data = array(
            'code' => 110,
            'info' => '没有数据'
        );
        $this->sign_log_model = new CustomerSignLogModel();
        if (empty($type)) {
            $type = 1;
        }
        $map = array(
            'customer_id' => $this->customer_info['customer_id'],
            'type' => $type
        );
        $sign_list = $this->sign_log_model->getList($map, 'id DESC', $page, 50);
        if (!empty($sign_list)) {
            $out_data['code'] = 0;
            $out_data['data'] = $sign_list;
            $out_data['info'] = "获取成功";
        }
        out_json_data($out_data);
    }

//---------------标记点结束---------------

//---------------识别接口---------------
    /**
     * 身份证识别
     * @param string $serverId
     * @param string $url
     * @param int $type 1人像页 2国徽页
     */
    public function idcard_ocr($serverId = '', $url = '', $type = 1)
    {
        $out_data = array(
            'code' => 100,
            'info' => "身份证识别失败"
        );
        if (empty($url)) {
            if (empty($serverId)) {
                out_json_data($out_data);
            }
            $image_data = get_img_url($serverId, "jpg", "idnumber", true);
            $image = $image_data['data'];
            $url = "/" . $image_data['url'];
        } else {
            $image = file_get_contents($url);
        }
        $baidu_config = Config::get('baiduaip');
        $client = new AipOcr($baidu_config['APP_ID'], $baidu_config['API_KEY'], $baidu_config['SECRET_KEY']);
//        $data = ChinaDataApi::idcard_ocr($image);
        if (intval($type) == 1) {
            $data = $client->idcard($image, 'front');
            file_put_contents("public/idcard/" . $this->customer_info['customer_id'] . "_front.txt", json_encode($data));
            if (!isset($data['error_code'])) {
                $words_result = $data['words_result'];
                $sex = $words_result['性别']['words'];
                $customer_sex = 0;
                if ($sex == "男") {
                    $customer_sex = 1;
                }
                $out_data['data'] = ['url' => $url, 'name' => $words_result['姓名']['words'], 'idcard' => $words_result['公民身份号码']['words']];
                $map['id'] = $this->customer_info['customer_id'];
                $car_customer = $this->customer_model->where(['id_number' => $words_result['公民身份号码']['words']])->field('id')->find();
                if (!empty($car_customer)) {
                    if (intval($car_customer['id']) != intval($this->customer_info['customer_id'])) {
                        $out_data['code'] = 201;
                        $out_data['info'] = "身份证已绑定其他账号";
                        out_json_data($out_data);
                    }
                }
                $updata = [
                    'customer_name' => $words_result['姓名']['words'],
                    'id_number' => $words_result['公民身份号码']['words'],
                    'id_number_image_front' => $url,
                    'customer_sex' => $customer_sex
                ];
                $this->customer_model->updateCustomerField($map, $updata);
                $out_data['code'] = 0;
                $out_data['info'] = "识别成功";
            }
        } else if (intval($type) == 2) {
            $data = $client->idcard($image, 'back');
            file_put_contents("public/idcard/" . $this->customer_info['customer_id'] . "_back.txt", json_encode($data));
            if (!isset($data['error_code'])) {
                $words_result = $data['words_result'];
                $qf_time = $words_result['签发日期']['words'];
                $sx_time = $words_result['失效日期']['words'];
                $qf_time = substr($qf_time, 0, 4) . "." . substr($qf_time, 4, 2) . "." . substr($qf_time, 6, 2);
                if (is_numeric($sx_time)) {
                    $sx_time = substr($sx_time, 0, 4) . "." . substr($sx_time, 4, 2) . "." . substr($sx_time, 6, 2);
                }
                if (!validity_time($qf_time . "-" . $sx_time, 0)) {
                    $out_data['code'] = 1002;
                    $out_data['info'] = "身份证有效期不足2个月" . $qf_time . "-" . $sx_time;
                    out_json_data($out_data);
                }
                $out_data['data'] = ['url' => $url, 'timelimit' => $qf_time . "-" . $sx_time];
                $map['id'] = $this->customer_info['customer_id'];
                $updata = [
                    'id_number_time' => $qf_time . "-" . $sx_time,
                    'id_number_image_reverse' => $url
                ];
                $this->customer_model->updateCustomerField($map, $updata);
                $out_data['code'] = 0;
                $out_data['info'] = "识别成功";
            }
        }
        out_json_data($out_data);
    }

    /**
     * 驾驶证识别
     * @param string $serverId
     * @param string $url
     * @param int $type 1驾驶证主页 2驾驶证副页
     */
    public function driving_ocr($serverId = '', $url = '', $type = 1)
    {
        $out_data = array(
            'code' => 100,
            'info' => "识别失败"
        );
        if (empty($url)) {
            if (empty($serverId)) {
                out_json_data($out_data);
            }
            $image_data = get_img_url($serverId, "jpg", "driverlicense", true);
            $image = $image_data['data'];
            $url = "/" . $image_data['url'];
        } else {
            $image = file_get_contents($url);
        }
//        if ($type == 1) {
//            $baidu_config = Config::get('baiduaip');
//            $client = new AipOcr($baidu_config['APP_ID'], $baidu_config['API_KEY'], $baidu_config['SECRET_KEY']);
//            $data = $client->drivingLicense($image);
//        }else{
//
//        }

        $image = base64_encode($image);
        $data = ChinaDataApi::driving_ocr($image);
        if ($data['code'] == "10000") {
            if (!empty($data['data']['info_Positive'])) {
                file_put_contents("public/driving/" . $this->customer_info['customer_id'] . "_positive.txt", json_encode($data));
                $out_data['data'] = $data['data']['info_Positive'];
            } else if (!empty($data['data']['info_negative'])) {
                $out_data['data'] = $data['data']['info_negative'];
                file_put_contents("public/driving/" . $this->customer_info['customer_id'] . "_negative.txt", json_encode($data));
            }
//            file_put_contents('driving_ocr.txt',json_encode($out_data['data']));
            $customer_data_temp = $this->customer_model->getCustomerField($this->customer_info['customer_id'], 'customer_name,id_number');
            $customer_data_temp = $customer_data_temp['data'];
            if (empty($customer_data_temp['customer_name']) || empty($customer_data_temp['id_number'])) {
                $out_data['code'] = 1000;
                $out_data['info'] = "请先上传身份证信息";
                out_json_data($out_data);
            } else {
                if ($customer_data_temp['customer_name'] != $out_data['data']['name'] && $customer_data_temp['id_number'] != $out_data['data']['idcard']) {
                    $out_data['code'] = 1001;
                    $out_data['info'] = "驾驶证信息与身份证不符";
                    out_json_data($out_data);
                }
            }
            $map['id'] = $this->customer_info['customer_id'];
            if (intval($type) == 1) {
                if ((!strpos($out_data['data']['end_date'], '年')) && strlen($out_data['data']['end_date']) >= 4 && strlen($out_data['data']['end_date']) < 10) {
                    $out_data['data']['end_date'] = substr($out_data['data']['end_date'], 0, 4) . "-" . substr($out_data['data']['begin_date'], 5, 5);
                }
                $has_time = str_replace('-', '.', $out_data['data']['begin_date']) . "-" . str_replace('-', '.', $out_data['data']['end_date']);
                if (!validity_time($has_time, 0)) {
                    $out_data['code'] = 1002;
                    $out_data['info'] = "驾驶证有效期不足";
                    out_json_data($out_data);
                }
                $updata = [
                    'driver_license_time' => $has_time,
                    'driver_license_name' => $customer_data_temp['customer_name'],
                    'driver_license_number' => $out_data['data']['idcard'],
                    'driver_license_image_front' => $url,
                ];
                $this->customer_model->updateCustomerField($map, $updata);
            } else if (intval($type) == 2) {
                $updata = [
                    'driver_license' => $out_data['data']['file_number'],
                    'driver_license_image_attach' => $url,
                ];
                $this->customer_model->updateCustomerField($map, $updata);
            }
            if (empty($out_data['data'])) {
                $out_data['code'] = 101;
                $out_data['info'] = "识别错误";
            } else {
                $out_data['data']['url'] = $url;
                $out_data['code'] = 0;
                $out_data['info'] = "识别成功";
            }
        }
        $out_data['info'] = $data['code'] . "";
        out_json_data($out_data);
    }

//---------------识别接口结束---------------

//---------------预定车辆-------------------
    /**
     * 添加预定
     * @param $car_id
     */
    public function add_reserve($car_id)
    {
        $out_data = [
            'code' => 110,
            'info' => "参数有误"
        ];
        if (empty($car_id)) {
            out_json_data($out_data);
        }
        $dataout = array(
            'code' => 1,
            'info' => '参数有误',
        );
        $customer_data_temp = $this->customer_model->getCustomerField($this->customer_info['customer_id'], 'customer_status,cash_is');
        $customer_status_data = CustomerStatus::$CUSTOMERSTATUS_CODE;
        $customer_status = intval($customer_data_temp['data']['customer_status']);
        switch ($customer_status) {
            case CustomerStatus::$CustomerStatusWait['code']:
                $dataout['code'] = 3;
                $dataout['info'] = $customer_status_data[$customer_status];
                out_json_data($dataout);
                break;
            case CustomerStatus::$CustomerStatusLock['code']:
                $dataout['code'] = 4;
                $dataout['info'] = $customer_status_data[$customer_status];
                out_json_data($dataout);
                break;
            case CustomerStatus::$CustomerStatusFailure['code']:
                $dataout['code'] = 3;
                $dataout['info'] = $customer_status_data[$customer_status];
                out_json_data($dataout);
                break;
            case CustomerStatus::$CustomerStatusCheck['code']:
                $dataout['code'] = 4;
                $dataout['info'] = $customer_status_data[$customer_status];
                out_json_data($dataout);
                break;
        }
        if (intval($customer_data_temp['data']['cash_is']) == 0) {
            $dataout['code'] = 5;
            $dataout['info'] = "未缴纳押金，请先缴纳押金";
            out_json_data($dataout);
        }
        $order_model = new Order();
        $dataout = $order_model->IsAddOrder($this->customer_info['customer_id'], GoodsType::$GoodsTypeElectrocar['code']);
        if (!empty($dataout['code'])) {
            out_json_data($dataout);
        }
        $reserve_model = new ReserveModel();
        $out_data = $reserve_model->addReserve($car_id, $this->customer_info['customer_id'], $this->customer_info['mobile_phone']);
        out_json_data($out_data);
    }

    /**
     * 取消预定
     * @param $car_id
     */
    public function cancel_reserve($car_id)
    {
        $out_data = [
            'code' => 110,
            'info' => "参数有误"
        ];
        if (empty($car_id)) {
            out_json_data($out_data);
        }
        $reserve_model = new ReserveModel();
        $out_data = $reserve_model->cancelReserve($car_id, $this->customer_info['customer_id']);
        out_json_data($out_data);
    }

    /**
     * 完成预定
     * @param $car_id
     */
    public function end_reserve($car_id)
    {
        $out_data = [
            'code' => 110,
            'info' => "参数有误"
        ];
        if (empty($car_id)) {
            out_json_data($out_data);
        }
        $reserve_model = new ReserveModel();
        $out_data = $reserve_model->endReserve($car_id, $this->customer_info['customer_id']);
        out_json_data($out_data);
    }

    /**
     * 获取预定信息
     */
    public function get_reserve()
    {
        $reserve_model = new ReserveModel();
        $out_data = $reserve_model->getReserve($this->customer_info['customer_id']);
        out_json_data($out_data);
    }

    /**
     * 预约 寻车
     * @param $car_id
     */
    public function find_car($car_id)
    {
        $reserve_model = new ReserveModel();
        $time = Session::get('reserve_find_car_time');
        if (!empty($time)) {
            if (time() - $time < 10) {
                $out_data['code'] = 102;
                $out_data['info'] = "指令下发过于频繁，请稍后";
            }
        }
        $out_data = $reserve_model->findCar($car_id, $this->customer_info['customer_id']);
        if (empty($out_data['code'])) {
            Session::set('reserve_find_car_time', time());
            $out_data['info'] = "指令下发成功";
        }
        out_json_data($out_data);
    }

//---------------预定车辆结束-------------------

//----------------获取代金券列表----------------
    public function get_coupon($page = 0, $state = -1)
    {
        $out_data = [
            'code' => 110,
            'info' => "暂无数据"
        ];
        if (empty($state)) {
            $state = 0;
        }
        $customer_coupon_model = new CustomerCoupon();
        $map = array(
            'customer_id' => $this->customer_info['customer_id'],
        );
        if (intval($state) >= 0) {
            $map['state'] = $state;
        }
        $coupon = $customer_coupon_model->getlist($map, 'start_time DESC', $page, 8);
        if (empty($coupon)) {
            out_json_data($out_data);
        }
        $out_data['code'] = 0;
        $out_data['data'] = $coupon;
        $out_data['info'] = "获取成功";
        out_json_data($out_data);
    }

//----------------获取代金券列表结束----------------

    public function get_channel($page = 0)
    {
        $out_data = array(
            'code' => 100,
            'info' => '参数有误'
        );
//        $customer_channel_model = new CustomerChannel();
//        $customer_channel_data = $customer_channel_model->where(['customer_id' => $this->customer_info['customer_id']])->find();
//        if (empty($customer_channel_data)) {
//            $out_data['code'] = 101;
//            $out_data['info'] = '暂无渠道信息';
//            out_json_data($out_data);
//        }
        $field = "mobile_phone,wechar_headimgurl,wechar_nickname,create_time,customer_status,customer_end_time";
        $customer_data_list = $this->customer_model->where(['channel_uid' => $this->customer_info['customer_id']])->field($field)->order('create_time DESC')->limit($page * 10, 10)->select();
        if (empty($customer_data_list)) {
            if (empty($page)) {
                $out_data['code'] = 104;
                $out_data['info'] = '暂无数据';
                out_json_data($out_data);
            }
            $out_data['code'] = 103;
            $out_data['info'] = '数据已加载完毕';
            out_json_data($out_data);
        }
        $out_data['code'] = 0;
        $out_data['data'] = $customer_data_list;
        $out_data['info'] = '获取成功';
        out_json_data($out_data);
    }

    /**
     * 验证短信验证码
     * @param $code_v 短信验证码
     * @param $mobile_v 验证的手机号码
     * @return array
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
            $dataout['code'] = "验证成功";
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

}