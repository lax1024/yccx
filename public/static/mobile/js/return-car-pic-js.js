var order_id;
var sn;
$(document).ready(function () {

    $("#js_user_back").on('click', function () {
        history.go(-1);
    });

    var pic_error = ["左前", "右前", "后方", "内部"];
    var pic_url = new Array();

    var use_car_pic = $(".js-use-car-pic");
    var use_pic_img = $("#js_pic_parent").find("img");
    order_id = getValueByParameter(location.href, "order_id");
    sn = getValueByParameter(location.href, "sn");
    $.ajax({
        url: website + "/api/Order/order_retunr_store.html",
        type: "POST",
        async: true,
        data: {
            order_id: order_id
        },
        timeout: 10000,
        dataType: "json",
        success: function (data) {
            if (parseInt(data.code) === 0) {
                var store_park_price = data.data.store_park_price;
                if (parseFloat(store_park_price) > 0) {
                    $("#js_park").show();
                    $("#js_invoice_parent").show();
                    $("#js_return_park_remark").text(data.data.return_park_remark);
                } else {
                    $("#js_park").hide();
                    $("#js_invoice_parent").hide();
                }
            }
        }
    });

    use_car_pic.on("click", function (ev) {
        if ($(this).hasClass("border_bottom")) {

        } else {
            $(this).siblings().removeClass("border_bottom");
            $(this).addClass("border_bottom");
            var index = parseInt($(this).attr("data-index"));
            for (var i = 0; i < pic_error.length; i++) {
                if (index == i) {
                    $(use_pic_img[i]).show();
                } else {
                    $(use_pic_img[i]).hide();
                }
            }
        }
    });

    $("#js_submit_use_car_pic").on("click", function () {
        Toast("等待审核图片", 3500);
        setTimeout(function () {
            submit_use_car_pic();
        }, 4000);
    });

    //提交用车图片
    function submit_use_car_pic() {
        var flag = false;
        for (var i = 0; i < pic_error.length; i++) {
            if ($(use_pic_img[i]).attr("data-picUrl") === "") {
                mui.alert(pic_error[i] + "图片为空", '温馨提示');
                flag = false;
                break;
            } else {
                pic_url[i] = $(use_pic_img[i]).attr("data-picUrl");
                flag = true;
            }
        }

        pic_url[4] = $(use_pic_img[4]).attr("data-picUrl");

        var add_pic_node = $("#car_pic_add_div").find('.car_pic_add');
        $.each(add_pic_node, function (index, val) {
            pic_url[5] += add_pic_node.eq(index).attr('src') + ",";
        });
        if (flag) {
            loadMessageBox("等待审核还车图片,以及车辆状态", 10);
            $.ajax({
                url: website + "/api/Order/return_order_picture.html",
                type: "POST",
                async: true,
                data: {
                    order_id: order_id,
                    left_front: pic_url[0],
                    right_front: pic_url[1],
                    car_back: pic_url[2],
                    interior: pic_url[3],
                    ticket: pic_url[4],
                    add_pic: pic_url[5]
                },
                timeout: 10000,
                dataType: "json",
                success: function (data) {
                    closeLoadMessageBox();
                    if (parseInt(data.code) === 0) {
                        end_order();
                        // window.location.href = "order_details_newenergy.html?order_id=" + order_id;
                    } else {
                        mui.alert(data.info + "(" + order_id+"|"+parseInt(data.code)+")", '温馨提示');
                    }
                }
            });
        }
    }

    function end_order() {
        loadMessageBox("图片上传成功,等待审核车辆状态", 20);
        $.ajax({
            url: website + "/api/Order/wait_return_order.html",
            type: "POST",
            async: true,
            data: {
                order_id: order_id
            },
            timeOut: 20000,
            dataType: "json",
            success: function (data) {
                closeLoadMessageBox();
                if (parseInt(data.code) === 0) {
                    location.href = "order_pay.html?sn=" + sn + "&goods_type=2";
                } else {
                    mui.alert(data.info + "(" + order_id+"|"+parseInt(data.code)+")", '温馨提示');
                }
            }
        });
    }

});