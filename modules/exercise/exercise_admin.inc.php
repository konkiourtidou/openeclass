<?php

/* ========================================================================
 * Open eClass 3.10
 * E-learning and Course Management System
 * ========================================================================
 * Copyright 2003-2020  Greek Universities Network - GUnet
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
 * @file exercise_admin.inc.php
 * @brief Create new exercise or modify an existing one
 */
require_once 'modules/search/indexer.class.php';
require_once 'modules/tags/moduleElement.class.php';

if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    if (isset($_POST['assign_type'])) {
        if ($_POST['assign_type'] == 2) {
            $data = Database::get()->queryArray("SELECT name,id FROM `group`
                                          WHERE course_id = ?d
                                          AND visible = 1
                                      ORDER BY name", $course_id);
        } elseif ($_POST['assign_type'] == 1) {
            $data = Database::get()->queryArray("SELECT user.id AS id, surname, givenname
                                    FROM user, course_user
                                    WHERE user.id = course_user.user_id
                                    AND course_user.course_id = ?d AND course_user.status = " . USER_STUDENT . "
                                    AND user.id ORDER BY surname", $course_id);
        }
        echo json_encode($data);
        exit;
    }
}
load_js('tools.js');
// the exercise form has been submitted
if (isset($_POST['submitExercise'])) {

    $v = new Valitron\Validator($_POST);
    $v->addRule('ipORcidr', function($field, $value, array $params) {
        //matches IPv4/6 and IPv4/6 CIDR ranges
        foreach ($value as $ip){
            $valid = isIPv4($ip) || isIPv4cidr($ip) || isIPv6($ip) || isIPv6cidr($ip);
            if (!$valid) {
                return false;
            }
        }
        return true;
    }, $langIPInvalid);
    $v->rule('required', array('exerciseTitle'));
    $v->rule('numeric', array('exerciseTimeConstraint', 'exerciseAttemptsAllowed'));
    $v->rule('date', array('exerciseEndDate', 'exerciseStartDate'));
    $v->rule('ipORcidr', array('exerciseIPLock'));
    $v->labels(array(
        'exerciseTitle' => "$langTheField $langExerciseName",
        'exerciseTimeConstraint' => "$langTheField $langExerciseConstrain",
        'exerciseAttemptsAllowed' => "$langTheField $langExerciseAttemptsAllowed",
        'exerciseEndDate' => "$langTheField $langFinish",
        'exerciseStartDate' => "$langTheField $langStart",
        'exerciseIPLock' => "$langTheField IPs"
    ));
    if($v->validate()) {
        $exerciseTitle = trim($_POST['exerciseTitle']);
        $objExercise->updateTitle($exerciseTitle);
        $objExercise->updateDescription($_POST['exerciseDescription']);
        $objExercise->updateType($_POST['exerciseType']);
        $objExercise->updateRange($_POST['exerciseRange']);
        if (isset($_POST['exerciseIPLock'])) {
            $objExercise->updateIPLock(implode(',', $_POST['exerciseIPLock']));
        } else {
            $objExercise->updateIPLock('');
        }
        $objExercise->updatePasswordLock($_POST['exercisePasswordLock']);
        $startDateTime_obj = isset($_POST['exerciseStartDate']) && !empty($_POST['exerciseStartDate']) ?
            DateTime::createFromFormat('d-m-Y H:i', $_POST['exerciseStartDate'])->format('Y-m-d H:i:s') : NULL;
        $objExercise->updateStartDate($startDateTime_obj);
        $endDateTime_obj = isset($_POST['exerciseEndDate']) && !empty($_POST['exerciseEndDate']) ?
            DateTime::createFromFormat('d-m-Y H:i', $_POST['exerciseEndDate'])->format('Y-m-d H:i:s') : NULL;
        $objExercise->updateEndDate($endDateTime_obj);
        $objExercise->updateTempSave($_POST['exerciseTempSave']);
        $objExercise->updateTimeConstraint($_POST['exerciseTimeConstraint']);
        $objExercise->updateAttemptsAllowed($_POST['exerciseAttemptsAllowed']);
        $objExercise->updateResults($_POST['dispresults']);
        $objExercise->updateScore($_POST['dispscore']);
        $objExercise->updateAssignToSpecific($_POST['assign_to_specific']);
        if (!isset($_POST['continueAttempt']) or !isset($_POST['continueTimeLimit'])) {
            $objExercise->updateContinueTimeLimit(0);
        } else {
            $objExercise->updateContinueTimeLimit($_POST['continueTimeLimit']);
        }
        $objExercise->save();
        // reads the exercise ID (only useful for a new exercise)
        $exerciseId = $objExercise->selectId();

        $objExercise->assignTo(filter_input(INPUT_POST, 'ingroup', FILTER_VALIDATE_INT, FILTER_REQUIRE_ARRAY));
        Indexer::queueAsync(Indexer::REQUEST_STORE, Indexer::RESOURCE_EXERCISE, $exerciseId);

        // tags
        $moduleTag = new ModuleElement($exerciseId);
        if (isset($_POST['tags'])) {
            $moduleTag->syncTags($_POST['tags']);
        } else {
            $moduleTag->syncTags(array());
        }
        redirect_to_home_page('modules/exercise/admin.php?course='.$course_code.'&exerciseId='.$exerciseId);
    } else {
        $new_or_modify = isset($_GET['NewExercise']) ? "&NewExercise=Yes" : "&exerciseId=$_GET[exerciseId]&modifyExercise=yes";
        Session::flashPost()->Messages($langFormErrors)->Errors($v->errors());
        redirect_to_home_page('modules/exercise/admin.php?course='.$course_code.$new_or_modify);
    }
} else {
    $exerciseId = $objExercise->selectId();
    $exerciseTitle = Session::has('exerciseTitle') ? Session::get('exerciseTitle') : $objExercise->selectTitle();
    $exerciseDescription = Session::has('exerciseDescription') ? Session::get('exerciseDescription') : $objExercise->selectDescription();
    $exerciseType = Session::has('exerciseType') ? Session::get('exerciseType') : $objExercise->selectType();
    $exerciseRange = Session::has('exerciseRange') ? Session::get('exerciseRange') : $objExercise->selectRange();
    //more population need to be done
    $exerciseStartDate = $objExercise->selectStartDate();
    if (is_null($exerciseStartDate) && !Session::has('exerciseStartDate')) {
        $exerciseStartDate = '';
    } else {
        $startDateTime = DateTime::createFromFormat('Y-m-d H:i:s', $exerciseStartDate);
        $exerciseStartDate = Session::has('exerciseStartDate') ? Session::get('exerciseStartDate') : $startDateTime->format('d-m-Y H:i');
    }
    $exerciseEndDate = $objExercise->selectEndDate();
    if (is_null($exerciseEndDate) && !Session::has('exerciseEndDate')) {
        $exerciseEndDate = '';
    } else {
        $endDateTime = DateTime::createFromFormat('Y-m-d H:i:s', $exerciseEndDate);
        $exerciseEndDate = Session::has('exerciseEndDate') ? Session::get('exerciseEndDate') : $endDateTime->format('d-m-Y H:i');
    }
    $enableStartDate = Session::has('enableStartDate') ? Session::get('enableStartDate') : ($exerciseStartDate ? 1 : 0);;
    $enableEndDate = Session::has('enableEndDate') ? Session::get('enableEndDate') : ($exerciseEndDate ? 1 : 0);
    $exerciseTempSave = Session::has('exerciseTempSave') ? Session::get('exerciseTempSave') : $objExercise->selectTempSave();
    $exerciseTimeConstraint = Session::has('exerciseTimeConstraint') ? Session::get('exerciseTimeConstraint') : $objExercise->selectTimeConstraint();
    $exerciseAttemptsAllowed = Session::has('exerciseAttemptsAllowed') ? Session::get('exerciseAttemptsAllowed') : $objExercise->selectAttemptsAllowed();
    $displayResults = Session::has('dispresults') ? Session::get('dispresults') : $objExercise->selectResults();
    $displayScore = Session::has('dispscore') ? Session::get('dispscore') : $objExercise->selectScore();
    $continueTimeLimit = Session::has('continueTimeLimit') ? Session::get('continueTimeLimit') : $objExercise->continueTimeLimit();
    $continueTimeField = str_replace('[]',
        "<input type='text' class='form-control' name='continueTimeLimit' value='$continueTimeLimit'>",
        $langContinueAttemptTime);
    if (!is_null($objExercise->selectIPLock())) {
        $exerciseIPLock = Session::has('exerciseIPLock') ? Session::get('exerciseIPLock') : explode(',', $objExercise->selectIPLock());
        $exerciseIPLockOptions = implode('', array_map(
            function ($item) {
                return $item? ('<option selected>' . q(trim($item)) . '</option>'): '';
            }, $exerciseIPLock));
    } else {
        $exerciseIPLockOptions = '';
    }

    $exercisePasswordLock = Session::has('exercisePasswordLock') ? Session::get('exercisePasswordLock') : $objExercise->selectPasswordLock();
    $exerciseAssignToSpecific = Session::has('assign_to_specific') ? Session::get('assign_to_specific') : $objExercise->selectAssignToSpecific();
    if ($objExercise->selectAssignToSpecific()) {
        //preparing options in select boxes for assigning to specific users/groups
        $assignee_options='';
        $unassigned_options='';
        if ($objExercise->selectAssignToSpecific() == 2) {
            $assignees = Database::get()->queryArray("SELECT `group`.id AS id, `group`.name
                FROM exercise_to_specific, `group`
                WHERE `group`.id = exercise_to_specific.group_id
                    AND `group`.visible = 1
                    AND exercise_to_specific.exercise_id = ?d", $exerciseId);
            $all_groups = Database::get()->queryArray("SELECT name, id FROM `group` WHERE course_id = ?d AND visible = 1", $course_id);
            foreach ($assignees as $assignee_row) {
                $assignee_options .= "<option value='".$assignee_row->id."'>".$assignee_row->name."</option>";
            }
            $unassigned = array_udiff($all_groups, $assignees,
                function ($obj_a, $obj_b) {
                    return $obj_a->id - $obj_b->id;
                }
            );
            foreach ($unassigned as $unassigned_row) {
                $unassigned_options .= "<option value='$unassigned_row->id'>$unassigned_row->name</option>";
            }
        } else {
            $assignees = Database::get()->queryArray("SELECT user.id AS id, surname, givenname
                FROM exercise_to_specific, user
                WHERE user.id = exercise_to_specific.user_id AND exercise_to_specific.exercise_id = ?d", $exerciseId);
            $all_users = Database::get()->queryArray("SELECT user.id AS id, user.givenname, user.surname
                FROM user, course_user
                WHERE user.id = course_user.user_id
                AND course_user.course_id = ?d
                AND course_user.status = " . USER_STUDENT . "
                AND user.id", $course_id);
            foreach ($assignees as $assignee_row) {
                $assignee_options .= "<option value='$assignee_row->id'>$assignee_row->surname $assignee_row->givenname</option>";
            }
            $unassigned = array_udiff($all_users, $assignees,
                function ($obj_a, $obj_b) {
                    return $obj_a->id - $obj_b->id;
                }
            );
            foreach ($unassigned as $unassigned_row) {
                $unassigned_options .= "<option value='$unassigned_row->id'>$unassigned_row->surname $unassigned_row->givenname</option>";
            }
        }
    }
}

// shows the form to modify the exercise
if (isset($_GET['modifyExercise']) or isset($_GET['NewExercise'])) {

    load_js('bootstrap-datetimepicker');
    load_js('select2');

    $head_content .= "<script type='text/javascript'>
        $(function() {
            $('#exerciseStartDate, #exerciseEndDate').datetimepicker({
                format: 'dd-mm-yyyy hh:ii',
                pickerPosition: 'bottom-right',
                language: '".$language."',
                autoclose: true
            }).on('changeDate', function(ev){
                if($(this).attr('id') === 'exerciseEndDate') {
                    $('#answersDispEndDate, #scoreDispEndDate').removeClass('hidden');
                }
            }).on('blur', function(ev){
                if($(this).attr('id') === 'exerciseEndDate') {
                    var end_date = $(this).val();
                    if (end_date === '') {
                        if ($('input[name=\"dispresults\"]:checked').val() == 4) {
                            $('input[name=\"dispresults\"][value=\"1\"]').prop('checked', true);
                        }
                        $('#answersDispEndDate, #scoreDispEndDate').addClass('hidden');
                    }
                }
            });
            $('#enableEndDate, #enableStartDate').change(function() {
                var dateType = $(this).prop('id').replace('enable', '');
                if($(this).prop('checked')) {
                    $('input#exercise'+dateType).prop('disabled', false);
                    if (dateType === 'EndDate' && $('input#exerciseEndDate').val() !== '') {
                        $('#answersDispEndDate, #scoreDispEndDate').removeClass('hidden');
                    }
                } else {
                    $('input#exercise'+dateType).prop('disabled', true);
                    if ($('input[name=\"dispresults\"]:checked').val() == 4) {
                        $('input[name=\"dispresults\"][value=\"1\"]').prop('checked', true);
                    }
                    $('#answersDispEndDate, #scoreDispEndDate').addClass('hidden');
                }
            });
            $('#exerciseAttemptsAllowed').blur(function(){
                var attempts = $(this).val();
                if (attempts ==0) {
                    $('#answersDispLastAttempt, #scoreDispLastAttempt').addClass('hidden');
                    if ($('input[name=\"dispresults\"]:checked').val() == 3) {
                        $('input[name=\"dispresults\"][value=\"1\"]').prop('checked', true);
                    }
                } else {
                    $('#answersDispLastAttempt, #scoreDispLastAttempt').removeClass('hidden');
                }
            });
            $('#exerciseIPLock').select2({
                minimumResultsForSearch: Infinity,
                tags: true,
                tokenSeparators: [',', ' ']
            });
            $('#assign_button_all').click(hideAssignees);
            $('#assign_button_user, #assign_button_group').click(ajaxAssignees);
            $('#continueAttempt').change(function () {
                if ($(this).prop('checked')) {
                    $('#continueTimeField').show('fast');
                } else {
                    $('#continueTimeField').hide('fast');
                }
            }).change();
        });
        function ajaxAssignees()
        {
            $('#assignees_tbl').removeClass('hide');
            var type = $(this).val();
            $.post('',
            {
              assign_type: type
            },
            function(data,status){
                var index;
                var parsed_data = JSON.parse(data);
                var select_content = '';
                if(type==1){
                    for (index = 0; index < parsed_data.length; ++index) {
                        select_content += '<option value=\"' + parsed_data[index]['id'] + '\">' + parsed_data[index]['surname'] + ' ' + parsed_data[index]['givenname'] + '<\/option>';
                    }
                } else {
                    for (index = 0; index < parsed_data.length; ++index) {
                        select_content += '<option value=\"' + parsed_data[index]['id'] + '\">' + parsed_data[index]['name'] + '<\/option>';
                    }
                }
                $('#assignee_box').find('option').remove();
                $('#assign_box').find('option').remove().end().append(select_content);
            });
        }
        function hideAssignees()
        {
            $('#assignees_tbl').addClass('hide');
            $('#assignee_box').find('option').remove();
        }
    </script>";
    $tool_content .= action_bar(array(
        array('title' => $langBack,
            'url' => $exerciseId ? "admin.php?course=$course_code&exerciseId=$exerciseId" : "index.php?course=$course_code",
            'icon' => 'fa-reply',
            'level' => 'primary-label'
        )
    ));

   $tool_content .= "
        <div class='col-sm-12'><div class='form-wrapper shadow-sm p-3 rounded'>
            <form class='form-horizontal' role='form' method='post' action='$_SERVER[SCRIPT_NAME]?course=$course_code".(isset($_GET['modifyExercise']) ? "&amp;exerciseId=$exerciseId" : "&amp;NewExercise=Yes")."'>
             <fieldset>
                 <div class='form-group ".(Session::getError('exerciseTitle') ? "has-error" : "")." mt-3'>
                   <label for='exerciseTitle' class='col-sm-6 control-label-notes'>$langExerciseName:</label>
                   <div class='col-sm-12'>
                     <input name='exerciseTitle' type='text' class='form-control' id='exerciseTitle' value='" . q($exerciseTitle) . "' placeholder='$langExerciseName'>
                     <span class='help-block'>".Session::getError('exerciseTitle')."</span>
                   </div>
                 </div>
                 <div class='form-group mt-3'>
                   <label for='exerciseDescription' class='col-sm-6 control-label-notes'>$langDescription:</label>
                   <div class='col-sm-12'>
                   " . rich_text_editor('exerciseDescription', 4, 30, $exerciseDescription) . "
                   </div>
                 </div>
                 <div class='form-group mt-3'>
                     <label for='exerciseDescription' class='col-sm-6 control-label-notes'>$langViewShow:</label>
                     <div class='col-sm-12'>
                         <div class='radio'>
                           <label>
                             <input type='radio' name='exerciseType' value='".SINGLE_PAGE_TYPE."' ".(($exerciseType == SINGLE_PAGE_TYPE)? 'checked' : '').">
                             $langSimpleExercise
                           </label>
                         </div>
                         <div class='radio'>
                           <label>
                             <input type='radio' name='exerciseType' value='".MULTIPLE_PAGE_TYPE."' ".(($exerciseType == MULTIPLE_PAGE_TYPE)? 'checked' : '').">
                             $langSequentialExercise
                           </label>
                         </div>
                         <div class='radio'>
                           <label>
                             <input type='radio' name='exerciseType' value='".ONE_WAY_TYPE."' ".(($exerciseType == ONE_WAY_TYPE)? 'checked' : '').">
                             $langOneWayExercise
                           </label>
                         </div>
                     </div>
                 </div>
                 <div class='form-group mt-3'>
                    <label class='col-sm-6 control-label-notes'>$langExerciseScaleGrade:</label>
                    <div class='col-sm-12'>
                        <select name='exerciseRange' class='form-control'>
                            <option value".($exerciseRange == 0 ? ' selected' : '').">-- $langExerciseNoScaleGrade --</option>
                            <option value='10'" . ($exerciseRange == 10 ? " selected" : "") .">0-10</option>
                            <option value='20'" . ($exerciseRange == 20 ? " selected" : "") .">0-20</option>
                            <option value='5'" . ($exerciseRange == 5 ? " selected " : "") .">0-5</option>
                            <option value='100'" . ($exerciseRange == 100 ? " selected" : "") .">0-100</option>
                        </select>
                    </div>
                </div>
                 <div class='input-append date form-group".(Session::getError('exerciseStartDate') ? " has-error" : "")." mt-3' id='startdatepicker' data-date='$exerciseStartDate' data-date-format='dd-mm-yyyy'>
                     <label for='exerciseStartDate' class='col-sm-6 control-label-notes'>$langStart:</label>
                     <div class='col-sm-12'>
                        <div class='input-group'>
                            <span class='input-group-addon'>
                                <input style='cursor:pointer;' type='checkbox' id='enableStartDate' name='enableStartDate' value='1'".($enableStartDate ? ' checked' : '').">
                            </span>
                            <input class='form-control' name='exerciseStartDate' id='exerciseStartDate' type='text' value='$exerciseStartDate'".($enableStartDate ? '' : ' disabled').">
                        </div>
                        <span class='help-block'>".(Session::hasError('exerciseStartDate') ? Session::getError('exerciseStartDate') : "&nbsp;&nbsp;&nbsp;<i class='fa fa-share fa-rotate-270'></i> $langExerciseStartHelpBlock")."</span>
                     </div>
                 </div>
                 <div class='input-append date form-group".(Session::getError('exerciseEndDate') ? " has-error" : "")." mt-3' id='enddatepicker' data-date='$exerciseEndDate' data-date-format='dd-mm-yyyy'>
                     <label for='exerciseEndDate' class='col-sm-6 control-label-notes'>$langFinish:</label>
                     <div class='col-sm-12'>
                        <div class='input-group'>
                            <span class='input-group-addon'>
                              <input style='cursor:pointer;' type='checkbox' id='enableEndDate' name='enableEndDate' value='1'".($enableEndDate ? ' checked' : '').">
                            </span>
                            <input class='form-control' name='exerciseEndDate' id='exerciseEndDate' type='text' value='$exerciseEndDate'".($enableEndDate ? '' : ' disabled').">
                        </div>
                        <span class='help-block'>".(Session::hasError('exerciseEndDate') ? Session::getError('exerciseEndDate') : "&nbsp;&nbsp;&nbsp;<i class='fa fa-share fa-rotate-270'></i> $langExerciseEndHelpBlock")."</span>
                     </div>
                 </div>
                 <div class='form-group mt-3'>
                     <label for='exerciseTempSave' class='col-sm-6 control-label-notes'>$langTemporarySave:</label>
                     <div class='col-sm-12'>
                         <div class='radio'>
                           <label>
                             <input type='radio' name='exerciseTempSave' value='0' ".(($exerciseTempSave==0)? 'checked' : '').">
                             $langDeactivate
                           </label>
                         </div>
                         <div class='radio'>
                           <label>
                             <input type='radio' name='exerciseTempSave' value='1' ".(($exerciseTempSave==1)? 'checked' : '').">
                             $langActivate
                           </label>
                         </div>
                     </div>
                 </div>
                 <div class='form-group ".(Session::getError('exerciseTimeConstraint') ? "has-error" : "")." mt-3'>
                   <label for='exerciseTimeConstraint' class='col-sm-6 control-label-notes'>$langExerciseConstrain:</label>
                   <div class='col-sm-12'>
                     <input type='text' class='form-control' name='exerciseTimeConstraint' id='exerciseTimeConstraint' value='$exerciseTimeConstraint' placeholder='$langExerciseConstrain'>
                     <span class='help-block'>".(Session::getError('exerciseTimeConstraint') ? Session::getError('exerciseTimeConstraint') : "$langExerciseConstrainUnit ($langExerciseConstrainExplanation)")."</span>
                   </div>
                 </div>
                 <div class='form-group ".(Session::getError('exerciseAttemptsAllowed') ? "has-error" : "")." mt-3'>
                   <label for='exerciseAttemptsAllowed' class='col-sm-6 control-label-notes'>$langExerciseAttemptsAllowed:</label>
                   <div class='col-sm-12'>
                     <input type='text' class='form-control' name='exerciseAttemptsAllowed' id='exerciseAttemptsAllowed' value='$exerciseAttemptsAllowed' placeholder='$langExerciseConstrain'>
                     <span class='help-block'>".(Session::getError('exerciseAttemptsAllowed') ? Session::getError('exerciseAttemptsAllowed') : "$langExerciseAttemptsAllowedUnit ($langExerciseAttemptsAllowedExplanation)")."</span>
                   </div>
                 </div>";

                $tool_content .= "<div class='form-group mt-3'>
                     <label for='dispresults' class='col-sm-6 control-label-notes'>$langAnswers:</label>
                     <div class='col-sm-12'>
                         <div class='radio'>
                           <label>
                             <input type='radio' name='dispresults' value='1' ".(($displayResults == 1)? 'checked' : '').">
                             $langAnswersDisp
                           </label>
                         </div>
                         <div class='radio'>
                           <label>
                             <input type='radio' name='dispresults' value='0' ".(($displayResults == 0)? 'checked' : '').">
                             $langAnswersNotDisp
                           </label>
                         </div>
                         <div id='answersDispLastAttempt' class='radio".($exerciseAttemptsAllowed ? '' : ' hidden')."'>
                           <label>
                             <input type='radio' name='dispresults' value='3' ".(($displayResults == 3)? 'checked' : '').">
                             $langAnswersDispLastAttempt
                           </label>
                         </div>
                         <div id='answersDispEndDate' class='radio".(!empty($exerciseEndDate) ? '' : ' hidden')."'>
                           <label>
                             <input type='radio' name='dispresults' value='4' ".(($displayResults == 4)? 'checked' : '').">
                             $langAnswersDispEndDate
                           </label>
                         </div>
                     </div>
                 </div>
                 <div class='form-group mt-3'>
                     <label for='dispresults' class='col-sm-6 control-label-notes'>$langScore:</label>
                     <div class='col-sm-12'>
                         <div class='radio'>
                           <label>
                             <input type='radio' name='dispscore' value='1' ".(($displayScore == 1)? 'checked' : '').">
                             $langScoreDisp
                           </label>
                         </div>
                         <div class='radio'>
                           <label>
                             <input type='radio' name='dispscore' value='0' ".(($displayScore == 0)? 'checked' : '').">
                             $langScoreNotDisp
                           </label>
                         </div>
                         <div id='scoreDispLastAttempt' class='radio".($exerciseAttemptsAllowed ? '' : ' hidden')."'>
                           <label>
                             <input type='radio' name='dispscore' value='3' ".(($displayScore == 3)? 'checked' : '').">
                             $langScoreDispLastAttempt
                           </label>
                         </div>
                         <div id='scoreDispEndDate' class='radio".(!empty($exerciseEndDate) ? '' : ' hidden')."'>
                           <label>
                             <input type='radio' name='dispscore' value='4' ".(($displayScore == 4)? 'checked' : '').">
                             $langScoreDispEndDate
                           </label>
                         </div>
                     </div>
                 </div>
                 <div class='form-group mt-3'>
                    <label class='col-sm-6 control-label-notes'>$m[WorkAssignTo]:</label>
                    <div class='col-sm-12'>
                        <div class='radio'>
                          <label>
                            <input type='radio' id='assign_button_all' name='assign_to_specific' value='0'".($exerciseAssignToSpecific == 0 ? " checked" : "").">
                            <span>$m[WorkToAllUsers]</span>
                          </label>
                        </div>
                        <div class='radio'>
                          <label>
                            <input type='radio' id='assign_button_user' name='assign_to_specific' value='1'".($exerciseAssignToSpecific == 1 ? " checked" : "").">
                            <span>$m[WorkToUser]</span>
                          </label>
                        </div>
                        <div class='radio'>
                          <label>
                            <input type='radio' id='assign_button_group' name='assign_to_specific' value='2'".($exerciseAssignToSpecific == 2 ? " checked" : "").">
                            <span>$m[WorkToGroup]</span>
                          </label>
                        </div>
                    </div>
                </div>
                <div class='form-group mt-3'>
                    <div class='col-sm-12'>
                        <div class='table-responsive'>
                            <table id='assignees_tbl' class='table-default".(in_array($exerciseAssignToSpecific, [1, 2]) ? '' : ' hide')."'>
                                <tr class='title1'>
                                  <td id='assignees'>$langStudents</td>
                                  <td class='text-center'>$langMove</td>
                                  <td>$m[WorkAssignTo]</td>
                                </tr>
                                <tr>
                                  <td>
                                    <select class='form-control' id='assign_box' size='10' multiple>
                                    ".((isset($unassigned_options)) ? $unassigned_options : '')."
                                    </select>
                                  </td>
                                  <td class='text-center'>
                                    <input type='button' onClick=\"move('assign_box','assignee_box')\" value='   &gt;&gt;   ' /><br /><input type='button' onClick=\"move('assignee_box','assign_box')\" value='   &lt;&lt;   ' />
                                  </td>
                                  <td width='40%'>
                                    <select class='form-control' id='assignee_box' name='ingroup[]' size='10' multiple>
                                    ".((isset($assignee_options)) ? $assignee_options : '')."
                                    </select>
                                  </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <div class='form-group mt-3'>
                    <label class='col-sm-6 control-label-notes'>$langContinueAttempt:</label>
                    <div class='col-sm-12'>
                        <div class='checkbox'>
                            <label>
                                <input id='continueAttempt' name='continueAttempt' type='checkbox' " . ($continueTimeLimit? 'checked' : '') . ">
                                $langContinueAttemptExplanation
                            </label>
                        </div>
                        <div id='continueTimeField' class='form-inline' style='margin-top: 15px; " .
                            ($continueTimeLimit? '': 'display: none') . "'>$continueTimeField</div>
                    </div>
                </div>

                <div class='course-info-title clearfix'>
                    <a role='button' data-bs-toggle='collapse' href='#CheckAccess' aria-expanded='false' aria-controls='CheckAccess'>
                        <h5 class='panel-heading'>
                            <span class='fa fa-chevron-down fa-fw'></span> $langCheckAccess
                        </h5>
                    </a>
                </div>

                <div id='CheckAccess' class='collapse'>
                    <div class='form-group ".(Session::getError('exercisePasswordLock') ? "has-error" : "")." mt-3'>
                        <label for='exercisePasswordLock' class='col-sm-6 control-label-notes'>$langPassCode:</label>
                        <div class='col-sm-12'>
                            <input name='exercisePasswordLock' type='text' class='form-control' id='exercisePasswordLock' value='$exercisePasswordLock' placeholder=''>
                            <span class='help-block'>".Session::getError('exercisePasswordLock')."</span>
                        </div>
                    </div>
                    <div class='form-group ".(Session::getError('exerciseIPLock') ? "has-error" : "")." mt-3'>
                        <label for='exerciseIPLock' class='col-sm-6 control-label-notes'>$langIPUnlock:</label>
                        <div class='col-sm-12'>
                            <select name='exerciseIPLock[]' class='form-control' id='exerciseIPLock' multiple>
                                $exerciseIPLockOptions
                            </select>
                            <span class='help-block'>".Session::getError('exerciseIPLock')."</span>
                        </div>
                    </div>" .
                    eClassTag::tagInput($exerciseId) . "
                </div>
                <div class='form-group mt-3'>
                    <div class='col-sm-offset-2 col-sm-10'>" .
                        form_buttons([
                            [ 'text'  => $langSave,
                              'name'  => 'submitExercise',
                              'value' => (isset($_GET['NewExercise']) ? $langCreate : $langModify),
                              'javascript' => "selectAll('assignee_box',true)"
                            ],
                            [ 'href' => $exerciseId ?
                                "admin.php?course=$course_code&exerciseId=$exerciseId" :
                                "index.php?course=$course_code",
                            ]
                        ]) . "
                    </div>
                 </div>
             </fieldset>
             </form>
        </div></div>";
} else {
    switch ($displayResults) {
        case 0:
            $disp_results_message = $langAnswersNotDisp;
            break;
        case 1:
            $disp_results_message = $langAnswersDisp;
            break;
        case 3:
            $disp_results_message = $langAnswersDispLastAttempt;
            break;
        case 4:
            $disp_results_message = $langAnswersDispEndDate;
            break;
    }
    switch ($displayScore) {
        case 0:
            $disp_score_message = $langScoreNotDisp;
            break;
        case 1:
            $disp_score_message = $langScoreDisp;
            break;
        case 3:
            $disp_score_message = $langScoreDispLastAttempt;
            break;
        case 4:
            $disp_score_message = $langScoreDispEndDate;
            break;
    }
    switch ($exerciseAssignToSpecific) {
        case 1: $assign_to_users_message = $m['WorkToUser'];
            break;
        case 2: $assign_to_users_message = $m['WorkToGroup'];
            break;
    }
    if ($exerciseType == MULTIPLE_PAGE_TYPE) {
        $exerciseType = $langSequentialExercise;
    } elseif ($exerciseType == ONE_WAY_TYPE) {
        $exerciseType = $langOneWayExercise;
    } else {
        $exerciseType = $langSimpleExercise;
    }
    $moduleTag = new ModuleElement($exerciseId);
    $tool_content .= action_bar([
        [ 'title' => $langExerciseExecute,
          'url' => "exercise_submit.php?course=$course_code&amp;exerciseId=$exerciseId",
          'icon' => 'fa-play-circle',
          'level' => 'primary-label',
          'button-class' => 'btn-danger' ],
        [ 'title' => $langCourseInfo,
          'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;exerciseId=$exerciseId&amp;modifyExercise=yes",
          'icon' => 'fa-edit',
          'level' => 'primary-label',
          'button-class' => 'btn btn-success' ],
        [ 'title' => $langBack,
          'url' => "index.php?course=$course_code",
          'icon' => 'fa-reply',
          'level' => 'primary-label' ],
    ]);

    $exerciseDescription = trim($exerciseDescription);
    if ($exerciseDescription !== '') {
        $exerciseDescription = "<div class='col-sm-12 ps-3 pe-3 pt-3 pb-3'><span class='control-label-notes'>$langDescription :</span>" .
            standard_text_escape($exerciseDescription) . '</div><hr>';
    }

    $startParts = explode(' ', $exerciseStartDate);
    $endParts = explode(' ', $exerciseEndDate);
    if ($exerciseStartDate and $exerciseEndDate) {
        $startWeekDay = $langDay_of_weekNames['long'][$startDateTime->format('w')];
        $periodLabel = "$langExercisePeriod:";
        if ($startParts[0] == $endParts[0]) { // start and end on same date
            $timeDuration = format_time_duration($endDateTime->getTimestamp() - $startDateTime->getTimestamp());
            $periodInfo = "$startWeekDay, $startParts[0] $startParts[1] &ndash; $endParts[1] <small>($timeDuration)</small>";
        } else {
            $endWeekDay = $langDay_of_weekNames['long'][$endDateTime->format('w')];
            $periodInfo = "$startWeekDay, $exerciseStartDate &ndash; $endWeekDay, $exerciseEndDate";
        }
    } elseif ($exerciseStartDate) {
        $periodLabel = "<span class='text-success'>$langStart:</span>";
        $periodInfo = $langDay_of_weekNames['long'][$startDateTime->format('w')] . ', ' . $exerciseStartDate;
    } elseif ($exerciseEndDate) {
        $periodLabel = "<span class='text-danger'>$langFinish:</span>";
        $periodInfo = $langDay_of_weekNames['long'][$endDateTime->format('w')] . ', ' . $exerciseEndDate;
    } else {
        $periodLabel = null;
    }
    $period = $periodLabel? "<div class='col-12 ps-3 pe-3 pt-3 pb-3'>$periodLabel <b>$periodInfo</b></div>": '';

    $tool_content .= "
    <div class='col-sm-12 mb-2'>
        <div class='panel panel-default'>
            <div class='panel-heading' data-bs-toggle='collapse' href='#exerciseInfoPanel' aria-expanded='false' aria-controls='#exerciseInfoPanel' style='cursor: pointer'>
                <h5><span class='fa fa-chevron-right fa-fw control-label-notes' id='exerciseInfoPanelIndicator'></span> " . q($exerciseTitle) . "</h5>
            </div>
            <div class='panel-body collapse' id='exerciseInfoPanel'>
                <div class='row p-2'>
                    $exerciseDescription
                    $period
                    <div class='col-sm-12 ps-3 pe-3 pt-3 pb-3'>
                        $exerciseType
                    </div>";

    if ($exerciseTempSave == 1) {
        $tool_content .= "<div class='col-12 ps-3 pe-3 pt-3 pb-3'><b>$langTemporarySave:</b> $langYes</div>";
    }
    if ($exerciseTimeConstraint > 0) {
        if ($exerciseTimeConstraint == 1) {
            $langExerciseConstrainUnit = $langminute;
        }
        $tool_content .= "<div class='col-12 ps-3 pe-3 pt-3 pb-3'>$langExerciseConstrain: <b>$exerciseTimeConstraint $langExerciseConstrainUnit</b></div>";
    }
    if ($exerciseAttemptsAllowed > 0) {
        $tool_content .= "<div class='col-12 ps-3 pe-3 pt-3 pb-3'>$langExerciseAttemptsAllowed: <b>$exerciseAttemptsAllowed $langExerciseAttemptsAllowedUnit</b></div>";
    }

    $tool_content .= "
                    <div class='col-sm-12 ps-3 pe-3 pt-3 pb-3' style='margin-top: 10px;'>$disp_results_message</div>
                    <div class='col-sm-12 ps-3 pe-3 pt-3 pb-3'>$disp_score_message</div>";

    if ($exerciseAssignToSpecific > 0) {
        $tool_content .= "<div class='col-sm-12 ps-3 pe-3 pt-3 pb-3' style='margin-top: 10px;'>$m[WorkAssignTo]: <b>$assign_to_users_message</b></div>";
    }

    $tags_list = $moduleTag->showTags();
    if ($tags_list) {
        $tool_content .= "
                    <div class='col-sm-12 ps-3 pe-3 pt-3 pb-3'>
                        $langTags: $tags_list
                    </div>";
    }
    $tool_content .= "
                </div>
            </div>
        </div></div>
        <script>$(function () {
            $('#exerciseInfoPanel')
                .on('show.bs.collapse', function () {
                    $('#exerciseInfoPanelIndicator').addClass('fa-rotate-90');
                })
                .on('hidden.bs.collapse', function () {
                    $('#exerciseInfoPanelIndicator').removeClass('fa-rotate-90');
                });
        });</script>";

}
