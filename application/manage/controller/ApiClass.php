<?php

namespace app\manage\controller;

use app\common\model\ApiClass as ApiClassModel;
use app\common\controller\AdminBase;

/**
 * 应用管理
 * Class Category
 * @package app\manage\controller
 */
class ApiClass extends AdminBase
{

    protected $apiclass_model;

    protected function _initialize()
    {
        parent::_initialize();
        $this->apiclass_model = new ApiClassModel();
    }

    /**
     * 应用管理
     * @param string $keyword
     * @param int $page
     * @return mixed
     */
    public function index($keyword = '', $page = 1)
    {
        $map = array();
        if (!empty($keyword)) {
            $map['app_name'] = ['like', "%{$keyword}%"];
        }
        $page_config = ['page' => $page, 'query'=>['keyword' => $keyword]];
        $apiclass_list = $this->apiclass_model->where($map)->order(['id' => 'DESC'])->paginate(15, false, $page_config);
        return $this->fetch('index', ['apiclass_list' => $apiclass_list, 'keyword' => $keyword]);
    }

    /**
     * 添加应用
     * @param string $pid
     * @return mixed
     */
    public function add($pid = '')
    {
        return $this->fetch('add', ['pid' => $pid]);
    }

    /**
     * 保存应用
     */
    public function save()
    {
        if ($this->request->isPost()) {
            $data = $this->request->only(['app_name', 'app_short', 'content']);
            $time = date('Y-m-d H:i:s');
            $data['update_time'] = $time;
            $data['create_time'] = $time;
            if ($this->apiclass_model->save($data)) {
                $this->success('保存成功');
            } else {
                $this->error('保存失败');
            }
        }
    }

    /**
     * 编辑应用
     * @param $id
     * @return mixed
     */
    public function edit($id)
    {
        $apiclass = $this->apiclass_model->find($id);
        return $this->fetch('edit', ['apiclass' => $apiclass]);
    }

    /**
     * 更新应用
     * @param $id
     */
    public function update($id)
    {
        if ($this->request->isPost()) {
            $data = $this->request->only(['app_name', 'app_short', 'content']);
            $class_data = $this->apiclass_model->find($id);
            if (empty($class_data)) {
                $this->error('更新失败');
            }
            $time = date('Y-m-d H:i:s');
            $class_data->app_name = $data['app_name'];
            $class_data->app_short = $data['app_short'];
            $class_data->content = $data['content'];
            $class_data->update_time = $time;
            if ($class_data->save() !== false) {
                $this->success('更新成功');
            } else {
                $this->error('更新失败');
            }
        }
    }

    /**
     * 删除应用
     * @param $id
     */
    public function delete($id)
    {
        if ($this->apiclass_model->destroy($id)) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }
}