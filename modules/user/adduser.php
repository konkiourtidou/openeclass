<?php

/* ========================================================================
 * Open eClass 3.6
 * E-learning and Course Management System
 * ========================================================================
 * Copyright 2003-2017  Greek Universities Network - GUnet
 * A full copyright notice can be read in "/info/copyright.txt".
 * For a full list of contributors, see "credits.txt".
 *
 * Open eClass is an open platform distributed in the hope that it will
 * be useful (without any warranty), under the terms of the GNU (General
 * Public License) as published by the Free Software Foundation.
 * The full license can be read in "/info/license/license_gpl.txt".
 *
 * Contact address: GUnet Asynchronous eLearning Group,
 *                  Network Operations Center, University of Athens,
 *                  Panepistimiopolis Ilissia, 15784, Athens, Greece
 *                  e-mail: info@openeclass.org
 * ======================================================================== */

/**
 * @file adduser.php
 * @brief Course admin can add users to the course.
 */
$require_current_course = true;
$require_course_admin = true;
$require_help = true;
$helpTopic = 'course_users';

require_once '../../include/baseTheme.php';
require_once 'include/sendMail.inc.php';
require_once 'include/log.class.php';
require_once 'include/lib/hierarchy.class.php';
require_once 'include/lib/user.class.php';

$tree = new Hierarchy();
$user = new User();

$toolName = $langUsers;
$pageName = $langAddUser;
$navigation[] = array('url' => "index.php?course=$course_code", 'name' => $langUsers);

