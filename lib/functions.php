<?php
/*
	対SQLインジェクションのサニタイズ
*/
function mysql_esc($value){
global $mysqli;
	// Stripslashes
	if (get_magic_quotes_gpc()) {
		$value = stripslashes($value);
	}
	// 数値以外をクオートする
	if (!is_numeric($value)) {
		$value = "'" . $mysqli->real_escape_string($value) . "'";
	}
	return $value;
}
/*
	対クロスサイトスクリプティングのサニタイズ
*/
function print_html($value){
	$value = htmlspecialchars($value);
	$value = preg_replace("/\r?\n/","<br>\n",$value);
	print($value);
	return $value;
}

/*
	event_id->name
*/
function getEventName($event_id){
global $mysqli;
	$SQL = sprintf("select name from event where event_id = %s;",mysql_esc($event_id));
	$name = $mysqli->query($SQL)->fetch_object()->name;
	return $name;
}
/*
	event_id->capacity
*/
function getEventCapacity($event_id){
global $mysqli;
	$SQL = sprintf("select capacity from event where event_id = %s;",
			mysql_esc($event_id));
	$capacity = $mysqli->query($SQL)->fetch_object()->capacity;
	return $capacity;
}
/*
	event_id->comment
*/
function getEventComment($event_id){
global $mysqli;
	$SQL = sprintf("select comment from event where event_id = %s;",
			mysql_esc($event_id));
	$comment = $mysqli->query($SQL)->fetch_object()->comment;
	return $comment;
}
/*
	event_id->参加人数
*/
function getEventNumber($event_id){
global $mysqli;
	$SQL = sprintf("select count(*) as cnt from reserve where event_id = %s and status = 1;",
			mysql_esc($event_id));
	$cnt = $mysqli->query($SQL)->fetch_object()->cnt;
	return $cnt;
}
/*
	event_id->キャンセル待ち
*/
function getEventWaitCancel($event_id){
global $mysqli;
	$SQL = sprintf("select count(*) as cnt from reserve where event_id = %s and status = 2;",
			mysql_esc($event_id));
	$cnt = $mysqli->query($SQL)->fetch_object()->cnt;
	return $cnt;
}
/*
	ses_ID->参加形態
*/
function getEntryTypeFromSesId($ses_ID){
global $mysqli;
	$SQL = sprintf("select GM from reserve where ses_ID = %s;",mysql_esc($ses_ID));
	$type = $mysqli->query($SQL)->fetch_object()->type;
	return $type;
}
/*
	ses_ID->email
*/
function getEmailFromSesId($ses_ID){
global $mysqli;
	$SQL = sprintf("select email from reserve where ses_ID = %s;",mysql_esc($ses_ID));
	$email = $mysqli->query($SQL)->fetch_object()->email;
	return $email;
}
/*
	logを書く
*/
function logger($msg){
global $logfile;
	$fh = fopen($logfile, "a");
	if($fh){
		fwrite($fh,date("[Y-m-d G:i:s]").$msg."\n");
		fclose($fh);
	}else{
		print("ファイルオープンエラー");
	}
}
/*
	ses_IDを生成
*/
function create_sesID($e,$email){
global $mysqli;
	$k = "";
	while ( $k == "" ){
		$k = hash("sha1",mt_rand()."g00d1uck".$e."ra93ten".$email);
		$SQL = sprintf("select count(email) as cnt from reserve where ses_ID = %s;",
				mysql_esc($k));
		if ( $mysqli->query($SQL)->fetch_object()->cnt != 0 ){
			$k = "";
			continue;
		}
		$SQL = sprintf("select count(email) as cnt from manager where ses_ID = %s;",
				mysql_esc($k));
		if ( $mysqli->query($SQL)->fetch_object()->cnt != 0 ){
			$k = "";
			continue;
		}
	}
	return $k;
}
/*
	ログイン状態のチェック
*/
function loginStatus(){
global $mysqli;
	$SQL = sprintf("select email from manager where ses_ID = %s;",
			mysql_esc($_COOKIE['z']));
	$email = $mysqli->query($SQL)->fetch_object()->email;
	if ( $email == "" ){
		return false;
	}else{
		return $email;
	}
}
/*
	ログインクッキー->email
	"" を返す場合はログインしてない。
*/
function getEmailFromCookie(){
global $mysqli;
	$SQL = sprintf("select email from manager where ses_ID = %s;",
			mysql_esc($_COOKIE['z']));
	$email = $mysqli->query($SQL)->fetch_object()->email;
	return $email;
}

?>