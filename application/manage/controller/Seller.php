<?php

namespace app\manage\controller;

use app\common\model\Seller as SellerModel;
use app\common\model\SellerGroup as SellerGroupModel;
use app\common\model\Store as StoreModel;
use app\common\controller\AdminBase;

/**
 * 商户管理
 * Class AdminUser
 * @package app\manage\controller
 */
class Seller extends AdminBase
{
    protected $seller_model;
    protected $sellergroup_model;
    protected $store_model;

    protected function _initialize()
    {
        parent::_initialize();
        $this->seller_model = new SellerModel();
        $this->store_model = new StoreModel();
        $this->sellergroup_model = new SellerGroupModel();
    }

    /**
     * 用户管理
     * @param string $keyword
     * @param int $page
     * @return mixed
     */
    public function index($keyword = '', $page = 1)
    {
        $map = [];
        if ($keyword) {
            $map['seller_name|seller_mobile'] = ['like', "%{$keyword}%"];
        }
        if ($this->web_info['is_super_admin']) {
            $map['parent_id'] = 0;
        } else {
            $map['parent_id'] = array('neq', 0);
            $map['store_key_id'] = $this->web_info['store_key_id'];
        }
        $page_config = ['page' => $page, 'query' => ['keyword' => $keyword]];
        $seller_list = $this->seller_model->where($map)->order('id DESC')->paginate(15, false, $page_config);
        return $this->fetch('index', ['seller_list' => $seller_list, 'keyword' => $keyword]);
    }

    /**
     * 添加用户
     * @return mixed
     */
    public function add()
    {
        $map['store_key_id'] = $this->web_info['store_key_id'];
        $selle_group_list = $this->sellergroup_model->getList($map);
        $store_site_list = $this->store_model->getChildList($map);
        return $this->fetch('add', ['seller_group_list' => $selle_group_list, 'store_site_list' => $store_site_list]);
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
            $in_seller_data = array(
                'seller_name' => $data['seller_name'],
                'seller_login_password' => $data['login_password'],
                'seller_mobile' => $data['seller_mobile'],
                'seller_group_id' => $data['seller_group_id'],//后台添加
                'parent_id' => $this->web_info['store_key_id'],
                'store_key_id' => $this->web_info['store_key_id'],
                'store_id' => $data['store_id'],
                'is_admin' => 0,
                'seller_status' => $data['seller_status']
            );
            $out_data = $this->seller_model->addSeller($in_seller_data);
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
        $map['store_key_id'] = $this->web_info['store_key_id'];
        $selle_group_list = $this->sellergroup_model->getList($map);
        $store_site_list = $this->store_model->getChildList($map);
        $seller_data = $this->seller_model->getSeller($id, '', true);
        if ($seller_data['code'] == 0) {
            return $this->fetch('edit', ['seller' => $seller_data['data'], 'seller_group_list' => $selle_group_list, 'store_site_list' => $store_site_list]);
        } else {
            $this->error($seller_data['info']);
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

            $login_password = $data['login_password'];
            $confirm_password = $data['confirm_password'];
            if ($login_password !== $confirm_password) {
                $this->error("两次密码不一致");
            }
            $up_seller_data = array(
                'seller_id' => $id,
                'seller_login_password' => $data['login_password'],
                'seller_mobile' => $data['seller_mobile'],
                'seller_group_id' => $data['seller_group_id'],//后台添加
                'parent_id' => $this->web_info['store_key_id'],
                'store_key_id' => $this->web_info['store_key_id'],
                'store_id' => $data['store_id'],
                'is_admin' => 0,
                'seller_status' => $data['seller_status']
            );
            $out_data = $this->seller_model->updateSeller($up_seller_data);
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
        if ($this->seller_model->addLockSeller($id, true)) {
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
        if ($this->seller_model->delLockSeller($id, true)) {
            $this->success('解除锁定成功');
        } else {
            $this->error('解除锁定失败');
        }
    }

}