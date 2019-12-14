<?php
namespace app\admin\controller;
use think\Db;
class Video extends Base
{
	public function create_cover()
	{
		$from = input("fileurl");  
		$to = "upload/tmp/";
		for ($i=1; $i <10 ; $i++) { 
			$name = rand(1,10000).$i.".png";
			$str = "ffmpeg -i ".$from." -y -f mjpeg -ss ".$i." -t 1 -s 100x100 ".$to.$name;
			system($str);
			$rst[]=$to.$name;
		}
		return json($rst);
	}
}