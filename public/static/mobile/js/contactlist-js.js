$(document).ready(function () {
    //获取单个数据节点
    var contact_item = $("#contact_div .js-contact-item").clone(true);
    $("#contact_div .driInn").remove();
    //返回功能(左上角的返回按钮)
    $("#goback").on("click", function () {
        var url = localStorage.getItem("contactlist_parent");
        if (url == "" || url == undefined) {
            location.href = "map_choose_car.html";
        }
        location.href = url;
        // window.history.back();
    });
    //新增驾驶员功能(isEdit==false表示需要新增驾驶员，并不是修改驾驶员信息)
    $(".addDriStr").on("click", function (ev) {
        location.href = "addcontact.html?isEdit=" + false;
    });


    //驾驶员选择
    $(".contact-wrap").find(".choose").on("click", function (ev) {
        $(".fillVi").removeClass('fillVi');
        $(this).addClass('fillVi');
    });


    $(".dropdown").on("click", function (ev) {
        console.log("OK");
        $(this).siblings(".dropdown-content").get(0).css({"display": "block"});
    });

    /**
     * 添加单个节点数据
     * @param json_item_data
     * @param div_id
     * @param item
     */
    function add_contact_item(json_item_data, div_id, item) {
        var item_temp = item.clone(true);
        item_temp.attr('data-id', json_item_data.id);
        item_temp.find('.js-contact-name').text(json_item_data.vehicle_drivers);
        item_temp.find('.js-contact-tel').text(json_item_data.mobile_phone);
        item_temp.find('.js-contact-idcar').text(json_item_data.id_number);
        if (parseInt(json_item_data.is_default) == 0) {
            item_temp.find('.choose').removeClass('fillVi');
        }
        item_temp.on('click', function () {
            var is_choose_contactlist = localStorage.getItem("is_choose_contactlist");
            if (is_choose_contactlist == 'choose') {
                localStorage.setItem("is_choose_contactlist", '');
                localStorage.setItem("driverId", json_item_data.id);
                location.href = getReferrUrl();
            }
        });
        item_temp.find('.choose').on('click', function () {
            $(".fillVi").removeClass('fillVi');
            $(this).addClass('fillVi');
            var data_id = $(this).parents('.driInn').attr('data-id');
            set_contact_default(data_id);
            var is_choose_contactlist = localStorage.getItem("is_choose_contactlist");
            if (is_choose_contactlist == 'choose') {
                localStorage.setItem("is_choose_contactlist", '');
                localStorage.setItem("driverId", json_item_data.id);
                location.href = getReferrUrl();
            }
            return false;
        });
        item_temp.find('.edit').on('click', function () {
            //从要修改的“驾驶员列表”选项中获取相应的信息
            var p_node = $(this).parents('.driInn');
            var userName = p_node.find(".js-contact-name").text();
            var userTel = p_node.find(".js-contact-tel").text();
            var userCardNum = p_node.find(".js-contact-idcar").text();
            //将信息传到“修改界面(添加界面)”，isEdit=true表示需要修改
            window.location.href = "addcontact.html?isEdit=" + true +
                "&driverId=" + json_item_data.id +
                "&userName=" + userName +
                "&userTel=" + userTel +
                "&userCardNum=" + userCardNum;
            return false;
        });
        item_temp.appendTo($("#" + div_id));
    }

    //利用ajax从服务器获取数据
    $.ajax({
        url: website + '/api/Customer/get_list_customer_drive.html',//请求地址
        type: 'GET', //请求方式
        async: true,    //或false,是否异步
        data: {//请求参数
        },
        timeout: 5000,    //超时时间
        dataType: 'json',    //返回的数据格式：json/xml/html/script/jsonp/text
        beforeSend: function (xhr) {
            // console.log(xhr)
            // console.log('发送前')
        },
        success: function (data) {//请求成功，做数据处理
            /**
             * 数据(json类型)一级遍历
             * data是请求到的数据
             * index“键”
             * value是一级“值”
             */
            if (parseInt(data.code) == 0) {
                $.each(data.data, function (index, value) {
                    add_contact_item(value, 'contact_div', contact_item);
                });
            } else {
                Toast(data.info, 2000);
            }

        },
        error: function (xhr, textStatus) {
            console.log('错误');
            console.log(xhr);
            console.log(textStatus)
        },
        complete: function () {
            // console.log('结束');
        }
    });

    /**
     * 设置为默认地址
     * @param drive_id
     */
    function set_contact_default(drive_id) {

        //利用ajax从服务器获取数据
        $.ajax({
            url: website + '/api/Customer/default_customer_drive.html',//请求地址
            type: 'POST', //请求方式
            async: true,    //或false,是否异步
            data: {//请求参数
                'drive_id': drive_id
            },
            timeout: 5000,    //超时时间
            dataType: 'json',    //返回的数据格式：json/xml/html/script/jsonp/text
            beforeSend: function (xhr) {
                // console.log(xhr)
                // console.log('发送前')
            },
            success: function (data) {//请求成功，做数据处理
                if (parseInt(data.code) == 0) {

                } else {
                    Toast(data.info, 2000);
                }

            },
            error: function (xhr, textStatus) {
                // console.log('错误');
                // console.log(xhr);
                // console.log(textStatus)
            },
            complete: function () {
                // console.log('结束');
            }
        });
    }
});
