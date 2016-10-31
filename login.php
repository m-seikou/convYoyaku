<?php
/*
ユーザー管理テーブル
email		メールアドレス(ユーザーIDとして使用)
passwd		パスワード
name		管理者名(表示用)
time_stamp	更新日時

drop table manager;
create table manager(
	email		varchar(255) not null,
	passwd		varchar(255) not null,
	name		varchar(255) not null,
	ses_id		varchar(40) default NULL,
	time_stamp	timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
	PRIMARY KEY  (email)
);
insert into manager values('angelan@mihoshi.info','hogehoge','命守 星光',default,default);
*/
//共通関数をインポート
require ('./functions.php');
require ('./forms.php');
// DBに接続
$mysqli = new mysqli("localhost","yoyaku","hogefuga","yoyaku");
if (mysqli_connect_errno()) {
	$error_msg = "データベース停止中。現在使用できません。";
	return;
} else {
	$mysqli->set_charset(DB_CHAR);
}
$email = $_REQUEST['email'];
$passwd = $_REQUEST['passwd'];

// パスワードのチェック
$SQL = sprintf("select count(email) as cnt from manager where email = %s and passwd = %s;",
		mysql_esc($email),
		mysql_esc($passwd));
// OKの場合、ログインクッキーを発行(更新)し、管理画面へ。
if ($mysqli->query($SQL)->fetch_object()->cnt == 1){
	$k = create_sesID("hogehoge",$email);
	$SQL = sprintf("update manager set ses_id = %s where email = %s;",
			mysql_esc($k),
			mysql_esc($email));
	$mysqli->query($SQL);
	setcookie("z",$k,0,"/",$_SERVER['SERVER_NAME']);
	$mysqli->close();
	header( "Location: http://".$_SERVER['SERVER_NAME']."/manage.php?s=0");
}

$mysqli->close();

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="ja">
<head>
	<META HTTP-EQUIV="Content-type" CONTENT="text/html; charset=shift-jis">
	<META HTTP-EQUIV="Content-Style-Type" content="text/css">
	<link rel="stylesheet" type="text/css" href="./sample.css">
	<title>予約管理</title>
</head>
<body>
	<h1>予約管理</h1>
	<form action="<?php print_html($_SERVER['SCRIPT_NAME']); ?>" method="post">
		メールアドレス<input type="text" name="email"/><br/>
		パスワード<input type="text" name="passwd"/><br/>
		<input type="submit" value="ログイン">
	</form>
</body>
</html>
