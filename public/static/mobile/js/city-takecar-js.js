$(document).ready(function () {
    //获取左上角的“取消”按钮并设置“取消”功能
    $("#goback").on("click", function (ev) {
        window.history.back();
    });
    // var city_hot = new Array();
    // var sub_list = new Array();
    //利用ajax从服务器获取数据(城市)
    // $.ajax({
    //     url: website + '/api/area/get_city_list.html',//请求地址
    //     type: 'GET', //请求方式
    //     async: true,    //或false,是否异步
    //     data: {//请求参数
    //
    //     },
    //     timeout: 5000,    //超时时间
    //     dataType: 'json',    //返回的数据格式：json/xml/html/script/jsonp/text
    //     beforeSend: function (xhr) {
    //         console.log(xhr)
    //         console.log('发送前')
    //     },
    //     success: function (data) {//请求成功，做数据处理
    //         // console.log('成功');
    //         /**
    //          * 数据(json类型)一级遍历
    //          * data是请求到的数据
    //          * keyWord是一级“键”(A-Z)
    //          * value是一级“值”(城市的相关信息)
    //          */
    //         if(parseInt(data.code)==0){
    //             $.each(data.data, function (keyWord, value) {
    //                 //根据键来动态添加div(界面上的A-Z分隔符)
    //                 var cityList =
    //                     "<div class=\"index\" id=\"" + keyWord + "\">" + keyWord + "</div>\n" +
    //                     "     <div class=\"sub-list\">\n" +
    //                     "</div>";
    //                 $("#city-main").append(cityList);
    //                 //根据键来动态添加快捷键(界面上右侧边沿上的A-Z按钮)
    //                 var key = "<li data-value=\"" + keyWord + "\"> " +
    //                     "<a href=\"#" + keyWord + "\">" + keyWord + "</a>" +
    //                     "</li>";
    //                 $("#city-key").append(key);
    //                 /**
    //                  * json二级遍历
    //                  * value城市相关信息，由一级json遍历得到
    //                  * key是二级遍历中，value的数据项数(即城市个数)
    //                  * city是所需城市信息
    //                  */
    //                     //向相应的位置添加城市列表
    //                 var view = document.getElementById(keyWord);
    //                 $.each(value, function (key, city) {
    //                     //动态添加到界面相应位置
    //                     var item = $("<div class=\"js-city-item\" data-id=\"" + city.id + "\">" + city.name + "</div>");
    //                     $(view).next().append(item);
    //                 })
    //             });
    //             //获取从城市选择界面传过来的城市
    //             var pcity = localStorage.getItem("pcity");
    //             //遍历热门城市(span)
    //             city_hot = $("#mainList").find(".list").find("span");
    //             //遍历一般城市(div)
    //             sub_list = $("#mainList").find(".sub-list").find("div");
    //             //将一般城市添加到热门城市
    //             for (var i = 0; i < sub_list.length; i++) {
    //                 city_hot.push(sub_list[i]);
    //             }
    //             //释放内存
    //             sub_list = null;
    //             //对数组进行操作(界面初始化：给默认的城市添加不一样的样式)
    //             for (var i = 0; i < city_hot.length; i++) {
    //                 if (city_hot[i].innerHTML == pcity) {
    //                     city_hot[i].classList.add("current");
    //                 }
    //             }
    //         }else {
    //             Toast(data.info,2000);
    //         }
    //     },
    //     error: function (xhr, textStatus) {
    //         console.log('错误');
    //         console.log(xhr);
    //         console.log(textStatus)
    //     },
    //     complete: function () {
    //         console.log('结束');
    //     }
    // });

    // //城市列表的获取和设置选择功能
    // $("#mainList").on("click", function (ev) {
    //     //对城市做出选择时的操作
    //     for (var i = 0; i < city_hot.length; i++) {
    //         if ($(city_hot[i]).text() == $(ev.target).text()) {
    //             $(city_hot[i]).addClass("current");
    //             localStorage.setItem("pcity", $(city_hot[i]).text());
    //             location.href = "area_takecar.html?pcity=" + $(city_hot[i]).text();
    //         } else {
    //             $(city_hot[i]).removeClass("current");
    //         }
    //     }
    // });
    $(".js-city-item").on('click',function () {
        $(this).addClass("current");
        localStorage.setItem("pcity", $(this).text());
        localStorage.setItem('back_url_area_takecar','index');
        location.href = "area_takecar.html";
    });
    //搜索框
    $("#cityKeyword1").bind("input propertychange", function () {
        var cityKeyword = $('#cityKeyword1').val();
        if (cityKeyword == "") {
            $("#scrollList").css({"display": "block"});
        }
        $.ajax({
            url: website+"/api/area/get_city_search.html",
            type: "POST",
            async: true,
            data: {
                keyword: cityKeyword
            },
            timeout: 5000,
            dataType: "json",
            beforeSend: function (xhr) {
                console.log(xhr);
                console.log("发送前");
            },
            success: function (data) {
                console.log("成功");
                console.log(data.data);
                $("#searchList").empty();
                $.each(data.data, function (key, value) {
                    $("#scrollList").css({"display": "none"});
                    var searchItem = $("<div class=\"js-city js-city-item\" data-id=\"" + value.id + "\">" + value.name + "</div>");
                    searchItem.on('click', function () {
                        localStorage.setItem("pcity", $(this).text());
                        localStorage.setItem('back_url_area_takecar','index');
                        location.href = "area_takecar.html";
                    });
                    $("#searchList").append(searchItem);
                });
            },
            error: function (xhr, textStatus) {
                console.log('错误');
                console.log(xhr);
                console.log(textStatus);
            },
            complete: function () {
                console.log('结束');
            }

        });
    });
});
