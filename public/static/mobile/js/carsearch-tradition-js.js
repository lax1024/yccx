$(document).ready(function () {
    //获取汽车和门店列表模型
    var carlist_item = $("#vehiclelist .vehCard").clone(true);
    carlist_item = carlist_item.show();
    var acquire_time = localStorage.getItem("acquire_time");
    var return_time = localStorage.getItem("return_time");
    var all_day = getDiffTime(acquire_time, return_time);
    if (acquire_time == null) {
        acquire_time = getNowFormatDate(0);
        return_time = getNowFormatDate(2);
        localStorage.setItem("acquire_time", acquire_time);
        localStorage.setItem("return_time", return_time);
    }
    $("#ptime").html("取 " + transformMD(acquire_time));
    $("#rtime").html("还 " + transformMD(return_time));

    //保存车型和门店信息的变量
    var json_data = new Object();
    //向界面添加默认车型和门店信息列表
    getCarList("day_price", 1);

    //获取左上角的返回按钮并加以返回功能
    var goback = document.getElementById("goback");
    goback.onclick = function () {
        // location.href = "index.html";
        window.history.back();
    };

    //界面上靠左的“车型选择”列表的选择功能
    $("#listCon-nav").on("click", function (ev) {
        var sortType = $("#sortTabList").find(".colorVi").attr("id");
        if (!($(ev.target).hasClass("curr"))) {//非重复选择
            $(ev.target).addClass("curr");
            $(ev.target).siblings().removeClass("curr");
            var index = $(ev.target).attr("data-index");
            update_list(json_data, sortType, index);
        }
    });
    //排序方式的选择
    $("#sortTabList").on("click", function (ev) {
        var gradeIndex = $("#listCon-nav").find(".curr").attr("data-index");
        if ($(ev.target).is("em")) {
            $(ev.target).parent().addClass("colorVi");
            $(ev.target).parent().siblings().removeClass("colorVi");
            $(ev.target).parent().find(".sign").removeClass("hide");
            $(ev.target).parent().siblings().children(".sign").removeClass("colorVi");
            $(ev.target).parent().siblings().children(".sign").addClass("hide");
            update_list(json_data, $(ev.target).parent().attr("id"), gradeIndex);
        } else {
            $(ev.target).addClass("colorVi");
            $(ev.target).siblings().removeClass("colorVi");
            $(ev.target).find(".sign").removeClass("hide");
            $(ev.target).siblings().children(".sign").removeClass("colorVi");
            $(ev.target).siblings().children(".sign").addClass("hide");
            update_list(json_data, $(ev.target).attr("id"), gradeIndex);
        }
    });
    //筛选列表的选择
    $("#list_Filter").on("click", function (ev) {
        if ($(ev.target).is("em")) {
            $(ev.target).parent().addClass("curr");
            $(ev.target).parent().siblings().removeClass("curr");

            $(ev.target).parent().find("em").removeClass("car-hide");
            $(ev.target).parent().siblings().find("em").addClass("car-hide");
        } else {
            $(ev.target).addClass("curr");
            $(ev.target).siblings().removeClass("curr");

            $(ev.target).find("em").removeClass("car-hide");
            $(ev.target).siblings().find("em").addClass("car-hide");
        }
    });

    //获取取还车区域的控件
    var pareaText = document.getElementById("parea");
    var rareaText = document.getElementById("rarea");
    //接收取车区域
    // if(getReferrUrl() == "area_takecar.html"){
    pareaText.innerHTML = localStorage.getItem("parea");
    var rarea = localStorage.getItem("rarea");
    var is_return = localStorage.getItem('is_return');
    if (is_return == 0) {
        rareaText.innerHTML = localStorage.getItem("parea");
    } else {
        rareaText.innerHTML = localStorage.getItem("rarea");
    }
    // }
    //接收还车车区域
    // if(getReferrUrl() == "area_returncar.html"){
    // pareaText.innerHTML = localStorage.getItem("parea");
    // rareaText.innerHTML = localStorage.getItem("rarea");
    // }
    //触发区域选择界面
    $(".area-l").click(function (ev) {
        if (this.getAttribute("data-type") == 1) {//跳转至取车区域选择界面
            localStorage.setItem('back_url_area_takecar', 'carsearch_tradition');
            location.href = "area_takecar.html";
        } else if (this.getAttribute("data-type") == 2) {//跳转至还车区域选择界面
            localStorage.setItem('is_return', 1);
            localStorage.setItem('back_url_area_returncar', 'carsearch_tradition');
            location.href = "area_returncar.html";
        }
    });

    (function ($) {
        $(function () {
            // ---------【数据部分开始】---------
            var nowDate = new Date();
            // 年月日 时分
            $.dateSelector({
                evEle: '.w1',
                year: nowDate.getFullYear() + "年",
                month: (nowDate.getMonth() + 1) < 10 ? "0" + (nowDate.getMonth() + 1) + "月" : (nowDate.getMonth() + 1) + "月",
                day: nowDate.getDate() + "日",
                startYear: nowDate.getFullYear(),
                endYear: nowDate.getFullYear() + 1,
                timeBoo: true,
                //添加到控件
                afterAction: function (pTime, rTime) {
                    setTimeout(function () {
                        var acquire_time = transformDateTime(pTime) + ":00";
                        var return_time = transformDateTime(rTime) + ":00";
                        $("#ptime").html("取 " + transformMD(acquire_time));
                        $("#rtime").html("还 " + transformMD(return_time));
                        localStorage.setItem("acquire_time", acquire_time);
                        localStorage.setItem("return_time", return_time);
                        var index = $("#listCon-nav .curr").attr('data-index');
                        var ids = $(".colorVi").attr('id');
                        getCarList(ids, parseInt(index));
                    }, 400);
                },
            });
        });
    })($);

    function getCarList(sortType, gradeIndex) {
        var acquire_time = localStorage.getItem("acquire_time");
        var return_time = localStorage.getItem("return_time");
        all_day = getDiffTime(acquire_time, return_time);
        var is_return = localStorage.getItem('is_return');
        var elng_temp = '';
        var elat_temp = '';
        if (is_return == 1) {
            elng_temp = localStorage.getItem("elng");
            elat_temp = localStorage.getItem("elat");
        }
        var ponitx = gcj2bd(localStorage.getItem("slat"), localStorage.getItem("slng"));
        $.ajax({
            url: website + '/api/Car/get_carcommon_list.html',//请求地址
            type: 'POST', //请求方式
            async: true,    //或false,是否异步
            data: {//请求参数
                goods_type:"1",
                slng: ponitx[1],//取车门店经度
                slat: ponitx[0],//取车门店纬度
                elng: elng_temp,//还车门店经度
                elat: elat_temp,//还车门店纬度
                acquire_time: localStorage.getItem("acquire_time"),//取车时间
                return_time: localStorage.getItem("return_time"),//还车时间
                km:"5"
            },
            timeout: 5000,    //超时时间
            dataType: 'json',    //返回的数据格式：json/xml/html/script/jsonp/text
            beforeSend: function (xhr) {
                // console.log(xhr)
                console.log('发送前')
            },
            success: function (data) {//请求成功，做数据处理
                console.log("成功")
                /**
                 * 数据(json类型)一级遍历
                 * data是请求到的数据
                 * index“键”
                 * value是一级“值”
                 */
                if (parseInt(data.code) == 0) {
                    json_data = data.data;
                    update_list(json_data, sortType, gradeIndex);
                } else {
                    Toast(data.info, 2000);
                }
            },
            error: function (xhr, textStatus) {
                console.log('错误');
                // console.log(xhr);
                // console.log(textStatus)
            },
            complete: function () {
                console.log('结束');
            }
        });
    }

    function update_list(json_data, sortType, gradeType) {
        $("#vehiclelist .vehCard").remove();
        $.each(json_data[sortType], function (index, value) {
            if (index == gradeType) {
                add_carlist_item(value.car, "vehiclelist", carlist_item);
                var storelist_item = $("#" + value.car.brand_id + " .vehSup").clone(true);
                $("#" + value.car.brand_id + " .vehSup").remove();
                $.each(value.store, function (store_index, store_value) {
                    add_storelist_item(store_value, value.car.brand_id, storelist_item);
                })
            }

        });
    }

    /**
     * 添加单个节点数据
     * @param json_item_data
     * @param div_id
     * @param item
     */
    function add_carlist_item(json_item_data, div_id, item) {
        var item_temp = item.clone(true);
        item_temp.attr('id', json_item_data.brand_id);
        item_temp.attr('data-series_id', json_item_data.series_id);
        item_temp.attr('data-cartype_id', json_item_data.cartype_id);
        item_temp.attr('data-car_grade', json_item_data.car_grade);
        item_temp.find('img').attr("src", "http://img.youchedongli.cn/public/" + json_item_data.series_img + "?x-oss-process=image/resize,m_lfit,h_100,w_100");
        item_temp.find('.typ').text(json_item_data.brand_name);
        item_temp.find('.desc').text(json_item_data.series_name);
        item_temp.find('.type').text(json_item_data.cartype_name);
        item_temp.appendTo($("#" + div_id));
    }


    function add_storelist_item(json_item_data, div_id, item) {
        var item_temp = item.clone(true);
        item_temp.attr('id', json_item_data.id);
        item_temp.attr('data-store_site_id', json_item_data.store_site_id);
        item_temp.attr('data-location_longitude', json_item_data.location_longitude);
        item_temp.attr('data-location_latitude', json_item_data.location_latitude);
        item_temp.attr('data-store_key_id', json_item_data.store_key_id);
        item_temp.attr('data-acquire_store_id', json_item_data.acquire_store_id);
        item_temp.attr('data-return_store_id', json_item_data.return_store_id);
        item_temp.find('.js-day-price').text(json_item_data.day_price);
        item_temp.find('.nam').text(json_item_data.store_key_name);
        var all_price = all_day * (parseFloat(json_item_data.day_price) + parseFloat(json_item_data.day_basic)) + parseFloat(json_item_data.day_procedure);
        item_temp.find('.mgt3').text("总价：" + all_price.toFixed(2) + "元");
        item_temp.find('.sit').text("距离：" + json_item_data.distance + "公里");
        $(item_temp).on("click", function (ev) {
            var src = $(ev.target).parents("dd").find("img").attr("src");
            var brand_name = $(ev.target).parents("dd").find(".typ").text();
            var series_name = $(ev.target).parents("dd").find(".desc").text();
            var cartype_name = $(ev.target).parents("dd").find(".type").text();
            localStorage.setItem("chooce_car_imgUrl", src.substr(0, src.indexOf("?")));
            localStorage.setItem("brand_name", brand_name);
            localStorage.setItem("series_name", series_name);
            localStorage.setItem("cartype_name", cartype_name);
            localStorage.setItem("store_key_name", json_item_data.store_key_name);
            localStorage.setItem('acquire_store_id', json_item_data.acquire_store_id);
            localStorage.setItem('return_store_id', json_item_data.return_store_id);
            localStorage.setItem('car_id', json_item_data.id);
            location.href = "order_fillin_tradition.html";

        });
        $("#" + div_id).find("ul").append(item_temp);
    }

});