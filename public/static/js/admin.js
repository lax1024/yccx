/**
 * 后台JS主入口
 */

var layer = layui.layer,
    element = layui.element(),
    laydate = layui.laydate,
    form = layui.form();


/**
 * AJAX全局设置
 */
$.ajaxSetup({
    type: "post",
    dataType: "json"
});
//自定义验证规则
form.verify({
    phone: [/^1[3|4|5|7|8]\d{9}$/, '手机必须11位，只能是数字！']
    , price: [/(^[0-9]\d*(\.\d+)?$)|^0\.\d+$/, '必须大于等于0的数值！']
    , email: [/^[a-z0-9._%-]+@([a-z0-9-]+\.)+[a-z]{2,4}$|^1[3|4|5|7|8]\d{9}$/, '邮箱格式不对']
});
/**
 * 后台侧边菜单选中状态
 */
$('.layui-nav-item').find('a').removeClass('layui-this');
$('.layui-nav-tree').find('a[href*="' + GV.current_controller + '"]').parent().addClass('layui-this').parents('.layui-nav-item').addClass('layui-nav-itemed');

layui.upload({
    url: "/index.php/api/upload/upload",
    type: 'image',
    ext: 'jpg|png|gif|bmp',
    success: function (data) {
        if (data.error === 0) {
            document.getElementById("thumb").value = data.url;
        } else {
            layer.msg(data.message);
        }
    }
});
/**
 * 通用日期时间选择
 */
$('.datetime').on('click', function () {
    laydate({
        elem: this,
        istime: true,
        format: 'YYYY-MM-DD hh:mm:ss'
    })
});

/**
 * 通用表单提交(AJAX方式)
 */
form.on('submit(*)', function (data) {
    $.ajax({
        url: data.form.action,
        type: data.form.method,
        data: $(data.form).serialize(),
        success: function (info) {
            if (info.code === 1) {
                setTimeout(function () {
                    location.href = info.url;
                }, 1000);
            }
            layer.msg(info.msg);
        }
    });

    return false;
});

//监听指定开关
form.on('switch(switchShow)', function (data) {
    if (this.checked == true) {
        $("#ddlist").show();
    } else {
        $("#ddlist").hide();
    }
});

/**
 * 通用批量处理（审核、取消审核、删除）
 */
$('.ajax-action').on('click', function () {
    var _action = $(this).data('action');
    layer.open({
        shade: false,
        content: '确定执行此操作？',
        btn: ['确定', '取消'],
        yes: function (index) {
            $.ajax({
                url: _action,
                data: $('.ajax-form').serialize(),
                success: function (info) {
                    if (info.code === 1) {
                        setTimeout(function () {
                            location.href = info.url;
                        }, 1000);
                    }
                    layer.msg(info.msg);
                }
            });
            layer.close(index);
        }
    });
    return false;
});


/**
 * 通用全选
 */
$('.check-all').on('click', function () {
    $(this).parents('table').find('input[type="checkbox"]').prop('checked', $(this).prop('checked'));
});

/**
 * 通用ajax
 */
$('.ajax-common').on('click', function () {
    var _href = $(this).attr('href');
    var _text = $(this).text();
    layer.open({
        shade: false,
        content: '确定' + _text + '？',
        btn: ['确定', '取消'],
        yes: function (index) {
            $.ajax({
                url: _href,
                type: "get",
                success: function (info) {
                    if (info.code === 1) {
                        setTimeout(function () {
                            location.reload();
                        }, 1000);
                    }
                    if (info.msg === undefined || info.msg === '') {
                        layer.msg(info.info);
                    } else {
                        layer.msg(info.msg);
                    }
                }
            });
            layer.close(index);
        }
    });

    return false;
});

/**
 * 通用ajax
 */
$('.ajax-remove-bind').on('click', function () {
    var _href = $(this).attr('href');
    var _text = $(this).text();
    layer.open({
        shade: false,
        content: '确定' + _text + '？',
        btn: ['确定', '取消'],
        yes: function (index) {
            $.ajax({
                url: _href,
                type: "get",
                success: function (info) {
                    if (info.code === 0) {
                        setTimeout(function () {
                            location.reload();
                        }, 1000);
                    }
                    layer.msg(info.info);
                }
            });
            layer.close(index);
        }
    });

    return false;
});

/**
 * 通用删除
 */
$('.ajax-delete').on('click', function () {
    var _href = $(this).attr('href');
    layer.open({
        shade: false,
        content: '确定删除？',
        btn: ['确定', '取消'],
        yes: function (index) {
            $.ajax({
                url: _href,
                type: "get",
                success: function (info) {
                    if (info.code === 1) {
                        setTimeout(function () {
                            location.href = info.url;
                        }, 1000);
                    }
                    layer.msg(info.msg);
                }
            });
            layer.close(index);
        }
    });

    return false;
});

/**
 * 通用get请求
 */
