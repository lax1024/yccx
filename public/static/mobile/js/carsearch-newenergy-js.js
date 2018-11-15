var carlist_item = $("#vehiclelist .vehCard").clone(true);
// carlist_item = carlist_item.show();

//保存车型和门店信息的变量
var json_data = new Object();
$(document).ready(function () {
    //获取汽车和门店列表模型
    //向界面添加默认车型和门店信息列表
    //获取左上角的返回按钮并加以返回功能
    var goback = document.getElementById("goback");
    goback.onclick = function () {
        history.go(-1);
    };

    //排序方式的选择
    $("#sortTabList").on("click", function (ev) {
        if ($(ev.target).is("em")) {
            $(ev.target).parent().addClass("colorVi");
            $(ev.target).parent().siblings().removeClass("colorVi");
            $(ev.target).parent().find(".sign").removeClass("hide");
            $(ev.target).parent().siblings().children(".sign").removeClass("colorVi");
            $(ev.target).parent().siblings().children(".sign").addClass("hide");
            update_list(json_data, $(ev.target).parent().attr("id"));
        } else {
            $(ev.target).addClass("colorVi");
            $(ev.target).siblings().removeClass("colorVi");
            $(ev.target).find(".sign").removeClass("hide");
            $(ev.target).siblings().children(".sign").removeClass("colorVi");
            $(ev.target).siblings().children(".sign").addClass("hide");
            update_list(json_data, $(ev.target).attr("id"));
        }
    });

    //触发区域选择界面
    $(".area-l").click(function (ev) {
        if (this.getAttribute("data-type") == 1) {//跳转至取车区域选择界面
            localStorage.setItem('back_url_area_takecar', 'carsearch_newenergy');
            location.href = "area_takecar.html";
        } else if (this.getAttribute("data-type") == 2) {//跳转至还车区域选择界面
            localStorage.setItem('is_return', 1);
            localStorage.setItem('back_url_area_returncar', 'carsearch_newenergy');
            location.href = "area_returncar.html";
        }
    });
});

function getCarList(sortType) {
    var is_return = localStorage.getItem('is_return');
    var elng_temp = '';
    var elat_temp = '';
    if (is_return == 1) {
        elng_temp = localStorage.getItem("elng");
        elat_temp = localStorage.getItem("elat");
    }
    $.ajax({
        url: website + '/api/Car/get_carcommon_list.html',//请求地址
        type: 'POST', //请求方式
        async: true,    //或false,是否异步
        data: {//请求参数
            goods_type: "2",
            slng: localStorage.getItem("slng"),//取车门店经度
            slat: localStorage.getItem("slat"),//取车门店纬度
            elng: elng_temp,//还车门店经度
            elat: elat_temp,//还车门店纬度
            acquire_time: localStorage.getItem("acquire_time"),//取车时间
            return_time: localStorage.getItem("return_time"),//还车时间
            km: "5"
        },
        timeout: 5000,    //超时时间
        dataType: 'json',    //返回的数据格式：json/xml/html/script/jsonp/text
        success: function (data) {//请求成功，做数据处理
            if (parseInt(data.code) == 0) {
                json_data = data.data;
                update_list(json_data, sortType);
                console.log(json_data);
            } else {
				//Toast("code"+data.code, 2000);
            }
        }
    });
}

function update_list(json_data, sortType) {
    console.log(json_data);
    console.log(sortType);
    $("#vehiclelist .vehCard").remove();
    $.each(json_data[sortType], function (inx, val) {
        add_carlist_item(val, "vehiclelist", carlist_item);
    });
}

/**
 * 添加单个节点数据
 * @param json_item_data 数据
 * @param div_id 容器id
 * @param item  节点
 */
function add_carlist_item(json_item_data, div_id, item) {
    var item_temp = item.clone(true);
    item_temp.show();
    item_temp.attr('id', json_item_data.id);
    item_temp.attr('data-series_id', json_item_data.series_id);
    item_temp.attr('data-cartype_id', json_item_data.cartype_id);
    item_temp.attr('data-store_site_id', json_item_data.store_site_id);
    item_temp.find('.js-series_img').attr("src", "http://img.youchedongli.cn/public/" + json_item_data.series_img + "?x-oss-process=image/resize,m_lfit,h_100,w_100");
    item_temp.find('.js-series_name').text(json_item_data.series_name);
    item_temp.find('.js-licence_plate').text("(" + json_item_data.licence_plate + ")");
    item_temp.find('.js-store_site_name').text(json_item_data.store_site_name);
    item_temp.find('.js-driving_mileage').text(json_item_data.driving_mileage);
    item_temp.find('.js-distance').text(json_item_data.distance);
    item_temp.find('.js-day_price').text(json_item_data.day_price);

    item_temp.find('.js-km_price').text(json_item_data.km_price);
    item_temp.on('click', function () {
        $.ajax({
            url: website + "/api/customer/add_reserve.html",
            type: "GET",
            async: true,
            data: {
                car_id: json_item_data.id
            },
            timeout: 5000,
            dataType: "json",
            success: function (data) {
                if (data.code === 0) {
                    // localStorage.setItem("goods_id", json_item_data.id);
                    localStorage.setItem("choose_store_id_take", json_item_data.store_site_id);
                    localStorage.setItem("choose_store_id_return", json_item_data.store_site_id);
                    location.href = "order_fillin_newenergy.html?goods_id=" + json_item_data.id;
                }else {
                    Toast(data.info,2000);
                }
            }
        });

    });
    item_temp.appendTo($("#" + div_id));
}