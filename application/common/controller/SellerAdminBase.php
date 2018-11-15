<?php

namespace app\common\controller;

use app\common\model\OperationLog;
use think\Cache;
use think\Controller;
use think\Db;
use app\common\model\Seller as SellerModel;
use think\Loader;
use think\Session;

class SellerAdminBase extends Controller
{
    protected $seller_info;//商户信息
    protected $web_info;//全局信息
    protected $seller_model;//商户信息模型

    protected function _initialize()
    {
        parent::_initialize();
        $this->seller_model = new SellerModel();
        $this->checkAuthSeller();
        $this->getMenuSeller();
        // 输出当前请求控制器（配合后台侧边菜单选中状态）
        $this->assign('controller', Loader::parseName($this->request->controller()));
        $this->web_info = array(
            'is_super_admin' => false,
            'is_admin' => Session::get('is_admin'),
            'store_key_id' => Session::get('store_key_id'),
            'store_key_name' => Session::get('store_key_name'),
        );
        $this->seller_info = array(
            'seller_id' => Session::get('seller_id'),
            'store_key_id' => Session::get('store_key_id'),
            'store_key_name' => Session::get('store_key_name'),
            'store_id' => Session::get('store_id'),
            'seller_name' => Session::get('seller_name'),
            'is_admin' => Session::get('is_admin'),
            'seller_data' => json_decode(Session::get('seller_data'))
        );
        $this->getSystem();
    }

    /**
     * 权限检查
     * @return bool
     */
    protected function checkAuthSeller()
    {
        if (!Session::has('seller_id')) {
            $this->redirect('seller/login/index');
        } else {
            $seller_status = Session::get('seller_status');
            if (empty($seller_status)) {
                $this->redirect('index/index/subjoin');
            }
        }
        $module = $this->request->module();
        $controller = $this->request->controller();
        $action = $this->request->action();
        // 排除权限
        $not_check = ['seller/Index/index', 'seller/SellerGroup/getjson', 'seller/System/clear'];
        if (!in_array($module . '/' . $controller . '/' . $action, $not_check)) {
            $seller_id = Session::get('seller_id');
            $seller_name = Session::get('seller_name');
            $is_admin = Session::get('is_admin');
            if (!$this->checkSeller($module . '/' . $controller . '/' . $action, $seller_id, $is_admin)) {
                $this->error('没有权限', url('seller/Index/index'), [], 1);
            }
            $operation_log_model = new OperationLog();
            $parameter = $this->request->param();
            $operation_log = [
                'operation_id' => $seller_id,
                'operation_name' => $seller_name,
                'type' => 'seller',
                'remark' => "",
                'path' => $module . '/' . $controller . '/' . $action,
                'parameter' => $parameter
            ];
            $operation_log_model->addOperationLog($operation_log);
        }
    }

    /**检测商家权限
     * @param $name
     * @param $seller_id
     * @param int $is_admin
     * @return bool
     */
    private function checkSeller($name, $seller_id, $is_admin = 0)
    {
        //如果是管理员
        if (intval($is_admin) == 1) {
            $map = array(
                'is_admin' => 0
            );
            $authList[] = array();
            $rules = Db::name('auth_rule')->where($map)->field('name')->select();

            foreach ($rules as $v) {
                $namet = str_replace('manage', 'seller', strtolower($v['name']));
                $authList[] = str_replace('_', '', strtolower($namet));
            }
            $name = str_replace('_', '', strtolower($name));
            if (in_array($name, $authList)) {
                return true;
            } else {
                return false;
            }
        } else {
//            如果不是管理员
            //获取权限组
            $map_access = array(
                'seller_id' => $seller_id
            );
            $group_access = Db::name('seller_group_access')->where($map_access)->field('group_id')->find();
            if (empty($group_access)) {
                return false;
            }
            $map_rules = array(
                'id' => $group_access['group_id'],
                'all_rules' => 1
            );
            $group_rules = Db::name('seller_group')->where($map_rules)->field('rules')->find();
            if (empty($group_rules['rules'])) {
                return false;
            }
            $rules_id_arr = explode(',', $group_rules['rules']);
            $map = array(
                'is_admin' => 0
            );
            $authList[] = array();
            $rules = Db::name('auth_rule')->where($map)->field('id,name')->select();
            foreach ($rules as $v) {
                if (in_array($v['id'], $rules_id_arr)) {
                    $authList[] = str_replace('manage', 'seller', strtolower($v['name']));
                }
            }
            $name = str_replace('_', '', strtolower($name));
            if (in_array($name, $authList)) {
                return true;
            } else {
                return false;
            }
        }
    }


    /**
     * 获取侧边栏菜单
     */
    protected function getMenuSeller()
    {
        $menu = [];
        $seller_id = Session::get('seller_id');
        $is_admin = Session::get('is_admin');
        $map = array(
            'is_admin' => 0,
            'status' => 1,
        );
        $auth_rule_list = Db::name('auth_rule')->where($map)->order(['sort' => 'DESC'])->select();
        if (intval($is_admin) == 1) {
            foreach ($auth_rule_list as $value) {
                $value['name'] = str_replace('manage', 'seller', $value['name']);
                $menu[] = $value;
            }
        } else {
            //获取权限组
            $map_access = array(
                'seller_id' => $seller_id
            );
            $group_access = Db::name('seller_group_access')->where($map_access)->field('group_id')->find();
            if (empty($group_access)) {
                return false;
            }
            $map_rules = array(
                'id' => $group_access['group_id'],
                'status' => 1,
                'all_rules' => 1
            );
            $group_rules = Db::name('seller_group')->where($map_rules)->field('rules')->find();
            if (empty($group_rules['rules'])) {
                return false;
            }
            $rules_id_arr = explode(',', $group_rules['rules']);
            foreach ($auth_rule_list as $v) {
                if (in_array($v['id'], $rules_id_arr)) {
                    $v['name'] = str_replace('manage', 'seller', $v['name']);
                    $menu[] = $v;
                }
            }
        }
        $menu = !empty($menu) ? array2tree($menu) : [];
        $this->assign('menu', $menu);
    }

    /**
     * 获取站点信息
     */
    protected function getSystem()
    {
        //获取渠道信息
        $channel_uid = input('channel_uid');
        if (is_numeric($channel_uid)) {
            //将渠道信息保存到Session
            Session::set('channel_uid', $channel_uid);
        }
        if (Cache::has('site_config')) {
            $site_config = Cache::get('site_config');
        } else {
            $site_config = Db::name('system')->field('value')->where('name', 'site_config')->find();
            $site_config = unserialize($site_config['value']);
            Cache::set('site_config', $site_config);
        }
        $this->assign($site_config);
    }

}