<?php
/*
受け取り変数
	e	event_id
	c	処理区分
		0:登録
		1:キャンセル
	s:処理の段階
		0:入力フォーム
		1:確認メール送信した
		2:確認画面
		3:登録処理
		4:予約一覧
	name	氏名
	email	メールアドレス
	t	参加形態
		1:GM
		0:PL
	comment	コメント
	key	確認画面



テーブル定義
予約テーブル
email		varchar(255)	メールアドレス
event_id	int				イベントID
name		varchar(255)	名前
GM			int				1:GM/0:PL
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
comment		text,			コメント
time_stamp	datetime		タイムスタンプ
create table event(
	event_id	int	not null AUTO_INCREMENT,
	name		varchar(255) not null,
	email		varchar(255) not null,
	st_date		date not null,
	ed_date		date not null,
	capacity	int not null,
	comment		text,
	time_stamp	timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
	PRIMARY KEY (event_id)
);
insert into event(name,email,st_date,ed_date,capacity)
values('kigenngire','angelan@mihoshi.info','2008-01-01','2008-03-31',30);
*/
//共通関数をインポート
require_once '../config.php';
require_once '../lib/loader.php';

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

// イベントID
$e = $_REQUEST['e'];
if ( !preg_match( '/^[\d]+$/'  , $e ) ){
	$error_msg = "入力エラー:e1";
}else{
	$SQL = sprintf("select if(st_date <= CURDATE() and CURDATE() <= ed_date,true,false) as date,st_date,ed_date from event where event_id = %s;",mysql_esc($e));
	if( $mysqli->query($SQL)->num_rows != 1){
		$error_msg = "入力エラー:e2";
	}
	$ROW = $mysqli->query($SQL)->fetch_object();
	$st_date = $ROW->st_date;
	$ed_date = $ROW->ed_date;
	if( !$ROW->date ){
		$error_msg  = "現在、このイベントは予約を受け付けていません。<br/>";
		$error_msg .= "受付期間は $st_date 〜 $ed_date です。<br/>";
	}
	$logfile = preg_replace("/[\d\w.]+$/","",$_SERVER['SCRIPT_FILENAME']).
			"log/event." . $e . ".log";
}
// 処理の区分
$c = $_REQUEST['c'];
if ( $c != 0 && $c != 1 ){ $error_msg = "入力エラー:c";}

// 処理の段階
$s = $_REQUEST['s'];
if ( !preg_match( '/^[0123456]$/'  , $s ) ){ $error_msg = "入力エラー:s";}

//  参加形態
$t = $_REQUEST['t'];
if ( $t != "" && $t != 0 && $t != 1 ){ $error_msg = "入力エラー:t"; }

//  確認画面のkey
$k = $_REQUEST['k'];
if ( preg_match( "/[^0-9a-zA-Z]/" , $k ) ){
	$error_msg = "入力エラー";
}else if ( $k != "" ){
	$SQL = sprintf("select email from reserve where ses_ID = %s;",mysql_esc($k));
	if( $mysqli->query($SQL)->num_rows != 1){
		$error_msg = "入力エラー:k";
	}
}

//  氏名
$name = $_REQUEST['name'];

//  メールアドレス
$email = $_REQUEST['email'];

//  コメント
$comment = $_REQUEST['comment'];

if ( $error_msg != "" ){
	print $error_msg;
	return;
}

