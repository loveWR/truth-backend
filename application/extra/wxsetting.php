<?php  
/**
 * 微信小程序配置
 */

return [
	'app_id'=>'wx2cd50ad2815d5baf',
	'app_secret'=>'18f3760e34ea309636cb2c3076a1e423',
	'token_url'=>'https://api.weixin.qq.com/sns/jscode2session?appid=%s&secret=%s&js_code=%s&grant_type=authorization_code',
	'wxlogin_url'=>'https://open.weixin.qq.com/connect/oauth2/authorize?appid=%s&redirect_uri=%s&response_type=code&scope=snsapi_userinfo&state=1',
	'openid_url'=>"https://api.weixin.qq.com/sns/oauth2/access_token?appid=%s&secret=%s&code=%s&grant_type=authorization_code"
];