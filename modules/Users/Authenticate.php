<?php
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ********************************************************************************/
require_once('modules/Users/Users.php');
require_once('modules/Users/CreateUserPrivilegeFile.php');
require_once('include/logging.php');
require_once('user_privileges/audit_trail.php');

global $mod_strings, $default_charset, $adb;

$focus = new Users();

// Add in defensive code here.
$focus->column_fields["user_name"] = to_html(vtlib_purify($_REQUEST['user_name']));
$user_password = $_REQUEST['user_password'];

$focus->load_user($user_password);

if($focus->is_authenticated())
{
	coreBOS_Session::destroy();
	//Inserting entries for audit trail during login
	if ($audit_trail == 'true') {
		$date_var = $adb->formatDate(date('Y-m-d H:i:s'), true);
		$query = "insert into vtiger_audit_trial values(?,?,?,?,?,?)";
		$params = array($adb->getUniqueID('vtiger_audit_trial'), $focus->id, 'Users','Authenticate','',$date_var);
		$adb->pquery($query, $params);
	}
	cbEventHandler::do_action('corebos.audit.authenticate',array($focus->id, 'Users', 'Authenticate', $focus->id, date('Y-m-d H:i:s')));

	// Recording the login info
	$usip=$_SERVER['REMOTE_ADDR'];
	$intime=date("Y/m/d H:i:s");
	require_once('modules/Users/LoginHistory.php');
	$loghistory=new LoginHistory();
	$Signin = $loghistory->user_login($focus->column_fields["user_name"],$usip,$intime);

	//Security related entries start
	require_once('include/utils/UserInfoUtil.php');

	createUserPrivilegesfile($focus->id);

	//Security related entries end
	coreBOS_Session::delete('login_password');
	coreBOS_Session::delete('login_error');
	coreBOS_Session::delete('login_user_name');

	coreBOS_Session::set('authenticated_user_id', $focus->id);
	coreBOS_Session::set('app_unique_key', $application_unique_key);

	//Enabled session variable for KCFINDER
	coreBOS_Session::setKCFinderVariables();

	// store the user's theme in the session
	if(!empty($focus->column_fields["theme"])) {
		$authenticated_user_theme = $focus->column_fields["theme"];
	} else {
		$authenticated_user_theme = $default_theme;
	}

	// store the user's language in the session
	if(!empty($focus->column_fields["language"])) {
		$authenticated_user_language = $focus->column_fields["language"];
	} else {
		$authenticated_user_language = $default_language;
	}

	coreBOS_Session::set('vtiger_authenticated_user_theme', $authenticated_user_theme);
	coreBOS_Session::set('authenticated_user_language', $authenticated_user_language);

	$log->debug("authenticated_user_theme is $authenticated_user_theme");
	$log->debug("authenticated_user_language is $authenticated_user_language");
	$log->debug("authenticated_user_id is ". $focus->id);
	$log->debug("app_unique_key is $application_unique_key");

	// Reset number of failed login attempts
	$query = 'UPDATE vtiger_users SET failed_login_attempts=0 where user_name=?';
	$adb->pquery($query, array($focus->column_fields['user_name']));

// Clear all uploaded import files for this user if it exists
	global $import_dir;

	$tmp_file_name = $import_dir. "IMPORT_".$focus->id;

	if (file_exists($tmp_file_name))
	{
		unlink($tmp_file_name);
	}
	if(isset($_SESSION['lastpage'])) {
		header("Location: index.php?".$_SESSION['lastpage']);
	} else {
		header("Location: index.php");
	}
}
else
{
	$sql = 'select failed_login_attempts from vtiger_users where user_name=?';
	$result = $adb->pquery($sql, array($focus->column_fields["user_name"]));
	$failed_login_attempts = 0;
	if ($result and $adb->num_rows($result)>0) {
		$failed_login_attempts = $adb->query_result($result, 0, 0);
	}
	$maxFailedLoginAttempts = GlobalVariable::getVariable('Application_MaxFailedLoginAttempts', 5);
	// Increment number of failed login attempts
	$query = 'UPDATE vtiger_users SET failed_login_attempts=COALESCE(failed_login_attempts,0)+1 where user_name=?';
	$adb->pquery($query, array($focus->column_fields['user_name']));
	coreBOS_Session::set('login_user_name', $focus->column_fields["user_name"]);
	coreBOS_Session::set('login_password', $user_password);
	if (empty($_SESSION['login_error'])) {
		coreBOS_Session::set('login_error', ($failed_login_attempts>=$maxFailedLoginAttempts ? $mod_strings['ERR_MAXLOGINATTEMPTS'] : $mod_strings['ERR_INVALID_PASSWORD']));
	}
	cbEventHandler::do_action('corebos.audit.login.attempt',array(0, $focus->column_fields["user_name"], 'Login Attempt', 0, date('Y-m-d H:i:s')));
	// go back to the login screen.
	// create an error message for the user.
	header("Location: index.php");
}

?>
