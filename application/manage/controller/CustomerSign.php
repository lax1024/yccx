<?php

namespace app\manage\controller;

use app\common\controller\AdminBase;
use app\common\model\CustomerSignLog as CustomerSignLogModel;
use definition\SignType;

/**
 * 用户建站推荐管理
 * Class AdminUser
 * @package app\manage\controller
 */
class CustomerSign extends AdminBase
{
    protected $customer_sign_model;

    protected function _initialize()
    {
        parent::_initialize();
        $this->customer_sign_model = new CustomerSignLogModel();
    }

    /**
     * 用户渠道管理
     * @param string $keyword
     * @param int $page
     * @param int $sign_type
     * @return mixed
     */
    public function index($keyword = '', $page = 1, $sign_type = 1)
    {
        $map = [];
        if (!empty($keyword)) {
            $map['customer_id|address'] = ['like', "%{$keyword}%"];
        }
        if (!empty($sign_type)) {
            $map['type'] = $sign_type;
        }
        $page_config = ['page' => $page, 'query' => ['keyword' => $keyword, 'sign_type' => $sign_type]];
        $customer_sign_list = $this->customer_sign_model->getPageList($map, 'id DESC', $page_config, 15);
        $sign_type_list = SignType::$GOODSTYPE_CODE;
        return $this->fetch('index', ['customer_sign_list' => $customer_sign_list, 'keyword' => $keyword, 'sign_type_list' => $sign_type_list, 'sign_type' => $sign_type]);
    }
}