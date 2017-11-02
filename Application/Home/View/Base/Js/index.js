$(document).ready(function () {
    init();
});
$(function () {
    $('.bxslider').bxSlider({
        auto: true,
        autoControls: true
    });
    var name = $("#name"),
        password = $("#password"),
        allFields = $([]).add(name).add(password),
        tips = $(".validateTips");
    function updateTips(t) {
        tips.text(t).addClass("ui-state-highlight");
        setTimeout(function () {
            tips.removeClass("ui-state-highlight", 1500);
        }, 500);
    }
    function checkLength(o, n, min, max) {
        if (o.val().length > max || o.val().length < min) {
            o.addClass("ui-state-error");
            updateTips("Length of " + n + " must be between " + min + " and " + max + ".");
            return false;
        }
        return true;
    }
    function checkRegexp(o, regexp, n) {
        if (!(regexp.test(o.val()))) {
            o.addClass("ui-state-error");
            updateTips(n);
            return false;
        }
        return true;
    }  
});
