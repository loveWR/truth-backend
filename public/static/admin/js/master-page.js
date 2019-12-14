/*masterpage方式相关JS*/

(function($) {
    $.Xframework = {
        zoomsmall:function(){  //小屏显示
            $(".zoom span").removeClass("fa-arrows zoomsmall").addClass("fa-arrows-alt zoombig");
            ace.settingFunction.main_container_fixed(null, true);
        },
        zoombig:function(){   //大屏显示
            $(".zoom span").removeClass("fa-arrows-alt zoombig").addClass("fa-arrows zoomsmall");
            ace.settingFunction.main_container_fixed(null, false);
        },
        init:function(){
            $(".zoom").on("click", ".zoomsmall", $.Xframework.zoomsmall);
            $(".zoom").on("click", ".zoombig", $.Xframework.zoombig);
            if ($("#main-container").hasClass("container")) {
                $(".zoom span").removeClass("zoomsmall").addClass("zoombig");
            }
        }
    };
    $(function() {
        $.Xframework.init();
    });

})(jQuery)
