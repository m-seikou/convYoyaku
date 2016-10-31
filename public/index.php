<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="ja">
<head>
	<META HTTP-EQUIV="Content-type" CONTENT="text/html; charset=shift-jis">
	<META HTTP-EQUIV="Content-Style-Type" content="text/css">
	<link rel="stylesheet" type="text/css" href="./sample.css">
	<title>受付中イベント一覧</title>
</head>
<body>
<h1>受付中イベント一覧</h1>
<?php
require_once '../config.php';
require_once '../lib/loader.php';
// DBに接続
$mysqli = new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);
if (mysqli_connect_errno()) {
	$error_msg = "データベース停止中。現在使用できません。";
} else {
	$mysqli->set_charset(DB_CHAR);
	$mysqli->query("LOCK TABLES trans reserve WRITE,event WRITE;");
}

	$SQL = "select event_id,name from event where st_date <= CURDATE() and CURDATE() <= ed_date;";
	$OUT = $mysqli->query($SQL);
	while ( $ROW = $OUT->fetch_object() ) {
		$url = preg_replace("/[\d\w.]+$/","",$_SERVER['SCRIPT_NAME']);
?>
	<a href="<?php print_html($url); ?>regist.php?c=0&s=0&e=<?php print_html($ROW->event_id); ?>"><?php print_html($ROW->name); ?></a><br>
<?php
	}
?>
