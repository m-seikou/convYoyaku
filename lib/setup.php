<?php

$_GET = array_merge([],$_GET);
$_POST = array_merge([],$_POST);
$request_default = [
	'c' => '',
	'comment' => '',
	'e' => '',
	'email' => '',
	'k' => '',
	'name' => '',
	'passwd' => '',
	'reg_capacity' => '',
	'reg_comment' => '',
	'reg_ed_date' => '',
	'reg_name' => '',
	'reg_st_date' => '',
	'reserv_email' => '',
	's' => 0,
	'status' => 0,
	't' => 0,
];
$_REQUEST = array_merge($request_default,$_REQUEST);
