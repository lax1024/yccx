//返回上一个页面(左上角的“取消”按钮功能)
$("#goback").on("click", function (ev) {
    window.history.back();
});
//“驾驶员”信息填写完毕的提交(右上角的“完成”按钮功能)
$("#sure").on("click", function (ev) {
    var customer_name = $("#js_idcard_name").val();
    var id_number = $("#js_idcard_num").val();
    var id_number_time = $("#js_id_number_time").val();
    var driver_license_name = $("#js_driver_license_name").val();
    var driver_license = $("#js_driver_license").val();
    var driver_license_time = $("#js_driver_license_time").val();
    //判断“驾驶员”信息是否为空
    if (customer_name == "") {
        mui.alert("身份证姓名不能为空", "提示");
    } else if (id_number == "") {
        mui.alert("身份证号码不能为空", "提示");
    } else if (id_number_time == "") {
        mui.alert("身份证背面识别失败，请重新上传", "提示");
    } else if (driver_license_name != customer_name) {
        mui.alert("驾驶证信息与身份证信息不符，请重新确认信息", "提示");
    } else if (driver_license_time == "" || driver_license_name == "") {
        mui.alert("驾驶证主页识别失败，请重新上传", "提示");
    } else if (driver_license == "") {
        mui.alert("档案编号不能为空", "提示");
    } else {
        loadMessageBox("等待系统审核信息", 30);
        $.ajax({
            url: website + "/api/Customer/update_customer_data.html",
            type: "POST",
            async: true,
            data: {
                customer_name: customer_name,
                id_number: id_number,
                id_number_time: id_number_time,
                driver_license: driver_license,
                driver_license_time: driver_license_time
            },
            timeout: 30000,
            dataType: "json",
            success: function (data) {
                closeLoadMessageBox();
                if (data.code == 0) {
                    mui.alert("实名制认证已通过", "提示", "确定", function () {
                        window.history.back();
                    });
                } else {
                    mui.alert(data.info, "提示", "确定", function () {
                        location.reload();
                    });
                }
            }
        });
    }
});

function chack_data() {
    var customer_name = $("#js_idcard_name").val();
    var id_number = $("#js_idcard_num").val();
    var id_number_time = $("#js_id_number_time").val();
    var driver_license_name = $("#js_driver_license_name").val();
    var driver_license_time = $("#js_driver_license_time").val();
    var driver_license = $("#js_driver_license").val();
    var idcard_1 = $("#js_pic_idcard_front").attr('data-img');
    var idcard_2 = $("#js_pic_idcard_back").attr('data-img');
    var license_1 = $("#js_pic_license_front").attr('data-img');
    var license_2 = $("#js_pic_license_back").attr('data-img');
    var face = $("#js_pic_customer_front").attr('data-img');

    if (customer_name != '' && id_number != "" && idcard_1 != "") {
        $("#js_pic_idcard_front_tip").text("上传完成");
    } else {
        $("#js_pic_idcard_front_tip").text("等待上传");
    }

    if (id_number_time != '' && idcard_2 != "") {
        $("#js_pic_idcard_back_tip").text("上传完成");
    } else {
        $("#js_pic_idcard_back_tip").text("等待上传");
    }

    if (driver_license_name != '' && driver_license_time != "" && license_1 != "") {
        $("#js_pic_license_front_tip").text("上传完成");
    } else {
        $("#js_pic_license_front_tip").text("等待上传");
    }

    if (driver_license_name != '' && driver_license != "" && license_2 != "") {
        $("#js_pic_license_back_tip").text("上传完成");
        $("#js_pic_customer_front").parent().removeClass('div_gray');
    } else {
        $("#js_pic_license_back_tip").text("等待上传");
        $("#js_pic_customer_front").parent().addClass('div_gray');
    }

    if (idcard_1 != '') {
        $("#js_pic_idcard_back").parent().removeClass('div_gray');
    } else {
        $("#js_pic_idcard_back").parent().addClass('div_gray');
    }
    if (idcard_2 != '') {
        $("#js_pic_license_front").parent().removeClass('div_gray');
    } else {
        $("#js_pic_license_front").parent().addClass('div_gray');
    }
    if (license_1 != '') {
        $("#js_pic_license_back").parent().removeClass('div_gray');
    } else {
        $("#js_pic_license_back").parent().addClass('div_gray');
    }
    if (face != '') {
        $("#js_pic_customer_front_tip").text("上传完成");
    } else {
        $("#js_pic_customer_front_tip").text("等待上传");
    }
    //判断“驾驶员”信息是否为空
    if (customer_name != "" && id_number != "" && id_number != "" && id_number_time != ""
        && driver_license != "" && driver_license_name != "" && driver_license_time != "" && idcard_1 != "" && idcard_2 != ""
        && license_1 != "" && license_2 != "" && face != "") {
        $("#sure").removeAttr('disabled');
        $("#sure").addClass('mui-btn-success');
    } else {
        $("#sure").attr('disabled', 'disabled');
        $("#sure").removeClass('mui-btn-success');
    }
}

