<?php
/*========================================================================
*   Open eClass 2.3
*   E-learning and Course Management System
* ========================================================================
*  Copyright(c) 2003-2010  Greek Universities Network - GUnet
*  A full copyright notice can be read in "/info/copyright.txt".
*
*  Developers Group:    Costas Tsibanis <k.tsibanis@noc.uoa.gr>
*                       Yannis Exidaridis <jexi@noc.uoa.gr>
*                       Alexandros Diamantidis <adia@noc.uoa.gr>
*                       Tilemachos Raptis <traptis@noc.uoa.gr>
*
*  For a full list of contributors, see "credits.txt".
*
*  Open eClass is an open platform distributed in the hope that it will
*  be useful (without any warranty), under the terms of the GNU (General
*  Public License) as published by the Free Software Foundation.
*  The full license can be read in "/info/license/license_gpl.txt".
*
*  Contact address:     GUnet Asynchronous eLearning Group,
*                       Network Operations Center, University of Athens,
*                       Panepistimiopolis Ilissia, 15784, Athens, Greece
*                       eMail: info@openeclass.org
* =========================================================================*/

/*===========================================================================
	ldapsearch.php
	@authors list: Karatzidis Stratos <kstratos@uom.gr>
		       Vagelis Pitsioygas <vagpits@uom.gr>
==============================================================================        
  @Description: This script/file tries to authenticate the user, using
  his user/pass pair and the authentication method defined by the admin
  
==============================================================================
*/

include '../../include/baseTheme.php';
include '../../include/sendMail.inc.php';
require_once 'auth.inc.php';

if (!isset($_POST['auth'])) {
        header('Location: ' . $urlServer);
        exit;
} else {
        $auth = intval($_POST['auth']);
}

$msg = "$langReqRegProf (".(get_auth_info($auth)).")";
$nameTools = $msg;
$navigation[]= array ("url"=>"registration.php", "name"=> "$langNewUser");
$navigation[]= array ("url"=>"ldapnewuser.php?p=TRUE&amp;auth=$auth", "name"=> "$langConfirmUser");

$tool_content = "";

$lang = langname_to_code($language);

$is_submit = isset($_POST['is_submit'])?$_POST['is_submit']:'';
$ldap_email = isset($_POST['ldap_email'])?$_POST['ldap_email']:'';
$ldap_passwd = isset($_POST['ldap_passwd'])?$_POST['ldap_passwd']:'';

$lastpage = 'ldapnewuser.php?p=TRUE&amp;auth='.$auth.'&amp;ldap_email='.$ldap_email;
$errormessage = "<br/><p>$ldapback <a href='$lastpage'>$ldaplastpage</a></p>";

if(!empty($is_submit)) {
	if (empty($ldap_email) or empty($ldap_passwd)) // check for empty username-password
	{
		$tool_content .= "
		<p class='caution'>$ldapempty  $errormessage </p>";
		draw($tool_content,0);
		exit();
	}  else  {
		// try to authenticate user
		$auth_method_settings = get_auth_settings($auth);
		switch($auth)
		{
			case '2':$pop3host = str_replace("pop3host=","",$auth_method_settings['auth_settings']);
				break;
			case '3':$imaphost = str_replace("imaphost=","",$auth_method_settings['auth_settings']);
				break;
			case '4':$ldapsettings = $auth_method_settings['auth_settings'];
				$ldap = explode("|",$ldapsettings);
				$ldaphost = str_replace("ldaphost=","",$ldap[0]);	//ldaphost
				$ldap_base = str_replace("ldap_base=","",$ldap[1]);  //ldap_base
				$ldapbind_dn = str_replace("ldapbind_dn=","",$ldap[2]); //ldapbind_dn
				$ldapbind_pw = str_replace("ldapbind_pw=","",$ldap[3]);	// ldapbind_pw
				$ldap_login_attr = str_replace("ldap_login_attr=","",$ldap[4]);  // ldap_login_attr
				$ldap_login_attr2 = str_replace("ldap_login_attr2=","",$ldap[5]);   // ldap_login_attr2
				break;
			case '5':$dbsettings = $auth_method_settings['auth_settings'];
				$edb = explode("|",$dbsettings);
				$dbhost = str_replace("dbhost=","",$edb[0]);	//dbhost
				$dbname = str_replace("dbname=","",$edb[1]);	//dbname
				$dbuser = str_replace("dbuser=","",$edb[2]);//dbuser
				$dbpass = str_replace("dbpass=","",$edb[3]);// dbpass
				$dbtable = str_replace("dbtable=","",$edb[4]);//dbtable
				$dbfielduser = str_replace("dbfielduser=","",$edb[5]);//dbfielduser
				$dbfieldpass = str_replace("dbfieldpass=","",$edb[6]);//dbfieldpass
				break;
			default:
				break;
		}
		$is_valid = auth_user_login($auth,$ldap_email,$ldap_passwd);
	}	

	if ($is_valid) { // connection successful	
		$tool_content .= "
		<form action='$_SERVER[PHP_SELF]' method='post'>" .
		(isset($GLOBALS['auth_user_info'])?
		('<input type="hidden" name="prenom_form" value="' . $GLOBALS['auth_user_info']['firstname'] .
		'" /><input type="hidden" name="nom_form" value="' . $GLOBALS['auth_user_info']['lastname'] .
		'" /><input type="hidden" name="email" value="' . $GLOBALS['auth_user_info']['email'] . '" />'): '') . "<p class='success'>$langTheUser $ldapfound </p>
                <fieldset>
                <legend>$langUserData</legend>
		<table width=\"99%\" class='tbl'>
		<tr>
                  <th class='left'>".$langName."</th>
		  <td>".(isset($GLOBALS['auth_user_info'])?
		$GLOBALS['auth_user_info']['firstname']: '<input type="text" name="prenom_form" size="38" />')."</td>
		</tr>
		<tr>
		  <th class='left'>".$langSurname."</th>
		  <td>".(isset($GLOBALS['auth_user_info'])?
		$GLOBALS['auth_user_info']['lastname']: '<input type="text" name="nom_form" size="38" />')."</td>
		</tr>
		<tr>
		  <th class='left'>".$langEmail."</th>
		  <td>".(isset($GLOBALS['auth_user_info'])?
		$GLOBALS['auth_user_info']['email']: '<input type="text" name="email" size="38" />')."</td>
		</tr>
		<tr>
		  <th class='left'>".$langPhone."</th>
		  <td><input type='text' name='userphone' size='38' value=\"\" />&nbsp;&nbsp;(*)</td>
		</tr>
		<tr>
		  <th class='left'>".$langComments."</th>
                  <td><textarea name='usercomment' cols='32' rows='4' />".@$usercomment."</textarea>&nbsp;&nbsp;(*) $profreason</td>
		</tr>
		<tr>
		  <th class='left'>".$langFaculty.":</th>
		  <td>
		    <select name='department'>";
		$deps=mysql_query("SELECT name, id FROM faculte ORDER BY id",$db);
		while ($dep = mysql_fetch_array($deps))  {
			$tool_content .= "\n<option value='$dep[1]'>$dep[0]</option>";
		}
		$tool_content .= "</select></td>
                </tr>
		<tr>
		  <th class='left'>$langLanguage</th>
		  <td>";
		$tool_content .= lang_select_options('localize');
		$tool_content .= "</td>
                </tr>	
		<tr>
		  <th class='left'>&nbsp;</th>
		  <td><input type=\"submit\" name=\"submit\" value=\"".$langRegistration."\" />
		      <input type='hidden' name=\"uname\" value=\"".$ldap_email."\" />
		      <input type='hidden' name=\"password\" value=\"".$ldap_passwd."\" />
		      <input type='hidden' name=\"auth\" value=\"".$auth."\" />
		      </td>
                </tr>
                <tr>
                  <th class='left'>&nbsp;</th>
                  <td><div align='right'>$langRequiredFields</div></td>
                </tr>
                </table>
                </fieldset>
		</form>";
	}  else {
		$tool_content .= "<p class='caution'>$langConnNo<br/>$langAuthNoValidUser</p>";
		$tool_content .= "<p>&laquo; <a href='$lastpage'>$langBack</a></p>";
	}
	draw($tool_content,0);
	exit();
} // end of if(is_submit)

