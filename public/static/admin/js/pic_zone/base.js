function searchNodes(){  
    var treeObj = $.fn.zTree.getZTreeObj("actionTree");  
    var keywords=$("#input-select-node").val();  
    var nodes = treeObj.getNodesByParam("dir_name", keywords, null);  
    if (nodes.length>0) {  
        treeObj.selectNode(nodes[0]);  
        $(".curSelectedNode").click()
    }
    else {
    	alert('目录不存在，请输入精准的目录名')
    }
}  
$(".search-tree .search-btn").click(searchNodes);  
function getselectids(){  //获取选择列表
	var  ids='';
	$("#J_Picture .ui-selected").each(function(){
		
		ids+=$(this).find('.img-name').data('id')+','
	})
	ids=tools.trim(ids,',');
	return ids;
}
function renamefile(){   //重命名文件夹
	
	$.modalPrompt({title:'请输入新名称',callBack:promptcb});
	
}
 function promptcb(text)
        {
        	
        	if(text==$(".home").text()){
        		alert('不能与旧名称重复');
        		return false
        	}
        	else {
        		var id=$(".home").data('id');
        		var urls='/admin/piczone/renameDir?id='+id+'&newname='+text+'&_input_charset=utf-8';
        		$.ajax({
				type:"get",
				url:urls,
				success:function(){
					
					location.reload()
				}
			});
			$("#J_Infor_Box").text("重命名成功").css({"opacity":1,"z-index":0});
			setTimeout('$("#J_Infor_Box").css({"opacity":0,"z-index":0})',1000)
			return false;
        	}
        }


function renameall(){
	$("#J_Picture").find(".base-msg").find("input").blur(function(){   //失去焦点后
	var isfolder=$(this).parent().parent().attr("class");
		var newname=$(this).val();
		var oldName=$(".ui-selected").find('.img-name').text()
		var id=$(".ui-selected").find('.img-name').data('id')
		var urls='/admin/piczone/renamePic?id='+id+'&newname='+newname+'&_input_charset=utf-8';

	


	if(newname==oldName){
			$("#J_Picture").find(".base-msg").find("input").hide();
					return false;
		
	}

	$("#J_Picture").find(".base-msg").find("input").hide();

	$.ajax({
		type:"get",
		url:urls,
		success:function(){
			
			location.reload()
		}
	});
	$("#J_Infor_Box").text("重命名成功").css({"opacity":1,"z-index":0});
	setTimeout('$("#J_Infor_Box").css({"opacity":0,"z-index":0})',1000)
	return false;

	
})
	
}

function renamepic(){  //重命名图片
	
		var objs=$("#J_Picture").find(".ui-selected").find(".base-msg").find("input");
		objs.show();	
		objs.focus();
	
}


$("li.rename").click(function(){   //点击重命名按钮
	if($("#J_Picture").find(".ui-selected").length==0){
		renamefile();
	}
	else {
		renamepic();
	}
})

$(".folder-name").click(function(){ //重命名文件夹
	
	renamefile();
	
})
$("#J_SelectBar li.delete").click(function(){   //删除
	
	$("#J_Modal").addClass("in");
	$("#J_Modal").find(".modal-title").text("删除文件");
	$("#J_Modal .modal-body").html('<div class="delete-file">请确定是否删除选中文件或文件夹（删除文件夹会删除包含的所有图片）?</div>');
	$("#J_Modal").show();
	$("#J_Modal .modal-footer").show();
	close_modal();
	$(".modal-footer .btn-primary").one('click',function(e){

		
		e.stopPropagation();
		if($("#J_Picture").find(".ui-selected").length==0){ 
			var id=$(".home").data('id');
        	var urls='/admin/piczone/delDir?ids='+id;
		}
		else {
			var ids=getselectids();
			var urls='/admin/piczone/delPic?ids='+ids;
		}
		$.ajax({
			type:"get",
			url:urls,
			success:function(){
				
				$(".ui-selected").remove()
			}
		});
	$("#J_Modal").removeClass("in");
	$("#J_Modal").hide();
	$("#J_Infor_Box").text("删除成功").css({"opacity":1,"z-index":0});
	setTimeout('$("#J_Infor_Box").css({"opacity":0,"z-index":0})',1000)
	return false;
		
	})

	
	
})

