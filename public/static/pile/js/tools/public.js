//从链接地址中获取所需的参数值
function getValueByParameter(url, parameter) {
    url = decodeURI(url);
    //记住参数的起始位置
    var que = url.indexOf("?");
    if (que == -1) {
        return null;
    } else {
        //截取地址中的参数部分(“?”之后的部分)
        var que = url.indexOf("?");
        url = url.substr(que + 1, url.length - 1);
        //记住所需参数的出现位置
        var pos = url.indexOf(parameter);
        if (pos == -1) {
            return null;
        } else {
            url = url.substr(pos + parameter.length + 1, url.length - 1);
            //记住所需参数的结束位置
            var next = url.indexOf("&") < 0 ? url.length : url.indexOf("&");
            return url.substr(0, next);
        }
    }
}

//获取上一个页面的地址
function getReferrUrl() {
    var url = document.referrer;
    var len = url.indexOf("?") == -1 ? (url.length - 1) : (url.indexOf("?") - url.lastIndexOf("/") - 1);
    return url.substr(url.lastIndexOf("/") + 1, len);
}

//将(xx月xx日 xx:xx)转化成(xx/xx xx:xx)
function transformTakeAndReturnTime(time) {
    var mouth = time.substr(0, 2);
    var date = time.substr(3, 2);
    var hour = time.substr(7, 2);
    var minute = time.substr(10, 2);
    return mouth + "/" + date + " " + hour + ":" + minute;
}

//将(xx/xx xx:xx)转化成(xx月xx日 xx:xx)
function transformTakeAndReturnTime2(time) {
    var mouth = time.substr(0, 2);
    var date = time.substr(3, 2);
    var hour = time.substr(6, 2);
    var minute = time.substr(9, 2);
    return mouth + "月" + date + "日 " + (hour.length > 1 ? hour : "0" + hour) + ":" + (minute.length > 1 ? minute : "0" + minute);
}

//将(xxxx年xx月xx日 xx时xx分)转化成(xxxx-xx-xx xx:xx:xx)
function transformTime(time) {
    var time = time.replace(/[年月]/g, "-").replace(/日/, '').replace(/[时分]/g, ":")
    return time + "00";
}

//将(xxxx-xx-xx xx:xx:xx)转化成(xx月xx日 xx:xx:xx)
function getMDHMS(timeData) {
    var mouth = timeData.substr(5, 2);
    var date = timeData.substr(8, 2);
    var time = timeData.substr(11, 5);

    return mouth + "月" + date + "日 " + time;
}


/**
 * 根据日期字符串获取星期几
 * @param dateString 日期字符串（如：2016-12-29），为空时为用户电脑当前日期
 * @returns {String}
 */
function getWeek(dateString) {
    var date;
    if (isNull(dateString)) {
        date = new Date();
    } else {
        var dateArray = dateString.split("-");
        date = new Date(dateArray[0], parseInt(dateArray[1] - 1), dateArray[2]);
    }
    return "星期" + "日一二三四五六".charAt(date.getDay());
}

/**
 * 是否为Null
 * @param object
 * @returns {Boolean}
 */
function isNull(object) {
    if (object == null || typeof object == "undefined") {
        return true;
    }
    return false;
}

//两个时间相差天数 兼容firefox chrome
function getDaysNumber(sDate1, sDate2) {    //sDate1和sDate2是2006-12-18格式
    var dateSpan,
        iDays;
    sDate1 = Date.parse(sDate1);
    sDate2 = Date.parse(sDate2);
    dateSpan = sDate2 - sDate1;
    dateSpan = Math.abs(dateSpan);
    iDays = Math.floor(dateSpan / (24 * 3600 * 1000));
    return iDays;
}

//时间差计算的辅助函数
Date.prototype.diff = function (date) {
    return (this.getTime() - date.getTime()) / (24 * 60 * 60 * 1000);
}

//计算两个日期之间相差xx天xx小时xx分  时间格式：xxxx-xx-xx xx:xx:xx
function getDaysHoursMinutes(start_time, end_time) {
    var sTime = Date.parse(start_time) / 1000;
    var eTime = Date.parse(end_time) / 1000;
    var seTime = eTime - sTime;
    if (seTime < 1800) {
        return "0天0小时";
    }
    var days = parseInt(seTime / (3600 * 24));
    var hour = parseFloat((seTime / 3600) % 24);
    return days + "天" + hour + "小时";
}

