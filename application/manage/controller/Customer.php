<?php

namespace app\manage\controller;

use app\common\controller\AdminBase;
use app\common\model\Customer as CustomerModel;
use app\common\model\CustomerBalanceLog;
use app\common\model\CustomerCoupon;
use app\common\model\Reserve;
use definition\CustomerStatus;
use definition\RegistType;

/**
 * 客户管理
 * Class AdminUser
 * @package app\manage\controller
 */
class Customer extends AdminBase
{
    protected $customer_model;

    protected function _initialize()
    {
        parent::_initialize();
        $this->customer_model = new CustomerModel();
    }

    /**
     * 用户管理
     * @param string $keyword
     * @param string $status
     * @param int $page
     * @param string $start_time
     * @param string $end_time
     * @param string $channel_uid
     * @return mixed
     */
    public function index($keyword = '', $status = '', $page = 1, $start_time = '', $end_time = '', $channel_uid = '')
    {
        $map = [];
        if ($keyword) {
            $map['id|mobile_phone|customer_name|customer_nickname|id_number'] = ['like', "%{$keyword}%"];
        }
        if (is_numeric($status) && intval($status) >= 0) {
            if (empty($status)) {
                $status = 0;
                $map['customer_status'] = $status;
            } else {
                $map['customer_status'] = $status;
            }
            $page_config = ['page' => $page, 'query' => ['keyword' => $keyword, 'status' => $status]];
        } else {
            $status = "-1";
            $page_config = ['page' => $page, 'query' => ['keyword' => $keyword]];
        }
        if (!empty($start_time) && !empty($end_time)) {
            $map['create_time'] = ['between', $start_time . "," . $end_time];
            $page_config['query']['create_time'] = ['between', $start_time . "," . $end_time];
        }
        if (!empty($channel_uid)) {
            $map['channel_uid'] = $channel_uid;
            $page_config['query']['channel_uid'] = $channel_uid;
        }
        $customer_list = $this->customer_model->where($map)->order('id DESC')->paginate(15, false, $page_config);
        foreach ($customer_list as &$value) {
            $this->customer_model->formatx($value);
        }
        $status_list = CustomerStatus::$CUSTOMERSTATUS_CODE;
        $finish = $this->customer_model->where(['customer_status' => 1])->count();
        $total = $this->customer_model->where([])->count();
        return $this->fetch('index', ['customer_list' => $customer_list, 'finish' => $finish, 'total' => $total, 'status_list' => $status_list, 'keyword' => $keyword, 'status' => $status, 'start_time' => $start_time, 'end_time' => $end_time, 'channel_uid' => $channel_uid]);
    }

    /**
     * 添加用户
     * @return mixed
     */
    public function add()
    {
        return $this->fetch();
    }