if (isset($_GET['add'])) {
    $uid_to_add = intval(getDirectReference($_GET['add']));
    $result = Database::get()->query("INSERT IGNORE INTO course_user (user_id, course_id, status, reg_date, document_timestamp)
                                    VALUES (?d, ?d, " . USER_STUDENT . ", " . DBHelper::timeAfter() . ", " . DBHelper::timeAfter(). ")", $uid_to_add, $course_id);
    $r = Database::get()->queryArray("SELECT id FROM course_user_request WHERE uid = ?d AND course_id = ?d", $uid_to_add, $course_id);
    if ($r) { // close course user request (if any)
        foreach ($r as $req) {
            Database::get()->query("UPDATE course_user_request SET status = 2 WHERE id = ?d", $req->id);
        }
    }

    Log::record($course_id, MODULE_ID_USERS, LOG_INSERT, array('uid' => $uid_to_add,
                                                               'right' => '+5'));
    if ($result) {
      //  Session::Messages($langTheU . ' ' . $langAdded, "alert alert-success");
        Session::flash('message',$langTheU . ' ' . $langAdded); 
        Session::flash('alert-class', 'alert-success');
        // notify user via email
        $email = uid_to_email($uid_to_add);
        if (!empty($email) and valid_email($email)) {
            $emailsubject = "$langYourReg " . course_id_to_title($course_id);
            $emailbody = "$langNotifyRegUser1 <a href='{$urlServer}courses/$course_code/'>" . q(course_id_to_title($course_id)) . "</a> $langNotifyRegUser2 $langFormula \n$gunet";

            $header_html_topic_notify = "<!-- Header Section -->
            <div id='mail-header'>
                <br>
                <div>
                    <div id='header-title'>$langYourReg " . course_id_to_title($course_id)."</div>
                </div>
            </div>";

            $body_html_topic_notify = "<!-- Body Section -->
            <div id='mail-body'>
                <br>
                <div id='mail-body-inner'>
                    $langNotifyRegUser1 '" . course_id_to_title($course_id) . "' $langNotifyRegUser2
                    <br><br>$langFormula<br>$gunet
                </div>
            </div>";

            $emailbody = $header_html_topic_notify.$body_html_topic_notify;

            $plainemailbody = html2text($emailbody);

            send_mail_multipart("{$_SESSION['givenname']} ${_SESSION['surname']}",  $_SESSION['email'], '', $email, $emailsubject, $plainemailbody, $emailbody);
        }
    } else {
      //  Session::Messages($langAddError, "alert alert-warning");
        Session::flash('message',$langAddError); 
        Session::flash('alert-class', 'alert-warning');
    }
    redirect_to_home_page("modules/user/index.php?course=$course_code");
} else {
    register_posted_variables(array('search_surname' => true,
                                    'search_givenname' => true,
                                    'search_username' => true,
                                    'search_am' => true), 'any');

    $tool_content .= action_bar(array(
            array('title' => $langBack,
                  'url' => "index.php?course=$course_code",
                  'icon' => 'fa-reply',
                  'level' => 'primary-label'
                 )));

    $tool_content .= "<div class='col-xxl-12 col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12'><div class='alert alert-info'>$langAskUser</div></div>

    <div class='col-12'>
                <div class='form-wrapper shadow-sm p-3 rounded'>
                <form class='form-horizontal' role='form' method='post' action='$_SERVER[SCRIPT_NAME]?course=$course_code'>
                <fieldset>

                <div class='row p-2'></div>

                <div class='form-group'>
                <label for='surname' class='col-sm-6 control-label-notes'>$langSurname:</label>
                <div class='col-sm-12'>
                    <input class='form-control' id='surname' type='text' name='search_surname' value='" . q($search_surname) . "' placeholder='$langSurname'></div>
                </div>

                <div class='row p-2'></div>

                <div class='form-group'>
                <label for='name' class='col-sm-6 control-label-notes'>$langName:</label>
                <div class='col-sm-12'>
                    <input class='form-control' id='name' type='text' name='search_givenname' value='" . q($search_givenname) . "' placeholder='$langName'></div>
                </div>

                <div class='row p-2'></div>

                <div class='form-group'>
                <label for='username' class='col-sm-6 control-label-notes'>$langUsername:</label>
                <div class='col-sm-12'>
                    <input class='form-control' id='username' type='text' name='search_username' value='" . q($search_username) . "' placeholder='$langUsername'></div>
                </div>

                <div class='row p-2'></div>

                <div class='form-group'>
                <label for='am' class='col-sm-6 control-label-notes'>$langAm:</label>
                <div class='col-sm-12'>
                    <input class='form-control' id='am' type='text' name='search_am' value='" . q($search_am) . "' placeholder='$langAm'></div>
                </div>

                <div class='row p-2'></div>

                <div class='form-group'>
                <div class='col-sm-offset-2 col-sm-10'>
                    <input class='btn btn-primary' type='submit' name='search' value='$langSearch'>
                    <a class='btn btn-secondary' href='index.php?course=$course_code'>$langCancel</a>
                </div>
                </div>
                </fieldset>
                </form>
                </div></div>";

    $search = array();
    $values = array();
    foreach (array('surname', 'givenname', 'username', 'am') as $term) {
        $tvar = 'search_' . $term;
        if (!empty($GLOBALS[$tvar])) {
            $search[] = "u.$term LIKE ?s";
            $values[] = $GLOBALS[$tvar] . '%';
        }
    }
    $query = join(' AND ', $search);
    if (!empty($query)) {
        Database::get()->query("CREATE TEMPORARY TABLE lala AS
                    SELECT user_id FROM course_user WHERE course_id = ?d", $course_id);
        $result = Database::get()->queryArray("SELECT u.id, u.surname, u.givenname, u.username, u.am FROM
                                                user u LEFT JOIN lala c ON u.id = c.user_id WHERE
                                                c.user_id IS NULL AND u.expires_at >= CURRENT_DATE() AND $query", $values);
        if ($result) {
            $tool_content .= "<table class='announcements_table'>
                                <tr class='notes_thead'>
                                  <th class='text-white'>$langID</th>
                                  <th class='text-white'>$langName</th>
                                  <th class='text-white'>$langSurname</th>
                                  <th class='text-white'>$langUsername</th>
                                  <th class='text-white'>$langFaculty</th>
                                  <th class='text-white'>$langActions</th>
                                </tr>";
            $i = 1;
            foreach ($result as $myrow) {
                $departments = $user->getDepartmentIds($myrow->id);
                $dep_content = '';
                $j = 1;
                foreach ($departments as $dep) {
                    $br = ($j < count($departments)) ? '<br>' : '';
                    $dep_content .= $tree->getPath($dep) . $br;
                    $j++;
                }
                $tool_content .= "<td class='text-right'>$i.</td><td>" . q($myrow->givenname) . "</td><td>" .
                        q($myrow->surname) . "</td><td>" . q($myrow->username) . "</td><td>" .
                        $dep_content . "</td><td class='text-center'>" .
                        icon('fa-sign-in', $langRegister, "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;add=" . getIndirectReference($myrow->id)). "</td></tr>";
                $i++;
            }
            $tool_content .= "</table>";
        } else {
            $tool_content .= "<div class='col-xxl-12 col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12'><div class='alert alert-danger'>$langNoUsersFound</div></div>";
        }
        Database::get()->query("DROP TABLE lala");
    }
}
draw($tool_content, 2);
