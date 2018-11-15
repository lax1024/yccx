<?php

namespace tool;

/**
 *车机设备 数据模型
 */

use definition\CarDeviceStatus;
use definition\TerminalCarType;
use think\Cache;
use think\cache\driver\Redis;
use think\Config;
use think\Paginator;

class CarDeviceTool
{
    /**
     * 更新设备信息
     * @param $device_data
     * 必须数据
     * id 数据id
     * 非必须数据
     * name 设备名称
     * device_number 设备编号
     * device_type 设备类型
     * device_status 设备状态
     * sim_imsi 设备SIM卡号
     * obd_versions 设备OBD版本
     * store_key_id 总店铺id
     * store_key_name 总店名称
     * @return array
     */
    public function updateDevice($device_data)
    {
        $out_data = array(
            'code' => 100,
            'info' => "参数有误"
        );
        $update_device_data = array();

        //获取设备数据id
        $id = $device_data['id'];
        if (empty($id)) {
            $out_data['code'] = 101;
            $out_data['info'] = "数据id非法";
            return $out_data;
        }
        $update_device_data['id'] = $id;
        //获取设备名称
        $name = $device_data['name'];
        if (!empty($name)) {
            $update_device_data['name'] = $name;
        }
        //获取商品id
        $goods_id = $device_data['goods_id'];
        if (!empty($goods_id)) {
            $update_device_data['goodsId'] = $goods_id;
        }
        //获取设备类型
        $device_type = $device_data['device_type'];
        if (is_numeric($device_type)) {
            $update_device_data['deviceType'] = $device_type;
        }
        //获取设备类型
        $device_status = $device_data['device_status'];
        if (is_numeric($device_status)) {
            $update_device_data['deviceStatus'] = $device_status;
        }
        //获取设备类型
        $terminal_car_type = $device_data['terminal_car_type'];
        if (is_numeric($terminal_car_type)) {
            $update_device_data['terminalCarType'] = $terminal_car_type;
        }
        //获取设备总店铺id
        $store_key_id = $device_data['store_key_id'];
        if (is_numeric($store_key_id)) {
            $update_device_data['storeKeyId'] = $store_key_id;
        }
        //获取设备总店铺名称
        $store_key_name = $device_data['store_key_name'];
        if (!empty($store_key_name)) {
            $update_device_data['storeKeyName'] = $store_key_name;
        }
        $url = Config::get('device_update_url');
//        $update_device_data = json_encode($update_device_data);
        $out_data = send_post($url, $update_device_data);
        $out_data['msg'] = $out_data['info'];
        return $out_data;
    }

    /**
     * 获取设备
     * @param $device_id
     * @return mixed
     */
    public function getDevice($device_id)
    {
        if (empty($device_id)) {
            $out_data['code'] = 100;
            $out_data['info'] = "参数有误";
            return $out_data;
        }
        $url = Config::get('device_get_url') . "?page=1&keyword=" . $device_id . "&size=1";
        $device_list = file_get_contents($url);
        $device_list = json_decode($device_list, true);
        $device_data = current($device_list['list']);
        if (empty($device_data)) {
            $out_data['code'] = 108;
            $out_data['info'] = "设备不存在";
            return $out_data;
        }
        $this->formatx($device_data);
        $out_data['code'] = 0;
        $out_data['data'] = $device_data;
        $out_data['info'] = "设备获取成功";
        return $out_data;
    }