    /**
     * 保存用户
     */
    public function save()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $login_password = $data['login_password'];
            $confirm_password = $data['confirm_password'];
            if ($login_password !== $confirm_password) {
                $this->error("两次密码不一致");
            }
            $in_customer_data = array(
                'mobile_phone' => $data['mobile_phone'],
                'login_password' => $data['login_password'],
                'regist_type' => RegistType::$RegistTypeAdmin['code'],//后台添加
                'customer_name' => $data['customer_name'],
                'customer_nickname' => $data['customer_nickname'],
                'customer_sex' => $data['customer_sex'],
                'address' => $data['address'],
                'id_number' => $data['id_number'],
                'id_number_image_front' => $data['id_number_image_front'],
                'id_number_image_reverse' => $data['id_number_image_reverse'],
                'driver_license' => $data['driver_license'],
                'driver_license_image_front' => $data['driver_license_image_front'],
                'driver_license_image_attach' => $data['driver_license_image_attach'],
                'email' => $data['email'],
                'qq' => $data['qq'],
                'wechar' => $data['wechar'],
                'customer_status' => $data['customer_status']
            );
            $out_data = $this->customer_model->addCustomer($in_customer_data);
            if ($out_data['code'] == 0) {
                $this->success('保存成功');
            } else {
                $this->error($out_data['info']);
            }
        }
    }

    /**
     * 编辑用户
     * @param $id
     * @return mixed
     */
    public function edit($id)
    {
        $customer_data = $this->customer_model->getCustomer('', $id, true);
        if ($customer_data['code'] == 0) {
            $status_list = CustomerStatus::$CUSTOMERSTATUS_CODE;
            return $this->fetch('edit', ['customer' => $customer_data['data'], 'status_list' => $status_list, 'address_config' => json_encode($customer_data['address_config'])]);
        } else {
            $this->error($customer_data['info']);
        }
    }

    /**
     * 更新用户
     * @param $id
     */
    public function update($id)
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $customer_data = array(
                'customer_id' => $id,
                'mobile_phone' => $data['mobile_phone'],
                'customer_name' => $data['customer_name'],
                'customer_nickname' => $data['customer_nickname'],
                'customer_sex' => $data['customer_sex'],
                'province_id' => $data['province_id'],
                'city_id' => $data['city_id'],
                'area_id' => $data['area_id'],
                'street_id' => $data['street_id'],
                'address' => $data['address'],
                'id_number' => $data['id_number'],
                'id_number_image_front' => $data['id_number_image_front'],
                'id_number_image_reverse' => $data['id_number_image_reverse'],
                'driver_license' => $data['driver_license'],
                'driver_license_image_front' => $data['driver_license_image_front'],
                'driver_license_image_attach' => $data['driver_license_image_attach'],
                'email' => $data['email'],
                'qq' => $data['qq'],
                'wechar' => $data['wechar'],
                'customer_status' => $data['customer_status'],
                'customer_remark' => $data['customer_remark']
            );
            $out_data = $this->customer_model->updateCustomer($customer_data);
            if ($out_data['code'] === 0) {
                $this->success('更新成功');
            } else {
                $this->error($out_data['info']);
            }
        }
    }

    /**
     * 锁定用户
     * @param $id
     */
    public function lock($id)
    {
        if ($this->customer_model->addLockCustomer($id)) {
            $this->success('锁定成功');
        } else {
            $this->error('锁定失败');
        }
    }

    /**
     * 解除锁定用户
     * @param $id
     */
    public function del_lock($id)
    {
        if ($this->customer_model->delLockCustomer($id)) {
            $this->success('解除锁定成功');
        } else {
            $this->error('解除锁定失败');
        }
    }

    public function mod_balance($id)
    {
        if (empty($id)) {
            $this->error('参数有误');
        }
        $customer_data = $this->customer_model->getCustomerField($id, 'id,mobile_phone,customer_name,wechar_nickname,customer_balance');
        return $this->fetch('mod_balance', ['customer_data' => $customer_data['data']]);
    }

    /**
     * 更改用户余额
     * @param $id
     * @param $balance
     * @param string $mod
     */
    public function mod_balance_save($id, $balance, $mod = 'add')
    {
        $admin_name = $this->manage_info['manage_name'];
        $admin_id = $this->manage_info['manage_id'];
        if ($mod == 'add') {
            $balance = array(
                'balance' => floatval($balance),// 单位 元
                'pay_sn' => "admin_" . date("YmdHis") . rand(1000, 9999),// 订单号
                'customer_id' => $id,//用户id
                'remark' => "管理员: " . $admin_name . "(" . $admin_id . ") 后台充值"//备注信息
            );
            if ($this->customer_model->addBalance($balance)) {
                $this->success("充值成功");
            }
            $this->error("充值失败");
        } else if ($mod == 'sub') {
            $balance = array(
                'balance' => floatval($balance),// 单位 元
                'pay_sn' => "admin_" . date("YmdHis") . rand(1000, 9999),// 订单号
                'customer_id' => $id,//用户id
                'remark' => "管理员: " . $admin_name . "(" . $admin_id . ") 后台扣除"//备注信息
            );
            if ($this->customer_model->subBalance($balance)) {
                $this->success("扣除成功");
            }
            $this->error("扣除失败");
        }
        $this->error("参数有误");
    }

    /**
     * 用户余额日志
     * @param string $keyword
     * @param int $page
     * @param $id
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function list_balance($keyword = '', $page = 1, $id)
    {
        if (empty($id)) {
            $this->error('参数有误');
        }
        $customer_balance_log_mode = new CustomerBalanceLog();
        $map = ['customer_id' => $id];
        if (!empty($keyword)) {
            $map['pay_sn|remark'] = ['like', "%{$keyword}%"];
        }
        $page_config = ['page' => $page, 'query' => ['keyword' => $keyword, 'id' => $id]];
        $customer_log_list = $customer_balance_log_mode->where($map)->order('id DESC')->paginate(15, false, $page_config);
        foreach ($customer_log_list as &$value) {
            $customer_balance_log_mode->formatx($value);
        }
        return $this->fetch('list_balance', ['customer_log_list' => $customer_log_list, 'id' => $id, 'keyword' => $keyword]);
    }

    /**
     * 代金券列表
     * @param string $keyword
     * @param int $page
     * @param $id
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function list_coupon($keyword = '', $page = 1, $id)
    {
        if (empty($id)) {
            $this->error('参数有误');
        }
        $customer_coupon_mode = new CustomerCoupon();
        $map = ['customer_id' => $id];
        if (!empty($keyword)) {
            $map['customer_id|coupon_sn'] = ['like', "%{$keyword}%"];
        }
        $page_config = ['page' => $page, 'query' => ['keyword' => $keyword, 'id' => $id]];
        $customer_coupon_list = $customer_coupon_mode->where($map)->order('id DESC')->paginate(15, false, $page_config);
        foreach ($customer_coupon_list as &$value) {
            $customer_coupon_mode->formatx($value);
        }
        return $this->fetch('list_coupon', ['customer_coupon_list' => $customer_coupon_list, 'id' => $id, 'keyword' => $keyword]);
    }

    public function add_coupon($id)
    {
        if (empty($id)) {
            $this->error('参数有误');
        }
        $customer_data = $this->customer_model->getCustomerField($id, 'id,mobile_phone,customer_name');
        $validity_list = [
            1 => "1个月",
            2 => "2个月",
            3 => "3个月",
            5 => "5个月",
            6 => "6个月",
            10 => "10个月",
            12 => "12个月",
        ];
//        'rebate' => 15,
//                'info' => "注册实名制完成,渠道返15代金券",
//                'explain' => "只可抵扣大于15元的费用",

        $coupon_list = array();
        for ($i = 5; $i < 50; $i += 5) {
            $coupon_list[] = $i;
        }
        for ($i = 50; $i <= 100; $i += 10) {
            $coupon_list[] = $i;
        }
        return $this->fetch('add_coupon', ['customer_data' => $customer_data['data'], 'validity_list' => $validity_list, 'coupon_list' => $coupon_list]);
    }

    /**
     * 保存代金券
     * @param $id
     * @param $coupon_type
     * @param $end_time
     */
    public function add_coupon_save($id, $coupon_type, $end_time)
    {
        $end_time = (3600 * 24 * 30 * intval($end_time));
        $channel_in_coupon = [
            'customer_id' => $id,
            'coupon_code' => "platformp",
            'coupon_type' => $coupon_type,
            'remark' => "平台人工发放," . $coupon_type . "代金券",
            'explain' => "只可抵扣大于" . $coupon_type . "元的费用",
            'end_time' => $end_time,
            'admin_id' => $this->manage_info['manage_id'],
            'admin_name' => $this->manage_info['manage_name']
        ];
        $coupon_model = new CustomerCoupon();
        if ($coupon_model->add_coupon($channel_in_coupon)) {
            $this->success("代金券添加成功");
        }
        $this->success("代金券添加失败");
    }

    /**
     * 用户推广情况
     * @param string $keyword
     * @param int $page
     * @param string $status
     * @param $id
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function channel_index($keyword = '', $page = 1, $status = '', $id)
    {
        if (empty($id)) {
            $this->error('参数有误');
        }
        $customer_data = $this->customer_model->where(['id' => $id])->find();

        $map = ['channel_uid' => $id];
        if (is_numeric($status) && intval($status) >= 0) {
            if (empty($status)) {
                $status = 0;
                $map['customer_status'] = $status;
            } else {
                $map['customer_status'] = $status;
            }
            $page_config = ['page' => $page, 'query' => ['keyword' => $keyword, 'status' => $status, 'id' => $id]];
        } else {
            $status = "-1";
            $page_config = ['page' => $page, 'query' => ['keyword' => $keyword, 'id' => $id]];
        }
        if (!empty($keyword)) {
            $map['mobile_phone|customer_name|customer_nickname|id_number'] = ['like', "%{$keyword}%"];
        }
        $customer_list = $this->customer_model->where($map)->order('id DESC')->paginate(15, false, $page_config);
        foreach ($customer_list as &$value) {
            $value['customer_status_str'] = CustomerStatus::$CUSTOMERSTATUS_CODE[intval($value['customer_status'])];
        }
        $status_list = CustomerStatus::$CUSTOMERSTATUS_CODE;
        return $this->fetch('channel_index', ['customer_list' => $customer_list, 'customer_data' => $customer_data, 'status' => $status, 'id' => $id, 'keyword' => $keyword, 'status_list' => $status_list]);
    }

    /**
     * 清除用户预约信息
     * @param $id
     */
    public function clear_reserve($id)
    {
        $reserve_model = new Reserve();
        $ret = $reserve_model->where(['customer_id' => $id])->delete();
        if ($ret) {
            $this->success("清除成功");
        } else {
            $this->success("清除失败");
        }
    }

    /**
     * 重新认证用户信息
     * @param $id
     */
    public function reset_approve($id)
    {
        if (empty($id)) {
            $this->error("参数有误");
        }
        $car_customer = $this->customer_model->where(['id' => $id])->field('id_number')->find();

        $count = $this->customer_model->where(['id_number' => $car_customer['id_number']])->count();
        if ($count > 1) {
            $this->error("身份证号码已绑定其他账号");
        }
        $out_data = $this->customer_model->checkPersonalFace($id);
        if ($out_data['code'] == 0) {
            $this->success("认证成功");
        } else {
            $this->error($out_data['info']);
        }
    }

    /**
     * 地图显示
     * @param string $start_time
     * @param string $end_time
     * @return mixed
     */
    public function map($start_time = '', $end_time = '')
    {
        if (empty($start_time)) {
            $start_time = date("Y-m-d", strtotime("-1 day"));
        }
        if (empty($end_time)) {
            $end_time = date("Y-m-d");
        }
        $map = [
            'wechar_location_lng' => ['gt', 0],
            'create_time' => ['between', $start_time . " 00:00:00," . $end_time . " 00:00:00"],
        ];
        $customer_list = $this->customer_model->where($map)->field("wechar_nickname,wechar_location_lat,wechar_location_lng")->select();
        $customer_data = [];
        foreach ($customer_list as $value) {
            $customer_data[] = [
                'name' => $value['wechar_nickname'],
                'position' => [$value['wechar_location_lng'], $value['wechar_location_lat']]
            ];
        }
        return $this->fetch('map', ['customer_data' => json_encode($customer_data), 'start_time' => $start_time, 'end_time' => $end_time]);
    }
}