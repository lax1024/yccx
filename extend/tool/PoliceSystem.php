<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/23
 * Time: 14:59
 */

//公安系统查询对比数据
namespace tool;

class PoliceSystem
{
    private $systemIp = "111.85.191.149";//公安提供接口的IP
    private $systemPort = "8080";//公安提供接口的端口
    private $systemId = "52000057";//公安提供接口的登录id
    private $systemPassword = "302506";//公安提供接口的登录密码
    private $url = "";//请求地址
    private $parm = "";//请求参数

    function __construct()
    {
        $this->url = "http://" . $this->systemIp . $this->systemPort;
        $this->parm = "?systemId=" . $this->systemId . "&systemPassword=" . $this->systemPassword;
    }

    /**
     *平台向上级平台获取租赁企业信息
     * @param $enterpriseId
     * @return object
     *  json 数组
     *  status 是否成功，成功为true,失败为false
     *  msg  返回信息描述
     *  data [{返回数据详情}]
     */
    public function get_enterprise_info_service($enterpriseId)
    {
        $url = $this->url . "/getEnterpriseInfoService" . $this->parm . "&enterpriseId=" . $enterpriseId;
        $data_json = file_get_contents($url);
        $data = json_decode($data_json, true);
        return $data;
    }

    /**
     *平台向上级平台获取租赁车辆信息
     * @param $enterpriseId
     * @return object
     *  json 数组
     *  status 是否成功，成功为true,失败为false
     *  msg  返回信息描述
     *  data [{返回数据详情}]
     */
    public function get_vehicle_info($enterpriseId)
    {
        $url = $this->url . "/getVehicleInfo" . $this->parm . "&enterpriseId=" . $enterpriseId;
        $data_json = file_get_contents($url);
        $data = json_decode($data_json, true);
        return $data;
    }

    /**
     *平台向上级平台报送租赁企业从业人员信息
     * @param $record json 对象
     * PersonID  人员ID(主键)，R+企业ID12位+4位流水号
     * EnterpriseID 租赁企业ID
     * Name 姓名
     * Sex 性别
     * Idcardno 身份证号码
     * 非必须
     * Address  现在居住地址
     * Mobliephone 手机号码
     * Position 职务名称
     * Entrytime 入职时间
     * Leavetime 离职时间
     * Ifleave 是否离职：1：在职 0：离职
     * Remarks 备注信息
     * Picture 人员照片
     * @return object
     *  json 数组
     *  status 是否成功，成功为true,失败为false
     *  msg  返回信息描述
     */
    public function post_workers_service($record)
    {
        $post_data = array(
            'systemId' => $this->systemId,
            'systemPassword' => $this->systemPassword,
            'record' => $record,
        );
        $data_json = send_post($this->url . "/postWorkersService", $post_data);
        $data = json_decode($data_json, true);
        return $data;
    }

    /**
     * 平台向上级平台报送租赁交易信息
     * @param $record
     * @param $credential
     * @return object
     *  json 数组
     *  status 是否成功，成功为true,失败为false
     *  msg  返回信息描述
     * date 租车时间
     */
    public function post_lease_record_service($record, $credential)
    {
        $post_data = array(
            'systemId' => $this->systemId,
            'systemPassword' => $this->systemPassword,
            'record' => $record,
            'credential' => $credential
        );
        $data_json = send_post($this->url . "/postLeaseRecordService", $post_data);
        $data = json_decode($data_json, true);
        return $data;
    }

    /**
     * 平台向上级平台报送租赁交易还车信息
     * @param $record
     * @return object
     *  json 数组
     *  status 是否成功，成功为true,失败为false
     *  msg  返回信息描述
     * date 还车时间
     */
    public function post_return_record_service($record)
    {
        $post_data = array(
            'systemId' => $this->systemId,
            'systemPassword' => $this->systemPassword,
            'record' => $record,
        );
        $data_json = send_post($this->url . "/postReturnRecordService", $post_data);
        $data = json_decode($data_json, true);
        return $data;
    }


}