<?php

namespace app\api\controller;

use think\Controller;
use think\Session;

/**
 * 通用上传接口
 * Class Upload
 * @package app\api\controller
 */
class Upload extends Controller
{
    private $config = array();

    protected function _initialize()
    {
        parent::_initialize();
        if (!Session::has('manage_id') && !Session::has('seller_id')) {
            $result = [
                'error' => 1,
                'message' => '未登录'
            ];
            return json($result);
        }
        $this->config = [
            'size' => 20971520,
            'ext' => 'jpg,gif,png,bmp',
            'oss_exts' => array(
                'image/jpg', 'image/jpeg', 'image/gif', 'image/png', 'image/bmp'
            ),
        ];
    }

    /**
     * 通用图片上传接口
     * @return \think\response\Json
     */
    public function upload()
    {
        $type = $this->request->get('type');
        $aliyun_oss = config('aliyun_oss');
        //oss上传
        $web = $aliyun_oss['weburl'];
        $bucketName = $aliyun_oss['Bucket'];
        $path = $aliyun_oss['Path'];
        //图片
        if (!empty($type)) {
//            $file = $this->request->file('file');
            $fFiles = $_FILES['file'];
            $save_path = '/' . $type . '/';
            $dataout = oss_up_pic($fFiles, $path, $save_path, $bucketName, $web, false);
//            $info = $file->validate($this->config)->move($upload_path);
//            if ($dataout) {
//                $result = [
//                    'error' => 0,
//                    'url' => str_replace('\\', '/', $save_path . $info->getSaveName())
//                ];
//            } else {
//                $result = [
//                    'error' => 1,
//                    'message' => $file->getError()
//                ];
//            }
            return json($dataout);
        }

//        $file = $this->request->file('file');
        $save_path = '/upload/';
        $fFiles = $_FILES['file'];
        $dataout = oss_up_pic($fFiles, $path, $save_path, $bucketName, $web, false);
//        if ($info) {
//            $result = [
//                'error' => 0,
//                'url' => str_replace('\\', '/', $save_path . $info->getSaveName())
//            ];
//        } else {
//            $result = [
//                'error' => 1,
//                'message' => $file->getError()
//            ];
//        }
        return json($dataout);
    }

    /**
     * 文件片上传接口
     * @return \think\response\Json
     */
    public function uploadfile()
    {
        $type = $this->request->get('type');
        $aliyun_oss = config('aliyun_oss');
        //oss上传
        $web = $aliyun_oss['weburl'];
        $bucketName = $aliyun_oss['Bucket'];
        $path = $aliyun_oss['Path'];
        //图片
        if (!empty($type)) {
//            $file = $this->request->file('file');
            $fFiles = $_FILES['file'];
            $save_path = '/api/' . $type . '/';
            $dataout = oss_up_pic($fFiles, $path, $save_path, $bucketName, $web, false);
            return json($dataout);
        }

//        $file = $this->request->file('file');
        $save_path = '/upload/';
        $fFiles = $_FILES['file'];
        $dataout = oss_up_pic($fFiles, $path, $save_path, $bucketName, $web, false);
//        if ($info) {
//            $result = [
//                'error' => 0,
//                'url' => str_replace('\\', '/', $save_path . $info->getSaveName())
//            ];
//        } else {
//            $result = [
//                'error' => 1,
//                'message' => $file->getError()
//            ];
//        }
        return json($dataout);
    }

    /**
     * Oss 上传图片
     */
    public function ossupload()
    {
        $aliyun_oss = config('aliyun_oss');
        //oss上传
        $web = $aliyun_oss['weburl'];
        $bucketName = $aliyun_oss['Bucket'];
        $path = $aliyun_oss['Path'];
        //图片
        $fFiles = $_FILES['name'];
        $dataout = oss_up_pic($fFiles, $path, '/upload/', $bucketName, $web, false);
        exit(json_encode($dataout));
    }

}