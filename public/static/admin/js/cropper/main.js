$(function () {
 'use strict';//表示强规则
 var console = window.console || { log: function () {} };
 var URL = window.URL || window.webkitURL;
 var $image = $('#image');
 var $download = $('#download');
 //获取图片截取的位置
  var $dataX = $('#dataX');
  var $dataY = $('#dataY');
  var $dataHeight = $('#dataHeight');
  var $dataWidth = $('#dataWidth');
  var $dataRotate = $('#dataRotate');
  var $datazoom = $('#dataZoom');
  var $height=$("#cropperHeight");
  var $width=$("#cropperWidth");
 var options = {
 // aspectRatio: 1 / 1, //裁剪框比例1:1
 preview: '.img-preview',
 dragMode:"move",
 crop: function (e) {
  $dataX.val(Math.round(e.x));
  $dataY.val(Math.round(e.y));
  $dataHeight.val(Math.round(e.height));
  $dataWidth.val(Math.round(e.width));
  $dataRotate.val(e.rotate);
  $width.val(Math.round($(this).cropper('getCropBoxData').width));
  $height.val(Math.round($(this).cropper('getCropBoxData').height));
 }
 };
 var originalImageURL = $image.attr('src');
 var uploadedImageURL;
 // Tooltip
 $('[data-toggle="tooltip"]').tooltip();
 // Cropper
 $image.on({
 ready: function (e) {
 },
 cropstart: function (e) {

 },
 cropmove: function (e) {

 },
 cropend: function (e) {
 
 },
 crop: function (e) {

 },
 zoom: function (e) {
 }
 }).cropper(options);
 // Buttons
 if (!$.isFunction(document.createElement('canvas').getContext)) {
 $('button[data-method="getCroppedCanvas"]').prop('disabled', true);
 }
 if (typeof document.createElement('cropper').style.transition === 'undefined') {
 $('button[data-method="rotate"]').prop('disabled', true);
 $('button[data-method="scale"]').prop('disabled', true);
 }

 // Options
 $('.docs-toggles').on('change', 'input', function () {
 var $this = $(this);
 var name = $this.attr('name');
 var type = $this.prop('type');
 var cropBoxData;
 var canvasData;
 if (!$image.data('cropper')) {
 return;
 }
 if (type === 'checkbox') {
 options[name] = $this.prop('checked');
 cropBoxData = $image.cropper('getCropBoxData');
 canvasData = $image.cropper('getCanvasData');
 options.ready = function () {
 $image.cropper('setCropBoxData', cropBoxData);
 $image.cropper('setCanvasData', canvasData);
 };
 } else if (type === 'radio') {
 options[name] = $this.val();
 }
 $image.cropper('destroy').cropper(options);
 });
  //设置宽度高度
  $("#cropperWidth").on("change",function(){
  	var widths=parseInt($(this).val());
  	$image.cropper('setData',{width:widths})
  })
  
    $("#cropperHeight").on("change",function(){
  	var height=parseInt($(this).val());
  	if(1){
  		
  		$image.cropper('setData',{height:height})
  	}
  })
    //旋转角度
    $("#rotate").on("change",function(){
    	
    	var rotate=parseInt($(this).val());
    	$image.cropper('rotateTo',rotate)
    	
    })
  //下拉框选择图片
  $("#chooseSize").on("change",function(){
  	var val=$(this).val();
  	$(this).find("option").each(function(){
  		
  		if($(this).val()==val){
  			$("#cropperWidth").val($(this).data("width"));
  			$("#cropperWidth").change();
  			$("#cropperHeight").val($(this).data("height"));
  			$("#cropperHeight").change();
  		}
  	})
  	
  })
 // Methods
 // 点击开始计算图片位置，获取位置
 $('.docs-buttons').on('click', '[data-method]', function () {
 var $this = $(this);
 var data = $this.data();
 var $target;
 var result;
 if ($this.prop('disabled') || $this.hasClass('disabled')) {
 return;
 }
 if ($image.data('cropper') && data.method) {
 data = $.extend({}, data); // Clone a new one
 if (typeof data.target !== 'undefined') {
 $target = $(data.target);
 if (typeof data.option === 'undefined') {
  try {
  data.option = JSON.parse($target.val());
  } catch (e) {
  console.log(e.message);
  }
 }
 }
 if (data.method === 'rotate') {
 $image.cropper('clear');
 }
 result = $image.cropper(data.method, data.option, data.secondOption);
 if (data.method === 'rotate') {
 $image.cropper('crop');
 }
 switch (data.method) {
 case 'scaleX':
 case 'scaleY':
  $(this).data('option', -data.option);
  break;
 case 'getCroppedCanvas':
 //上传头像
  if (result) {
  var src=$image.attr('src');
  var imgBase=result.toDataURL('image/jpeg');
  var data={imgBase:imgBase,width:$width.val(),height:$height.val(),src:src};
//$.post("/admin/attfile/cropsave",data,function(ret){
//	var obj=JSON.parse(ret)
//if(obj.state=="success"){
//	$image.data('src', obj.data.url);
//}else{
//alert('上传失败');
//}
//},'text');
		var cb=$(this).data("cb");
      $.ajaxSubmitForm({
                url: "/admin/attfile/cropsave",
                param: data,
                success: function(data) {
                    if(cb) {
                        cb(data);
                    }
                }
            });
  }
  break;
 case 'destroy':
  if (uploadedImageURL) {
  URL.revokeObjectURL(uploadedImageURL);
  uploadedImageURL = '';
  $image.attr('src', originalImageURL);
  }
  break;
 }
 if ($.isPlainObject(result) && $target) {
 try {
  $target.val(JSON.stringify(result));
 } catch (e) {
  console.log(e.message);
 }
 }
 }
 });
 // Keyboard
 $(document.body).on('keydown', function (e) {
 if (!$image.data('cropper') || this.scrollTop > 300) {
 return;
 }
 switch (e.which) {
 case 37:
 e.preventDefault();
 $image.cropper('move', -1, 0);
 break;
 case 38:
 e.preventDefault();
 $image.cropper('move', 0, -1);
 break;
 case 39:
 e.preventDefault();
 $image.cropper('move', 1, 0);
 break;
 case 40:
 e.preventDefault();
 $image.cropper('move', 0, 1);
 break;
 }
 });
 // Import image
 var $inputImage = $('#inputImage');
 if (URL) {
 $inputImage.change(function () {
 var files = this.files;
 var file;
 if (!$image.data('cropper')) {
 return;
 }
 if (files && files.length) {
 file = files[0];
 if (/^image\/\w+$/.test(file.type)) {
  if (uploadedImageURL) {
  URL.revokeObjectURL(uploadedImageURL);
  }
  uploadedImageURL = URL.createObjectURL(file);
  $image.cropper('destroy').attr('src', uploadedImageURL).cropper(options);
  $inputImage.val('');
 } else {
  window.alert('Please choose an image file.');
 }
 }
 });
 } else {
 $inputImage.prop('disabled', true).parent().addClass('disabled');
 }
});