    /**
     *获取设备日志
     * @param $device_id
     * @param $stime
     * @param $etime
     * @return mixed
     */
    public function getDeviceLog($device_id, $stime = '', $etime = '')
    {
        if (empty($stime)) {
            $stime = date("Y-m-d H:i:s", time() - 3600 * 2);
        }
        if (empty($etime)) {
            $etime = date("Y-m-d H:i:s");
        }
        $stime = strtotime($stime) * 1000;
        $etime = strtotime($etime) * 1000;
        if (empty($device_id)) {
            $out_data['code'] = 100;
            $out_data['info'] = "参数有误";
            return $out_data;
        }
        $url = Config::get('device_get_log_url') . "/" . $device_id . "?start=" . $stime . "&end=" . $etime;
        $device_list = file_get_contents($url);
        if (empty($device_list)) {
            $out_data['code'] = 100;
            $out_data['info'] = "暂无数据";
        } else {
            $device_list = json_decode($device_list, true);
            $out_data['code'] = 0;
            $out_data['data'] = $device_list;
            $out_data['info'] = "获取成功";
        }
        return $out_data;
    }

    /**
     * 格式化数据
     * @param $data
     */
    public function formatx(&$data)
    {
//        设备状态 1审核中 2维修中 3未绑定 4已绑定
        $device_status = CarDeviceStatus::$CARDEVICESTATUS_CODE;
        $data['device_status_str'] = $device_status[intval($data['deviceStatus'])];
        if (empty($data['name'])) {
            $data['name'] = "";
        }
        if (empty($data['deviceType'])) {
            $data['deviceType'] = 2;
        }
        if (empty($data['deviceStatus'])) {
            $data['deviceStatus'] = 1;
        }
        if (empty($data['storeKeyId'])) {
            $data['storeKeyId'] = 0;
        }
        if (empty($data['storeKeyName'])) {
            $data['storeKeyName'] = "";
        }
        if (empty($data['terminalCarType'])) {
            $data['carName'] = "未设定";
        } else {
            $TerminalCarType = TerminalCarType::$CARDEVICETYPE_CODE;
            $data['carName'] = $TerminalCarType[intval($data['terminalCarType'])]['name'];
        }
        $redis = new Redis();
        $data['deviceOnline'] = 0;
        if ($redis->has("login:" . $data['deviceId'])) {
            $data['deviceOnline'] = 1;
        }
        $data['createTime_str'] = date("Y-m-d H:i:s", $data['createTime'] / 1000);
        $data['updateTime_str'] = date("Y-m-d H:i:s", $data['updateTime'] / 1000);
    }

    /**
     * 获取列表
     * @param $keyword
     * @param $order
     * @param $page_config
     * @param $limit
     * @return array|mixed
     */
    public function getPageList($keyword = '', $order = 'DESC', $page_config, $limit = 8)
    {
        $url = Config::get('device_get_url') . "?page=" . $page_config['page'] . "&keyword=" . urlencode($keyword) . "&size=" . $limit . "&orderBy=" . $order;
        $device_list = file_get_contents($url);
//        $device_list = $str_json;
        $device_list_data = json_decode($device_list, true);
        foreach ($device_list_data['list'] as &$value) {
            $this->formatx($value);
        }
        $config = array_merge(Config::get('paginate'), $page_config);
        $listRows = $limit ?: $config['list_rows'];
        $class = false !== strpos($config['type'], '\\') ? $config['type'] : '\\think\\paginator\\driver\\' . ucwords($config['type']);
        $page = isset($config['page']) ? (int)$config['page'] : call_user_func([
            $class,
            'getCurrentPage',
        ], $config['var_page']);
        $page = $page < 1 ? 1 : $page;
        $config['path'] = isset($config['path']) ? $config['path'] : call_user_func([$class, 'getCurrentPath']);
        return $class::make($device_list_data['list'], $listRows, $page, $device_list_data['total'], false, $config);
    }

    /**
     * 开门
     * @param $device_ID
     * @return  array code info
     */
    public function openDoor($device_ID)
    {
        $out_data_josn = $this->_sendCmd('8600', $device_ID, 'A20F', '00');
        return json_decode($out_data_josn, true);
    }

    /**
     * 关门
     * @param $device_ID
     * @return  array code info
     */
    public function closeDoor($device_ID)
    {
        //关门
        $out_data_josn = $this->_sendCmd('8600', $device_ID, 'A200', '00');
        //关窗
        return json_decode($out_data_josn, true);
    }

