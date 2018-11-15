<?php

namespace app\manage\controller;

use app\common\controller\AdminBase;
use app\common\model\Activity as ActivityModel;

/**
 * 幸运果道具分类管理
 * Class PropType
 * @package app\manage\controller
 */
class Activity extends AdminBase
{
    protected $activity_model;

    protected function _initialize()
    {
        parent::_initialize();
        $this->activity_model = new ActivityModel();
    }

    /**
     * 道具分类管理
     * @param string $keyword
     * @return mixed
     */
    public function index($keyword = '')
    {
        $map = [];
        if ($keyword) {
            $map['name'] = ['like', "%{$keyword}%"];
        }
        $class_list = $this->activity_model->where($map)->order('id DESC')->select();
        $class_list = array2leveltree($class_list);
        foreach ($class_list as &$v) {
            $this->activity_model->format($v);
        }
        return $this->fetch('index', ['class_list' => $class_list, 'keyword' => $keyword]);
    }

    /**
     * 添加道具分类
     * @return mixed
     */
    public function add($id = '')
    {
        $parent_list = $this->activity_model->getParent();
        return $this->fetch('add', ['parent_list' => $parent_list, 'parent' => array('id' => $id)]);
    }

    /**
     * 保存道具分类
     */
    public function save()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $validate_result = empty($data['name']) ? "请输入分类名称" : true;
            if ($validate_result !== true) {
                $this->error($validate_result);
            } else {
                if (!empty($data['pid'])) {
                    $temp_gc = $this->activity_model->find($data['pid']);
                    $data['pname'] = $temp_gc['name'];
                } else {
                    unset($data['pid']);
                }
                $data['orders'] = empty($data['orders']) ? 0 : $data['orders'];
                $data['start_time'] = strtotime($data['start_time']);
                $data['end_time'] = strtotime($data['end_time']);
                if ($this->activity_model->save($data)) {
                    $this->success('保存成功');
                } else {
                    $this->error('保存失败');
                }
            }
        }
    }

    /**
     * 编辑道具分类
     * @param $id
     * @return mixed
     */
    public function edit($id)
    {
        $class = $this->activity_model->find($id);
        $this->activity_model->format($class);
        $parent_list = $this->activity_model->getParent();
        return $this->fetch('edit', ['parent_list' => $parent_list, 'class' => $class]);
    }

    /**
     * 更新道具分类
     * @param $id
     */
    public function update($id)
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $validate_result = empty($data['name']) ? "请输入分类名称" : true;
            if ($validate_result !== true) {
                $this->error($validate_result);
            } else {
                $goodsclass = $this->activity_model->find($id);
                $goodsclass->name = $data['name'];
                $goodsclass->img = $data['img'];
                $goodsclass->start_time = $data['start_time'];
                $goodsclass->end_time = $data['end_time'];
                if (!empty($data['pid'])) {
                    $goodsclass->pid = $data['pid'];
                    $temp_gc = $this->activity_model->find($data['pid']);
                    $goodsclass->pname = $temp_gc['pname'];
                } else {
                    $goodsclass->pid = 0;
                    $goodsclass->pname = '';
                }
                $goodsclass->orders = empty($data['orders']) ? 0 : $data['orders'];
                if ($goodsclass->save() !== false) {
                    $this->success('更新成功');
                } else {
                    $this->error('更新失败');
                }
            }
        }
    }

    /**
     * 删除道具分类
     * @param $id
     */
    public function delete($id)
    {
        if ($this->activity_model->destroy($id)) {
            $this->activity_model->destroy(array('pid' => $id));
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }
}