$('.ajax-all-get').on('click', function () {
    var _href = $(this).attr('href');
    var t = $(this).text();
    layer.open({
        shade: false,
        content: '确定要' + t + '吗？',
        btn: ['确定', '取消'],
        yes: function (index) {
            $.ajax({
                url: _href,
                type: "get",
                success: function (info) {
                    if (info.code === 1) {
                        setTimeout(function () {
                            location.href = info.url;
                        }, 1000);
                    }
                    layer.msg(info.msg);
                }
            });
            layer.close(index);
        }
    });

    return false;
});


/**
 * 通用状态更改
 */
$('.ajax-status').on('click', function () {
    var _href = $(this).attr('href');
    layer.open({
        shade: false,
        content: '确定要更改状态吗？',
        btn: ['确定', '取消'],
        yes: function (index) {
            $.ajax({
                url: _href,
                type: "get",
                success: function (info) {
                    if (info.code === 1) {
                        setTimeout(function () {
                            location.href = info.url;
                        }, 1000);
                    }
                    layer.msg(info.msg);
                }
            });
            layer.close(index);
        }
    });

    return false;
});

/**
 * 通用备注更改
 */
$('.ajax-notes').on('click', function () {
    var _href = $(this).attr('href');
    layer.open({
        shade: false,
        content: "<textarea id='ajax-notes-text' placeholder='请输入备注信息' class='layui-textarea'></textarea>",
        btn: ['确定', '取消'],
        yes: function (index) {
            var text = $("#ajax-notes-text").val();
            $.ajax({
                url: _href,
                type: "POST",
                data: {
                    notes: text
                },
                dataType: 'json',
                success: function (info) {
                    if (info.code === 1) {
                        setTimeout(function () {
                            location.href = info.url;
                        }, 1000);
                    }
                    layer.msg(info.msg);
                }
            });
            layer.close(index);
        }
    });

    return false;
});
/**
 * 通用额外费用更改
 */
$('.ajax-cost').on('click', function () {
    var _href = $(this).attr('href');
    var _title = $(this).text();
    layer.open({
        title: _title,
        area: 'auto',
        maxWidth: '560',
        maxHeight: '560',
        shade: false,
        content: "<input id='ajax-cost-value' required='required' lay-verify='required' type='text' placeholder='请输入额外费用' class='layui-input'>" +
            "<textarea id='ajax-cost-notes' required='required' lay-verify='required' placeholder='请输入备注信息' class='layui-textarea'></textarea>",
        btn: ['确定', '取消'],
        yes: function (index) {
            var rests_cost = $("#ajax-cost-value").val();
            var rests_cost_notes = $("#ajax-cost-notes").val();
            $.ajax({
                url: _href,
                type: "POST",
                data: {
                    rests_cost: rests_cost,
                    rests_cost_notes: rests_cost_notes
                },
                dataType: 'json',
                success: function (info) {
                    if (info.code === 1) {
                        setTimeout(function () {
                            location.href = info.url;
                        }, 1000);
                    }
                    layer.msg(info.msg);
                }
            });
            layer.close(index);
        }
    });

    return false;
});

var node = $("#store_site_list").clone(true);
$("#store_site_list").remove();
/**
 * 车辆调度界面
 */
$('.ajax-dispatch').on('click', function () {
    var _href = $(this).attr('href');
    var store_site_id = $(this).attr('data-sid');
    layer.open({
        title: "选择调度门店",
        shade: false,
        content: node.html(),
        btn: ['确定', '取消'],
        yes: function (index) {
            var store_site_id = $("#store_site_id").val();
            $.ajax({
                url: _href,
                type: "POST",
                data: {
                    store_site_id: store_site_id
                },
                dataType: 'json',
                success: function (info) {
                    if (info.code === 0) {
                        setTimeout(function () {
                            window.location.reload();
                        }, 1000);
                    }
                    layer.msg(info.info);
                }
            });
            layer.close(index);
        }
    });
    // console.log(store_site_id);
    $("#store_site_id").val(store_site_id);
    return false;
});
/** 通用运费更改
 */
$('.ajax-fee').on('click', function () {
    var _href = $(this).attr('href');
    layer.open({
        shade: false,
        // <input type="text" name="title" required  lay-verify="required" placeholder="请输入标题" autocomplete="off" class="layui-input">
        content: "<input type='number' id='ajax-notes-text' placeholder='请输入运费' class='layui-input'>",
        btn: ['确定', '取消'],
        yes: function (index) {
            var fee = $("#ajax-notes-text").val();
            $.ajax({
                url: _href,
                type: "get",
                data: {
                    fee: fee
                },
                success: function (info) {
                    if (info.code === 1) {
                        setTimeout(function () {
                            location.href = info.url;
                        }, 1000);
                    }
                    layer.msg(info.msg);
                }
            });
            layer.close(index);
        }
    });

    return false;
});
/**
 * 通用添加
 */
$('.ajax-add').on('click', function () {
    var _href = $(this).attr('href');
    layer.open({
        shade: false,
        content: '确定添加吗？',
        btn: ['确定', '取消'],
        yes: function (index) {
            $.ajax({
                url: _href,
                type: "post",
                data: $("#ajaxdata").serialize(),
                success: function (info) {
                    if (info.code === 1) {
                        setTimeout(function () {
                            location.href = info.url;
                        }, 1000);
                    }
                    layer.msg(info.msg);
                }
            });
            layer.close(index);
        }
    });

    return false;
});
/**
 * 清除缓存
 */