    /**
     * 断油/断电/熄火
     * @param $device_ID
     * @return  array code info
     */
    public function powerFailure($device_ID)
    {
        $out_data_josn = $this->_sendCmd('8600', $device_ID, '7803', '');
        return json_decode($out_data_josn, true);
    }

    /**
     * 供油/供电/点火
     * @param $device_ID
     * @return  array code info
     */
    public function powerSupply($device_ID)
    {
        $out_data_josn = $this->_sendCmd('8600', $device_ID, '7806', '');
        return json_decode($out_data_josn, true);
    }

    /**
     * 重启设备（初始化设备）
     * @param $device_ID
     * @return  array code info
     */
    public function restartTerminal($device_ID)
    {
        $out_data_josn = $this->_sendCmd('8600', $device_ID, '7801', '');
        return json_decode($out_data_josn, true);
    }

    /**
     * 清除订单（还车）
     * @param $device_ID
     * @return  array code info
     */
    public function clearOrder($device_ID)
    {
//        //还车
//        $out_data = array(
//            'code' => 20,
//            'info' => "还车成功"
//        );
//        $out_data_josn = $this->powerFailure($device_ID);
//        if ($out_data_josn['code'] != 20) {
//            $out_data['code'] = 102;
//            $out_data['info'] = "熄火失败";
//        } else if ($out_data_josn['code'] == 10) {
//            $out_data['code'] = 10;
//            $out_data['info'] = "车机离线";
//        }
//        $out_data_josn = $this->closeDoor($device_ID);
//        if ($out_data_josn['code'] != 20) {
//            $out_data['code'] = 103;
//            $out_data['info'] = "关门失败";
//        }
//        return $out_data;
        //还车
        $out_data_josn = $this->_sendCmd('8600', $device_ID, 'A000', '00');
        $out_data = json_decode($out_data_josn, true);
        if ($out_data['code'] == 1) {
            usleep(100000);
            $redis = new Redis();
            $time_count = 0;
            while ($time_count < 20) {
                $time_count++;
                $code = $redis->get("0600:A000" . ":" . $device_ID);
                if (!empty($code)) {
                    if ($code == "00") {
                        $code = 1;
                        $info = "还车成功";
                        $out_data['code'] = $code;
                        $out_data['info'] = $info;
                        return $out_data;
                    } else {
                        $out_data['code'] = 0;
                        $out_data['info'] = "(code:" . $code . ")还车失败，请检查车内电源以及车门车窗是否关好";
                        return $out_data;
                    }
                    break;
                }
                usleep(300000);
            }
            $out_data['code'] = 0;
            $out_data['info'] = "(code:10)还车失败，请检查车内电源以及车门车窗是否关好";
            return $out_data;
        }
        return $out_data;

    }

    /**
     * 远程寻车
     * @param $device_ID
     * @return  array code info
     */
    public function findCar($device_ID)
    {
        $out_data_josn = $this->_sendCmd('8600', $device_ID, 'A101', '00');
        return json_decode($out_data_josn, true);
    }

    /**
     *删除设备
     * @param $ID
     * @return string
     */
    public function delTerminal($ID)
    {
        $url = Config::get('device_del_url');
        $update_device_data['id'] = $ID;
        $out_data = send_post($url, $update_device_data);
        $out_data['msg'] = $out_data['info'];
        return $out_data;
    }

    /**
     * 生成订单（点名车辆）
     * @param $device_ID
     * @return  array code info
     */
    public function startOrder($device_ID)
    {
        $out_data_josn = $this->_sendCmd('8600', $device_ID, 'A001', '00');
//        $out_data_josn = $this->callTerminal($device_ID);
//        return $out_data_josn;
        return json_decode($out_data_josn, true);
    }

