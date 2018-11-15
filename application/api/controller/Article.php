<?php

namespace app\api\controller;

use app\common\controller\HomeBase;
use app\common\model\Article as ArticleModel;

/**
 * 文章列表
 * Class Ueditor
 * @package app\api\controller
 */
class Article extends HomeBase
{
    protected $article_model;

    protected function _initialize()
    {
        parent::_initialize();
        $this->article_model = new ArticleModel();
    }

    //获取列表
    public function getlist($cid, $page)
    {
        $map = array('cid' => $cid);
        if (empty($page)) {
            $page = 1;
        }
        $article_list = $this->article_model->where($map)->order('sort DESC')->paginate(8, false, ['page' => $page]);

        $dataout = array(
            'code' => 1,
            'info' => '没有数据'
        );
        $jsonstr = json_encode($article_list);
        $data = json_decode($jsonstr);
        if (empty($data->data)) {
            exit(json_encode($dataout));
        } else {
            $dataout['code'] = 0;
            $dataout['info'] = "获取成功";
            $dataout['data'] = $data->data;
            $dataout['total'] = $data->total;
            $dataout['per_page'] = $data->per_page;
            $dataout['current_page'] = $data->current_page;
            exit(json_encode($dataout));
        }
    }

    //文章点赞
    public function praise($id = '')
    {
        $dataout = array(
            'code' => 1,
            'info' => '参数有误',
        );
        if (empty($id)) {
            exit(json_encode($dataout));
        }
        $uid = $this->user_info['user_id'];
        $art = $this->article_model->find($id);
        $praise_uid = unserialize($art['praise_uid']);
        if (is_array($praise_uid)) {
            if (in_array($uid, $praise_uid)) {
                del_arr_val($praise_uid, $uid);
                array_filter($praise_uid);
                $this->article_model->where(array('id' => $id))->setField('praise_uid', serialize($praise_uid));
                if (intval($art['praise']) > 0) {
                    $this->article_model->where(array('id' => $id))->setDec('praise', 1);
                }
                $dataout['code'] = 2;
                $dataout['info'] = '取消点赞';
                exit(json_encode($dataout));
            } else {
                $praise_uid[] = $uid;
                $this->article_model->where(array('id' => $id))->setField('praise_uid', serialize($praise_uid));
                $this->article_model->where(array('id' => $id))->setInc('praise', 1);
                $dataout['code'] = 0;
                $dataout['info'] = '点赞成功';
                exit(json_encode($dataout));
            }
        } else {
            $praise_uid[] = $uid;
            $this->article_model->where(array('id' => $id))->setField('praise_uid', serialize($praise_uid));
            $this->article_model->where(array('id' => $id))->setInc('praise', 1);
            $dataout['code'] = 0;
            $dataout['info'] = '点赞成功';
            exit(json_encode($dataout));
        }
    }
}