$('#clear-cache').on('click', function () {
    var _url = $(this).data('url');
    if (_url !== 'undefined') {
        $.ajax({
            url: _url,
            success: function (data) {
                if (data.code === 1) {
                    setTimeout(function () {
                        location.href = location.pathname;
                    }, 1000);
                }
                layer.msg(data.msg);
            }
        });
    }

    return false;
});


$(".goods-attr-add").on('click', function () {
    var id = $(".goods-attr-select").val();
    var is_has = false;
    $('.goods-attr-item').each(function () {
        if ($(this).attr('data-attr') == id) {
            is_has = true;
        }
    });
    if (!is_has) {
        var name = $(".goods-attr-select").find("option:selected").text();
        var item = $('.goods-attr-item').eq(0).clone(true).show();
        item.attr('data-attr', id);
        item.find('.data-attr-name').text(name);
        item.find('input').attr('name', 'goods_attr[' + id + "]");
        item.find('input').attr('value', id);
        $(".goods-attr-div").append(item);
        var classtypev = '';
        $(".goods-attr-item").each(function () {
            var vtemp = $(this).attr('data-attr');
            if (vtemp != '') {
                classtypev += vtemp + ',';
            }
            if (vtemp == '') {
                classtypev += ',' + vtemp;
            }
        });
        $("#classtype").val(classtypev);
    } else {
        layer.open({
            title: '系统提醒'
            , content: '商品分类已经指派，无需重复指派'
        });
    }
});

$(".data-attr-del").on('click', function () {
    $(this).parent().remove();
    var classtypev = '';
    $(".goods-attr-item").each(function () {
        var vtemp = $(this).attr('data-attr');
        //if (vtemp != '') {
        classtypev += vtemp + ',';
        // }
    });
    $("#classtype").val(classtypev);
});

/**
 * 拾取地理坐标值
 */
function pickup_location(lng, lat) {
    var addr = $("#p2").find("input");
    var addr_stt = '';
    if (addr.length > 3) {
        addr_stt = addr.eq(0).val() + addr.eq(1).val() + addr.eq(2).val();
    }
    var address = $("#address").val();
    if (address != undefined) {
        addr_stt = addr_stt + address;
    }
    layer.open({
        type: 2,
        title: '选择地理坐标',
        shadeClose: true,
        shade: 0.8,
        area: ['600px', '90%'],
        btn: ['确定', '取消'],
        content: "/index/common/location.html?keyword=" + addr_stt + "&lng=" + $("#" + lng).text() + "&lat=" + $("#" + lat).text(),
        yes: function (index, layero) {
            var lng_num = localStorage.getItem('lng');
            var lat_num = localStorage.getItem('lat');
            $("#" + lng).text(lng_num);
            $("#" + lng + "_input").val(lng_num);
            $("#" + lat).text(lat_num);
            $("#" + lat + "_input").val(lat_num);
            localStorage.setItem('lng', '0.000000');
            localStorage.setItem('lat', '0.000000');
            layer.close(index);
        }, btn2: function (index) {
            localStorage.setItem('lng', '0.000000');
            localStorage.setItem('lat', '0.000000');
            layer.close(index);
        }
    });
}

/**
 * 拾取地理坐标值
 */
function pickup_area(store_area, lng, lat) {
    var json_str = $("#" + store_area + "_input").val();
    layer.open({
        type: 2,
        title: '选择地理坐标',
        shadeClose: true,
        shade: 0.8,
        area: ['600px', '90%'],
        btn: ['确定', '取消'],
        content: "/index/common/map_area.html?map_json=" + json_str + "&lng=" + $("#" + lng).text() + "&lat=" + $("#" + lat).text(),
        yes: function (index, layero) {
            var json_area = localStorage.getItem('store_area_path');
            json_area = json_area.replace(/"/g,"'");
            $("#" + store_area + "_input").val(json_area);
            console.log(json_area);
            localStorage.setItem('store_area_path', '');
            layer.close(index);
        }, btn2: function (index) {
            localStorage.setItem('store_area_path', '');
            layer.close(index);
        }
    });
}


/**
 * 拾取地理坐标值
 */
function open_car_record(car_id, did, stime, etime, carnum) {
    layer.open({
        type: 2 //此处以iframe举例
        , title: '监听当前车辆：' + carnum
        , area: ['490px', '450px']
        , shadeClose: true
        , shade: 0
        , maxmin: true
        , content: "/index/common/car_record/did/" + did + "/id/" + car_id + "/stime/" + stime + "/etime/" + etime
        , btn: ['关闭'] //只是为了演示
        , yes: function (index) {
            layer.close(index);
        }
        , zIndex: layer.zIndex //重点1
        , success: function (layero) {
            layer.setTop(layero); //重点2
        }
    });
}
