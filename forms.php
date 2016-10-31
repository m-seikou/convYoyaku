<?php
/*
	topへのリンクを表示
*/
function print_top_link($e){
	$URL1 = $_SERVER['SCRIPT_NAME'] . "?s=0&c=0&e=" . $e ;
	$URL2 = $_SERVER['SCRIPT_NAME'] . "?s=0&c=1&e=" . $e ;
?>
<a href="<?php print_html($URL1) ?>">予約申し込み画面へ</a><br/>
<a href="<?php print_html($URL2) ?>">予約キャンセル画面へ</a><br/>
<?php
}


/*以下を表示

<h2>現在の予約者</h2>
<table border=1>
<tr><th>名前</th><th>GM/PL</th></tr>
<tr><td>命守 星光</td><td>GM</td></tr>
<tr><td>偽</td><td>PL</td></tr>
</table>
予約者 3名<br/>
受付け人数 30名<br/>
キャンセル待ち ??名<br/>

*/
function reserve_list($event_id){
global $mysqli;
	print("<h2>現在の予約者</h2>\n");
	print("予約者 " . getEventNumber($event_id) . "名<br/>\n");
	print("受付け人数 " . getEventCapacity($event_id) . "名<br/>\n");
	print("キャンセル待ち ". getEventWaitCancel($event_id)."名<br/>\n");
	print("<table border=1>\n");
	print("<tr><th>名前</th><th>GM/PL</th></tr>");
	$SQL = sprintf("select name,email,GM from reserve where event_id = %s and status = 1;",mysql_esc($event_id));
	$OUT = $mysqli->query($SQL);
	while ( $ROW = $OUT->fetch_object() ) {
		print("<tr><td>" . $ROW->name . "</td><td>");
		if( $ROW->GM == 0 ){
			print("PL");
		}else{
			print("GM");
		}
		print("</td></tr>\n");
	}
	print("</table>\n");
}

