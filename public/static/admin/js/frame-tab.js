/*
后台首页框架，横向TAB选项卡实现 。
*/
(function ($) {
    $.XframeworkTab = {
        requestFullScreen: function () {
            var de = document.documentElement;
            if (de.requestFullscreen) {
                de.requestFullscreen();
            } else if (de.mozRequestFullScreen) {
                de.mozRequestFullScreen();
            } else if (de.webkitRequestFullScreen) {
                de.webkitRequestFullScreen();
            }
        },
        exitFullscreen: function () {
            var de = document;
            if (de.exitFullscreen) {
                de.exitFullscreen();
            } else if (de.mozCancelFullScreen) {
                de.mozCancelFullScreen();
            } else if (de.webkitCancelFullScreen) {
                de.webkitCancelFullScreen();
            }
        },
        refreshTab: function () {//根据TAB的data-id 找到对应的iframe 刷新
            var currentId = $(".page-tabs-content").find('.active').attr('data-id');
            var target = $('.tabiframe[data-id="' + currentId + '"]');
            var url = target.attr('src');
            $.loading(true);
            target.attr('src', url).load(function () {
                $.loading(false);
            });
            return false;
        },
        activeTab: function () {//根据TAB的data-id 将某个TAB激活为当前，并将对应的iframe 显示
            var currentId = $(this).data('id');
            if (!$(this).hasClass('active')) {
                $('.mainContent .tabiframe[data-id="' + currentId + '"]').show().siblings('.tabiframe').hide();
                $(this).addClass('active').siblings('.menuTab').removeClass('active');
                $.XframeworkTab.scrollToTab(this);
            }
        },
        closeOtherTabs: function () {//除当前激活的TAB外， 其它的TAB都关闭，相应移除被关闭的iframe，右上角没有X的TAB不会被关闭
            var ar = $(".page-tabs-content .fa-remove[data-id]").parent("a:not('.active')");
            ar.each(function (){
                $('.tabiframe[data-id="' + $(this).data('id') + '"]').remove();
                $(this).remove();
            });
            $('.page-tabs-content').css("margin-left", "0");

        },
       closeleft:function(){
        	   	var activeid=$('.page-tabs-content .menuTab.target').data("id");
        	$(".page-tabs-content .menuTab.target").prevAll().each(function(){
        		var closeTabId=$(this).data("id")
        	if($(this).find(".fa-remove").length>0)
  					{
  						$("a.menuTab[data-id='" + closeTabId + "'").remove();
  						$(".tabiframe[data-id='" + closeTabId + "'").remove();
  						
  					}
        		
        	})
        	$("a.menuTab[data-id='" + activeid + "'").addClass("active").siblings("a.menuTab").removeClass("active");
  			$(".tabiframe[data-id='" + activeid + "'").show().siblings('.tabiframe').hide();
        },
        closeright:function(){
        	var activeid=$('.page-tabs-content .menuTab.target').data("id");
        	$(".page-tabs-content .menuTab.target").nextAll().each(function(){
        		var closeTabId=$(this).data("id")
        	if($(this).find(".fa-remove").length>0)
  					{
  						$("a.menuTab[data-id='" + closeTabId + "'").remove();
  						$(".tabiframe[data-id='" + closeTabId + "'").remove();
  						
  					}
        		
        	})
        	$("a.menuTab[data-id='" + activeid + "'").addClass("active").siblings("a.menuTab").removeClass("active");
  			$(".tabiframe[data-id='" + activeid + "'").show().siblings('.tabiframe').hide();
        },
        closeTab: function () {
            //点击TAB右上角的X，关闭本TAB，$(this)是TAB右上角的交叉 
            var closeTabId = $(this).data("id");
            var currentWidth = $(this).parent().width();
            if ($(this).parent().hasClass('active')) {
                //关闭当前为激活的TAB
                var activeId;
                if ($(this).parent().next('.menuTab').size()) {
                    //把紧邻的后一个TAB激活
                    activeId = $(this).parent().next('.menuTab').data('id');
                    $(".menuTab[data-id='" + activeId + "']").addClass('active');
                    $(".tabiframe[data-id='" + activeId + "'").show().siblings('.tabiframe').hide();
                            
                    var marginLeftVal = parseInt($('.page-tabs-content').css('margin-left'));
                    if (marginLeftVal < 0) {
                        $('.page-tabs-content').animate({
                            marginLeft: (marginLeftVal + currentWidth) + 'px'
                        }, "fast");
                    }
                    $(this).parent().remove();
                    $(".tabiframe[data-id='" + closeTabId + "'").remove();
                } else if ($(this).parent().prev('.menuTab').size()) {
                    //把紧邻的前面的激活
                    activeId = $(this).parent().prev('.menuTab').data('id');
                    $(".menuTab[data-id='" + activeId + "']").addClass('active');
                    $(".tabiframe[data-id='" + activeId + "'").show().siblings('.tabiframe').hide();
                    $(this).parent().remove();
                    $(".tabiframe[data-id='" + closeTabId + "'").remove();
                }
            }
            else {
                //关闭非激活的TAB
                $(".menuTab[data-id='" + closeTabId + "']").remove();
                $('.mainContent .tabiframe[data-id=' + closeTabId + ']').remove();
                $.XframeworkTab.scrollToTab($('.menuTab.active'));
            }
            return false;
        },
        addTab: function (id, tabText, url) {

            if (!url||!id ) {return false;}  
            var tab = $(".page-tabs-content .menuTab[data-id='" + id + "']");
   
            var isAdd = tab.length === 0;
            if (isAdd) {
            	    
                var tabstr = '<a href="javascript:void(0);" class="active menuTab"  data-id="{0}" data-url="{2}" >{1}<i class="fa fa-remove" data-id="{0}"></i></a>'.format(id,   tabText,url);
                $('.page-tabs-content .menuTab').removeClass('active');
                    
                var framestr = '<iframe class="tabiframe" id="tabiframe{0}" name="tabiframe{0}" data-id="{0}"  data-url="{1}"  width="100%" height="100%" src="{1}" frameborder="0" seamless></iframe>'.format(id, url);
                $('.mainContent .tabiframe').hide();
          
              //  $.loading(true);
                $('.mainContent').append(framestr);
                $('.mainContent iframe:visible')
                    .load(function() {
                        $.loading(false);
                    });
                $('.menuTabs .page-tabs-content').append(tabstr);
                tab = $(".page-tabs-content .menuTab[data-id='" + id + "']");
                    
            } else {
                    tab.addClass('active').siblings('.menuTab').removeClass('active');
                    $('.mainContent .tabiframe[data-id=' + id + ']').show().siblings('.tabiframe').hide();
            }
            $.XframeworkTab.scrollToTab(tab);
            
    		$('.menuTab').contextmenu({
				  target:'#context-menu', 
				  before: function(e,context) {
				    // execute code before context menu if shown
				    context.addClass("target").siblings().removeClass("target");//右键点击菜单后标记点击的菜单
				  },
				  onItem: function(context,e) {
	
				  }
				})
            return false;
             
        },
        scrollTabRight: function () {
            var marginLeftVal = Math.abs(parseInt($('.page-tabs-content').css('margin-left')));
            var tabOuterWidth = $.XframeworkTab.calSumWidth($(".content-tabs").children().not(".menuTabs"));
            var visibleWidth = $(".content-tabs").outerWidth(true) - tabOuterWidth;
            var scrollVal = 0;
            if ($(".page-tabs-content").width() < visibleWidth) {
                return false;
            } else {
                var tabElement = $(".menuTab:first");
                var offsetVal = 0;
                while ((offsetVal + $(tabElement).outerWidth(true)) <= marginLeftVal) {
                    offsetVal += $(tabElement).outerWidth(true);
                    tabElement = $(tabElement).next();
                }
                offsetVal = 0;
                while ((offsetVal + $(tabElement).outerWidth(true)) < (visibleWidth) && tabElement.length > 0) {
                    offsetVal += $(tabElement).outerWidth(true);
                    tabElement = $(tabElement).next();
                }
                scrollVal = $.XframeworkTab.calSumWidth($(tabElement).prevAll());
                if (scrollVal > 0) {
                    $('.page-tabs-content').animate({
                        marginLeft: 0 - scrollVal + 'px'
                    }, "fast");
                }
            }
        },
        scrollTabLeft: function () {
            var marginLeftVal = Math.abs(parseInt($('.page-tabs-content').css('margin-left')));
            var tabOuterWidth = $.XframeworkTab.calSumWidth($(".content-tabs").children().not(".menuTabs"));
            var visibleWidth = $(".content-tabs").outerWidth(true) - tabOuterWidth;
            var scrollVal = 0;
            if ($(".page-tabs-content").width() < visibleWidth) {
                return false;
            } else {
                var tabElement = $(".menuTab:first");
                var offsetVal = 0;
                while ((offsetVal + $(tabElement).outerWidth(true)) <= marginLeftVal) {
                    offsetVal += $(tabElement).outerWidth(true);
                    tabElement = $(tabElement).next();
                }
                offsetVal = 0;
                if ($.XframeworkTab.calSumWidth($(tabElement).prevAll()) > visibleWidth) {
                    while ((offsetVal + $(tabElement).outerWidth(true)) < (visibleWidth) && tabElement.length > 0) {
                        offsetVal += $(tabElement).outerWidth(true);
                        tabElement = $(tabElement).prev();
                    }
                    scrollVal = $.XframeworkTab.calSumWidth($(tabElement).prevAll());
                }
            }
            $('.page-tabs-content').animate({
                marginLeft: 0 - scrollVal + 'px'
            }, "fast");
        },
        scrollToTab: function (element) {
            var marginLeftVal = $.XframeworkTab.calSumWidth($(element).prevAll()), marginRightVal = $.XframeworkTab.calSumWidth($(element).nextAll());
            var tabOuterWidth = $.XframeworkTab.calSumWidth($(".content-tabs").children().not(".menuTabs"));
            var visibleWidth = $(".content-tabs").outerWidth(true) - tabOuterWidth;
            var scrollVal = 0;
            if ($(".page-tabs-content").outerWidth() < visibleWidth) {
                scrollVal = 0;
            } else if (marginRightVal <= (visibleWidth - $(element).outerWidth(true) - $(element).next().outerWidth(true))) {
                if ((visibleWidth - $(element).next().outerWidth(true)) > marginRightVal) {
                    scrollVal = marginLeftVal;
                    var tabElement = element;
                    while ((scrollVal - $(tabElement).outerWidth()) > ($(".page-tabs-content").outerWidth() - visibleWidth)) {
                        scrollVal -= $(tabElement).prev().outerWidth();
                        tabElement = $(tabElement).prev();
                    }
                }
            } else if (marginLeftVal > (visibleWidth - $(element).outerWidth(true) - $(element).prev().outerWidth(true))) {
                scrollVal = marginLeftVal - $(element).prev().outerWidth(true);
            }
            $('.page-tabs-content').animate({
                marginLeft: 0 - scrollVal + 'px'
            }, "fast");
        },
        calSumWidth: function (element) {
            var width = 0;
            $(element).each(function () {
                width += $(this).outerWidth(true);
            });
            return width;
        },
        init: function () {
        	$(".nav-list li.menu_item a").not(".dropdown-toggle ").on("click",function(){
        		$(".nav-list").find(".active").removeClass("active");
        		$(this).parent().addClass("active");
        		
        	})
            $(".nav-list a[target='iframe']").on('click', function () {
       
                var id = $(this).data("menuid");
                var menutext = $(this).data("menutext")||$.trim($(this).text());
                var url = $(this).attr("href");
                $.XframeworkTab.addTab(id, menutext, url);
                return false;
            });
            $('.content-tabs .menuTabs').on('click', '.menuTab i', $.XframeworkTab.closeTab); //关闭TAB
            $('.content-tabs .menuTabs').on('click', '.menuTab', $.XframeworkTab.activeTab); //激活TAB
            $('.content-tabs .tabLeft').on('click', $.XframeworkTab.scrollTabLeft);//左滚
            $('.content-tabs .tabRight').on('click', $.XframeworkTab.scrollTabRight);//右滚
            $('#refresh-togglers').on('click', $.XframeworkTab.refreshTab);//刷新
    		$('.menuTab').contextmenu({
				  target:'#context-menu', 
				  before: function(e,context) {
				    // execute code before context menu if shown
				     context.addClass("target").siblings().removeClass("target");//右键点击菜单后标记点击的菜单
				  },
				  onItem: function(context,e) {
				    // execute on menu item selection
				  }
				})
    		$("body").on("click",".tabCloseLeft",$.XframeworkTab.closeleft);
    		$("body").on("click",".tabCloseRight",$.XframeworkTab.closeright);
            $('body').on('click','.tabCloseAll', function () {//关闭所有
                $('.page-tabs-content .fa-remove[data-id]').each(function () {//所有X的TAB，都关闭
                    var id = $(this).data('id');
                    $('.tabiframe[data-id="' + id+ '"]').remove();
                    $('.page-tabs-content .menuTab[data-id="' + id + '"]').remove();
                });
                $('.page-tabs-content .menuTab[data-id]:first').each(function () {//剩下的第一个设为激活
                    $('.tabiframe[data-id="' + $(this).data('id') + '"]').show();
                    $(this).addClass("active");
                });
                $('.page-tabs-content').css("margin-left", "0");
                $("#context-menu").removeClass("open");
            });
            $(".sidebar-toggler").click(function(){
            	$(this).toggleClass("active");
            	$("#sidebar").toggleClass("hidden");
            	if($("#sidebar").hasClass("hidden")){
            		
            		$(".sidebar +.main-content").css({"margin-left":0})
            	}
            	else {
            		
            		$(".sidebar +.main-content").css({"margin-left":""})
            	}
            })
            $("body").on('click',".tabCloseOther", $.XframeworkTab.closeOtherTabs) //关闭其它
            $('.fullscreen').on('click', function () { //全屏与否
                if (!$(this).attr('fullscreen')) {
                    $(this).attr('fullscreen', 'true');
                    $(this).find("i").addClass("fa-arrows");
                    $.XframeworkTab.requestFullScreen();
                } else {
                    $(this).removeAttr('fullscreen');
                    $.XframeworkTab.exitFullscreen();
                    $(this).find("i").removeClass("fa-arrows");
                }

            });
        }
    };
    $(function () {
        $.XframeworkTab.init();
       	
    });
})(jQuery);

//在任何页中，可这样创建新的TAB：top.$.XframeworkTab.addTab(320, "测试", "/test1.aspx");