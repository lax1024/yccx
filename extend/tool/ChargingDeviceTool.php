<?php

namespace tool;

/**
 *充电桩设备 数据模型
 */

use definition\ChargingTypeList;
use think\Cache;
use think\cache\driver\Redis;
use think\Config;
use think\Paginator;

class ChargingDeviceTool
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
        $url = Config::get('charging_terminal_get_url') . "?page=1&keyword=" . $device_id . "&size=1";
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

    }

    /**
     * 格式化数据
     * @param $data
     */
    public function formatx(&$data)
    {
//        设备状态 1审核中 2维修中 3未绑定 4已绑定
        $pile_type = ChargingTypeList::$CHARGINGTYPELIST_CODE;
        $data['pile_type_str'] = $pile_type[intval($data['pile_type'])];
        $redis = new Redis(['select' => 1]);
        $data['online'] = 0;
        if ($redis->has($data['terminal_number'])) {
            $data['online'] = 1;
        }
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
        $url = Config::get('charging_terminal_get_url') . "?page=" . $page_config['page'] . "&keyword=" . $keyword . "&size=" . $limit . "&orderBy=" . $order;
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

    /** 充电桩开启与关闭
     * @param int $terminal_number 设备编号
     * @param int $gun_number 充电枪编号 （一个充电枪默认是0）
     * @param int $start_stop 01启动  02停止
     * @param string $mobile_phone 电话号码
     * @param int $order_id 订单流水号
     * @param int $mode 充电模式 0.自动充电 1.金额模式 2.时间模式 3.电量模式
     * @param int $param 参数
     * @param int $money 余额
     * @return mixed
     */
    public function switchCharging($terminal_number, $gun_number = 0, $start_stop = 1, $mobile_phone, $order_id = 0, $mode = 0, $param = 0, $money = 0)
    {
        if (intval($start_stop) == 1) {
            $start_stop = "01"; //启动
        }
        if (intval($start_stop) == 2) {
            $start_stop = "02"; //停止
        }
        $data_page_hex = $start_stop;//启停标志  01启动  02停止
        $data_page_hex .= str_pad(getBytes($mobile_phone, true), 40, "0", STR_PAD_RIGHT);//手机号码 ASCII 码  20个字节
        $data_page_hex .= byteConvert($money, 4);//余额
        $data_page_hex .= byteConvert($order_id, 4);//交易流水号
        $data_page_hex .= byteConvert($mode, 4);//充电模式
        $data_page_hex .= byteConvert($param, 4);//充电参数
        $data_page_hex .= byteConvert(0, 4);//保留数据
        $out_data_josn = $this->_sendCmd($terminal_number, '10', $gun_number, $data_page_hex);
        return json_decode($out_data_josn, true);
    }

    /**
     * 获取充电桩费率
     * @param $terminal_number
     * @return mixed
     */
    public function getCharging($terminal_number)
    {
        $data_page_hex = "2C000100020003000400050006000700080009000A000B000C000D000E000F0010001100120013001400150016001700180019001A001B001C001D001E001F0020002100220023002400250026002700280029002A002B002C00";
        $out_data_josn = $this->_sendCmd($terminal_number, '04', 1, $data_page_hex);
        return json_decode($out_data_josn, true);
    }

    /**
     * 设置充电桩 数据
     * @param $terminal_number
     * @param float $price
     * @return mixed
     */
    public function setCharging($terminal_number, $price = 1.60)
    {
        $data_page_hex = "1400150002DC00160002DC00170002DC00180002DC00190002DC001A0002DC001B0002DC001C0002DC001D0002DC001E0002DC001F00020000200002010021000202002200020300230002040024000206002500020D00260002130027000216002800021700";
        $price = floatval($price) * 100;
        $price = strtoupper(str_pad(dechex($price), 4, "0", STR_PAD_LEFT));
        $price = substr($price, 2, 2) . substr($price, 0, 2);
        $data_page_hex = str_replace('02DC00', "02" . $price, $data_page_hex);
        $out_data_josn = $this->_sendCmd($terminal_number, '05', 1, $data_page_hex);
        return json_decode($out_data_josn, true);
    }

    /**
     * 发送命令
     * @param $terminal_number
     * @param $command_word
     * @param int $gun_number
     * @param $data_page_hex
     * @return string
     */
    private function _sendCmd($terminal_number, $command_word, $gun_number = 0, $data_page_hex)
    {
        $limit_time = 15;
        $time_delayed = Cache::get($terminal_number . $command_word . $gun_number);
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
        $cmd_url = Config::get('charging_cmd_url');
        $message_body_length = intval(strlen($data_page_hex) / 2) + 14;
        $out_data = array(
            'length' => $message_body_length,
            'terminal_number' => $terminal_number,
            'command_word' => $command_word,
            'gun_number' => $gun_number,
            'data_page_hex' => $data_page_hex
        );
        $json_data = https_post($cmd_url, $out_data);
        $json_data = json_decode($json_data, true);
        $json_data['msg'] = $json_data['info'];
        return json_encode($json_data);
    }
}