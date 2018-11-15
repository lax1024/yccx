<?php

namespace app\common\controller;

use app\common\model\OperationLog;
use org\Auth;
use think\Loader;
use think\Controller;
use think\Db;
use think\Session;

/**
 * 后台公用基础控制器
 * Class AdminBase
 * @package app\common\controller
 */
class AdminBase extends Controller
{
    protected $web_info;
    protected $manage_info;

    protected function _initialize()
    {
        parent::_initialize();
        if (Session::has('manage_id')) {
            $this->manage_info = array(
                'manage_id' => Session::get('manage_id'),
                'manage_name' => Session::get('manage_name')
            );
            $this->web_info = array(
                'is_super_admin' => true,
                'is_admin' => 0,
                'store_key_id' => 153
            );
            $this->checkAuth();
            $this->getMenu();
            // 输出当前请求控制器（配合后台侧边菜单选中状态）
            $this->assign('controller', Loader::parseName($this->request->controller()));
        } else {
            $this->redirect('manage/login/index');
        }
    }

    /**
     * 权限检查
     * @return bool
     */
    protected function checkAuth()
    {
        if (!Session::has('manage_id')) {
            $this->redirect('manage/login/index');
        }
        $module = $this->request->module();
        $controller = $this->request->controller();
        $action = $this->request->action();
        // 排除权限
        $not_check = ['manage/Index/index', 'manage/AuthGroup/getjson', 'manage/Store/getlistjson', 'manage/System/clear'];
        if (!in_array($module . '/' . $controller . '/' . $action, $not_check)) {
            $auth = new Auth();
            $manage_id = Session::get('manage_id');
            $manage_name = Session::get('manage_name');
            if (!$auth->check($module . '/' . $controller . '/' . $action, $manage_id) && $manage_id != 1) {
                $this->error('没有权限', url('manage/Index/index'), [], 1);
            }
            $operation_log_model = new OperationLog();
            $parameter = $this->request->param();
            $operation_log = [
                'operation_id' => $manage_id,
                'operation_name' => $manage_name,
                'type' => 'manage',
                'remark' => "",
                'path' => $module . '/' . $controller . '/' . $action,
                'parameter' => $parameter
            ];
            $operation_log_model->addOperationLog($operation_log);
        }
    }

    /**
     * 获取侧边栏菜单
     */
    protected function getMenu()
    {
        $menu = [];
        $manage_id = Session::get('manage_id');
        $auth = new Auth();
        $auth_rule_list = Db::name('auth_rule')->where('status', 1)->order(['sort' => 'DESC'])->select();
        foreach ($auth_rule_list as $value) {
            if ($auth->check($value['name'], $manage_id) || $manage_id == 1) {
                $menu[] = $value;
            }
        }
        $menu = !empty($menu) ? array2tree($menu) : [];
        $this->assign('menu', $menu);
    }

}