// -----------------------------------------
// registration
// -----------------------------------------
if (isset($_POST['submit']))  {
	$uname = $_POST['uname'];
	$uname = escapeSimple($uname);
	$email = isset($_POST['email'])?$_POST['email']:'';
	$prenom_form = isset($_POST['prenom_form'])?$_POST['prenom_form']:'';
	$nom_form = isset($_POST['nom_form'])?$_POST['nom_form']:'';
	$department = isset($_POST['department'])?$_POST['department']:'';
	$usercomment = isset($_POST['usercomment'])?$_POST['usercomment']:'';
	$userphone = isset($_POST['userphone'])?$_POST['userphone']:'';
	
	// check if there are empty fields
	if (empty($nom_form) or empty($prenom_form) or empty($userphone)
	or empty($usercomment) or empty($uname) or (empty($email))) {
		$tool_content .= "<p class='caution'>";
		$tool_content .= "$langEmptyFields <br /><a href='javascript:history.go(-1)'>$langAgain</a></p>";
		draw($tool_content,0);
		exit();
	}
	
	if($auth!=1) {
		switch($auth)
		{
			case '2': $password = "pop3";
			break;
			case '3': $password = "imap";
			break;
			case '4': $password = "ldap";
			break;
			case '5': $password = "db";
			break;
			default:  $password = "";
			break;
		}
	}
	
	$username = $uname;
	$usermail = $email;
	$surname = $nom_form;
	$name = $prenom_form;
	$depid = intval($department);
	
	$sql = "INSERT INTO prof_request(profname, profsurname, profuname, profpassword,
		profemail, proftmima, profcomm, status, date_open, comment, lang, statut) VALUES(
		'$name','$surname','$username','$password','$usermail','$depid','$userphone',
		1, NOW(), '$usercomment', '$lang', 1)";
	$upd = db_query($sql,$mysqlMainDb);
	
	// send email
        $MailMessage = $mailbody1 . $mailbody2 . "$name $surname\n\n" . $mailbody3
        . $mailbody4 . $mailbody5 . "$mailbody6\n\n" . "$langFaculty: " . find_faculty_by_id($department) . "
	\n$langComments: $usercomment\n"
        . "$langProfUname : $username\n$langProfEmail : $usermail\n" . "$contactphone : $userphone\n\n\n$logo\n\n";
	
	if (!send_mail('', $emailhelpdesk, $gunet, $emailhelpdesk, $mailsubject, $MailMessage, $charset)) {
		$tool_content .= "<p class=\"alert1\">$langMailErrorMessage &nbsp; <a href=\"mailto:$emailhelpdesk\">$emailhelpdesk</a></p>";
		draw($tool_content,0);
		exit();
	}

	$tool_content .= "<p class='success'>$langDearProf<br />$success<br />$infoprof</p><p>&laquo; <a href='$urlServer'>$langBack</a></p>";
}

draw($tool_content,0);