/*
	入力画面の出力
*/
function print_input_form($e,$c){
	$name=getEventName($e);
	$comment=getEventComment($e);
	if ( $c == 0 ){
		$C = "予約";
	}else{
		$C = "キャンセル";
	}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="ja">
<head>
	<META HTTP-EQUIV="Content-type" CONTENT="text/html; charset=shift-jis">
	<META HTTP-EQUIV="Content-Style-Type" content="text/css">
	<link rel="stylesheet" type="text/css" href="./sample.css">
	<title><?php print_html($name); ?> 事前予約</title>
</head>
<body>
	<h1><?php print_html($name . $C); ?>ページ</h1>
	<?php print_html($comment); ?><hr/>
	必要事項をご記入の上、お申し込み下さい。<br/>
	<br/>
	<form action="<?php print_html($_SERVER['SCRIPT_NAME']); ?>" method="post">
		<input type="hidden" name="e" value="<?php print_html($e); ?>"/>
		<input type="hidden" name="s" value="1"/>
		<input type="hidden" name="c" value="<?php print_html($c); ?>"/>
		<?php if ( $c == 0 ){ ?>
		お名前(必須)<input type="text" name="name"/><br/>
		<?php } ?>
		メールアドレス(必須)<input type="text" name="email"/><br/>
		<?php if ( $c == 0 ){ ?>
		<input type="radio" name="t" value="0" checked />PL参加
		<input type="radio" name="t" value="1"/>GM参加<br/>
		お気づきのことや、伝えておきたいこと。<br/>
		<textarea name="comment"></textarea><br/>
		<?php } ?>
		<?php if ( $c == 0 ){ ?>
		<input type="submit" value="申し込む"/>
		<?php }else{ ?>
		<input type="submit" value="キャンセル"/>
		<?php } ?>
	</form>
<?php
	print_top_link($e);
print("</body></html>");
}
/*
	確認画面の出力
*/
function print_confirm_form($e,$k,$c){
global $mysqli;
	$event_name=getEventName($e);
	if ( $c == 0 ){
		$C = "以下の内容で予約します。";
		$event_name .= " 予約確認";
	}else{
		$C = "以下の予約をキャンセルします。";
		$event_name .= " キャンセル確認";
	}

	$SQL = sprintf("select name,GM,comment,status from reserve where ses_ID = %s;",
			mysql_esc($k));
	$ROW = $mysqli->query($SQL)->fetch_object();
	$name = $ROW->name;
	if( $ROW->GM == 0 ){
		$T = "PLとして参加";
	}else{
		$T = "GMとして参加";
	}
	$comment = $ROW->comment;
	$s = $ROW->status;


?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="ja">
<head>
	<META HTTP-EQUIV="Content-type" CONTENT="text/html; charset=shift-jis">
	<META HTTP-EQUIV="Content-Style-Type" content="text/css">
	<link rel="stylesheet" type="text/css" href="./sample.css">
	<title><?php print_html($event_name); ?> 事前予約</title>
</head>
<body>

	<h1><?php print_html($event_name);?></h1>
	<?php print_html($C);?>よろしければ[確認]を押してください。
	<dl>
	<dt>お名前</dt><dd><?php print_html($name); ?></dd>
	<dt>GM/PL</dt><dd><?php print_html($T); ?></dd>
	<dt>ひとこと</dt><dd><?php print_html($comment); ?></dd>
	</dl>
	<form action="<?php print_html($_SERVER['SCRIPT_NAME']); ?>" method="post">
		<input type="hidden" name="e" value="<?php print($e); ?>"/>
		<input type="hidden" name="k" value="<?php print($k); ?>"/>
		<input type="hidden" name="s" value="3"/>
		<input type="hidden" name="c" value="<?php print_html($c); ?>"/>
		<input type="submit" value="確認"/>
	</form>
<?php
	print_top_link($e);
print("</body></html>");
}
/*
	イベント一覧
*/
function print_event_list($email){
global $mysqli;

	?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="ja">
<head>
	<META HTTP-EQUIV="Content-type" CONTENT="text/html; charset=shift-jis">
	<META HTTP-EQUIV="Content-Style-Type" content="text/css">
	<link rel="stylesheet" type="text/css" href="./sample.css">
	<title>イベント一覧</title>
</head>
<body>
	<h1>イベント一覧</h1>
	<table border="1">
		<tr><th>イベント名</th><th>受付け開始日</th><th>受付け終了日</th><th>定員</th><th>&nbsp;</th></tr>
	<?php

	$SQL = sprintf("select e.event_id as event_id,name,st_date,ed_date,capacity ".
			"from event as e where email = %s order by ed_date;",
			mysql_esc($email));
	$OUT = $mysqli->query($SQL);
	while ( $ROW = $OUT->fetch_object() ) {
		print("		<tr><td><a href=\"");
		print_html($_SERVER['SCRIPT_NAME']);
		print("?s=1&e=");
		print_html($ROW->event_id);
		print("\">");
		print_html($ROW->name);
		print("</a></td><td>");
		print_html($ROW->st_date);
		print("</td><td>");
		print_html($ROW->ed_date);
		print("</td><td>");
		print_html($ROW->capacity);
		print("</td><td><a href=\"");
		print_html($_SERVER['SCRIPT_NAME']);
		print("?s=6&e=");
		print_html($ROW->event_id);
		print("\">編集</a></td></tr>\n");
	}
	?></table><a href="<?php print_html($_SERVER['SCRIPT_NAME']);?>?s=5&e=0">イベントの新規登録</a></body></html><?php
}
/*
	イベント予約状況
*/
function print_event_reserve($e){
global $mysqli;
	$SQL = sprintf("select e.event_id as event_id,e.name as name,st_date,ed_date,capacity,".
			"sum(case when status=0 then 1 else 0 end) as wait,".
			"sum(case when status=1 then 1 else 0 end) as reserved,".
			"sum(case when status=2 then 1 else 0 end) as cancel ".
			"from event as e left join reserve as r USING (event_id) ".
			"where event_id = %s group by event_id,name,st_date,ed_date,capacity;",
			mysql_esc($e));
	$ROW = $mysqli->query($SQL)->fetch_object();
	$url="";
	if($_SERVER['HTTPS'] != ""){
		$url = "https://";
		$port=443;
	}else{
		$url = "http://";
		$port=80;
	}
	$url .= $_SERVER['SERVER_NAME'];
	if($_SERVER['SERVER_PORT'] !=$port ){
		$url .= ":" . $_SERVER['SERVER_PORT'];
	}
	$url .= preg_replace("/[\d\w.]+$/","",$_SERVER['SCRIPT_NAME']).
			"regist.php?s=0&c=0&e=" . $e;

	?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="ja">
<head>
	<META HTTP-EQUIV="Content-type" CONTENT="text/html; charset=shift-jis">
	<META HTTP-EQUIV="Content-Style-Type" content="text/css">
	<link rel="stylesheet" type="text/css" href="./sample.css">
	<title><?php print_html($ROW->name);?>予約状況</title>
</head>
<body>
	<h1><?php print_html($ROW->name);?>予約状況</h1>
	<table border=1>
		<tr>
			<td>予約受付け期間</td>
			<td><?php print_html($ROW->st_date);?>〜<?php print_html($ROW->ed_date);?></td>
		</tr>
		<tr>
			<td>定員</td>
			<td><?php print_html($ROW->capacity);?>名</td>
		</tr>
		<tr>
			<td>確認待ち</td>
			<td><?php print_html($ROW->wait);?>名</td>
		</tr>
		<tr>
			<td>予約済み</td>
			<td><?php print_html($ROW->reserved);?>名</td>
		</tr>
		<tr>
			<td>キャンセル待ち</td>
			<td><?php print_html($ROW->cancel);?>名</td>
		</tr>
		<tr>
			<td>予約ページ</td>
			<td><?php print_html($url);?></td>
		</tr>
		<tr>
			<td colspan=2><a href="<?php print_html($_SERVER['SCRIPT_NAME']); ?>?s=4&e=<?php print_html($e); ?>">作業履歴</a></td>
		</tr>
	</table>
	<table border="1" width="100%">
		<tr><th width="15%">名前</th><th width="25%">メールアドレス</th><th width="5%">参加形態</th><th width="7%">状態</th><th width="30%">コメント</th><th width="14%">備考</th><th width="4%">状態変更</th></tr>
<?php
	$SQL = sprintf("select name,email,GM,status,comment from reserve ".
			"where event_id = %s order by time_stamp;",
			mysql_esc($e));
	$OUT = $mysqli->query($SQL);
	while ( $ROW = $OUT->fetch_object() ) {
		print("		<tr><td>");
		print_html($ROW->name);
		print("</td><td>");
		$reserv_email = $ROW->email;
		print_html($reserv_email);
		print("</td><td>");
		switch ($ROW->GM){
			case 0:print("PL");break;
			case 1:print("GM");break;
			default:print("&nbsp;");break;
		}
		print("</td><td>");
		switch ($ROW->status){
			case 0:print("確認待ち");break;
			case 1:print("予約済み");break;
			case 2:print("キャンセル待ち");break;
			default:print("&nbsp;");break;
		}
		print("</td><td>");
		print_html($ROW->comment);
		?></td><td>&nbsp;</td><td><form action="<?php print_html($_SERVER['SCRIPT_NAME']); ?>" method="POST"><input type="submit" value="変更"><input type="hidden" name="e" value="<?php print_html($e); ?>"><input type="hidden" name="reserv_email" value="<?php print_html($reserv_email); ?>"><input type="hidden" name="s" value="2"></form></td></tr>
<?php
	}
?>	</table>
	<a href="<?php print_html($_SERVER['SCRIPT_NAME']); ?>?s=0">イベント一覧へ戻る</a>
</body>
</html>
<?php
}
/*
	予約ステータス更新画面
*/

