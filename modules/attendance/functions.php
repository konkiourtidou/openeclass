<?php

/* ========================================================================
 * Open eClass
 * E-learning and Course Management System
 * ========================================================================
 * Copyright 2003-2014  Greek Universities Network - GUnet
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
 * ========================================================================
 */

/**
 * @brief admin available attendances
 * @global type $course_id
 * @global type $tool_content
 * @global type $course_code
 * @global type $langEditChange
 * @global type $langDelete
 * @global type $langConfirmDelete
 * @global type $langCreateDuplicate
 * @global type $langAvailableAttendances
 * @global type $langNoAttendances
 * @global type $is_editor
 */
function display_attendances() {

    global $course_id, $tool_content, $course_code, $langEditChange,
           $langDelete, $langConfirmDelete, $langCreateDuplicate,
           $langAvailableAttendances, $langNoAttendances, $is_editor,
           $langViewHide, $langViewShow, $langEditChange, $langStart, $langEnd, $uid;

    if ($is_editor) {
        $result = Database::get()->queryArray("SELECT * FROM attendance WHERE course_id = ?d", $course_id);
    } else {
        $result = Database::get()->queryArray("SELECT attendance.* "
                . "FROM attendance, attendance_users "
                . "WHERE attendance.active = 1 "
                . "AND attendance.course_id = ?d "
                . "AND attendance.id = attendance_users.attendance_id AND attendance_users.uid = ?d", $course_id, $uid);
    }
    if (count($result) == 0) { // no attendances
        $tool_content .= "<div class='row p-2'></div>
            <div class='col-xxl-12 col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12'>
                <div class='alert alert-info'>$langNoAttendances</div></div>";
    } else {
        $tool_content .= "<div class='table-responsive'>";
        $tool_content .= "<table class='announcements_table'>";
        $tool_content .= "<tr class='notes_thead'>
                            <th class='text-white'>$langAvailableAttendances</th>
                            <th class='text-white'>$langStart</th>
                            <th class='text-white'>$langEnd</th>";
        if( $is_editor) {
            $tool_content .= "<th class='text-white text-start'>" . icon('fa-gears') . "</th>";
        }
        $tool_content .= "</tr>";
        foreach ($result as $a) {
            $start_date = DateTime::createFromFormat('Y-m-d H:i:s', $a->start_date)->format('d-m-Y H:i');
            $end_date = DateTime::createFromFormat('Y-m-d H:i:s', $a->end_date)->format('d-m-Y H:i');
            $row_class = !$a->active ? "class='not_visible'" : "";
            $tool_content .= "
                    <tr $row_class>
                        <td>
                            <a href='$_SERVER[SCRIPT_NAME]?course=$course_code&amp;attendance_id=$a->id'>".q($a->title)."</a>
                        </td>
                        <td>$start_date</td>
                        <td>$end_date</td>";
            if( $is_editor) {
                $tool_content .= "<td class='option-btn-cell'>";
                $tool_content .= action_button(array(
                                    array('title' => $langEditChange,
                                          'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;attendance_id=$a->id&amp;editSettings=1",
                                          'icon' => 'fa-cogs'),
                                    array('title' => $a->active ? $langViewHide : $langViewShow,
                                          'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;attendance_id=$a->id&amp;vis=" .
                                                  ($a->active ? '0' : '1'),
                                          'icon' => $a->active ? 'fa-eye-slash' : 'fa-eye'),
                                    array('title' => $langCreateDuplicate,
                                          'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;attendance_id=$a->id&amp;dup=1",
                                          'icon' => 'fa-copy'),
                                    array('title' => $langDelete,
                                          'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;delete_at=$a->id",
                                          'icon' => 'fa-times',
                                          'class' => 'delete',
                                          'confirm' => $langConfirmDelete))
                                        );
                $tool_content .= "</td>";
            }
            $tool_content .= "</tr>";
        }
        $tool_content .= "</table></div>";
    }
}

/**
 * @brief display attendance users
 * @global type $tool_content
 * @global type $course_id
 * @global type $course_code
 * @global type $actID
 * @global type $langName
 * @global type $langSurname
 * @global type $langRegistrationDateShort
 * @global type $langAttendanceAbsences
 * @global type $langAm
 * @global type $langAttendanceEdit
 * @global type $langAttendanceBooking
 * @global type $langID
 * @param type $attendance_id
 */