$("#J_SpecForm").click(function(){return false;}) 
$("#J_SearForm .search-btn").click(function(){ //搜索
	get_json();
	$("#J_Crumbs").text($("#J_SearForm .search-form .search-input").val()+'的搜索结果');
}) 
$("#J_SelectAll").click(function(){  //全选
	if($(this).is(":checked")){
		$("#J_Picture").find(".item").addClass("ui-selected");
		$("#J_SortBar").hide();
		if($("#J_Picture").find(".folder").length>0){
					$("#J_ControlBar .move,#J_ControlBar .delete").show();
		}
		else {
			$("#J_ControlBar .move,#J_ControlBar .delete,#J_ControlBar .copy,#J_ControlBar .tophone").show();
		}

			
	}
	else {
		$("#J_Picture").find(".item").removeClass("ui-selected");
		$("#J_SortBar").show();
		$("#J_ControlBar li").hide();
	}
	
})
function get_json(cat){ //搜索
	
	var cat=$(".list-head").data('cat')
	var sort=$("#J_Sort a.dropdown-toggle").attr("data-sort");
	var field=$("#J_Sort a.dropdown-toggle").attr("data-type");
	var keywords=$("#J_SearForm .search-form .search-input").val()?$("#J_SearForm .search-form .search-input").val():'';
	var url='/admin/piczone/getpic.html?id='+cat+'&order='+sort+'&ignore_cat='+cat+'&field='+field+'&keywords='+keywords
	init_data('',url,1)
	
}

$("#J_SortBar .my-dropdown").mouseenter(function(){  
	$(this).find(".dropdown-menu").show();
})
$("#J_SortBar .my-dropdown").mouseleave(function(){
	$(this).find(".dropdown-menu").hide();
})

$("#J_Sort .dropdown-menu li").click(function(){
	var strings=$(this).find("a").attr("class").split(" ")[1];
	var data_type=$(this).find("a").attr("data-type");
	var data_sort=$(this).find("a").attr("data-sort");
	$("#J_Sort .dropdown-toggle").text($(this).text()).removeClass("up down").addClass(strings);
	$("#J_Sort .dropdown-toggle").attr({"data-type":data_type});
	$("#J_Sort .dropdown-toggle").attr({"data-sort":data_sort});
	
	get_json()
	
	
})
$("#J_ShowList").click(function(){  //列表显示
	
	$("#J_PicContainer").addClass("list-show");
	
})
$("#J_ShowPic").click(function(){  //大图显示
	
	$("#J_PicContainer").removeClass("list-show");
	
})
function select_elements(){
	
	$("#J_Picture .item").click(function(){  //单击文件夹或者图片
	$("#J_Picture").click(function(e){
	if(!$(e.target).is("#J_Picture .ui-selected,#J_Picture .ui-selected *,#J_SelectBar *"))	
	{
		// $("#J_Picture .item").removeClass("ui-selected");
		//$("#J_ControlBar li").hide();
	}
	
	})
//	$(this).addClass("ui-selected").siblings().removeClass("ui-selected");
	$(this).toggleClass("ui-selected")
	if($(".ui-selected").length==0){
					$("#J_ControlBar li").hide();
					$("#J_ControlBar .move,#J_ControlBar .delete,#J_ControlBar .rename").show();
			
		}
		else if($(".ui-selected").length==1){
			 {
			$("#J_ControlBar li").css('display','block');
			var oldid=getselectids();
			var uploader1 = WebUploader.create({
        // 选完文件后，是否自动上传。
        auto: true,
        // swf文件路径
        swf: '/public/static/admin/js/webuploader/Uploader.swf',
        // 文件接收服务端。
        server: '/admin/upload/replaceFile?oldid='+oldid,
        // 选择文件的按钮。可选。
        // 内部根据当前运行是创建，可能是input元素，也可能是flash.
        pick:  '.replace',
        duplicate:true,//允许重复上传

        // 只允许选择图片文件。
        accept: {
            title: '请选择要上传的图片',
            extensions: filetypes, //'gif,jpg,jpeg,bmp,png,ico';
            mimeTypes: '*'
        },
        
    });
     uploader1.on( 'uploadSuccess', function( file , response) {
       location.reload();
    });
			$("#J_Picture").find(".ui-selected").find(".base-msg").find(".img-name").click(function(){
					renamepic();
	})
			
		}
		}
		else {
			$("#J_ControlBar .rename,#J_ControlBar .replace").hide();
		}
	$("#J_SelectAll").attr({"checked":false})
	
})
}

