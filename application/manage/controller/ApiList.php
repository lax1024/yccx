<?php

namespace app\manage\controller;

use app\common\model\ApiList as ApiListModel;
use app\common\model\ApiClass as ApiClassModel;
use app\common\controller\AdminBase;

/**
 * 接口管理
 * Class Category
 * @package app\manage\controller
 */
class ApiList extends AdminBase
{

    protected $apiclass_model;
    protected $apilist_model;

    protected function _initialize()
    {
        parent::_initialize();
        $this->apilist_model = new ApiListModel();
        $this->apiclass_model = new ApiClassModel();
    }

    /**
     * 接口管理
     * @param string $pid
     * @param string $keyword
     * @param int $page
     * @return mixed
     */
    public function index($pid = '', $keyword = '', $page = 1)
    {
        $apiclass_list = $this->apiclass_model->where(array())->select();
        if (empty($apiclass_list)) {
            $this->error('请添加应用', url('manage/ApiClass/index'));
        }
        if (empty($pid)) {
            $pid = $apiclass_list[0]['id'];
        }
        $map = array(
            'class_id' => $pid
        );
        if (!empty($keyword)) {
            $map['api_name'] = ['like', "%{$keyword}%"];
        }
        $page_config = ['page' => $page, 'query'=>['pid' => $pid, 'keyword' => $keyword]];
        $apilist_list = $this->apilist_model->where($map)->order(['id' => 'DESC'])->paginate(15, false, $page_config);
        return $this->fetch('index', ['apilist_list' => $apilist_list, 'apiclass_list' => $apiclass_list, 'keyword' => $keyword, 'pid' => $pid]);
    }

    /**
     * 添加接口
     * @param string $pid
     * @return mixed
     */
    public function add($pid = '')
    {
        $appclass = $this->apiclass_model->find($pid);
        return $this->fetch('add', ['pid' => $pid, 'appclass' => $appclass]);
    }

    /**
     * 保存接口
     */
    public function save()
    {
        if ($this->request->isPost()) {
            $data = $this->request->only(['class_id', 'api_name', 'api_control', 'api_function', 'api_intro', 'api_parameter', 'api_returndata', 'api_codelist', 'api_test_url']);
            if (empty($data['class_id']) || empty($data['api_control']) || empty($data['api_function'])) {
                $this->error('参数有误');
            }
            $map = array(
                'class_id' => $data['class_id'],
                'api_control' => $data['api_control'],
                'api_function' => $data['api_function']
            );
            $temp_data = $this->apilist_model->where($map)->find();
            if (!empty($temp_data)) {
                $this->error('接口已存在');
            }
            $time = date('Y-m-d H:i:s');
            $data['update_time'] = $time;
            $data['create_time'] = $time;
            if ($this->apilist_model->save($data)) {
                $this->success('保存成功');
            } else {
                $this->error('保存失败');
            }
        }
    }

    /**
     * 编辑接口
     * @param $id
     * @return mixed
     */
    public function edit($id)
    {
        $apilist = $this->apilist_model->find($id);
        return $this->fetch('edit', ['apilist' => $apilist]);
    }

    /**
     * 更新接口
     * @param $id
     */
    public function update($id)
    {
        if ($this->request->isPost()) {
            $data = $this->request->only(['class_id', 'api_name', 'api_control', 'api_function', 'api_intro', 'api_parameter', 'api_returndata', 'api_codelist', 'api_test_url']);
            if (empty($data['class_id']) || empty($data['api_control']) || empty($data['api_function'])) {
                $this->error('参数有误');
            }
            $map = array(
                'class_id' => $data['class_id'],
                'api_control' => $data['api_control'],
                'api_function' => $data['api_function']
            );
            $temp_data = $this->apilist_model->where($map)->find();
            if (!empty($temp_data)) {
                if (intval($temp_data['id']) != intval($id)) {
                    $this->error('接口已存在');
                }
            }
            $update_data = $this->apilist_model->where(array('id' => $id))->find();
            $update_data->api_name = $data['api_name'];
            $update_data->api_control = $data['api_control'];
            $update_data->api_function = $data['api_function'];
            $update_data->api_intro = $data['api_intro'];
            $update_data->api_parameter = $data['api_parameter'];
            $update_data->api_returndata = $data['api_returndata'];
            $update_data->api_codelist = $data['api_codelist'];
            $update_data->api_test_url = $data['api_test_url'];
            if ($update_data->save() !== false) {
                $this->success('更新成功');
            } else {
                $this->error('更新失败');
            }
        }
    }

    /**
     * 删除接口
     * @param $id
     */
    public function delete($id)
    {
        if ($this->apilist_model->destroy($id)) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }
}