function register_user_presences($attendance_id, $actID) {

    global $tool_content, $course_id, $course_code, $dateFormatMiddle,
           $langName, $langSurname, $langRegistrationDateShort, $langAttendanceAbsences,
           $langAm, $langAttendanceBooking, $langID, $langAttendanceEdit, $langCancel;

    $result = Database::get()->querySingle("SELECT * FROM attendance_activities WHERE id = ?d", $actID);
    $act_type = $result->auto; // type of activity
    $tool_content .= "<div class='row p-2'></div>
    <div class='col-xxl-12 col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12'>
        <div class='alert alert-info'>" . q($result->title) . "</div></div>";

    if(isset($_POST['bookUsersToAct'])) {
        if (!isset($_POST['token']) || !validate_csrf_token($_POST['token'])) csrf_token_error();

        //get all the active users
        $activeUsers = Database::get()->queryArray("SELECT uid as userID FROM attendance_users WHERE attendance_id = ?d", $attendance_id);
        if ($activeUsers) {
            foreach ($activeUsers as $result) {
                $userInp = intval(@$_POST[$result->userID]); //get the record from the teacher (input name is the user id)
                // //check if there is record for the user for this activity
                $checkForBook = Database::get()->querySingle("SELECT COUNT(id) as count FROM attendance_book
                                                        WHERE attendance_activity_id = ?d AND uid = ?d", $actID, $result->userID);
                if($checkForBook->count) { //update
                    Database::get()->query("UPDATE attendance_book SET attend = ?d WHERE attendance_activity_id = ?d AND uid = ?d", $userInp, $actID, $result->userID);
                } else {  //insert
                    Database::get()->query("INSERT INTO attendance_book SET uid = ?d,
                                                    attendance_activity_id = ?d, attend = ?d, comments = ?s", $result->userID, $actID, $userInp, '');
                }
            }
            //Session::Messages($langAttendanceEdit,"alert-success");
            Session::flash('message',$langAttendanceEdit); 
            Session::flash('alert-class', 'alert-success');
            redirect_to_home_page("modules/attendance/index.php");
        }
    }

    //display users
    $resultUsers = Database::get()->queryArray("SELECT attendance_users.id AS recID, attendance_users.uid AS userID,
                                                user.surname AS surname, user.givenname AS name, user.am AS am, course_user.reg_date AS reg_date
                                            FROM attendance_users, user, course_user
                                                WHERE attendance_id = ?d
                                                AND attendance_users.uid = user.id
                                                AND `user`.id = `course_user`.`user_id`
                                                AND `course_user`.`course_id` = ?d ", $attendance_id, $course_id);
    if ($resultUsers) {
        //table to display the users
        $tool_content .= "<div class='col-12'><div class='form-wrapper shadow-sm p-3 rounded'>
        <form class='form-horizontal' id='user_attendances_form' method='post' action='$_SERVER[SCRIPT_NAME]?course=$course_code&amp;attendance_id=$attendance_id&amp;ins=" . getIndirectReference($actID) . "'>
        <table id='users_table{$course_id}' class='announcements_table custom_list_order'>
            <thead class='notes_thead'>
                <tr>
                  <th class='text-white text-center' width='5%'>$langID</th>
                  <th class='text-white text-left'>$langName $langSurname</th>
                  <th class='text-white text-center'>$langAm</th>
                  <th class='text-white text-center'>$langRegistrationDateShort</th>
                  <th class='text-white text-center'>$langAttendanceAbsences</th>
                </tr>
            </thead>
            <tbody>";

        $cnt = 0;
        foreach ($resultUsers as $resultUser) {
            $cnt++;
            $tool_content .= "<tr>
                <td class='text-center'>$cnt</td>
                <td> " . display_user($resultUser->userID). "</td>
                <td>$resultUser->am</td>
                <td class='text-center'>" . claro_format_locale_date($dateFormatMiddle, strtotime($resultUser->reg_date)) . "</td>
                <td class='text-center'><input type='checkbox' value='1' name='$resultUser->userID'";
                //check if the user has attendace for this activity already OR if it should be automatically inserted here
                $q = Database::get()->querySingle("SELECT attend FROM attendance_book WHERE attendance_activity_id = ?d AND uid = ?d", $actID, $resultUser->userID);
                if(isset($q->attend) && $q->attend == 1) {
                    $tool_content .= " checked";
                }
                $tool_content .= "><input type='hidden' value='" . getIndirectReference($actID) . "' name='actID'></td>";
                $tool_content .= "</tr>";
        }
        $tool_content .= "</tbody></table>";
        $tool_content .= "<div class='form-group'>";
        $tool_content .= "<div class='col-xs-12'>" .
                        form_buttons(array(
                            array(
                                'text' => $langAttendanceBooking,
                                'name' => 'bookUsersToAct',
                                'value'=> $langAttendanceBooking
                                ))).
                "<a href='index.php?course=$course_code&amp;attendance_id=" . $attendance_id . "' class='btn btn-secondary'>$langCancel</a>";
        $tool_content .= "</div></div>";
        $tool_content .= generate_csrf_token_form_field() ."</form></div></div>";
        $tool_content .= "</tbody></table>";
    }
}

/**
 * @brief display attendance activities
 * @global type $tool_content
 * @global type $course_code
 * @global type $langAttendanceActList
 * @global type $langTitle
 * @global type $langType
 * @global type $langAttendanceActivityDate
 * @global type $langAttendanceNoTitle
 * @global type $langExercise
 * @global type $langAssignment
 * @global type $langAttendanceInsAut
 * @global type $langAttendanceInsMan
 * @global type $langDelete
 * @global type $langEditChange
 * @global type $langConfirmDelete
 * @global type $langAttendanceNoActMessage1
 * @global type $langAttendanceActivity
 * @global type $langHere
 * @global type $langAttendanceNoActMessage3
 * @global type $langcsvenc2
 * @global type $langConfig
 * @global type $langUsers
 * @global type $langGradebookAddActivity
 * @global type $langInsertWorkCap
 * @global type $langInsertExerciseCap
 * @global type $langAdd
 * @global type $langExport
 * @global type $langInsertTcMeeting
 * @param type $attendance_id
 */
function display_attendance_activities($attendance_id) {

    global $tool_content, $course_code, $attendance, $langAttendanceInsMan,
           $langAttendanceActList, $langTitle, $langType, $langAttendanceActivityDate,
           $langGradebookNoTitle, $langExercise, $langAssignment,$langAttendanceInsAut,
           $langDelete, $langEditChange, $langConfirmDelete, $langAttendanceNoActMessage1,
           $langHere, $langAttendanceNoActMessage3, $langcsvenc2, $langAttendanceActivity,
           $langConfig, $langStudents, $langGradebookAddActivity, $langInsertWorkCap, $langInsertExerciseCap,
           $langAdd, $langExport, $langBack, $langNoStudentsInAttendance, $langBBB;

    $attendance_id_ind = getIndirectReference($attendance_id);
    $tool_content .= action_bar(
            array(
                array('title' => $langAdd,
                      'level' => 'primary-label',
                      'options' => array(
                          array('title' => $langGradebookAddActivity,
                                'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;attendance_id=$attendance_id&amp;addActivity=1",
                                'icon' => 'fa fa-plus space-after-icon',
                                'class' => ''),
                          array('title' => "$langInsertWorkCap",
                                'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;attendance_id=$attendance_id&amp;addActivityAs=1",
                                'icon' => 'fa fa-flask space-after-icon',
                                'class' => ''),
                          array('title' => "$langInsertExerciseCap",
                                'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;attendance_id=$attendance_id&amp;addActivityEx=1",
                                'icon' => 'fa fa-edit space-after-icon',
                                'class' => ''),
                          array('title' => "$langBBB",
                                'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;attendance_id=$attendance_id&amp;addActivityTc=1",
                                'icon' => 'fa fa-exchange space-after-icon',
                                'class' => '')),
                     'icon' => 'fa-plus'),
                array('title' => $langStudents,
                      'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;attendance_id=$attendance_id&amp;attendanceBook=1",
                      'level' => 'primary-label',
                      'icon' => 'fa-users'),
                array('title' => $langBack,
                  'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code",
                  'icon' => 'fa-reply',
                  'level' => 'primary-label'),
                array('title' => $langConfig,
                      'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;attendance_id=$attendance_id&amp;editSettings=1",
                      'icon' => 'fa-cog'),
                array('title' => "$langExport",
                        'url' => "dumpattendancebook.php?course=$course_code&amp;attendance_id=$attendance_id_ind",
                    'icon' => 'fa-file-excel-o'),
                array('title' => "$langExport ($langcsvenc2)",
                        'url' => "dumpattendancebook.php?course=$course_code&amp;attendance_id=$attendance_id_ind&amp;enc=UTF-8",
                        'icon' => 'fa-file-excel-o'),
            ),
            true
        );


    $participantsNumber = Database::get()->querySingle("SELECT COUNT(id) AS count
                                            FROM attendance_users WHERE attendance_id=?d ", $attendance_id)->count;
    if ($participantsNumber == 0) {
        $tool_content .= "<div class='row p-2'></div>
        <div class='col-xxl-12 col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12'>
            <div class='alert alert-warning'>$langNoStudentsInAttendance <a href='$_SERVER[SCRIPT_NAME]?course=$course_code&amp;attendance_id=" . $attendance->id . "&amp;editUsers=1'>$langHere</a>.</div></div>";
    }
    //get all the available activities
    $result = Database::get()->queryArray("SELECT * FROM attendance_activities WHERE attendance_id = ?d  ORDER BY `DATE` DESC", $attendance_id);
    if (count($result) > 0) {
        $tool_content .= "<div class='table-responsive'>
                        <table class='announcements_table'>
                        <tr class='bg-light' style='height:45px;'><th class='control-label-notes text-center' colspan='5'>$langAttendanceActList</th></tr>
                        <tr class='notes_thead'>
                            <th class='text-white'>$langTitle</th>
                            <th class='text-white'>$langAttendanceActivityDate</th>
                            <th class='text-white'>$langType</th>
                            <th class='text-white'>$langStudents</th>
                            <th class='text-white text-center'><i class='fa fa-cogs'></i></th>
                        </tr>";
        foreach ($result as $details) {
            $content = ellipsize_html($details->description, 50);
            $tool_content .= "<tr><td>";
             if (empty($details->title)) {
                $tool_content .= "<a href='$_SERVER[SCRIPT_NAME]?course=$course_code&amp;attendance_id=$attendance_id&amp;ins=" . getIndirectReference($details->id). "'>$langGradebookNoTitle</a>";
            } else {
                $tool_content .= "<a href='$_SERVER[SCRIPT_NAME]?course=$course_code&amp;attendance_id=$attendance_id&amp;ins=" . getIndirectReference($details->id) . "'>".q($details->title)."</a>";
            }
            $tool_content .= "</td>
                    <td>" . nice_format($details->date, true, true) . "</td>";
            $tool_content .= "<td>";

            if($details->module_auto_id) {
                if($details->module_auto_type == GRADEBOOK_ACTIVITY_EXERCISE) {
                        $tool_content .= $langExercise;
                } elseif($details->module_auto_type == GRADEBOOK_ACTIVITY_ASSIGNMENT) {
                        $tool_content .= $langAssignment;
                }
                if($details->auto) {
                    $tool_content .= "<small class='help-block'>($langAttendanceInsAut)</small>";
                } else {
                    $tool_content .= "<small class='help-block'>($langAttendanceInsMan)</small>";
                }
            } else {
                $tool_content .= $langAttendanceActivity;
            }
            $tool_content .= "</td>";
            $tool_content .= "<td>" . userAttendTotalActivityStats($details->id, $participantsNumber, $attendance_id) . "</td>";
            $tool_content .= "<td class='text-center option-btn-cell'>".
                    action_button(array(
                                array('title' => $langEditChange,
                                    'icon' => 'fa-edit',
                                    'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;attendance_id=$attendance_id&amp;modify=" . getIndirectReference($details->id)
                                    ),
                                array('title' => $langDelete,
                                    'icon' => 'fa-times',
                                    'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;&amp;attendance_id=$attendance_id&amp;delete=" .getIndirectReference($details->id),
                                    'confirm' => $langConfirmDelete,
                                    'class' => 'delete'))).
                    "</td></tr>";
        } // end of while
        $tool_content .= "</table></div>";
    } else {
        $tool_content .= "<div class='row p-2'></div>
        <div class='col-xxl-12 col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12'>
            <div class='alert alert-warning'>$langAttendanceNoActMessage1 $langAttendanceNoActMessage3</div></div>";
    }
}

/**
 * @brief display available exercises for adding them to attendance
 * @global type $course_id
 * @global type $course_code
 * @global type $tool_content
 * @global type $langGradebookActivityDate2
 * @global type $langDescription
 * @global type $langAdd
 * @global type $langAttendanceNoActMessageExe4
 * @global type $langTitle
 * @param type $attendance_id
 */
function attendance_display_available_exercises($attendance_id) {

    global $course_id, $course_code, $tool_content,
           $langGradebookActivityDate2, $langDescription, $langAdd, $langAttendanceNoActMessageExe4, $langTitle;

    $checkForExer = Database::get()->queryArray("SELECT * FROM exercise WHERE exercise.course_id = ?d
                                AND exercise.active = 1 AND exercise.id
                                NOT IN (SELECT module_auto_id FROM attendance_activities WHERE module_auto_type = 2 AND attendance_id = ?d)", $course_id, $attendance_id);
    $checkForExerNumber = count($checkForExer);
    if ($checkForExerNumber > 0) {
        $tool_content .= "<div class='table-responsive'>";
        $tool_content .= "<table class='announcements_table'>";
        $tool_content .= "<tr class='notes_thead'><th class='text-white'>$langTitle</th><th class='text-white'>$langGradebookActivityDate2</th><th class='text-white'>$langDescription</th>";
        $tool_content .= "<th class='text-white text-center'><i class='fa fa-cogs'></i></th>";
        $tool_content .= "</tr>";

        foreach ($checkForExer as $newExerToGradebook) {
            $content = ellipsize_html($newExerToGradebook->description, 50);
            $tool_content .= "<tr><td><b>";
            if (!empty($newExerToGradebook->title)) {
                $tool_content .= q($newExerToGradebook->title);
            }
            $tool_content .= "</b>";
            $tool_content .= "</td>"
                    . "<td><div class='smaller'><span class='day'>" . nice_format($newExerToGradebook->start_date, true, true) . " </div></td>"
                    . "<td>" . $content . "</td>";
            $tool_content .= "<td width='70' class='text-center'>" . icon('fa-plus', $langAdd, "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;attendance_id=$attendance_id&amp;addCourseActivity=" . $newExerToGradebook->id . "&amp;type=2");
        }
        $tool_content .= "</td></tr></table></div>";
    } else {
        $tool_content .= "<div class='row p-2'></div>
        <div class='col-xxl-12 col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12'>
        <div class='alert alert-warning'>$langAttendanceNoActMessageExe4</div></div>";
    }
}

/**
 * @brief display available assignments for adding them to attendance
 * @global type $course_id
 * @global type $course_code
 * @global type $tool_content
 * @global type $dateFormatLong
 * @global type $m
 * @global type $langDescription
 * @global type $langAttendanceNoActMessageAss4
 * @global type $langAdd
 * @global type $langTitle
 * @global type $langHour
 * @param type $attendance_id
 */
function attendance_display_available_assignments($attendance_id) {

    global $course_id, $course_code, $tool_content, $dateFormatLong,
           $m, $langDescription, $langAttendanceNoActMessageAss4,
           $langAdd, $langTitle, $langHour;

    $checkForAss = Database::get()->queryArray("SELECT * FROM assignment WHERE assignment.course_id = ?d
                                                AND assignment.active = 1
                                                AND assignment.id NOT IN
                                            (SELECT module_auto_id FROM attendance_activities WHERE module_auto_type = 1
                                                        AND attendance_id = ?d)", $course_id, $attendance_id);

    $checkForAssNumber = count($checkForAss);

    if ($checkForAssNumber > 0) {
        $tool_content .= "<div class='table-responsive'>
                            <table class='announcements_table'";
        $tool_content .= "<tr><th style='background-color:#003F87; height:45px;' class='text-white'>$langTitle</th><th style='background-color:#003F87; height:45px;' class='text-white'>$m[deadline]</th><th style='background-color:#003F87; height:45px;' class='text-white'>$langDescription</th>";
        $tool_content .= "<th style='background-color:#003F87; height:45px;' class='text-white text-center'><i class='fa fa-cogs'></i></th>";
        $tool_content .= "</tr>";
        foreach ($checkForAss as $newAssToGradebook) {
            $content = ellipsize_html($newAssToGradebook->description, 50);
            if($newAssToGradebook->assign_to_specific){
                $content .= "$m[WorkAssignTo]:<br>";
                $checkForAssSpec = Database::get()->queryArray("SELECT user_id, user.surname, user.givenname
                                                    FROM `assignment_to_specific`, user
                                                    WHERE user_id = user.id AND assignment_id = ?d", $newAssToGradebook->id);
                foreach ($checkForAssSpec as $checkForAssSpecR) {
                    $content .= q($checkForAssSpecR->surname). " " . q($checkForAssSpecR->givenname) . "<br>";
                }
            }
            if ((int) $newAssToGradebook->deadline){
                $d = strtotime($newAssToGradebook->deadline);
                $date_str = ucfirst(claro_format_locale_date($dateFormatLong, $d));
                $hour_str = "($langHour: " . ucfirst(date('H:i', $d)).")";
            } else {
                $date_str = $m['no_deadline'];
                $hour_str = "";
            }
            $tool_content .= "<tr><td><b>";
            if (!empty($newAssToGradebook->title)) {
                $tool_content .= q($newAssToGradebook->title);
            }
            $tool_content .= "</b>";
            $tool_content .= "</td>"
                    . "<td><div class='smaller'><span class='day'>$date_str</span> $hour_str </div></td>"
                    . "<td>" . $content . "</td>";
            $tool_content .= "<td width='70' class='text-center'>".icon('fa-plus', $langAdd, "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;attendance_id=$attendance_id&amp;addCourseActivity=" . $newAssToGradebook->id . "&amp;type=1");
        } // end of while
        $tool_content .= "</tr></table></div>";
    } else {
        $tool_content .= "<div class='row p-2'></div>
        <div class='col-xxl-12 col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12'>
        <div class='alert alert-warning'>$langAttendanceNoActMessageAss4</div></div>";
    }
}

/**
 * @brief display available tc sessions for adding them to attendance
 * @global type $tool_content
 * @global type $course_id
 * @global type $course_code
 * @global type $langAttendanceNoActMessageTc
 * @global type $langGradebookActivityDate
 * @global type $langTitle
 * @global type $langAdd
 * @param type $attendance_id
 */
function attendance_display_available_tc($attendance_id) {

    global $tool_content, $course_code, $course_id, $langGradebookActivityDate,
            $langTitle, $langAdd, $langAttendanceNoActMessageTc;

    $checkForTc = Database::get()->queryArray("SELECT * FROM tc_session WHERE course_id = ?d
                                                AND active = '1'
                                                AND (end_date IS NULL OR end_date >= " . DBHelper::timeAfter() . ")
                                                AND id NOT IN
                                            (SELECT module_auto_id FROM attendance_activities WHERE module_auto_type = 1
                                                        AND attendance_id = ?d)", $course_id, $attendance_id);

    $checkForTcNumber = count($checkForTc);

    if ($checkForTcNumber > 0) {
        $tool_content .= "<div class='table-responsive'>
                            <table class='announcements_table'";
        $tool_content .= "<tr class='notes_thead'><th class='text-white'>$langTitle</th><th class='text-white'>$langGradebookActivityDate</th>";
        $tool_content .= "<th class='text-white text-center'><i class='fa fa-cogs'></i></th>";
        $tool_content .= "</tr>";
        foreach ($checkForTc as $data) {
            $tool_content .= "<tr><td><b>" . q($data->title) . "</b></td>";
            $tool_content .= "<td>".nice_format($data->start_date, true) . "</td>";
            $tool_content .= "<td width='70' class='text-center'>".icon('fa-plus', $langAdd, "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;attendance_id=$attendance_id&amp;addCourseActivity=" . $data->id . "&amp;type=4");
        } // end of while
        $tool_content .= "</tr></table></div>";
    } else {
        $tool_content .= "<div class='row p-2'></div>
        <div class='col-xxl-12 col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12'>
        <div class='alert alert-warning'>$langAttendanceNoActMessageTc</div></div>";
    }
}

/**
 * @brief add other attendance activity
 * @global type $tool_content
 * @global type $course_code
 * @global type $langTitle
 * @global type $langAttendanceInsAut
 * @global type $langAdd
 * @global type $langAdd
 * @global type $langSave
 * @global type $langAttendanceActivityDate
 * @param type $attendance_id
 */
function add_attendance_other_activity($attendance_id) {

    global $tool_content, $course_code, $langDescription,
           $langTitle, $langAttendanceInsAut, $langAdd,
           $langAdd, $langSave, $langAttendanceActivityDate,
           $language, $head_content;

    load_js('bootstrap-datetimepicker');
    $head_content .= "
    <script type='text/javascript'>
    $(function() {
        $('#startdatepicker').datetimepicker({
            format: 'dd-mm-yyyy hh:ii',
            pickerPosition: 'bottom-right',
            language: '$language',
            autoclose: true
        });
    });
    </script>";

    $date_error = Session::getError('date');
    $tool_content .= "
    <div class='row p-2'></div>
    <div class='col-sm-12'>
            <div class='form-wrapper shadow-sm p-3 rounded'>
                <form class='form-horizontal' role='form' method='post' action='$_SERVER[SCRIPT_NAME]?course=$course_code&amp;attendance_id=$attendance_id'>
                    <fieldset>";
                    if (isset($_GET['modify'])) { // modify an existing attendance activity

                        $id  = filter_var(getDirectReference($_GET['modify']), FILTER_VALIDATE_INT);
                        //All activity data (check if it's in this attendance)
                        $modifyActivity = Database::get()->querySingle("SELECT * FROM attendance_activities WHERE id = ?d AND attendance_id = ?d", $id, $attendance_id);
                        $titleToModify = Session::has('actTitle') ? Session::get('actTitle') : $modifyActivity->title;
                        $contentToModify = Session::has('actDesc') ? Session::get('actDesc') : $modifyActivity->description;
                        $attendanceActivityToModify = $id;
                        if (Session::has('date')) {
                            $date = Session::get('date');
                        }
                        if ($modifyActivity->date) {
                            $date = DateTime::createFromFormat('Y-m-d H:i:s', $modifyActivity->date)->format('d-m-Y H:i:s');
                        }
                        $module_auto_id = $modifyActivity->module_auto_id;
                        $auto = $modifyActivity->auto;
                    }  else { //new activity
                        $attendanceActivityToModify = "";
                        $titleToModify = Session::has('actTitle') ? Session::get('actTitle') : '';
                        $contentToModify = Session::has('actDesc') ? Session::get('actDesc') : '';
                        $date = Session::has('date') ? Session::get('date') : '';
                    }
                    if (!isset($contentToModify)) $contentToModify = "";
                    @$tool_content .= "
                        <div class='form-group'>
                            <label for='actTitle' class='col-sm-6 control-label-notes'>$langTitle:</label>
                            <div class='col-sm-12'>
                                <input type='text' class='form-control' name='actTitle' value='$titleToModify'/>
                            </div>
                        </div>

                        <div class='row p-2'></div>

                        <div class='form-group".($date_error ? " has-error" : "")."'>
                            <label for='date' class='col-sm-6 control-label-notes'>$langAttendanceActivityDate:</label>
                            <div class='col-sm-12'>
                                <input type='text' class='form-control' name='date' id='startdatepicker' value='" . datetime_remove_seconds($date) . "'/>
                                <span class='help-block'>$date_error</span>
                            </div>
                        </div>

                        <div class='row p-2'></div>

                        <div class='form-group'>
                            <label for='actDesc' class='col-sm-6 control-label-notes'>$langDescription:</label>
                            <div class='col-sm-12'>
                                " . rich_text_editor('actDesc', 4, 20, $contentToModify) . "
                            </div>
                        </div>";
                    if (isset($module_auto_id) && $module_auto_id != 0) { //accept the auto attendance mechanism
                        $tool_content .= "
                        <div class='row p-2'></div>
                        
                        <div class='form-group'>
                            <label for='weight' class='col-sm-6 control-label-notes'>$langAttendanceInsAut:</label>
                                <div class='col-sm-12'><input type='checkbox' value='1' name='auto' ";
                        if ($auto) {
                            $tool_content .= " checked";
                        }
                        $tool_content .= "/></div>";
                    }
                    $tool_content .= "
                    <div class='row p-2'></div>

                    <div class='form-group'>
                    <div class='col-sm-10 col-sm-offset-2'>".form_buttons(array(
                        array(
                            'text' => $langSave,
                            'name' => 'submitAttendanceActivity',
                            'value'=> $langAdd
                        ),
                        array(
                            'href' => "$_SERVER[SCRIPT_NAME]?course=$course_code"
                        )
                    ))."</div></div>";
                    if (isset($_GET['modify'])) {
                        $tool_content .= "<input type='hidden' name='id' value='" . $attendanceActivityToModify . "'>";
                    } else {
                        $tool_content .= " <input type='hidden' name='id' value=''>";
                    }
                    $tool_content .= "</fieldset>
                            </form>
                        </div></div>";
}



/**
 * @brief add available activity in attendance
 * @global type $course_id
 * @param type $attendance_id
 * @param type $id
 * @param type $type
 */
function add_attendance_activity($attendance_id, $id, $type) {

    global $course_id;
    $actTitle = "";
    if ($type == GRADEBOOK_ACTIVITY_ASSIGNMENT) { //  add  assignments
        //checking if it's new or not
        $checkForAss = Database::get()->querySingle("SELECT * FROM assignment WHERE assignment.course_id = ?d
                                                        AND assignment.active = 1 AND assignment.id
                                            NOT IN (SELECT module_auto_id FROM attendance_activities
                                                    WHERE module_auto_type = 1
                                                    AND attendance_id = ?d)
                                                    AND assignment.id = ?d", $course_id, $attendance_id, $id);
        if ($checkForAss) {
            $module_auto_id = $checkForAss->id;
            $module_auto_type = 1;
            $module_auto = 1; //auto grade enabled by default
            $actTitle = $checkForAss->title;
            $actDate = $checkForAss->deadline;
            $actDesc = $checkForAss->description;
            Database::get()->query("INSERT INTO attendance_activities
                                        SET attendance_id = ?d, title = ?s, `date` = ?t, description = ?s,
                                        module_auto_id = ?d, auto = ?d, module_auto_type = ?d",
                                    $attendance_id, $actTitle, $actDate, $actDesc, $module_auto_id, $module_auto, $module_auto_type);
            $sql = Database::get()->queryArray("SELECT uid FROM attendance_users WHERE attendance_id = ?d", $attendance_id);
            foreach ($sql as $u) {
                $grd = Database::get()->querySingle("SELECT * "
                        . "FROM assignment_submit "
                        . "WHERE assignment_id =?d "
                        . "AND uid = $u->uid", $id);
                if($grd) {
                    update_attendance_book($u->uid, $id, GRADEBOOK_ACTIVITY_ASSIGNMENT);
                }
            }
        }
    }

    if ($type == GRADEBOOK_ACTIVITY_EXERCISE) { // add exercises
        //checking if it is new or not
        $checkForExe = Database::get()->querySingle("SELECT * FROM exercise WHERE exercise.course_id = ?d
                                                            AND exercise.active = 1 AND exercise.id
                                                    NOT IN (SELECT module_auto_id FROM attendance_activities
                                                                WHERE module_auto_type = 2 AND attendance_id = ?d)
                                                    AND exercise.id = ?d", $course_id, $attendance_id, $id);
        if ($checkForExe) {
            $module_auto_id = $checkForExe->id;
            $module_auto_type = 2; //2 for exercises
            $module_auto = 1;
            $actTitle = $checkForExe->title;
            $actDate = $checkForExe->end_date;
            $actDesc = $checkForExe->description;

            Database::get()->query("INSERT INTO attendance_activities
                                        SET attendance_id = ?d, title = ?s, `date` = ?t, description = ?s,
                                        module_auto_id = ?d, auto = ?d, module_auto_type = ?d",
                                    $attendance_id, $actTitle, $actDate, $actDesc, $module_auto_id, $module_auto, $module_auto_type);
            $sql = Database::get()->queryArray("SELECT uid FROM attendance_users WHERE attendance_id = ?d", $attendance_id);
            foreach ($sql as $u) {
                $exerciseUserRecord = Database::get()->querySingle("SELECT * FROM exercise_user_record WHERE eid = ?d AND uid = $u->uid AND attempt_status != ?s AND attempt_status != ?s LIMIT 1", $id, ATTEMPT_PAUSED, ATTEMPT_CANCELED);
                if ($exerciseUserRecord) {
                    update_attendance_book($u->uid, $id, GRADEBOOK_ACTIVITY_EXERCISE);
                }
            }
        }
    }

    if ($type == GRADEBOOK_ACTIVITY_TC) { // add tc
        $checkForTc = Database::get()->querySingle("SELECT * FROM tc_session WHERE course_id = ?d
                                                AND active = '1'
                                                AND (end_date IS NULL OR end_date >= " . DBHelper::timeAfter() . ")
                                                AND id NOT IN
                                            (SELECT module_auto_id FROM attendance_activities WHERE module_auto_type = 4
                                                        AND attendance_id = ?d) AND id = ?d", $course_id, $attendance_id, $id);

        if ($checkForTc) {
            $module_auto_id = $checkForTc->id;
            $module_auto_type = 4; // 4 for tc
            $module_auto = 1;
            $actTitle = $checkForTc->title;
            $actDate = $checkForTc->start_date;
            $actDesc = $checkForTc->description;
            $meetingid = $checkForTc->meeting_id;

            Database::get()->query("INSERT INTO attendance_activities
                                        SET attendance_id = ?d, title = ?s, `date` = ?t, description = ?s,
                                        module_auto_id = ?d, auto = ?d, module_auto_type = ?d",
                                    $attendance_id, $actTitle, $actDate, $actDesc, $module_auto_id, $module_auto, $module_auto_type);
            $sql = Database::get()->queryArray("SELECT uid FROM attendance_users WHERE attendance_id = ?d", $attendance_id);
            foreach ($sql as $u) {
                $TcUserRecord = Database::get()->querySingle("SELECT * FROM tc_attendance WHERE meetingid = ?s "
                                                            . "AND bbbuserid = (SELECT bbbuserid "
                                                                                . "FROM tc_log "
                                                                                . "WHERE fullName = '" . uid_to_name($u->uid) . "' "
                                                                                . "AND meetingid = ?s ORDER BY `date` LIMIT 1)", $meetingid, $meetingid);
                if ($TcUserRecord) {
                    update_attendance_book($u->uid, $id, GRADEBOOK_ACTIVITY_TC);
                }
            }
        }
    }
    return $actTitle;
}


/**
 *
 * @param type $attendance_id
 * @param type $uid
 */
function update_user_attendance_activities($attendance_id, $uid) {
    $attendanceActivities = Database::get()->queryArray("SELECT * FROM attendance_activities WHERE attendance_id = ?d AND auto = 1", $attendance_id);
    foreach ($attendanceActivities as $attendanceActivity) {
        if ($attendanceActivity->module_auto_type == GRADEBOOK_ACTIVITY_EXERCISE) {
            $exerciseUserRecord = Database::get()->querySingle("SELECT * FROM exercise_user_record WHERE eid = ?d AND uid = $uid AND attempt_status != ?d AND attempt_status != ?d LIMIT 1", $attendanceActivity->module_auto_id, ATTEMPT_PAUSED, ATTEMPT_CANCELED);
            if ($exerciseUserRecord) {
                $allow_insert = TRUE;
            }
        } elseif ($attendanceActivity->module_auto_type == GRADEBOOK_ACTIVITY_ASSIGNMENT) {
            $grd = Database::get()->querySingle("SELECT * FROM assignment_submit WHERE assignment_id = ?d AND uid = $uid", $attendanceActivity->module_auto_id);
            if ($grd) {
                $allow_insert = TRUE;
            }
        }
        if (isset($allow_insert) && $allow_insert) {
            update_attendance_book($uid, $attendanceActivity->module_auto_id, $attendanceActivity->module_auto_type, $attendance_id);
        }
        unset($allow_insert);
    }
}

/**
 * @brief create new attendance
 * @global string $tool_content
 * @global type $course_code
 * @global type $langNewAttendance2
 * @global type $langTitle
 * @global type $langSave
 * @global type $langInsert
 */
function new_attendance() {

    global $tool_content, $course_code, $langNewAttendance2, $head_content,
           $langTitle, $langSave, $langInsert, $langAttendanceLimitNumber,
           $attendance_limit, $langStart, $langEnd, $language;

    load_js('bootstrap-datetimepicker');
    $head_content .= "
    <script type='text/javascript'>
        $(function() {
            $('#start_date, #end_date').datetimepicker({
                format: 'dd-mm-yyyy hh:ii',
                pickerPosition: 'bottom-right',
                language: '".$language."',
                autoclose: true
            });
        });
    </script>";
    $title_error = Session::getError('title');
    $title = Session::has('title') ? Session::get('title') : '';
    $start_date_error = Session::getError('start_date');
    $start_date = Session::has('start_date') ? Session::get('start_date') : '';
    $end_date_error = Session::getError('end_date');
    $end_date = Session::has('end_date') ? Session::get('end_date') : '';
    $limit_error  = Session::getError('limit');
    $limit = Session::has('limit') ? Session::get('limit') : '';

    $tool_content .= "<div class='col-sm-12'><div class='form-wrapper shadow-sm p-3 rounded'>
            <form class='form-horizontal' role='form' method='post' action='$_SERVER[SCRIPT_NAME]?course=$course_code' onsubmit=\"return checkrequired(this, 'antitle');\">
  
            
            <div class='form-group mt-3'>
                    <label class='col-12 control-label-notes'>$langNewAttendance2</label></div>
                    <div class='form-group".($title_error ? " has-error" : "")."'>
                        <div class='col-12'>
                            <input class='form-control' type='text' placeholder='$langTitle' name='title'>
                            <span class='help-block'>$title_error</span>
                        </div>
                    </div>

                    

                    <div class='form-group mt-3".($start_date_error ? " has-error" : "")."'>
                        <div class='col-12'>
                            <label class='control-label-notes'>$langStart</label>
                        </div>
                        <div class='col-12'>
                            <input class='form-control' type='text' name='start_date' id='start_date' value='$start_date'>
                            <span class='help-block'>$start_date_error</span>
                        </div>
                    </div>

                    

                    <div class='form-group mt-3".($end_date_error ? " has-error" : "")."'>
                        <div class='col-12'>
                            <label class='control-label-notes'>$langEnd</label>
                        </div>
                        <div class='col-12'>
                            <input class='form-control' type='text' name='end_date' id='end_date' value='$end_date'>
                            <span class='help-block'>$end_date_error</span>
                        </div>
                    </div>

               

                    <div class='form-group mt-3".($limit_error ? " has-error" : "")."'>
                        <label class='col-12 control-label-notes'>$langAttendanceLimitNumber:</label>
                        <div class='col-sm-12'>
                            <input class='form-control' type='text' name='limit' value='$attendance_limit'>
                            <span class='help-block'>$limit_error</span>
                        </div>
                    </div>

                    

                    <div class='form-group mt-3'>
                        <div class='col-12'>".form_buttons(array(
                            array(
                                    'text' => $langSave,
                                    'name' => 'newAttendance',
                                    'value'=> $langInsert
                                ),
                            array(
                                'href' => "$_SERVER[SCRIPT_NAME]?course=$course_code"
                                )
                            ))."</div>
                    </div>
            </form>
        </div></div>";
}

/**
 * @brief dislay user presences
 * @global type $course_code
 * @global type $tool_content
 * @global type $langTitle
 * @global type $langType
 * @global type $langAttendanceNewBookRecord
 * @global type $langDate
 * @global type $langAttendanceNoActMessage1
 * @global type $langAttendanceBooking
 * @global type $langAttendanceActAttend
 * @global type $langAttendanceActCour
 * @global type $langAttendanceInsAut
 * @global type $langAttendanceInsMan
 * @global type $langGradebookUpToDegree
 * @global type $langAttendanceBooking
 * @param type $attendance_id
 */
function display_user_presences($attendance_id) {

    global $course_code, $tool_content,
           $langTitle, $langType, $langAttendanceNewBookRecord, $langDate,
           $langAttendanceNoActMessage1, $langAttendanceBooking,
           $langAttendanceActAttend, $langAttendanceActCour,
           $langAttendanceInsAut, $langAttendanceInsMan,
           $langAttendanceBooking;

        $attendance_limit = get_attendance_limit($attendance_id);

        $limit = isset($_REQUEST['limit']) ? intval($_REQUEST['limit']) : 0;
        $userID = intval($_GET['book']); //user
        //check if there are booking records for the user, otherwise alert message for first input
        $checkForRecords = Database::get()->querySingle("SELECT COUNT(attendance_book.id) AS count FROM attendance_book, attendance_activities
                            WHERE attendance_book.attendance_activity_id = attendance_activities.id
                            AND uid = ?d AND attendance_activities.attendance_id = ?d", $userID, $attendance_id)->count;
        if(!$checkForRecords) {
            $tool_content .="<div class='alert alert-success'>$langAttendanceNewBookRecord</div>";
        }

        //get all the activities
        $result = Database::get()->queryArray("SELECT * FROM attendance_activities WHERE attendance_id = ?d  ORDER BY `DATE` DESC", $attendance_id);
        $actNumber = count($result);
        if ($actNumber > 0) {
            $tool_content .= "<h5>". display_user($userID) ."</h5>";
            $tool_content .= "<form method='post' action='$_SERVER[SCRIPT_NAME]?course=$course_code&amp;attendance_id=$attendance_id&amp;book=" . $userID . "' onsubmit=\"return checkrequired(this, 'antitle');\">
                              <table class='announcements_table'>";
            $tool_content .= "<tr class='notes_thead'><th class='text-white'>$langTitle</th><th class='text-white'>$langDate</th><th class='text-white'>$langType</th>";
            $tool_content .= "<th width='10' class='text-white text-center'>$langAttendanceBooking</th>";
            $tool_content .= "</tr>";
        } else {
            $tool_content .= "<div class='row p-2'></div>
            <div class='col-xxl-12 col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12'>
            <div class='alert alert-warning'>$langAttendanceNoActMessage1</div></div>";
        }

        if ($result) {
            foreach ($result as $activity) {
                //check if there is auto mechanism
                if($activity->auto == 1) {
                    if($activity->module_auto_type) { //assignments, exercises, lp(scorms)
                        $userAttend = attendForAutoActivities($userID, $activity->module_auto_id, $activity->module_auto_type);
                        if ($userAttend == 0) {
                            $q = Database::get()->querySingle("SELECT attend FROM attendance_book WHERE attendance_activity_id = ?d AND uid = ?d", $activity->id, $userID);
                            if ($q) {
                                $userAttend = $q->attend;
                            }
                        }
                    }
                } else {
                    $q = Database::get()->querySingle("SELECT attend FROM attendance_book
                                                        WHERE attendance_activity_id = ?d AND uid = ?d", $activity->id, $userID);
                    if ($q) {
                        $userAttend = $q->attend;
                    } else {
                        $userAttend = 0;
                    }
                }
                $content = standard_text_escape($activity->description);
                $tool_content .= "<tr><td><b>";

                if (!empty($activity->title)) {
                    $tool_content .= q($activity->title);
                }
                $tool_content .= "</b>";
                $tool_content .= "</td>";
                if($activity->date){
                    $tool_content .= "<td><div class='smaller'><span class='day'>" . nice_format($activity->date, true, true) . "</div></td>";
                } else {
                    $tool_content .= "<td>-</td>";
                }
                if ($activity->module_auto_id) {
                    $tool_content .= "<td class='smaller'>$langAttendanceActCour";
                    if ($activity->auto) {
                        $tool_content .= "<br>($langAttendanceInsAut)";
                    } else {
                        $tool_content .= "<br>($langAttendanceInsMan)";
                    }
                    $tool_content .= "</td>";
                } else {
                    $tool_content .= "<td class='smaller'>$langAttendanceActAttend</td>";
                }
                $tool_content .= "<td class='text-center'>
                <input type='checkbox' value='1' name='" . $activity->id . "'";
                if(isset($userAttend) && $userAttend) {
                    $tool_content .= " checked";
                }
                $tool_content .= ">
                <input type='hidden' value='" . $userID . "' name='userID'>
                </td></tr>";
            } // end of while
        }
        $tool_content .= "</table>";
        $tool_content .= "<div class='pull-right'><input class='btn btn-primary' type='submit' name='bookUser' value='$langAttendanceBooking'></div>";
}


/**
 * @brief display all users presences
 * @global type $course_id
 * @global type $course_code
 * @global type $tool_content
 * @global type $langName
 * @global type $langSurname
 * @global type $langID
 * @global type $langAm
 * @global type $langRegistrationDateShort
 * @global type $langAttendanceAbsences
 * @global type $langAttendanceBook
 * @global type $langAttendanceDelete
 * @global type $langConfirmDelete
 * @global type $langNoRegStudent
 * @global type $langHere
 * @param type $attendance_id
 */
function display_all_users_presences($attendance_id) {

    global $course_id, $course_code, $tool_content, $langName, $langSurname,
           $langID, $langAm, $langRegistrationDateShort, $langAttendanceAbsences,
           $langAttendanceBook, $langAttendanceDelete, $langConfirmDelete,
           $langNoStudentsInAttendance, $langHere, $dateFormatMiddle;

    $attendance_limit = get_attendance_limit($attendance_id);

    $resultUsers = Database::get()->queryArray("SELECT attendance_users.id as recID,
                                                    attendance_users.uid AS userID, user.surname AS surname,
                                                    user.givenname AS name, user.am AS am,
                                                    DATE(course_user.reg_date) AS reg_date
                                                FROM attendance_users, user, course_user
                                                    WHERE attendance_id = ?d
                                                    AND attendance_users.uid = user.id
                                                    AND `user`.id = `course_user`.`user_id`
                                                    AND `course_user`.`course_id` = ?d ", $attendance_id, $course_id);
    if (count($resultUsers)) {
        //table to display the users
        $tool_content .= "<table id='users_table{$course_id}' class='announcements_table custom_list_order'>
            <thead class='notes_thead'>
                <tr>
                  <th width='1' class='text-white'>$langID</th>
                  <th class='text-white'>$langName $langSurname</th>
                  <th class='text-white text-center'>$langAm</th>
                  <th class='text-white text-center'>$langRegistrationDateShort</th>
                  <th class='text-white text-center'>$langAttendanceAbsences</th>
                  <th class='text-white text-center'><i class='fa fa-cogs'></i></th>
                </tr>
            </thead>
            <tbody>";
        $cnt = 0;
        foreach ($resultUsers as $resultUser) {
            $cnt++;
            $tool_content .= "<tr>
                <td>$cnt</td>
                <td>" . display_user($resultUser->userID) . "</td>
                <td>$resultUser->am</td>
                <td class='text-center'>" . claro_format_locale_date($dateFormatMiddle, strtotime($resultUser->reg_date)) . "</td>
                <td class='text-center'>" . userAttendTotal($attendance_id, $resultUser->userID) . "/" . $attendance_limit . "</td>
                <td class='option-btn-cell'>"
                   . action_button(array(
                        array('title' => $langAttendanceBook,
                            'icon' => 'fa-plus',
                            'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;attendance_id=$attendance_id&amp;book=" . $resultUser->userID),
                       array('title' => $langAttendanceDelete,
                            'icon' => 'fa-times',
                            'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;at=$attendance_id&amp;ruid=$resultUser->userID&amp;deleteuser=yes",
                            'confirm' => $langConfirmDelete,
                            'class' => 'delete')))."</td>
            </tr>";
        }
        $tool_content .= "</tbody></table>";
    } else {
        $tool_content .= "<div class='row p-2'></div>
        <div class='col-xxl-12 col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12'><div class='alert alert-warning'>$langNoStudentsInAttendance <a href='$_SERVER[SCRIPT_NAME]?course=$course_code&amp;attendance_id=$attendance_id&amp;editUsers=1'>$langHere</a>.</div></div>";
    }
}

/**
 * @brief insert/modify attendance settings
 * @global string $tool_content
 * @global type $course_code
 * @global type $langTitle
 * @global type $langSave
 * @global type $langAttendanceLimitNumber
 * @global type $langAttendanceUpdate
 * @global type $langSave
 * @global type $attendance_title
 * @param type $attendance_id
 */
function attendance_settings($attendance_id) {

    global $tool_content, $course_code, $language,
           $langTitle, $langSave, $langAttendanceLimitNumber,
           $langAttendanceUpdate, $langSave, $head_content,
           $attendance, $langStart, $langEnd;
    load_js('bootstrap-datetimepicker');
    $head_content .= "
    <script type='text/javascript'>
        $(function() {
            $('#start_date, #end_date').datetimepicker({
                format: 'dd-mm-yyyy hh:ii',
                pickerPosition: 'bottom-right',
                language: '".$language."',
                autoclose: true
            });
        });
    </script>";
    $title_error = Session::getError('title');
    $title = Session::has('title') ? Session::get('title') : $attendance->title;
    $start_date_error = Session::getError('start_date');
    $start_date = Session::has('start_date') ? Session::get('start_date') : DateTime::createFromFormat('Y-m-d H:i:s', $attendance->start_date)->format('d-m-Y H:i');
    $end_date_error = Session::getError('end_date');
    $end_date = Session::has('end_date') ? Session::get('end_date') : DateTime::createFromFormat('Y-m-d H:i:s', $attendance->end_date)->format('d-m-Y H:i');
    $limit_error  = Session::getError('limit');
    $limit = Session::has('limit') ? Session::get('limit') : get_attendance_limit($attendance_id);
    // update attendance title
    $tool_content .= "

    <div class='col-sm-12'>
            <div class='form-wrapper shadow-sm p-3 rounded'>
                <form class='form-horizontal' role='form' method='post' action='$_SERVER[SCRIPT_NAME]?course=$course_code&attendance_id=$attendance_id'>
                    <div class='form-group".($title_error ? " has-error" : "")."'>
                        <label class='col-12 control-label-notes'>$langTitle</label>
                        <div class='col-12'>
                            <input class='form-control' type='text' placeholder='$langTitle' name='title' value='".q($title)."'>
                            <span class='help-block'>$title_error</span>
                        </div>
                    </div>

                    <div class='row p-2'></div>

                    <div class='form-group".($start_date_error ? " has-error" : "")."'>
                        <div class='col-12'>
                            <label class='control-label-notes'>$langStart</label>
                        </div>
                        <div class='col-12'>
                            <input class='form-control' type='text' name='start_date' id='start_date' value='$start_date'>
                            <span class='help-block'>$start_date_error</span>
                        </div>
                    </div>

                    <div class='row p-2'></div>

                    <div class='form-group".($end_date_error ? " has-error" : "")."'>
                        <div class='col-12'>
                            <label class='control-label-notes'>$langEnd</label>
                        </div>
                        <div class='col-12'>
                            <input class='form-control' type='text' name='end_date' id='end_date' value='$end_date'>
                            <span class='help-block'>$end_date_error</span>
                        </div>
                    </div>

                    <div class='row p-2'></div>

                    <div class='form-group".($limit_error ? " has-error" : "")."'>
                        <label class='col-12 control-label-notes'>$langAttendanceLimitNumber:</label>
                        <div class='col-sm-12'>
                            <input class='form-control' type='text' name='limit' value='$limit'/>
                            <span class='help-block'>$limit_error</span>
                        </div>
                    </div>

                    <div class='row p-2'></div>

                    <div class='form-group'>
                        <div class='col-12'>".form_buttons(array(
                            array(
                                'text' => $langSave,
                                'name' => 'submitAttendanceBookSettings',
                                'value'=> $langAttendanceUpdate
                            ),
                            array(
                                'href' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;attendance_id=$attendance_id"
                            )
                        ))."</div>
                        </div>
                    </fieldset>
                </form>
            </div></div>";
}

/**
 * @brief modify user attendance settings
 * @global string $tool_content
 * @global type $course_code
 * @global type $langGroups
 * @global type $langAttendanceUpdate
 * @global type $langAttendanceInfoForUsers
 * @global type $langRegistrationDate
 * @global type $langFrom2
 * @global type $langTill
 * @global type $langRefreshList
 * @global type $langUserDuration
 * @global type $langAll
 * @global type $langSpecificUsers
 * @global type $langStudents
 * @global type $langMove
 * @global type $langParticipate
 * @param type $attendance_id
 */
function user_attendance_settings($attendance_id) {

    global $tool_content, $course_code, $langGroups, $language,
           $langAttendanceUpdate, $langAttendanceInfoForUsers,
           $langRegistrationDate, $langFrom2, $langTill, $langRefreshList,
           $langUserDuration, $langAll, $langSpecificUsers, $head_content,
           $langStudents, $langMove, $langParticipate, $attendance;

    load_js('bootstrap-datetimepicker');
    $head_content .= "
    <script type='text/javascript'>
        $(function() {
            $('#UsersStart, #UsersEnd').datetimepicker({
                format: 'dd-mm-yyyy hh:ii',
                pickerPosition: 'bottom-right',
                language: '".$language."',
                autoclose: true
            });
        });
    </script>";

    // default values
    $UsersStart = date('d-m-Y', strtotime('now -6 month'));
    $UsersEnd = date('d-m-Y', strtotime('now'));

    $start_date = DateTime::createFromFormat('Y-m-d H:i:s', $attendance->start_date)->format('d-m-Y H:i');
    $end_date = DateTime::createFromFormat('Y-m-d H:i:s', $attendance->end_date)->format('d-m-Y H:i');
    $tool_content .= "
    
    <div class='col-sm-12'>
        
            <div class='form-wrapper shadow-sm p-3 rounded'>
                <form class='form-horizontal' role='form' method='post' action='$_SERVER[SCRIPT_NAME]?course=$course_code&attendance_id=$attendance_id&editUsers=1'>
                    <div class='form-group'>
                        <label class='col-12 text-secondary'><span class='help-block'>$langAttendanceInfoForUsers</span></label>
                    </div>

                    <div class='row p-2'></div>

                    <div class='form-group'>
                    <label class='col-sm-6 control-label-notes'>$langUserDuration:</label>
                        <div class='col-sm-12'>
                            <div class='radio'>
                              <label>
                                <input type='radio' id='button_all_users' name='specific_attendance_users' value='0' checked>
                                <span id='button_all_users_text'>$langAll</span>
                              </label>
                            </div>
                            <div class='radio'>
                              <label>
                                <input type='radio' id='button_some_users' name='specific_attendance_users' value='1'>
                                <span id='button_some_users_text'>$langSpecificUsers</span>
                              </label>
                            </div>
                            <div class='radio'>
                              <label>
                                <input type='radio' id='button_groups' name='specific_attendance_users' value='2'>
                                <span id='button_groups_text'>$langGroups</span>
                              </label>
                            </div>
                        </div>
                    </div>

                    <div class='row p-2'></div>

                    <div class='form-group' id='all_users'>
                        <div class='input-append date form-group' id='startdatepicker'>
                            <label for='UsersStart' class='col-sm-6 control-label-notes'>$langRegistrationDate $langFrom2:</label>
                            <div class='row'>
                                <div class='col-10 col-sm-9'>
                                    <input class='form-control' name='UsersStart' id='UsersStart' type='text' value='$start_date'>
                                </div>
                                <div class='col-2 col-sm-1'>
                                    <span class='add-on'><i class='fa fa-calendar'></i></span>
                                </div>
                            </div>
                        </div>
                        <div class='row p-2'></div>
                        <div class='input-append date form-group' id='enddatepicker'>
                            <label for='UsersEnd' class='col-sm-2 control-label-notes'>$langTill:</label>
                            <div class='row'>
                                <div class='col-10 col-sm-9'>
                                    <input class='form-control' name='UsersEnd' id='UsersEnd' type='text' value='$end_date'>
                                </div>
                                <div class='col-2 col-sm-1'>
                                    <span class='add-on'><i class='fa fa-calendar'></i></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class='row p-2'></div>

                    <div class='form-group'>
                        <div class='col-sm-12 col-sm-offset-2'>
                            <div class='table-responsive'>
                                <table id='participants_tbl' class='announcements_table hide'>
                                    <tr class='title1 notes_thead'>
                                      <td id='users' class='text-white'>$langStudents</td>
                                      <td class='text-white text-center'>$langMove</td>
                                      <td class='text-white'>$langParticipate</td>
                                    </tr>
                                    <tr>
                                      <td>
                                        <select class='form-select' id='users_box' size='10' multiple></select>
                                      </td>
                                      <td class='text-center'>
                                        <input type='button' onClick=\"move('users_box','participants_box')\" value='   &gt;&gt;   ' /><br />
                                        <input type='button' onClick=\"move('participants_box','users_box')\" value='   &lt;&lt;   ' />
                                      </td>
                                      <td width='40%'>
                                        <select class='form-select' id='participants_box' name='specific[]' size='10' multiple></select>
                                      </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class='row p-2'></div>

                    <div class='form-group'>
                        <div class='col-10 col-offset-2'>".form_buttons(array(
                        array(
                            'text' => $langRefreshList,
                            'name' => 'resetAttendanceUsers',
                            'value'=> $langAttendanceUpdate,
                            'javascript' => "selectAll('participants_box',true)"
                        ),
                        array(
                            'href' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;attendance_id=$attendance_id&amp;attendanceBook=1"
                        )
                    ))."</div>
                    </div>
                </form>
            </div></div>";

}

/**
 * @brief display user presences (student view)
 * @global type $tool_content
 * @global type $uid
 * @global type $langAttendanceStudentFailure
 * @global type $langGradebookTotalGrade
 * @global type $langTitle
 * @global type $langAttendanceActivityDate2
 * @global type $langDescription
 * @global type $langAttendanceAbsencesYes
 * @global type $langAttendanceAbsencesNo
 * @global type $langBack
 * @global type $course_code
 * @param type $attendance_id
 */
function student_view_attendance($attendance_id) {

    global $tool_content, $uid, $langAttendanceAbsencesNo, $langAttendanceAbsencesFrom,
           $langAttendanceAbsencesFrom2, $langAttendanceStudentFailure,
           $langTitle, $langAttendanceActivityDate2, $langDescription,
           $langAttendanceAbsencesYes, $langBack, $course_code;

    $attendance_limit = get_attendance_limit($attendance_id);
    //check if there are attendance records for the user, otherwise alert message that there is no input
    $checkForRecords = Database::get()->querySingle("SELECT COUNT(attendance_book.id) AS count
                                            FROM attendance_book, attendance_activities
                                        WHERE attendance_book.attendance_activity_id = attendance_activities.id
                                            AND uid = ?d
                                            AND attendance_activities.attendance_id = ?d", $uid, $attendance_id)->count;
    $tool_content .= action_bar(array(
        array(  'title' => $langBack,
                'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code",
                'icon' => 'fa-reply',
                'level' => 'primary-label'),
    ));
    if (!$checkForRecords) {
        $tool_content .="<div class='row p-2'></div>
        <div class='col-xxl-12 col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12'><div class='alert alert-warning'>$langAttendanceStudentFailure</div></div>";
    }

    $result = Database::get()->queryArray("SELECT * FROM attendance_activities WHERE attendance_id = ?d ORDER BY `DATE` DESC", $attendance_id);
    $results = count($result);

    if ($results > 0) {
        if ($checkForRecords) {
            $range = Database::get()->querySingle("SELECT `limit` FROM attendance WHERE id = ?d", $attendance_id)->limit;
            $tool_content .= "<div class='row p-2'></div>
            <div class='col-xxl-12 col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12'><div class='alert alert-info'>" . userAttendTotal($attendance_id, $uid) ." ". $langAttendanceAbsencesFrom . " ". q($attendance_limit) . " " . $langAttendanceAbsencesFrom2. " </div></div>";
        }

        $tool_content .= " <div class='col-xxl-12 col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12'><table class='announcements_table' >";
        $tool_content .= "<tr class='notes_thead'><th class='text-white'>$langTitle</th>
                              <th class='text-white'>$langAttendanceActivityDate2</th>
                              <th class='text-white'>$langDescription</th>
                              <th class='text-white'>$langAttendanceAbsencesYes</th>
                          </tr>";
    }
    if ($result) {
        foreach ($result as $details) {
            $content = standard_text_escape($details->description);
            $tool_content .= "<tr><td><b>";
            if (!empty($details->title)) {
                $tool_content .= q($details->title);
            }
            $tool_content .= "</b>";
            $tool_content .= "</td>"
                    . "<td><div class='smaller'>" . nice_format($details->date, true, true) . "</div></td>"
                    . "<td>" . $content . "</td>";
            $tool_content .= "<td width='70' class='text-center'>";
            //check user grade for this activity
            $sql = Database::get()->querySingle("SELECT attend FROM attendance_book
                                                            WHERE attendance_activity_id = ?d
                                                                AND uid = ?d", $details->id, $uid);
            if ($sql) {
                $attend = $sql->attend;
                if ($attend) {
                    $tool_content .= icon('fa-check-circle', $langAttendanceAbsencesYes);
                } else {
                    $auto_activity = Database::get()->querySingle("SELECT auto FROM attendance_activities WHERE id = ?d", $details->id)->auto;
                    if (!$auto_activity and ($details->date > date("Y-m-d"))) {
                        $tool_content .= icon('fa-question-circle', $langAttendanceStudentFailure);
                    } else {
                        $tool_content .= icon('fa-times-circle', $langAttendanceAbsencesNo);
                    }
                }
            } else {
                $tool_content .= icon('fa-question-circle', $langAttendanceStudentFailure);
            }
            $tool_content .= "</td></tr>";
        } // end of while
    }
    $tool_content .= "</table></div>";
}

/**
 * @brief Function to get the total attend number for a user in a course attendance
 * @param type $attendance_id
 * @param type $userID
 * @return int
 */
function userAttendTotal ($attendance_id, $userID){

    $userAttendTotal = Database::get()->querySingle("SELECT SUM(attend) as count FROM attendance_book, attendance_activities
                                            WHERE attendance_book.uid = ?d
                                            AND attendance_book.attendance_activity_id = attendance_activities.id
                                            AND attendance_activities.attendance_id = ?d", $userID, $attendance_id)->count;

    if($userAttendTotal){
        return $userAttendTotal;
    } else {
        return 0;
    }
}

/**
 * @brief Function to get the total attend number for a user in a course attendance
 * @param type $activityID
 * @param type $participantsNumber
 * @return string
 */
function userAttendTotalActivityStats ($activityID, $participantsNumber, $attendance_id){

    $sumAtt = 0;
    $userAttTotalActivity = Database::get()->queryArray("SELECT attend, attendance_book.uid FROM attendance_book, attendance_users
                                                            WHERE attendance_activity_id = ?d
                                                        AND attendance_users.uid=attendance_book.uid
                                                        AND attendance_users.attendance_id=?d", $activityID, $attendance_id);
    foreach ($userAttTotalActivity as $module) {
        $sumAtt += $module->attend;
    }
    //check if participantsNumber is zero
    if ($participantsNumber) {
        $mean = round(100 * $sumAtt / $participantsNumber, 2);
        return $sumAtt."/". $participantsNumber . " (" . $mean . "%)";
    } else {
        return "-";
    }

}


/**
 * @brief check for attend in auto activities
 * @param type $userID
 * @param type $exeID
 * @param type $exeType
 * @return int
 */
function attendForAutoActivities($userID, $exeID, $exeType) {

    if ($exeType == 1) { //asignments: valid submission!
       $autoAttend = Database::get()->querySingle("SELECT COUNT(id) AS count FROM assignment_submit
                                    WHERE uid = ?d AND assignment_id = ?d", $userID, $exeID)->count;
       if ($autoAttend) {
           return 1;
       } else {
           return 0;
       }
    }
    if ($exeType == 2) { //exercises: valid submission!
       $autoAttend = Database::get()->querySingle("SELECT COUNT(eurid) AS count FROM exercise_user_record
                                            WHERE uid = ?d AND eid = ?d
                                            AND total_score > 0 AND attempt_status != ".ATTEMPT_PAUSED."", $userID, $exeID)->count;
        if ($autoAttend) {
            return 1;
        }else{
            return 0;
        }
    }
}

/**
 * @brief update attendance about user activities
 * @param type $id
 * @param type $activity
 * @return type
 */
function update_attendance_book($uid, $id, $activity, $attendance_id = 0) {
    $params = [$activity, $id];
    $sql = "SELECT attendance_activities.id, attendance_activities.attendance_id
                            FROM attendance_activities, attendance
                            WHERE attendance.start_date < NOW()
                            AND attendance.end_date > NOW()
                            AND attendance_activities.module_auto_type = ?d
                            AND attendance_activities.module_auto_id = ?d
                            AND attendance_activities.auto = 1
                            AND attendance_activities.attendance_id = attendance.id
                            AND attendance_activities.attendance_id ";
    if ($attendance_id) {
        $sql .= "= ?d";
        array_push($params, $attendance_id);
    } else {
        $sql .= "IN (
                    SELECT attendance_id
                    FROM attendance_users
                    WHERE uid = ?d)";
        array_push($params, $uid);
    }
    // This query gets the attendance activities that:
    // 1) belong to attendancebooks (or specific attendancebook if $attendance_id != 0)
    // withing the date constraints
    // 2) of a specifc module and have grade auto-submission enabled
    // 3) attended by a specifc user
    $attendanceActivities = Database::get()->queryArray($sql, $params);

    foreach ($attendanceActivities as $attendanceActivity) {
            $attendance_book = Database::get()->querySingle("SELECT attend FROM attendance_book WHERE attendance_activity_id = $attendanceActivity->id AND uid = ?d", $uid);
            if(!$attendance_book) {
                Database::get()->query("INSERT INTO attendance_book SET attendance_activity_id = $attendanceActivity->id, uid = ?d, attend = 1, comments = ''", $uid);
            }

    }

    return;
}

/**
 * @brief delete attendance
 * @global type $course_id
 * @global type $langAttendanceDeleted
 * @param type $attendance_id
 */
function delete_attendance($attendance_id) {

    global $course_id, $langAttendanceDeleted, $langAttendanceDelFailure;

    $r = Database::get()->queryArray("SELECT id FROM attendance_activities WHERE attendance_id = ?d", $attendance_id);
    foreach ($r as $act) {
        delete_attendance_activity($attendance_id, $act->id);
    }
    Database::get()->query("DELETE FROM attendance_users WHERE attendance_id = ?d", $attendance_id);
    $action = Database::get()->query("DELETE FROM attendance WHERE id = ?d AND course_id = ?d", $attendance_id, $course_id);
    if ($action) {
        //Session::Messages("$langAttendanceDeleted", "alert-success");
        Session::flash('message',$langAttendanceDeleted); 
        Session::flash('alert-class', 'alert-success');
    } else {
        //Session::Messages("$langAttendanceDelFailure", "alert-danger");
        Session::flash('message',$langAttendanceDelFailure); 
        Session::flash('alert-class', 'alert-danger');
    }
}

/**
 * @brief delete attendance activity
 * @global type $langAttendanceDel
 * @global type $langAttendanceDelFailure
 * @param type $attendance_id
 * @param type $activity_id
 */
function delete_attendance_activity($attendance_id, $activity_id) {

    global $langAttendanceDel, $langAttendanceDelFailure;

    $delAct = Database::get()->query("DELETE FROM attendance_activities WHERE id = ?d AND attendance_id = ?d", $activity_id, $attendance_id)->affectedRows;
    Database::get()->query("DELETE FROM attendance_book WHERE attendance_activity_id = ?d", $activity_id)->affectedRows;
    if($delAct) {
        //Session::Messages("$langAttendanceDel", "alert-success");
        Session::flash('message',$langAttendanceDel); 
        Session::flash('alert-class', 'alert-success');
    } else {
        //Session::Messages("$langAttendanceDelFailure", "alert-danger");
        Session::flash('message',$langAttendanceDelFailure); 
        Session::flash('alert-class', 'alert-danger');
    }
}


/**
 * @brief delete user from attendance
 * @global type $langGradebookEdit
 * @param type $attendance_id
 * @param type $userid
 */
function delete_attendance_user($attendance_id, $userid) {

    global $langGradebookEdit;

    Database::get()->query("DELETE FROM attendance_book WHERE uid = ?d AND attendance_activity_id IN
                                (SELECT id FROM attendance_activities WHERE attendance_id = ?d)", $userid, $attendance_id);
    Database::get()->query("DELETE FROM attendance_users WHERE uid = ?d AND attendance_id = ?d", $userid, $attendance_id);
    //Session::Messages($langGradebookEdit,"alert-success");
    Session::flash('message',$langGradebookEdit); 
    Session::flash('alert-class', 'alert-success');
}


/**
 * @brief clone attendance
 * @global type $course_id
 * @param type $attendance_id*
 */
function clone_attendance($attendance_id) {

    global $course_id, $langCopyDuplicate;
    $attendance = Database::get()->querySingle("SELECT * FROM attendance WHERE id = ?d", $attendance_id);
    $newTitle = $attendance->title.' '.$langCopyDuplicate;
    $new_attendance_id = Database::get()->query("INSERT INTO attendance SET course_id = ?d,
                                                      students_semester = 1, `limit` = ?d,
                                                      active = 1, title = ?s, start_date = ?t, end_date = ?t", $course_id, $attendance->limit, $newTitle, $attendance->start_date, $attendance->end_date)->lastInsertID;
    Database::get()->query("INSERT INTO attendance_activities (attendance_id, title, date, description, module_auto_id, module_auto_type, auto)
                                SELECT $new_attendance_id, title, " . DBHelper::timeAfter() . ", description, module_auto_id, module_auto_type, auto
                                 FROM attendance_activities WHERE attendance_id = ?d", $attendance_id);
}

/**
 * @brief get attendance title
 * @param type $attendance_id
 * @return type
 */
function get_attendance_title($attendance_id) {

    $at_title = Database::get()->querySingle("SELECT title FROM attendance WHERE id = ?d", $attendance_id)->title;

    return $at_title;
}


/**
 * @brief get activity title from an attendance
 * @param type $attendance_id
 * @param type $activity_id
 * @return type
 */
function get_attendance_activity_title($attendance_id, $activity_id) {

    $at_act_title = Database::get()->querySingle("SELECT title FROM attendance_activities
                    WHERE id = ?d AND attendance_id = ?d", $activity_id, $attendance_id)->title;

    return $at_act_title;
}

/**
 * @brief get attendance limit
 * @param type $attendance_id
 * @return type
 */
function get_attendance_limit($attendance_id) {

    $at_limit = Database::get()->querySingle("SELECT `limit` FROM attendance WHERE id = ?d", $attendance_id)->limit;

    return $at_limit;

}
