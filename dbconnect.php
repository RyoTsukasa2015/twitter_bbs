<?php
	$db = mysqli_connect('localhost','root','seedkun','twitter_bbs')or die(mysqli_connect_error());
	mysqli_set_charset($db,'utf8');
//requie ファイルを参照するということ
//require_once 一回だけファイルを参照できる
?>