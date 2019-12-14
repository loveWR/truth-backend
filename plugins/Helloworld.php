<?php
namespace plugins;

class Helloworld
{
	public function run(&$params)
	{
		echo "<script>alert('hello world')</script>";
	}
}