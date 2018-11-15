var time_now = new Date();
var mouth = ((time_now.getMonth() + 1) < 10 ? ("0" + (time_now.getMonth() + 1) + "月"): ((time_now.getMonth() + 1)) + "月");
var date = time_now.getDate() < 10 ? "0" + (time_now.getDate()  + "日"):(time_now.getDate()  + "日");
var sure = document.getElementById("carTimeSure");
var cansel = document.getElementById("carTimeCansel");
var isReturn = document.getElementById("isReturn");
document.getElementById("j_pdata_data").innerHTML = mouth + date;
function getTakeCarTime() {
    document.getElementById("carTimer").style.display = "block";
}
cansel.onclick = function() {
    document.getElementById("carTimer").style.display = "none";
}
sure.onclick = function () {
    document.getElementById("carTimer").style.display = "none";
}
isReturn.onclick = function () {
    var flag = document.getElementById("isReturn-flag");
    if(document.getElementById("rCityDiv").style.display == "none"){
        flag.innerHTML = "";
        flag.classList.add("colorGre");
        document.getElementById("rCityDiv").style.display = "block";
    }else {
        flag.innerHTML = "";
        flag.classList.remove("colorGre");
        document.getElementById("rCityDiv").style.display = "none";
    }
}
