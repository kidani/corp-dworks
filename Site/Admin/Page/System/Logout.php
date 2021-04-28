<?php

// セッション変数を全て解除
$_SESSION = array();
session_destroy();
header('Location:?p=System/Login');