function getDiffTime(start_time, end_time) {
    var sTime = Date.parse(start_time) / 1000;
    var eTime = Date.parse(end_time) / 1000;
    var seTime = eTime - sTime;
    var day = seTime / 3600 / 24;
    // console.log(day + "天"+hour+"小时"+minute+"分");
    return parseFloat(day).toFixed(2);
}

function transformDate(date) {
    return date.replace(/[年月]/g, "-").replace(/日/, '');
}

function transformDateTime(date) {
    return date.replace(/[年月]/g, "-").replace(/[日分]/g, '').replace(/时/, ":");
}

function transformMD(date) {
    var mdhm = date.substr(5, 11);
    return mdhm.replace(/月/, "/").replace(/[日分]/g, '').replace(/时/, ":");
}

function transformYMDHis(date) {
    return date.replace(/[年月日时分]/g, '');
}


function getRentTime(time) {
    return time.substr(0, time.indexOf(" "));
}

function getMD(ymdHM) {
    var md = ymdHM.substr(5, 6).replace('-', '月');
    return md + "日";
}

function getWHM(ymdHM) {
    var week = getWeek(transformDate(getRentTime(ymdHM)));
    return week + " " + ymdHM.substr(11, 5).replace(':', '时') + "分";
}

//自定义弹框 (类似于Android的Toast)
function Toast(msg, duration) {
    if (duration != null) {
        duration = parseInt(duration);
    } else {
        duration = 2000;
    }
    var m = document.createElement('div');
    m.innerHTML = msg;
    m.style.cssText = "width:60%; min-width:150px; background:#000; opacity:0.5; height:auto; color:#fff; text-align:center; border-radius:5px; position:fixed; top:61.8%; left:20%; z-index:999999; font-weight:bold;font-size:16px;";
    document.body.appendChild(m);
    setTimeout(function () {
        var d = 0.5;
        m.style.webkitTransition = '-webkit-transform ' + d + 's ease-in, opacity ' + d + 's ease-in';
        m.style.opacity = '0';
        setTimeout(function () {
            document.body.removeChild(m)
        }, d * 1000);
    }, duration);
}

Date.prototype.Format = function (fmt) { // author: meizz
    var o = {
        "M+": this.getMonth() + 1, // 月份
        "d+": this.getDate(), // 日
        "h+": this.getHours(), // 小时
        "m+": this.getMinutes(), // 分
        "s+": this.getSeconds(), // 秒
        "q+": Math.floor((this.getMonth() + 3) / 3), // 季度
        "S": this.getMilliseconds() // 毫秒
    };
    if (/(y+)/.test(fmt))
        fmt = fmt.replace(RegExp.$1, (this.getFullYear() + "").substr(4 - RegExp.$1.length));
    for (var k in o)
        if (new RegExp("(" + k + ")").test(fmt)) fmt = fmt.replace(RegExp.$1, (RegExp.$1.length == 1) ? (o[k]) : (("00" + o[k]).substr(("" + o[k]).length)));
    return fmt;
}

function getNowFormatDate(day) {
    var date = new Date();
    var seperator1 = "-";
    var seperator2 = ":";
    var month = date.getMonth() + 1;
    var strDate = date.getDate() + day;
    if (month >= 1 && month <= 9) {
        month = "0" + month;
    }
    if (strDate >= 0 && strDate <= 9) {
        strDate = "0" + strDate;
    }
    var currentdate = date.getFullYear() + seperator1 + month + seperator1 + strDate
        + " " + date.getHours() + seperator2 + '30'
        + seperator2 + '00';
    return currentdate;
}

/**
 * 数据前面补充0
 * @param num 数据
 * @param n 位数
 * @returns {string}
 */
function pad(num, n) {
    return Array(n > ('' + num).length ? (n - ('' + num).length) + 1 : 0).join(0) + num;
}

/**
 * 将时间戳转换为分秒
 * @param data_time
 * @returns {*}
 */
function timeToStr(data_time) {
    if (parseInt(data_time) <= 0) {
        return "0分0秒";
    }
    var minute = parseInt((data_time) / 60);
    var second = parseInt(data_time % 60);
    return minute + "分" + second + "秒";
}

