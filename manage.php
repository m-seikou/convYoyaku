<?php
/*
受け取り変数
	e	event_id

テーブル定義
予約テーブル
email		varchar(255)	メールアドレス
event_id	int				イベントID
name		varchar(255)	名前
GM			int				GM/PL
comment		text			コメント
status		int				0:メール送信後 1:登録済み 2:キャンセル待ち
ses_ID		varchar(40)		セッションID
time_stamp	datetime		タイムスタンプ
event_idとemailでPK
name にindex
ses_ID にindex

drop table reserve;
create table reserve(
	email		varchar(255) not null,
	event_id	int not null,
	name		varchar(255) not null,
	GM			int not null,
	comment		text,
	status		int not null,
	ses_id		varchar(40),
	time_stamp	timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
	PRIMARY KEY  (email,event_id),
	KEY K_NAME (name),
	KEY K_SES_ID (ses_id)
);

イベントテーブル
event_id	int PK autoinc
name		varchar(255)	名前
st_date		datet			受付け開始日
ed_date		datet			受付け終了日
capacity	int				定員
time_stamp	datetime		タイムスタンプ
create table event(
	event_id	int	not null AUTO_INCREMENT,
	name		varchar(255) not null,
	email		varchar(255) not null,
	st_date		date not null,
	ed_date		date not null,
	capacity	int not null,
	time_stamp	timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
	PRIMARY KEY (event_id)
);
insert into event(name,email,st_date,ed_date,capacity)
values('kigenngire','angelan@mihoshi.info','2008-01-01','2008-03-31',30);
*/
//共通関数をインポート
require ('./functions.php');
require ('./forms.php');
require_once 'config.php';

// エラーメッセージ
$error_msg = "";

// DBに接続
$mysqli = new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);
if (mysqli_connect_errno()) {
	$error_msg = "データベース停止中。現在使用できません。";
} else {
	$mysqli->set_charset(DB_CHAR);
	$mysqli->query("LOCK TABLES trans reserve WRITE,event WRITE;");
}

// ゴミ掃除
$SQL = "delete from reserve where status = 0 and ADDTIME(time_stamp,'24:00:00')<= CURDATE();";
$mysqli->query($SQL);

$email = loginStatus();
if(!$email){
	$error_msg = "ログインしていません。";
}
//入力チェック
// 処理の段階
$s = $_REQUEST['s'];
if ( !preg_match( '/^[01234567]$/'  , $s ) ){ $error_msg = "入力エラー:s";}

// event_id
$e = $_REQUEST['e'];
if($e == ""){
	if($s != 0 ){
		$error_msg = "入力エラー:e0";
	}
}else if ( !preg_match( '/^[\d]+$/'  , $e ) ){
	$error_msg = "入力エラー:e1";
}else{
	$SQL = sprintf("select event_id from event where event_id = %s and email = %s;",
			mysql_esc($e),
			mysql_esc($email));
	if( $mysqli->query($SQL)->num_rows != 1 && $e != 0){
		$error_msg = "入力エラー:e2";
	}
	$ROW = $mysqli->query($SQL)->fetch_object();
	$st_date = $ROW->st_date;
	$ed_date = $ROW->ed_date;
	$logfile = preg_replace("/[\d\w.]+$/","",$_SERVER['SCRIPT_FILENAME']).
			"event." . $e . ".log";
}

// 予約者のメールアドレス
$reserv_email = $_REQUEST['reserv_email'];

// フォーム入力値