function close_modal(){
	
	$(".modal-header .close").click(function(){  //关闭弹窗
	$(".modal").hide();
	
})
	$(".modal-footer .btn-default").click(function(){
		
		$(".modal").hide();
	})
	
}

$("#J_UpAndNew .new").click(function(){
	$("#J_Modal").addClass("in");
	$("#J_Modal").find(".modal-title").text("新建文件夹");
	$("#J_Modal").find(".modal-subTitle").text("请输入文件夹名称");
	$("#J_Modal .modal-body").html('<div class="new-folder"><input type="text" id="J_NewFoldername" tabindex="1" placeholder="新建文件夹"></div>');
	$("#J_Modal").show();
	close_modal();
	$("#J_ModalSure").on('click',function(){
		var cat_id=$(".list-head").data("cat");
		var siblingsName=$("#J_Picture").find(".folder-name").text();
		if($("#J_NewFoldername").val()==""){
			$(".modal-content .modal-msg").text("名称不能为空").show();
			setTimeout(function(){$(".modal-content .modal-msg").hide()},1000)
			return false;
		}
		if(!(siblingsName.indexOf($("#J_NewFoldername").val())==-1))
		{
			$(".modal-content .modal-msg").text("新建失败: 在同一个目录下有相同的名字").show();
			setTimeout(function(){$(".modal-content .modal-msg").hide()},1000);
			return false;
			
		}
		var newName=$("#J_NewFoldername").val();
		$.ajax({
			type:"get",
			url:'/admin/piczone/createDir?cmd=json_folder_insert&pid='+cat_id+'&dirname='+newName+'&_input_charset=utf-8',
			success:function(){
				 location.reload()
			}
		});
		
		$("#J_Modal").removeClass("in").hide();
		$(".modal-content .modal-msg").text("新建成功").css({"opacity":1,"z-index":0});
		setTimeout('$("#J_Infor_Box").css({"opacity":0,"z-index":0})',1000)
		return false;
	})
	
})


 
 $(function() {
        var search = function(e) {
          var pattern = $('#input-search').val();
          var options = {
            ignoreCase: $('#chk-ignore-case').is(':checked'),
            exactMatch: $('#chk-exact-match').is(':checked'),
            revealResults: $('#chk-reveal-results').is(':checked')
          };

        }

        $('.search-btn').on('click', search);
        $('#input-search').on('keyup', search);

        $('#btn-clear-search').on('click', function (e) {
          $searchableTree.treeview('clearSearch');
          $('#input-search').val('');
          $('#search-output').html('');
        });





        var findSelectableNodes = function() {
         
        };
       

      

	});
