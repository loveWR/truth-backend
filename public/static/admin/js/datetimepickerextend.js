(function($) {

		$.fn.datetimepickerextend = function() {
			var element;
			element=$(this);
			function Today() { //今天 即凌晨零点
				mydate = new Date();
				myYear = mydate.getFullYear();
				myMouth = parseInt(mydate.getMonth()) < 10 ? "0" + (mydate.getMonth() + 1) : mydate.getMonth() + 1;
				mydates = parseInt(mydate.getDate()) < 10 ? "0" + mydate.getDate() : mydate.getDate();

				return myYear + '-' + myMouth + '-' + mydates + ' 00:00:00';
			}
			function getToday(){
				
				
				mydate = new Date();
				myYear = mydate.getFullYear();
				myMouth = parseInt(mydate.getMonth()) < 10 ? "0" + (mydate.getMonth() + 1) : mydate.getMonth() + 1;
				mydates = parseInt(mydate.getDate()) < 10 ? "0" + mydate.getDate() : mydate.getDate();
				return myYear + '-' + myMouth + '-' + mydates;
				
			}
	
			function reset() { //重置到今天 时分秒设置为零

				element.find("input.form-control").val(Today());
				element.find(".rc-calendar-time-input").val('00');

			}
			
			function getTimes(){   //获取时分秒输入框的时间值
				
				var hours=element.find(".rc-calendar-time .hours").val();
				var minutes=element.find(".rc-calendar-time .minutes").val();
				var seconds=element.find(".rc-calendar-time .seconds").val();
				if(element.find("input.form-control").val()){
					var initTimes=element.find("input.form-control").val().substr(0,10);
					
				}
				else 
				{
					var initTimes=getToday();
					
				}
				element.find("input.form-control").val(initTimes+" "+hours+":"+minutes+":"+seconds)
				
			}

			this.datetimepicker({
				format: "YYYY-MM-DD HH:mm:ss",
				debug: true,
				locale:"zh-cn"   //语种 依赖moment.js moment.js没有中文  moment.min.js有中文
			}); //初始化插件  
			this.on("dp.show", function() {
				element.find(".glyphicon-time").hide();
				element.find(".list-unstyled").after(str);
				element.find(".list-unstyled .collapse").not(".in").css({"position":"absolute","top":0,"left":"23px"})
				element.find(".rc-calendar-now-btn ").click(function() { //点击此刻后更新时分秒输入框的值
					mydate = new Date();
					myHours = mydate.getHours();
					myMinutes = parseInt(mydate.getMinutes()) < 10 ? "0" + mydate.getMinutes() : mydate.getMinutes();
					mySeconds = parseInt(mydate.getSeconds()) < 10 ? "0" + mydate.getSeconds() : mydate.getSeconds();
					element.find(".rc-calendar-time-input").eq(0).val(myHours);
					element.find(".rc-calendar-time-input").eq(1).val(myMinutes);
					element.find(".rc-calendar-time-input").eq(2).val(mySeconds);
					getTimes();
				})
				element.find(".rc-calendar-time-input").click(function() {

						element.find("ul.list-unstyled .collapse.in").css({"opacity":0});
						
		
					$(this).focus();
					
					element.find(".collapse").show();
					element.find(".collapse .timepicker").show();
					if($(this).parent().hasClass("rc-calendar-time"))
					{
						
						 element.find(".timepicker-hours").show().siblings().hide();
						 element.find("td.hour").on("click",function(){
					
							element.find(".rc-calendar-time-input").eq(0).val($(this).text())
							 element.find(".collapse .timepicker").hide();
							 element.find("ul.list-unstyled .collapse.in").css({"opacity":1});

						})
				
				
					}
					if($(this).parent().hasClass("rc-calendar-time-minute"))
					{
						
						 element.find(".timepicker-minutes").show().siblings().hide();
						 element.find("td.minute").on("click",function(){
					
							element.find(".rc-calendar-time-input").eq(1).val($(this).text())
							 element.find(".collapse .timepicker").hide();
							  element.find("ul.list-unstyled .collapse.in").css({"opacity":1});
		
						})
					}
					if($(this).parent().hasClass("rc-calendar-time-second"))
					{
						
						 element.find(".timepicker-seconds").show().siblings().hide();
						 element.find("td.second").on("click",function(){
							element.find(".rc-calendar-time-input").eq(2).val($(this).text())
							 element.find(".collapse .timepicker").hide();
							  element.find("ul.list-unstyled .collapse.in").css({"opacity":1});

							
						})
					}
				//	element.find(".list-unstyled li.collapse").eq(0).hide();
				//	element.find(".list-unstyled li.collapse").eq(1).css({
				//		"display": "block",
				//		'visibility': 'visible'
				//	});

				})

				element.find(".rc-calendar-time input").change(function(){
					var values=$(this).val();
					if(values<0) {alert("请输入大于零的数字") ;$(this).val("");return;}
					if(isNaN(values)) {alert("请输入数字") ;$(this).val("");return;}
					if(values<10){
						
						$(this).val('0'+parseInt($(this).val()))
						var values=$(this).val();
					}
					var now=element.find("input.form-control").val();
					
					if($(this).hasClass("hours")){
						if(values>23 || values<0) {
							
							alert("必须输入24小时制数字") ;$(this).val("");return;
						}
						var nothourspix=now.substring(0,10);
						var nothours=now.substring(14);
						element.find("input.form-control").val(nothourspix+ ' '+values+':'+nothours)
					}
					if($(this).hasClass("minutes")){
						if(values>59 || values<0) {
							
							alert("必须输入60制数字") ;$(this).val("");return;
						}
						var notminutespix=now.substring(0,13);
						var notminutes=now.substring(16);
						element.find("input.form-control").val(notminutespix+':'+values+notminutes)
					}
					if($(this).hasClass("seconds")){
						if(values>59 || values<0) {
							
							alert("必须输入60制数字") ;$(this).val("");return;
						}
						var notsecondpix=now.substring(0,16);
						element.find("input.form-control").val(notsecondpix+':'+values)
					}
				
					
				})
				element.find(".rc-calendar-reset-btn").click(function() {

					reset()

				})
				element.find(".rc-calendar-clear-btn").click(function() {

					element.find("input.form-control").val("");
					element.data("DateTimePicker").hide();

				})
				
				element.find(".rc-calendar-zero-btn").click(function() {

					element.find(".rc-calendar-time").find("input").val("00");
					getTimes();

				})
				element.find(".rc-calendar-today-btn").click(function() {


					setTimeout(function(){
						
						getTimes();
						
					},10)
					

				})

				element.find(".rc-calendar-ok-btn").click(function() {
					$(".rc-calendar-time-input").blur();
					element.data("DateTimePicker").hide();
					

				})

			})

			var str = '<div class="rc-calendar-footer"><div class="rc-calendar-time" style="text-align:center"><input class="rc-calendar-time-input hours" value="00" placeholder="00"  maxlength="2"><span class="rc-calendar-time-minute" ><span > : </span><input class="rc-calendar-time-input minutes" value="00" placeholder="00" maxlength="2"></span><span class="rc-calendar-time-second"><span> : </span><input class="rc-calendar-time-input seconds" value="00" placeholder="00"  maxlength="2"></span></div></br><span class="rc-calendar-footer-btn"><a class="rc-calendar-now-btn " role="button" title="2016-10-13">此刻</a><a class="rc-calendar-zero-btn" role="button">零点</a><a data-action="today" class="rc-calendar-today-btn" role="button">今天</a><a class="rc-calendar-clear-btn" role="button">清空</a><a class="rc-calendar-ok-btn" role="button">确定</a></span></div>';

	        return this;
		
		
		};

})(jQuery)