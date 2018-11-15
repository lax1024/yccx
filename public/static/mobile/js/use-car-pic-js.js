$(document).ready(function () {

    var pic_error = ["左前","右前","左后","右后","内部"];
    var pic_url = new Array();

    var use_car_pic = $(".js-use-car-pic");
    var use_pic_img = $("#js_pic_parent").find("img");

    var order_id = getValueByParameter(location.href,"order_id");

    use_car_pic.on("click",function (ev) {
        if($(this).hasClass("border_bottom")){

        }else {
            $(this).siblings().removeClass("border_bottom");
            $(this).addClass("border_bottom");
            var index = parseInt($(this).attr("data-index"));
            for(var i = 0 ; i < 5; i ++ ){
                if(index == i){
                    $(use_pic_img[i]).show();
                }else {
                    $(use_pic_img[i]).hide();
                }
            }
        }
    });
    
    $("#js_submit_use_car_pic").on("click",function () {
        submit_use_car_pic();
    });

    //提交用车图片
    function submit_use_car_pic() {
        var flag = false;
        for(var i = 0 ; i < 5; i ++ ){
			
            if($(use_pic_img[i]).attr("data-picUrl") == ""){
                alert(pic_error[i] + "图片为空");
                flag = false;
                break;
            }else {
                pic_url[i] = $(use_pic_img[i]).attr("data-picUrl");
                flag = true;
            }
        }

        if(flag){
            $.ajax({
                url: website + "/api/Order/acquire_order_picture.html",
                type: "POST",
                async: true,
                data: {
                    order_id:order_id,
                    left_front:pic_url[0],
                    right_front:pic_url[1],
                    left_back:pic_url[2],
                    right_back:pic_url[3],
                    interior:pic_url[4]
                },
                timeout: 5000,
                dataType: "json",
                beforeSend: function (xhr) {

                },
                success: function (data) {
                    if (data.code == 0) {
                        Toast("图片上传成功，下单成功",2000);
                        window.location.href = "order_details_newenergy.html?order_id=" + order_id;
                    } else {
                        Toast(data.info, 2000);
                    }
                },
                error: function (xhr, textStatus) {

                },
                complete: function () {

                }
            });
        }
    }

});