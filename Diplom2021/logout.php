<?php	//LogOut
	include("inc\\BeginPage.php");
	if(isset($_SESSION['AppName']) && $_SESSION['AppName']==$AppName && isset($_SESSION['UserID']))
		EasyQueryNoResult($conn, "CALL WriteLog($_SESSION[UserID],3,0,'')");
	session_destroy();
	header('Location: index.php');	
	exit;
?>