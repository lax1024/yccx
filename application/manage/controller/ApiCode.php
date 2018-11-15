<?php

namespace app\manage\controller;

use app\common\model\ApiClass as ApiClassModel;
use app\common\model\ApiCode as ApiCodeModel;
use app\common\controller\AdminBase;

/**
 *  应用错误码错误码错误码管理
 * Class Category
 * @package app\manage\controller
 */
class ApiCode extends AdminBase
{
    protected $apiclass_model;
    protected $apicode_model;

    protected function _initialize()
    {
        parent::_initialize();
        $this->apiclass_model = new ApiClassModel();
        $this->apicode_model = new ApiCodeModel();
    }

    /**
     *  应用错误码管理
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
            $map['api_code|api_code_intro|api_url'] = ['like', "%{$keyword}%"];
        }
        $page_config = ['page' => $page, 'query'=>['pid' => $pid, 'keyword' => $keyword]];
        $apicode_list = $this->apicode_model->where($map)->order(['id' => 'DESC'])->paginate(15, false, $page_config);
        return $this->fetch('index', ['apicode_list' => $apicode_list, 'apiclass_list' => $apiclass_list, 'keyword' => $keyword, 'pid' => $pid]);
    }

    /**
     * 添加 应用错误码
     * @param string $pid
     * @return mixed
     */
    public function add($pid = '')
    {
        return $this->fetch('add', ['pid' => $pid]);
    }

    /**
     * 保存 应用错误码
     */
    public function save()
    {
        if ($this->request->isPost()) {
            $data = $this->request->only(['class_id', 'api_code', 'api_code_intro', 'api_url']);
            if (empty($data['api_code'])) {
                $this->error('数据有误');
            }
            $data_temp = $this->apicode_model->where(array('api_code' => $data['api_code']))->find();
            if (!empty($data_temp)) {
                $this->error('错误码已存在');
            }
            $time = date('Y-m-d H:i:s');
            $data['update_time'] = $time;
            $data['create_time'] = $time;
            if ($this->apicode_model->save($data)) {
                $this->success('保存成功');
            } else {
                $this->error('保存失败');
            }
        }
    }

    /**
     * 编辑 应用错误码
     * @param $id
     * @return mixed
     */
    public function edit($id)
    {
        $apicode = $this->apicode_model->find($id);
        return $this->fetch('edit', ['apicode' => $apicode]);
    }

    /**
     * 更新 应用错误码
     * @param $id
     */
    public function update($id)
    {
        if ($this->request->isPost()) {
            $data = $this->request->only(['api_code_intro', 'api_url']);
            $code_data = $this->apicode_model->find($id);
            if (empty($code_data)) {
                $this->error('参数有误');
            }
            $time = date('Y-m-d H:i:s');
            $code_data->api_code_intro = $data['api_code_intro'];
            $code_data->api_url = $data['api_url'];
            $code_data->update_time = $time;
            if ($code_data->save() !== false) {
                $this->success('更新成功');
            } else {
                $this->error('更新失败');
            }
        }
    }

    /**
     * 删除 应用错误码
     * @param $id
     */
    public function delete($id)
    {
        if ($this->apicode_model->destroy($id)) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }
}