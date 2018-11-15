<?php

namespace app\api\controller;

use app\common\controller\UserBase;

/**
 * 通用上传接口
 * Class Upload
 * @package app\api\controller
 */
class Dynupload extends UserBase
{
    private $config = array();

    protected function _initialize()
    {
        parent::_initialize();
        $this->config = [
            'size' => 2097152,
            'ext' => 'jpg,gif,png,bmp',
            'oss_exts' => array(
                'image/jpg', 'image/jpeg', 'image/gif', 'image/png', 'image/bmp'
            ),
            ''
        ];
    }

    /**
     * 通用图片上传接口
     * @param $sid
     * @param string $type
     * @param string $path idnumber身份证  driverlicense驾驶证 customerfront
     * @param string $field 图片存储字段
     */
    public function upload($sid = '', $type = 'jpg', $path = 'idnumber', $field = '')
    {
        $img_url = get_img_url($sid, $type, $path);
        $dataout = array(
            'code' => 0,
            'url' => $img_url,
            'info' => '成功'
        );
        if (!empty($field)) {
            $customer_data = $this->customer_model->getCustomerField($this->customer_info['customer_id'], 'customer_status');
            if (!empty($customer_data)) {
                $customer_data = $customer_data['data'];
                if (intval($customer_data['customer_status']) == 1) {
                    $dataout['code'] = 22;
                    $dataout['info'] = "账号已审核，无法再上传图片";
                    out_json_data($dataout);
                } else if (intval($customer_data['customer_status']) == 2) {
                    $dataout['code'] = 20;
                    $dataout['info'] = "账号被锁定，无法上传图片";
                    out_json_data($dataout);
                } else if (intval($customer_data['customer_status']) == 4) {
                    $dataout['code'] = 30;
                    $dataout['info'] = "身份证信息正在审核，无法上传图片";
                    out_json_data($dataout);
                }
                $updata = array(
                    $field => $img_url
                );
                $map['id'] = $this->customer_info['customer_id'];
                $this->customer_model->updateCustomerField($map, $updata);
            }
        }
        out_json_data($dataout);
    }

    /**
     * 通用语音上传接口
     * @param $sid
     * @param string $type
     * @param string $path
     */
    public function upload_voice($sid = '', $type = 'amr', $path = 'comment')
    {
        $path = "voice/" . $path;
        $img_url = get_voice_url($sid, $type, $path);
        $dataout = array(
            'code' => 0,
            'url' => $img_url,
            'info' => '成功'
        );
        out_json_data($dataout);
    }

    /**
     * 本地图片转移到OSS
     * @param string $file public_uploads_20171204_287415455ce8a8dc4ebc977ea03dc045.jpg
     */
    public function oss_move($file = '')
    {
        $data = oss_move($file);
        $dataout = array(
            'code' => $data['error'],
            'url' => $data['url'],
            'info' => $data['message']
        );
        exit(json_encode($dataout));
    }

}