function init_data(id,url,page){
	var id=id? id:1
	if(!url){
		var url='/admin/piczone/getpic.html?id='+id+'&page='+page
	}
	
	
	$("#J_Picture .wrap").html('');
	$.ajax({
		type:"get",
		url:url,
		success:function(result){
			if(result.totalpage>0){
				$(".list-head").data({"cat":result.pics.data[0].dir_id})
			var mylist=result.pics.data;
			var domains="http://"+window.location.host;
			for (var list in mylist)
		
			{   var ins=mylist[list].isUsed ? " in":""
				if(mylist[list].isFloder) {
				var flags="folder";
				var names="folder-name";
				var containers="folder-msg";
				var without='<div class="without-img"></div></div><div class="'+names+'" title="'+mylist[list].oldname+'" data-id="'+mylist[list].id+'">'+mylist[list].oldname+'</div>';
				}
				else {
				var flags="image";
				var names="img-name";
				var containers="img-container";
				var without='<img src="'+mylist[list].fileurl+'" alt=""></div><div class="'+names+'" title="'+mylist[list].oldname+'" data-id="'+mylist[list].id+'">'+mylist[list].oldname+'</div>'
				
				}
				var mystr='<div class="item ui-widget-content ui-selectee">';
				mystr+='<div class="'+flags+'">';
				mystr+='<div class="base-msg">';
				mystr+='<div class="'+containers+'">';
				mystr+=without;
				mystr+='<input type="text" value="'+mylist[list].oldname+'">';
				mystr+='<div class="qout icon'+ins+'"></div>';
				mystr+='<ul class="handle clearfix">';
				mystr+='<li class="copy-link" data-clipboard-text="'+domains+mylist[list].fileurl+'" title="复制链接"><span class="icon"></span></li>';
				mystr+='<li class="copy-code" data-clipboard-text="'+domains+mylist[list].fileurl+'" title="复制代码"><span class="icon"></span></li>';
				mystr+='</ul>';
				mystr+='</div>';
				mystr+='<div class="out">jpg</div>';
				mystr+='<div class="out">'+mylist[list].filesize+'</div>';
				mystr+='<div class="out">'+mylist[list].uploadtime+'</div>';
				mystr+='</div>';
				mystr+='</div>';
			    $("#J_Picture .wrap").append(mystr);
				
			}
			$('.home').text(result.dirname);
			$('.home').data('id',result.pics.data[0].dir_id)
			$(".page a").text(result.pics.current_page);
			if(result.pics.current_page==result.totalpage){
        				$(".servernext").addClass("disabled");
        				$(".servernext").parents().addClass("disabled")
        			}
        			if(result.pics.current_page==1){
        				
        				$(".serverprevious").parents().addClass("disabled");
        				$(".serverprevious").addClass("disabled")
        			}
		}
		},
			
		error:function(result){
			
			
		}
		,
		async:false
	});
	select_elements();
	renameall();
	$("#J_Picture .item").on("mouseenter",function(){
	
	$(this).find("ul.handle").css({"bottom":0})
	
	
})
$("#J_Picture .item").on("mouseleave",function(){
	
	$(this).find("ul.handle").css({"bottom":-30})
	
	
})
}


$("body").on('click','li.copy-pic',function(){
	
	$("#J_Modal").addClass("in");
	$("#J_Modal").find(".modal-title").text("复制");
	$("#J_Modal .modal-body").html('<div class="delete-file">请确定是否删除选中文件夹及包含的所有图片? <span>(删除的图片7天内可以在回收站内还原)</span></div>');
	$("#J_Modal").show();
	close_modal();
	return false
})
$("body").on('click','li.copy-link',function(){
	var links=$(this).data('clipboard-text')
	$("#J_Modal").addClass("in");
	$("#J_Modal").find(".modal-title").text("复制图片链接");
	$("#J_Modal .modal-body").html('<div class="delete-file"><textarea style="width:100%;height:50px">'+links+'</textarea></span></div>');
	$("#J_Modal .modal-footer").hide();
	$("#J_Modal").show();
	close_modal();
	return false

	
})
$("body").on('click','li.copy-code',function(){
	var links=$(this).data('clipboard-text')
	var code='<img src="'+links+'">'
	$("#J_Modal").addClass("in");
	$("#J_Modal").find(".modal-title").text("复制图片代码");
	$("#J_Modal .modal-body").html('<div class="delete-file"><textarea style="width:100%;height:50px">'+code+'</textarea></span></div>');
	$("#J_Modal .modal-footer").hide();
	$("#J_Modal").show();
	close_modal();

	
})

$(window).resize(function(){
	
	var flashUpBtn=($(window).width()-600)/2+420;
	$(".flash-up-btn").css({"left":flashUpBtn})
   
	
})
$(window).load(function(){
	
	var flashUpBtn=($(window).width()-600)/2+420;
	$(".flash-up-btn").css({"left":flashUpBtn})
   
	
})

$("ul.handle li").click(function(){
	
	return false;
	
})
      $(function(){   //点击分页
      	$("body").on('click','.servernext:not(".disabled")',function(){	
      		init_data('','',parseInt($('.page').text())+1)
      		$(".serverprevious ").parents().removeClass('disabled');
      		$(".serverprevious ").removeClass('disabled')
      	})
      	$("body").on('click','.serverprevious:not(".disabled")',function(){	
      		init_data('','',parseInt($('.page').text())-1)
      		$(".servernext").parents().removeClass('disabled');
      		$(".servernext").removeClass('disabled')
      	})
  

      	
      	
      })