/**
 * 姓名影藏
 * @param vehicle_drivers
 * @returns {string}
 */
function nama_hide(vehicle_drivers) {
    var drivers = "";
    if (vehicle_drivers.length <= 2) {
        drivers = "*" + vehicle_drivers.substr(1, 1);
    } else {
        drivers = vehicle_drivers.substr(0, 1) + "*" + vehicle_drivers.substr(-1);
    }
    return drivers;
}

/**
 * 身份证号码影藏
 * @param idcard
 * @returns {string|*}
 */
function idcard_hide(idcard) {
    idcard = idcard.substr(0, 6) + "**********" + idcard.substr(-2);
    return idcard;
}

/**
 * 电话号码影藏
 * @param phone
 * @returns {string|*}
 */
function phone_hide(phone) {
    phone = phone.substr(0, 3) + "****" + phone.substr(-4);
    return phone;
}


$("#modal_cancel").on("click", function () {
    $("#user_message_modal").hide();
});


/**
 * 获得两个日期之间相差的天数
 * @param date1  起始日期
 * @param date2  结束日期
 * @returns {number}  返回相差天数
 */
function getDays(date1, date2, hour1, hour2) {
    var date1Str = date1.split("-");//将日期字符串分隔为数组,数组元素分别为年.月.日
    //根据年 . 月 . 日的值创建Date对象
    var date1Obj = new Date(date1Str[0], (date1Str[1] - 1), date1Str[2]);
    var date2Str = date2.split("-");
    var date2Obj = new Date(date2Str[0], (date2Str[1] - 1), date2Str[2]);
    var t1 = date1Obj.getTime();
    var t2 = date2Obj.getTime();
    var dateTime = 1000 * 60 * 60 * 24; //每一天的毫秒数
    var minusDays = Math.floor(((t2 - t1) / dateTime));//计算出两个日期的天数差
    // var days = Math.abs(minusDays);//取绝对值
    var days = minusDays;//取绝对值

    if (days < 0) {
        return -1;//开始时间在结束时间之后
    } else if (days == 0 && hour1 > hour2) {
        return -1;//开始时间在结束时间之后
    } else {
        //不足一天算一天
        hour1 < hour2 ? days = days + 1 : days;
        return days;
    }
}

/**
 * 给指定的日期时间加上指定天数  yyyy-MM-dd
 * @param start_time  需要加的时间
 * @param addNum   需要加的天数
 * @param dataType   返回的类型：str ：返回字符串，array ：键值对数组
 * @returns {*}
 */
function getLocalTime(start_time, addNum, dataType) {
    //设置给指定日期加的天数默认值：为1
    if (addNum == undefined) {
        addNum = 1;
    }
    //设置返回的数据类型：为字符串
    if (dataType == undefined) {
        dataType = "str";
    }

    var start, ms, end, y, m, d, endDate;
    start = new Date(start_time).getTime();
    ms = start + addNum * 24 * 60 * 60 * 1000;
    end = new Date(ms);
    y = end.getFullYear();
    m = (end.getMonth() + 1) < 10 ? "0" + (end.getMonth() + 1) : (end.getMonth() + 1);
    d = end.getDate() < 10 ? "0" + end.getDate() : end.getDate();
    endDate = y + "-" + m + "-" + d;
    switch (dataType) {
        case "str":
            return endDate;
        case "array":
            y = end.getFullYear();
            m = end.getMonth();
            d = end.getDate();
            return {'y': y, 'm': m, 'd': d};
        default:
            break;
    }
    return endDate;
}


/**
 * ajax请求的封装(返回json数据)
 * @param type  请求类型：POST/GET
 * @param url   请求地址
 * @param json_param   请求参数：json类型
 * @param fun_handle   数据处理函数
 */
function ajax_request_byjson(type, url, json_param, fun_handle) {//data数据可以为空
    $.ajax({
        type: type,
        url: url,
        dataType: "json",
        data: json_param,
        outTime: 5000,
        beforSend: function () {
            // 请求之前
        },
        error: function (data) {
            //请求失败
            Toast(data.info + "，错误代码：" + data.code, 2000);
        },
        success: function (data) {
            //请求成功
            fun_handle(data);
        }
    });
}


//将上一个页面进栈
function pushHistory() {
    var state = {
        title: "title",
        url: "#"
    };
    window.history.pushState(state, "title", "#");
}