function submit_driver_pic(viewId, localIds, type, path, field) {
    $.ajax({
        url: website + "/api/Dynupload/upload.html",
        type: "POST",
        async: true,
        data: {
            sid: localIds,
            type: type,
            path: path,
            field: field
        },
        timeout: 5000,
        dataType: "json",
        success: function (data) {
            if (data.code == 0) {
                if (type == "customerfront") {
                    $("#div_sure").show();
                }
                $("#" + viewId).attr('src', "http://img.youchedongli.cn" + data.url);
                $("#" + viewId).attr('data-img', data.url);
                if (field === 'customer_front') {
                    setTimeout(function () {
                        chack_data();
                    }, 2000);
                }
                chack_data();
            } else {
                mui.alert(data.info, "提示");
            }
        }
    });
}

function distinguish_idcard_by_pic(viewId, serverId) {
    var type = 1;
    if (viewId == "js_pic_idcard_front") {
        type = 1;
    } else if (viewId == "js_pic_idcard_back") {
        type = 2;
    }
    $.ajax({
        url: website + "/api/Customer/idcard_ocr.html",
        type: "GET",
        data: {
            serverId: serverId,
            type: type
        },
        timeout: 30000,
        success: function (data) {
            var code = data.code;
            if (parseInt(code) == 0) {
                var data = data.data;
                if (viewId == "js_pic_idcard_front") {
                    var idcard_name = data.name;
                    var idcard_num = data.idcard;
                    if (idcard_name == "" || idcard_num == "" || idcard_name == undefined || idcard_num == undefined) {
                        mui.alert("证件信息识别有误，请重新拍摄", "提示");
                        return;
                    }
                    $("#js_idcard_name").val(idcard_name);
                    $("#js_idcard_num").val(idcard_num);

                    $("#" + viewId).attr('src', "http://img.youchedongli.cn" + data.url);
                    $("#" + viewId).attr('data-img', data.url);
                    // submit_driver_pic(viewId, serverId, "jpg", "idnumber", "id_number_image_front");
                } else if (viewId == "js_pic_idcard_back") {
                    var timelimit = data.timelimit;
                    $("#js_id_number_time").val(timelimit);
                    $("#" + viewId).attr('src', "http://img.youchedongli.cn" + data.url);
                    $("#" + viewId).attr('data-img', data.url);
                    // submit_driver_pic(viewId, serverId, "jpg", "idnumber", "id_number_image_reverse");
                }
                chack_data();
            } else {
                mui.alert(data.info, "提示");
            }
        },
        error: function () {
            mui.alert("提交失败", "提示");
        }
    });
}

function distinguish_license_by_pic(viewId, serverId) {
    var type = 1;
    if (viewId == "js_pic_license_front") {
        type = 1;
    } else if (viewId == "js_pic_license_back") {
        type = 2;
    }
    $.ajax({
        url: website + "/api/Customer/driving_ocr.html",
        type: "GET",
        data: {
            serverId: serverId,
            type: type
        },
        timeout: 30000,
        dataType: "json",
        success: function (data) {
            if (parseInt(data.code) == 0) {
                var data = data.data;
                if (viewId == "js_pic_license_front") {
                    var begin_date = data.begin_date;
                    var end_date = data.end_date;
                    var driver_license_name = data.name;
                    var driver_license_number = data.idcard;
                    var driver_license_time = midline2point(begin_date) + "-" + midline2point(end_date);
                    $("#js_driver_license_name").val(driver_license_name);
                    $("#js_driver_license_number").val(driver_license_number);
                    $("#js_driver_license_time").val(driver_license_time);
                    $("#" + viewId).attr('src', "http://img.youchedongli.cn" + data.url);
                    $("#" + viewId).attr('data-img', data.url);
                } else if (viewId == "js_pic_license_back") {
                    var file_number = data.file_number;//档案编号
                    var driver_license_name = data.name;
                    var driver_license_number = data.idcard;
                    if (driver_license_name != $("#js_driver_license_name").val()) {
                        mui.alert(driver_license_name + "驾驶证副页信息不一致，请重拍" + $("#js_driver_license_name").val(), "提示");
                        return;
                    }
                    $("#js_driver_license").val(file_number);
                    $("#js_driver_license_name").val(driver_license_name);
                    $("#js_driver_license_number").val(driver_license_number);
                    $("#" + viewId).attr('src', "http://img.youchedongli.cn" + data.url);
                    $("#" + viewId).attr('data-img', data.url);
                }
                chack_data();
            } else {
                mui.alert(data.info + ",请重新上传", "提示");
            }
        },
        error: function () {
            mui.alert("提交失败", "提示");
        }
    });
}

chack_data();

function midline2point(time) {
    return time.replace(/-/g, '.');
}