    /**
     * 拍照
     * @param $device_ID 设备id
     * @param int $direction 拍照摄像头 1前面 2内部 3后面
     * @return mixed
     */
    public function takePhotos($device_ID, $direction = 1)
    {
        if (empty($direction)) {
            $direction = 1;
        }
        $out_data_josn = array(
            'code' => 100,
            'info' => "拍照失败"
        );
        $photos_dpi = Config::get('TakePhotosDpi');
        $dpi = $photos_dpi['content'];//分辨率 01 160&120,02 320*240 ,03 640*480
        $dpi_f_b = $photos_dpi['front_back'];//分辨率 01 160&120,02 320*240 ,03 640*480
        switch ($direction) {
            case 1:
                $out_data_josn = $this->_sendCmd('8452', $device_ID, '', '010102' . $dpi_f_b . '000001000A', false);
                break;
            case 2:
                $out_data_josn = $this->_sendCmd('8452', $device_ID, '', '010103' . $dpi . '000001000A', false);
                break;
            case 3:
                $out_data_josn = $this->_sendCmd('8452', $device_ID, '', '010104' . $dpi_f_b . '000001000A', false);
                break;
        }
        return json_decode($out_data_josn, true);
    }

    /**
     * 设备升级
     * @param $device_ID
     * @param string $updata
     * @return mixed
     */
    public function updateTerminal($device_ID, $updata = '')
    {
        //$updata = "HLT400_V2033323,120.92.20.209,21,ftp_holloo,ftp_holloo_*TP_SZ,HLT400_V203332.BIN,./,";
        $data_page = strToHex($updata);
        $out_data_josn = $this->_sendCmd('8502', $device_ID, '', $data_page, false);
        return json_decode($out_data_josn, true);
    }

    /**
     * 设置终端主动连接ip 端口port
     * @param $device_ID
     * @param string $ip
     * @param string $port
     * @return mixed
     */
    public function setTerminalIPPort($device_ID, $ip = '', $port)
    {
        $data_page = "02A310" . strToHex($ip);
        $data_page .= "A311" . strToHex($port);
        $out_data_josn = $this->_sendCmd('8400', $device_ID, '', $data_page, false);
        return json_decode($out_data_josn, true);
    }

    /**
     * 设置蓝牙名称
     * @param $device_ID
     * @param $name
     * @return mixed
     */
    public function setTerminalBluetoothName($device_ID, $name)
    {
        $name = "yccx_" . $name;
        $length = str_pad(strtoupper(dechex(strlen(strToHex($name)) / 2)), 4, STR_PAD_LEFT);
        $data_page = $length . strToHex($name);
        $out_data_josn = $this->_sendCmd('8400', $device_ID, '0000A33E', $data_page, false);
        return json_decode($out_data_josn, true);
    }

    /**
     * 设置蓝牙名称
     * @param $device_ID
     * @param int $pass
     * @return mixed
     */
    public function setTerminalBluetoothPass($device_ID, $pass = 0000)
    {
        $pass = str_pad(strtoupper(strToHex($pass . "")), 8, STR_PAD_LEFT);
        $length = str_pad(strtoupper(dechex(strlen(strToHex($pass)) / 2)), 4, STR_PAD_LEFT);
        $data_page = $length . strToHex($pass);
        $out_data_josn = $this->_sendCmd('8400', $device_ID, '0000A33D', $data_page, false);
        return json_decode($out_data_josn, true);
    }

    /**
     * 设置终端车型
     * @param $device_ID
     * @param string $carType 车型
     * @return mixed
     */
    public function setTerminalCarType($device_ID, $carType = 'FD21')
    {
        $data_page = "0002" . $carType;
        $out_data_josn = $this->_sendCmd('8400', $device_ID, '0000A332', $data_page, false);
        return json_decode($out_data_josn, true);
    }

    /**
     * 设置终端休眠时间
     * @param $device_ID
     * @param string $TimeCode 时间代码  默认3分钟
     * @return mixed
     */
    public function setTerminalDormancy($device_ID, $TimeCode = '00008CA0')
    {

        $data_page = "0004" . $TimeCode;
        $out_data_josn = $this->_sendCmd('8400', $device_ID, '0000D00D', $data_page, false);
        return json_decode($out_data_josn, true);
    }

