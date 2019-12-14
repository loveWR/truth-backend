/*frame框架首页的JS*/
$(function() {
	function initFrameWork() { //初始化页面高度
		var initHeight = $(window).height() - 45; //初始高度等于窗口高度减去navbar的45像素
		$("#main-container,#sidebar").css({
			height: initHeight
		}); //设置页面主体高度为初始高度
		$('#sidebar').ace_scroll({
				size: initHeight-20
			});
		$("#iframeWrap").css({
			height: $(".main_page-content").height() - 41
		}); //初始frame父高度为初始高度减去tab选项卡的41像素
		$("#sidebar").on("click", "li.menu_item", function(e) {
			$('#sidebar').ace_scroll({
				size: initHeight - 10
			});
		})


	}
	initFrameWork();
	$(window).resize(function() {
		initFrameWork();
	})


})