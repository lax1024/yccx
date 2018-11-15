<?php

namespace app\manage\controller;

use app\common\model\SellerSubjoin as SellerSubjoinModel;
use app\common\model\Seller as SellerModel;
use app\common\controller\AdminBase;

/**
 * 店铺审核管理
 * Class AdminUser
 * @package app\manage\controller
 */
class StoreCheck extends AdminBase
{
    protected $seller_subjoin_model;
    protected $seller_model;

    protected function _initialize()
    {
        parent::_initialize();
        $this->seller_subjoin_model = new SellerSubjoinModel();
        $this->seller_model = new SellerModel();
    }

    /**
     * 店铺管理
     * @param string $keyword
     * @param int $page
     * @return mixed
     */
    public function index($keyword = '', $page = 1)
    {
        $map = [];
        if ($keyword) {
            $map['seller_id|store_name|address|store_principal'] = ['like', "%{$keyword}%"];
        }
        $page_config = ['page' => $page, 'query' => ['keyword' => $keyword]];
        $subjoin_list = $this->seller_subjoin_model->where($map)->order('id DESC')->paginate(15, false, $page_config);
        return $this->fetch('index', ['subjoin_list' => $subjoin_list, 'keyword' => $keyword]);
    }

    /**
     * 添加店铺
     * @return mixed
     */
    public function add()
    {
        $this->error('禁止访问');
        return $this->fetch('add');
    }

    /**
     * 保存店铺信息
     */
    public function save()
    {
        $this->error('禁止访问');
        if ($this->request->isPost()) {
        }
    }

    /**
     * 编辑审核店铺信息
     * @param $id
     * @return mixed
     */
    public function edit($id)
    {
        $subjoin_data = $this->seller_subjoin_model->getSellerSubjoin($id);
        if ($subjoin_data['code'] != 0) {
            $this->error('申请信息不存在');
        }
        return $this->fetch('edit', ['subjoin' => $subjoin_data['data']]);
    }

    /**
     * 审核店铺信息
     * @param $id
     */
    public function update($id)
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $remark = $data['remark'];
            $store_status = $data['store_status'];
            $check_manage_id = $this->manage_info['manage_id'];
//            * @param $id
//            * @param $remark
//            * @param $check_manage_id
            $out_data['code'] = 1;
            $out_data['info'] = "审核失败";
            if (intval($store_status) == 1) {
                $this->seller_subjoin_model->where(array('id' => $id))->setField(['store_status' => $store_status, 'remark' => $remark, 'check_manage_id' => $check_manage_id]);
                $out_data['code'] = 0;
                $out_data['info'] = '审核完成';
            } else if (intval($store_status) == 2) {
                $out_data = $this->seller_model->checkSellerSubjoin($id, $remark, $check_manage_id);
            }
            if ($out_data['code'] === 0) {
                $this->success('审核成功');
            } else {
                $this->error($out_data['info']);
            }
        }
    }
}