function print_event_reserve_status($e,$reserv_email){
global $mysqli;

	$SQL = sprintf("select email,name,case GM when 0 then 'PL' when 1 then 'GM' end as GM,status,comment ".
			"from reserve as r where event_id = %s and email = %s;",
			mysql_esc($e),mysql_esc($reserv_email));
	$ROW = $mysqli->query($SQL)->fetch_object();
	$event_name = getEventName($e);
	$status = $ROW->status;
	?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="ja">
<head>
	<META HTTP-EQUIV="Content-type" CONTENT="text/html; charset=shift-jis">
	<META HTTP-EQUIV="Content-Style-Type" content="text/css">
	<link rel="stylesheet" type="text/css" href="./sample.css">
	<title><?php print_html($event_name);?>予約状況</title>
</head>
<body>
	<h1><?php print_html($event_name);?>予約状況</h1>
	<table border="1">
		<tr><td>名前</td><td><?php print_html($ROW->name);?></td></tr>
		<tr><td>メールアドレス</td><td><?php print_html($ROW->email);?></td></tr>
		<tr><td>参加形態</td><td><?php print_html($ROW->GM);?></td></tr>
		<tr><td>コメント</td><td><?php print_html($ROW->comment);?></td></tr>
	</table>
	<form action="<? print_html($_SERVER['SCRIPT_NAME']); ?>" method="POST">
		<input type="radio" name="status" value="0"<?php if($status==0){print(" checked");} ?>>確認待ち<br/>
		<input type="radio" name="status" value="1"<?php if($status==1){print(" checked");} ?>>予約済み<br/>
		<input type="radio" name="status" value="2"<?php if($status==2){print(" checked");} ?>>キャンセル待ち<br/>
		<input type="radio" name="status" value="3">強制キャンセル<br/>
		<input type="submit" value="変更">
		<input type="hidden" name="e" value="<?php print_html($e); ?>">
		<input type="hidden" name="s" value="3">
		<input type="hidden" name="reserv_email" value="<?php print_html($reserv_email); ?>">
	</form>


	<a href="<?php print_html($_SERVER['SCRIPT_NAME']); ?>?s=1&e=<?php print_html($e); ?>"><?php print_html($event_name);?>予約状況へ戻る</a>
</body>
</html>
<?php
}
/*
	予約ステータス完了画面
*/

