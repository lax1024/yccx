pushHistory();
window.addEventListener("popstate", function(e) {
    location.href="http://www.baidu.com";
}, false);
function pushHistory() {
    var state = {
        title: "title",
        url: "#"
    };
    window.history.pushState(state, "title", "#");
}