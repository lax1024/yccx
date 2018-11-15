<?php

namespace app\seller\controller;

use app\common\controller\SellerAdminBase;
use app\common\model\Customer as CustomerModel;
use app\common\model\CustomerBalanceLog;
use definition\CustomerStatus;
use definition\RegistType;

/**
 * 客户管理
 * Class AdminUser
 * @package app\manage\controller
 */
class Customer extends SellerAdminBase
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
     * @param int $page
     * @param string $status
     * @return mixed
     */
    public function index($keyword = '', $status = '', $page = 1)
    {
        $map = [];
        if ($keyword) {
            $map['mobile_phone|customer_name|customer_nickname|id_number'] = ['like', "%{$keyword}%"];
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
        $customer_list = $this->customer_model->where($map)->order('id DESC')->paginate(15, false, $page_config);
        foreach ($customer_list as &$value) {
            $this->customer_model->formatx($value);
        }
        $status_list = CustomerStatus::$CUSTOMERSTATUS_CODE;
        return $this->fetch('index', ['customer_list' => $customer_list, 'status_list' => $status_list, 'keyword' => $keyword, 'status' => $status]);
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
}