function print_event_reserve_status_conf($e){
global $mysqli;

	$event_name = getEventName($e);
	$SQL = sprintf("select email,name,case GM when 0 then 'PL' when 1 then 'GM' end as GM,status,comment ".
			"from reserve as r where event_id = %s and email = %s;",
			mysql_esc($e),mysql_esc($reserv_email));
	$ROW = $mysqli->query($SQL)->fetch_object();
	$status = $ROW->status;
	?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="ja">
<head>
	<META HTTP-EQUIV="Content-type" CONTENT="text/html; charset=shift-jis">
	<META HTTP-EQUIV="Content-Style-Type" content="text/css">
	<link rel="stylesheet" type="text/css" href="./sample.css">
	<title><?php print_html($event_name);?>予約状況</title>
</head>
<body>
	<h1><?php print_html($event_name);?>予約状況</h1>
	予約状態を更新しました。詳細は予約状況画面から、作業記録を確認してください。<br/>
	<a href="<? print_html($_SERVER['SCRIPT_NAME']); ?>?s=1&e=<?php print_html($e); ?>"><?php print_html($event_name);?>予約状況へ戻る</a><br/>
</body>
</html>
	
<?php
}

/*
	イベント管理ログ
*/

function print_event_reserve_log($e){
global $logfile;
	$event_name = getEventName($e);
	?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="ja">
<head>
	<META HTTP-EQUIV="Content-type" CONTENT="text/html; charset=shift-jis">
	<META HTTP-EQUIV="Content-Style-Type" content="text/css">
	<link rel="stylesheet" type="text/css" href="./sample.css">
	<title><?php print_html($event_name);?>作業記録</title>
</head>
<body>
	<h1><?php print_html($event_name);?>作業記録</h1>

<?php

	$fh = fopen($logfile, "r+");
	while ( $STR = fread($fh,8192) ){
		print_html($STR);
	}
	print("</body></html>");
}
/*
	イベントの更新/登録画面
*/

function print_event_edit($e,$email){
global $mysqli;

	if($e != 0 ){
		$SQL = sprintf("select name,st_date,ed_date,capacity,comment ".
				"from event as r where event_id = %s and email = %s;",
				mysql_esc($e),mysql_esc($email));
		$ROW = $mysqli->query($SQL)->fetch_object();
		$name = $ROW->name;
		$st_date = $ROW->st_date;
		$ed_date = $ROW->ed_date;
		$capacity = $ROW->capacity;
		$comment = htmlspecialchars($ROW->comment);
	}else{
		$name = "新規イベント";
		$st_date = "";
		$ed_date = "";
		$capacity = "";
		$comment = "";
	}
	?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="ja">
<head>
	<META HTTP-EQUIV="Content-type" CONTENT="text/html; charset=shift-jis">
	<META HTTP-EQUIV="Content-Style-Type" content="text/css">
	<link rel="stylesheet" type="text/css" href="./sample.css">
	<title><?php print_html($name);?>の編集</title>
</head>
<body>
	<h1><?php print_html($name);?>の編集</h1>
	<form action="<?php print_html($_SERVER['SCRIPT_NAME']); ?>" method="POST">
		<table>
			<tr><td>イベント名</td><td><input type="text" name="reg_name" value="<?php print_html($name); ?>"></td></tr>
			<tr><td>募集開始日</td><td><input type="text" name="reg_st_date" value="<?php print_html($st_date); ?>">YYYY-MM-DD</td></tr>
			<tr><td>募集締切日</td><td><input type="text" name="reg_ed_date" value="<?php print_html($ed_date); ?>">YYYY-MM-DD</td></tr>
			<tr><td>定員</td><td><input type="text" name="reg_capacity" value="<?php print_html($capacity); ?>">名</td></tr>
			<tr><td>コメント</td><td><textarea name="reg_comment" cols="80" rows="25"><?php print($comment); ?></textarea></td></tr>
		</table>
		<input type="submit" value="完了">
		<input type="hidden" name="e" value="<?php print_html($e); ?>">
		<input type="hidden" name="s" value="7">
	</form>


	<a href="<?php print_html($_SERVER['SCRIPT_NAME']); ?>?s=0">イベント一覧へ戻る</a>
</body>
</html>
<?php
}
/*
	イベント編集完了画面
*/

function print_event_edit_conf($e){
global $mysqli;

	$event_name = getEventName($e);
	?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="ja">
<head>
	<META HTTP-EQUIV="Content-type" CONTENT="text/html; charset=shift-jis">
	<META HTTP-EQUIV="Content-Style-Type" content="text/css">
	<link rel="stylesheet" type="text/css" href="./sample.css">
	<title><?php print_html($event_name);?>編集</title>
</head>
<body>
	<h1><?php print_html($event_name);?>編集</h1>
	<?php print_html($event_name);?>の内容を編集しました<br/>
	<a href="<?php print_html($_SERVER['SCRIPT_NAME']); ?>?s=0&e=0">予約一覧へ戻る</a><br/>
</body>
</html>
	
<?php
}

?>