    /**
     * 远程点名
     * @param $device_ID
     * @return mixed
     */
    public function callTerminal($device_ID)
    {
        $out_data_josn = $this->_sendCmd('8255', $device_ID, '', '0', false);
        return json_decode($out_data_josn, true);
    }

    /**
     * 发送控制命令
     * @param $functional_ID
     * @param $device_ID
     * @param $com_ID
     * @param $data_page_hex
     * @param $is_control
     * @return string 返回结果 code info
     */
    private function _sendCmd($functional_ID, $device_ID, $com_ID = '', $data_page_hex, $is_control = true)
    {
        $control_max_number = "0001";
        $control_min_number = "01";
        $limit_time = 30;
        if ($functional_ID == '8255') {
            $limit_time = 5;
        }
        $time_delayed = Cache::get($device_ID . $functional_ID . $com_ID . $data_page_hex);
        if (!empty($time_delayed)) {
            if (time() - $time_delayed < $limit_time) {
                $json_data = array(
                    'code' => 110,
                    'info' => '发送指令间隔时间太短，请稍后',
                    'msg' => '发送指令间隔时间太短，请稍后'
                );
                return json_encode($json_data);
            }
        }
        if ($is_control) {
            $control_max_number = $message_number = Cache::get($device_ID . $functional_ID . 'control_max_number');
            if (empty($control_max_number) || strtoupper($control_max_number) == "FFFF") {
                $control_max_number = "0001";
            }
            $control_min_number = $message_number = Cache::get($device_ID . $functional_ID . 'control_min_number');
            if (empty($control_min_number) || strtoupper($control_min_number) == "FF") {
                $control_min_number = "01";
            }
            if (strlen($data_page_hex) < 20) {
                $data_page_hex = str_pad($data_page_hex, 20, "0", STR_PAD_RIGHT);
            }
            $data_page_hex = $control_max_number . $control_min_number . $data_page_hex;
        }
        $message_number = $message_number = Cache::get($device_ID . $functional_ID . 'message_number');
        if (empty($message_number) || strtoupper($message_number) == "FFFF") {
            $message_number = "0001";
        }
        $cmd_url = Config::get('cmd_url');
        $message_body = $com_ID . $data_page_hex;
        if (empty($message_body)) {
            $message_body_length = 0;
        } else {
            $message_body_length = intval(strlen($message_body) / 2);
        }
        $e6leng = substr_count($message_body, 'E6');
        $e7leng = substr_count($message_body, 'E7');
        $message_body_length = $message_body_length + $e6leng + $e7leng;
        $out_data = array(
            'functional_ID' => $functional_ID,
            'message_body_length' => $message_body_length,
            'device_ID' => $device_ID,
            'message_number' => $message_number,
            'data_page_hex' => $message_body
        );
        $json_data = https_post($cmd_url, $out_data);
        $json_data = json_decode($json_data, true);
        //消息流水处理
        $message_number = strtoupper(str_pad(dechex(hexdec($message_number) + 1), 4, "0", STR_PAD_LEFT));
        Cache::set($device_ID . $functional_ID . 'message_number', $message_number);
        if ($is_control) {
            $control_max_number = strtoupper(str_pad(dechex(hexdec($control_max_number) + 1), 4, "0", STR_PAD_LEFT));
            Cache::set($device_ID . $functional_ID . 'control_max_number', $control_max_number);
            $control_min_number = strtoupper(str_pad(dechex(hexdec($control_min_number) + 1), 2, "0", STR_PAD_LEFT));
            Cache::set($device_ID . $functional_ID . 'control_min_number', $control_min_number);
        }
        //设置命令发送时间（用于防止短时间内容多次下发指令）
        Cache::set($device_ID . $functional_ID . $com_ID . $data_page_hex, time());
//        $json_data = json_decode($json_str, true);
        $json_data['msg'] = $json_data['info'];
        return json_encode($json_data);
    }
}