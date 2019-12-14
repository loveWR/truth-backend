<?php
namespace plugins;

class GoodBye
{
	public function run(&$params)
	{
		echo "<script>alert('GoodBye')</script>";
	}
}