if ( $s == 0 ){
	print_input_form($e,$c);
	if ( $c == 0 ){reserve_list($e);}
} else if ( $s == "1" ){
	if ( $c == 0 ){
		// 登録確認メールの送信
		// 未入力チェック
		if(strlen($name)==0){
			print "入力エラー:氏名が入力されていません";
			return;
		}
		if(strlen($t)==0){
			print "入力エラー:GM/PLが選択されていません。";
			return;
		}

		// メールアドレスの重複チェック
		$SQL = sprintf("select email from reserve " .
				"where email = %s and event_id = %s;",
				mysql_esc($email),mysql_esc($e));
		$OUT = $mysqli->query($SQL);
		$ROW = $OUT->fetch_object();
		if ( $OUT->num_rows == 0 ){
			// 重複なし 登録待ち

			// キーの生成
			$k = create_sesID($e,$email);

			$SQL = sprintf("insert into reserve(email,event_id,name,GM,comment,status,ses_id)
					values( %s,%s,%s,%s,%s,'0',%s );",
					mysql_esc($email),
					mysql_esc($e),
					mysql_esc($name),
					mysql_esc($t),
					mysql_esc($comment),
					mysql_esc($k));
			$OUT = $mysqli->query($SQL);
			logger("$email の予約を受付けました。");
		} else {
			?>現在、このメールアドレスは登録できません。<?php
			return;
		}
		$C="今回はご予約、ありがとうございます。";
	} else {
		// キャンセルの場合はメールアドレスの存在をチェック
		$SQL = sprintf("select email,ses_ID from reserve " .
				"where event_id=%s and email = %s;",
				mysql_esc($e),mysql_esc($email));
		$OUT = $mysqli->query($SQL);
		if( $OUT->num_rows != 1 ){
			?>このメールアドレスは処理できません。<?php
			return;
		}
		$k = $OUT->fetch_object()->ses_ID;
		$C="今回のイベントのキャンセル処理を行います。";
	}

	/* 確認メール送信処理 */
	$preferences = array(
			"input-charset" => "shift-jis",
			"output-charset" => "iso-2022-jp",
			"line-length" => 76,
			"scheme" => "B",
			"line-break-chars" => "\n"
	);
	$event_name=getEventName($e);
	$subject = $event_name . "への予約";
	$subject = iconv_mime_encode( "subject" , $subject, $preferences);
	$subject = preg_replace  ( "/^[^=]+/" , ""  , $subject );
	$msg = "こんにちは、" . $event_name . "です。
$C

以下のURLから予約手続きを引き続き行ってください。
http://{$_SERVER['SERVER_NAME']}{$_SERVER['SCRIPT_NAME']}?s=2&k=$k&c=$c&e=$e

このメールに心当たりの無い方はメールを破棄してください。
";
	$res =  mail( $email , $subject , $msg , "From:no-reply@mihoshi.info\r\n" );
	if ( $res ){
		print_html("確認のメールを$email へ送りました。");
	}
} else if ( $s == "2" ){
	print_confirm_form($e,$k,$c);
} else if ( $s == "3" ){
	if ( $c == 0 ){
		if (getEventCapacity($e) > getEventNumber($e)){
		// 登録完了
			$SQL = sprintf("update reserve set status = 1 where ses_ID = %s;",
					mysql_esc($k));
			$msg="予約を受付けました。";
			logger(getEmailFromSesId($k)." の予約を完了しました。");
		}else{
			$SQL = sprintf("update reserve set status = 2 where ses_ID = %s;",
					mysql_esc($k));
			$msg="予約が埋まっています。キャンセル待ちとして受付けました。\nキャンセルが出た際に、改めてお知らせします。";
			logger(getEmailFromSesId($k)." の予約をキャンセル待ちで受付けました。");
		}
		$mysqli->query($SQL);
	}else{
		// キャンセル完了
		$SQL = sprintf("delete from reserve where ses_ID = %s;",mysql_esc($k));
		logger(getEmailFromSesId($k)." の予約をキャンセルしました。");
		$mysqli->query($SQL);
		$msg="予約をキャンセルしました。";
		// キャンセル待ちの受付け処理
		$SQL = sprintf("select email,ses_id from reserve where event_id = %s and status = 2 order by time_stamp desc limit 1;",mysql_esc($e));
		$OUT=$mysqli->query($SQL);
		if( $OUT->num_rows == 1 ){
			$ROW = $mysqli->query($SQL)->fetch_object();
			$reg_ses_id = $ROW->ses_id;
			$reg_email = $ROW->email;
			$SQL = sprintf("update reserve set status = 1 where ses_ID = %s;",
					mysql_esc($reg_ses_id));
			$mysqli->query($SQL);
			logger($reg_email." のキャンセル待ちから予約済みにしました。");

			/* 確認メール送信処理 */
			$preferences = array(
					"input-charset" => "shift-jis",
					"output-charset" => "iso-2022-jp",
					"line-length" => 76,
					"scheme" => "B",
					"line-break-chars" => "\n"
			);
			$event_name=getEventName($e);
			$subject = $event_name . "への予約";
			$subject = iconv_mime_encode( "subject" , $subject, $preferences);
			$subject = preg_replace  ( "/^[^=]+/" , ""  , $subject );
			$body = "こんにちは、" . $event_name . "です。
キャンセルが出ましたので、予約を受付けました。

このメールに心当たりの無い方はメールを破棄してください。
";
			$res =  mail( $reg_email , $subject , $body ,
					"From:no-reply@mihoshi.info\r\n" );
		}
	}
	print($msg);
} else if ( $s == "4" ){
	// 予約一覧
	$SQL = sprintf("select name,email,GM,comment from reserve where event_id = %s;",
			mysql_esc($e));
	$OUT = $mysqli->query($SQL);
?>
	<table border="1">
		<tr><th>名前</th><th>mail address</th><th>GM?</th><th>コメント</th></tr>
<?php
	while ( $ROW = $OUT->fetch_object() ) {
		$name = $ROW->name;
		$email = $ROW->email;
		if( $ROW->GM == 0 ){
			$t = "PLとして参加";
		}else{
			$t = "GMとして参加";
		}
		$comment = $ROW->comment;

?>
		<tr><td><?php print_html($name); ?></td><td><?php print_html($email); ?></td><td><?php print_html($t); ?></td><td><?php print_html($comment); ?></td></td>
<?php
	}
?>
	</table>
<?php
}
$mysqli->query("UNLOCK TABLES;");
$mysqli->close();
?>
</body>
</html>
