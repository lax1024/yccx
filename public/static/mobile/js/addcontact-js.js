$(document).ready(function () {
    //返回上一个页面(左上角的“取消”按钮功能)
    $("#goback").on("click",function (ev) {
        location.href = "contactlist.html";
    });
    //证件选择器的显示
    // $(".cardtype-icon").on("click",function (ev) {
    //     $("#maskbody").css({"display":"block"});
    //     $("#ui-view-61").css({"display":"block"});
    // });
    // //证件选择器的隐藏
    // $("#ui-view-61").on("click",function (ev) {
    //     $("#maskbody").css({"display":"none"});
    //     $("#ui-view-61").css({"display":"none"});
    // });
    //证件种类的选择
    var ddlist = $("#maskbody").find("dd");//获取证件类别列表
    ddlist.on("click",function (ev) {
        for(var i = 0; i < ddlist.length; i ++){
            if($(this).attr("data-index") == i){//获取选择的证件类别的id
                $(".card-name").html($(this).text());
                if($(this).find("em").length < 1 ){
                    //在选择器中对应的证件类别后面添加已选择的标志
                    $(ddlist[i]).append("<em class=\"isdIco colorGre size18\"></em>");
                }
                //隐藏证件选择器
                $("#maskbody").css({"display":"none"});
                $("#ui-view-61").css({"display":"none"});
            }else {
                //在选择器中删除未选择的证件类别后面的选择标志
                $(ddlist[i]).find("em").remove();
            }
        }
    });
    //“驾驶员”信息填写完毕的提交(右上角的“完成”按钮功能)
    $("#sure").on("click",function (ev) {
        //获取填写的“驾驶员”信息
        var userName = $("#userName").val();
        var userTel = $("#userTel").val();
        var userCardNum = $("#userCardNum").val();
        //判断“驾驶员”信息是否为空
        if(userName == ""){
            Toast("请输入正确的姓名",2000);
        }else if(userTel == ""){
            Toast("请输入正确的手机号",2000);
        }else if(userCardNum == ""){
            Toast("请输入正确的证件号",2000);
        }else{
            var isEdit = getValueByParameter(location.href,"isEdit");
            console.log("isEdit："+isEdit);
            $.ajax({
                url:website+"/api/Customer/"+((isEdit == "true")?"update_customer_drive.html":"add_customer_drive.html"),
                type:"POST",
                async:true,
                data:{
                    drive_id:((isEdit == "true")?getValueByParameter(location.href,"driverId"):""),
                    vehicle_drivers:userName,
                    mobile_phone:userTel,
                    id_number:userCardNum
                },
                timeout:5000,
                dataType:"json",
                beforeSend:function (xhr) {
                    //console.log('发送前');
                    //console.log(xhr);
                },
                success:function (data) {
                    if(data.code == 0){
						Toast(data.info,2000);
                        location.href = "contactlist.html";
                    }else{
						Toast(data.info,2000);
					}
                },
                error:function (xhr,textStatus) {
                    //console.log('错误');
                    //console.log(xhr);
                    //console.log(textStatus);
                },
                complete:function () {
                    //console.log('结束');
                }

            });
        }
    });
    //判断是否是修改“驾驶员”信息(isEdit == true时为修改)
    //修改信息时需要将需要修改的“驾驶员”信息填入相应的位置
    if(getValueByParameter(location.href,"isEdit")){
        var userName = getValueByParameter(location.href,"userName");
        var userTel = getValueByParameter(location.href,"userTel");
        var userCardNum = getValueByParameter(location.href,"userCardNum");
        $("#userName").val(userName);
        $("#userTel").val(userTel);
        $("#userCardNum").val(userCardNum);
    }
    
    // //驾驶证主页
    // $("#js_pic_license_front").on("click",function () {
    //
    // });
    // //驾驶证副页
    // $("#js_pic_license_back").on("click",function () {
    //
    // });
    // //身份证正面
    // $("#js_pic_idcard_front").on("click",function () {
    //
    // });
    // //身份证反面
    // $("#js_pic_idcard_back").on("click",function () {
    //
    // });
    //

});