switch($s){
case 3:
	$status = $_REQUEST['status'];
	if ( !$status == "" && preg_match( '/[^0123]/'  , $status ) ){
		$error_msg = "入力エラー:status";
	}
	break;
case 7:
	$reg_name = $_REQUEST['reg_name'];
	if ( $reg_name == "" ){
		$error_msg = "入力エラー:イベント名が空白です。";
	}
	$reg_st_date = $_REQUEST['reg_st_date'];
	if ( !preg_match( '/^\d\d\d\d-\d\d-\d\d$/'  , $reg_st_date ) ){
		$error_msg = "入力エラー:募集開始日を正しく入力してください。";
	}
	$reg_ed_date = $_REQUEST['reg_ed_date'];
	if ( !preg_match( '/^\d\d\d\d-\d\d-\d\d$/'  , $reg_ed_date ) ){
		$error_msg = "入力エラー:募集締切日を正しく入力してください。";
	}
	$reg_capacity = $_REQUEST['reg_capacity'];
	if ( !preg_match( '/^\d+$/'  , $reg_capacity ) ){
		$error_msg = "入力エラー:定員を正しく入力してください。";
	}
	$reg_comment = $_REQUEST['reg_comment'];
	if ( $reg_comment == "" ){
		$error_msg = "入力エラー:コメントが空白です。";
	}
	break;
default:
}

if ( $error_msg != "" ){
	print $error_msg;
	return;
}

switch ( $s ){
case 0:
	// イベント一覧
	print_event_list($email);
	break;
case 1:
	// 予約状況一覧 イベントに対しての予約者の一覧
	print_event_reserve($e);
	break;
case 2:
	// 予約状況変更 予約者のステータス変更。入力画面
	print_event_reserve_status($e,$reserv_email);
	break;
case 3:
	// 予約状況変更 予約者のステータス変更処理、完了画面
	if($status==3){
		$SQL = sprintf("delete from reserve where event_id = %s and email = %s;",
				mysql_esc($e),
				mysql_esc($reserv_email));
		logger("主催者が $reserv_email をキャンセルしました。");
	}else{
		$SQL = sprintf("update reserve set status = %s where event_id = %s and email = %s;",
				mysql_esc($status),
				mysql_esc($e),
				mysql_esc($reserv_email));
		switch ( $status ){
		case 0:
			logger("主催者が $reserv_email を「確認待ち」にしました。");
			break;
		case 1:
			logger("主催者が $reserv_email を「予約済み」にしました。");
			break;
		case 2:
			logger("主催者が $reserv_email を「キャンセル待ち」しました。");
			break;
		}
	}
	$mysqli->query($SQL);
	
	print_event_reserve_status_conf($e);
	break;
case 4:
	// イベントの予約状況のログ
	print_event_reserve_log($e);
	break;
case 5:
	// イベントの新規作成
	print_event_edit(0,$email);
	break;
case 6:
	// イベントの編集
	print_event_edit($e,$email);
	break;
case 7:
	// イベントの編集/更新
	if($e == 0){
		$SQL = "select max(event_id) + 1 as e from event;";
		$e = $mysqli->query($SQL)->fetch_object()->e;
		$logfile = preg_replace("/[\d\w.]+$/","",$_SERVER['SCRIPT_FILENAME']).
				"event." . $e . ".log";
		$SQL = sprintf("insert into event(event_id,email,name,st_date,ed_date,capacity,comment) 
				values(%s,%s,%s,%s,%s,%s,%s);",
				mysql_esc($e),
				mysql_esc($email),
				mysql_esc($reg_name),
				mysql_esc($reg_st_date),
				mysql_esc($reg_ed_date),
				mysql_esc($reg_capacity),
				mysql_esc($reg_comment));
//print($SQL);
		$mysqli->query($SQL);
		logger("イベント ".getEventName($e)." が作成されました。");
	}else{
		$SQL = sprintf("update event set name = %s, st_date = %s, ed_date = %s, capacity = %s, comment = %s where event_id = %s;",
				mysql_esc($reg_name),
				mysql_esc($reg_st_date),
				mysql_esc($reg_ed_date),
				mysql_esc($reg_capacity),
				mysql_esc($reg_comment),
				mysql_esc($e));
		$mysqli->query($SQL);
		logger("イベント ".getEventName($e)." が更新されました。");
	}
	
	print_event_edit_conf($e);
	break;
default:
}

$mysqli->query("UNLOCK TABLES;");
$mysqli->close();
?>
