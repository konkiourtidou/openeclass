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
 * @file index.php
 * @brief main exercise module script
 */

require_once 'exercise.class.php';
require_once 'question.class.php';
require_once 'answer.class.php';
$require_current_course = TRUE;
$guest_allowed = TRUE;
$require_help = TRUE;
$helpTopic = 'exercises';

include '../../include/baseTheme.php';
require_once 'modules/group/group_functions.php';
require_once 'include/lib/modalboxhelper.class.php';
require_once 'include/lib/multimediahelper.class.php';
require_once 'modules/search/indexer.class.php';
ModalBoxHelper::loadModalBox();
/* * ** The following is added for statistics purposes ** */
require_once 'include/action.php';
$action = new action();
$action->record(MODULE_ID_EXERCISE);

load_js('datatables');

$pageName = $langExercices;

//Unsetting the redirect cookie which is set in case of exercise page unload event
//More info in exercise_submit.php comments
if (isset($_COOKIE['inExercise'])) {
    setcookie("inExercise", "", time() - 3600);
}

$head_content .= "<script type='text/javascript'>
        $(document).ready(function() {
            $('#ex').DataTable ({
                'sPaginationType': 'full_numbers',
                'bAutoWidth': true,
                'searchDelay': 1000,
                'order' : [[1, 'desc']],
                'oLanguage': {
                   'sLengthMenu':   '$langDisplay _MENU_ $langResults2',
                   'sZeroRecords':  '" . $langNoResult . "',
                   'sInfo':         '$langDisplayed _START_ $langTill _END_ $langFrom2 _TOTAL_ $langTotalResults',
                   'sInfoEmpty':    '$langDisplayed 0 $langTill 0 $langFrom2 0 $langResults2',
                   'sInfoFiltered': '',
                   'sInfoPostFix':  '',
                   'sSearch':       '',
                   'sUrl':          '',
                   'oPaginate': {
                       'sFirst':    '&laquo;',
                       'sPrevious': '&lsaquo;',
                       'sNext':     '&rsaquo;',
                       'sLast':     '&raquo;'
                   }
               }
            });
            $('.dataTables_filter input').attr({
                          class : 'form-control input-sm',
                          placeholder : '$langSearch...'
                        });
        });
        </script>";

// only for administrator
if ($is_editor) {
    load_js('tools.js');

    if (isset($_GET['exerciseId'])) {
        $exerciseId = $_GET['exerciseId'];
    }
    if (!empty($_GET['choice'])) {
        // construction of Exercise
        $objExerciseTmp = new Exercise();
        if ($objExerciseTmp->read($exerciseId)) {
            switch ($_GET['choice']) {
                case 'delete': // deletes an exercise
                    if (!resource_belongs_to_progress_data(MODULE_ID_EXERCISE, $exerciseId)) {
                        $objExerciseTmp->delete();
                        Indexer::queueAsync(Indexer::REQUEST_REMOVE, Indexer::RESOURCE_EXERCISE, $exerciseId);
                        Session::Messages($langPurgeExerciseSuccess, 'alert-success');
                        redirect_to_home_page('modules/exercise/index.php?course='.$course_code);
                    } else {
                        Session::Messages($langResourceBelongsToCert, "alert-warning");
                    }
                case 'purge': // purge exercise results
                    if (!resource_belongs_to_progress_data(MODULE_ID_EXERCISE, $exerciseId)) {
                        $objExerciseTmp->purge();
                        Session::Messages($langPurgeExerciseResultsSuccess);
                        redirect_to_home_page('modules/exercise/index.php?course='.$course_code);
                    } else {
                        Session::Messages($langResourceBelongsToCert, "alert-warning");
                    }
                case 'enable':  // enables an exercise
                    $objExerciseTmp->enable();
                    $objExerciseTmp->save();
                    Indexer::queueAsync(Indexer::REQUEST_STORE, Indexer::RESOURCE_EXERCISE, $exerciseId);
                    redirect_to_home_page('modules/exercise/index.php?course='.$course_code);
                case 'disable': // disables an exercise
                    if (!resource_belongs_to_progress_data(MODULE_ID_EXERCISE, $exerciseId)) {
                        $objExerciseTmp->disable();
                        $objExerciseTmp->save();
                        Indexer::queueAsync(Indexer::REQUEST_STORE, Indexer::RESOURCE_EXERCISE, $exerciseId);
                        redirect_to_home_page('modules/exercise/index.php?course='.$course_code);
                    } else {
                        Session::Messages($langResourceBelongsToCert, "alert-warning");
                    }
                case 'public':  // make exercise public
                    $objExerciseTmp->makepublic();
                    $objExerciseTmp->save();
                    Indexer::queueAsync(Indexer::REQUEST_STORE, Indexer::RESOURCE_EXERCISE, $exerciseId);
                    redirect_to_home_page('modules/exercise/index.php?course='.$course_code);
                case 'limited':  // make exercise limited
                    $objExerciseTmp->makelimited();
                    $objExerciseTmp->save();
                    Indexer::queueAsync(Indexer::REQUEST_STORE, Indexer::RESOURCE_EXERCISE, $exerciseId);
                    redirect_to_home_page('modules/exercise/index.php?course='.$course_code);
                case 'clone':  // make exercise limited
                    $objExerciseTmp->duplicate();
                    Session::Messages($langCopySuccess, 'alert-success');
                    redirect_to_home_page('modules/exercise/index.php?course='.$course_code);
            }
        }
        // destruction of Exercise
        unset($objExerciseTmp);
    }
    $result = Database::get()->queryArray("SELECT start_date, id, title, description, type, active, public, ip_lock, password_lock FROM exercise "
                                . "WHERE course_id = ?d "
                                . "ORDER BY start_date DESC", $course_id);
    $qnum = Database::get()->querySingle("SELECT COUNT(*) as count FROM exercise WHERE course_id = ?d", $course_id)->count;
} else {
        $gids = user_group_info($uid, $course_id);
        if (!empty($gids)) {
            $gids_sql_ready = implode(',',array_keys($gids));
        } else {
            $gids_sql_ready = "''";
        }
    $result = Database::get()->queryArray("SELECT start_date, id, title, description, type, active, public, end_date, time_constraint, attempts_allowed, score, ip_lock, password_lock " .
            "FROM exercise WHERE course_id = ?d AND active = 1 "
            . "AND (assign_to_specific = '0' OR assign_to_specific != '0' AND id IN
                       (SELECT exercise_id FROM exercise_to_specific WHERE user_id = ?d UNION SELECT exercise_id FROM exercise_to_specific WHERE group_id IN ($gids_sql_ready))
                    ) "
            ."ORDER BY start_date DESC", $course_id, $uid);
    $qnum = Database::get()->querySingle("SELECT COUNT(*) as count FROM exercise WHERE course_id = ?d AND active = 1", $course_id)->count;
}

$num_of_ex = $qnum; //Getting number of all active exercises of the course
$nbrExercises = count($result); //Getting number of limited (offset and limit) exercises of the course (active and inactive)
if ($is_editor) {
    $pending_exercises = Database::get()->queryArray("SELECT eid, title FROM exercise_user_record a "
            . "JOIN exercise b ON a.eid = b.id WHERE a.attempt_status = ?d AND b.course_id = ?d GROUP BY eid, title", ATTEMPT_PENDING, $course_id);
    if (count($pending_exercises) > 0) {
        foreach ($pending_exercises as $row) {
            $tool_content .="<div class='alert alert-info'>$langPendingExercise " . q($row->title) . ". (<a href='results.php?course=$course_code&exerciseId=".getIndirectReference($row->eid)."&status=2'>$langViewShow</a>)</div>";
        }
    }
    $tool_content .= action_bar(array(
        array('title' => $langNewEx,
            'url' => "admin.php?course=$course_code&amp;NewExercise=Yes",
            'icon' => 'fa-plus-circle',
            'level' => 'primary-label',
            'button-class' => 'btn-success'
        ),
        array('title' => $langQuestionCats,
            'url' => "question_categories.php?course=$course_code",
            'icon' => 'fa-cubes',
            'level' => 'primary'
            ),
        array('title' => $langQuestionPool,
            'url' => "question_pool.php?course=$course_code",
            'icon' => 'fa-university',
            'level' => 'primary'
            )
    ),false);

} else {
    $tool_content .= "";
}

if (!$nbrExercises) {
    $tool_content .= "<div class='alert alert-warning'>$langNoEx</div>";
} else {
    $tool_content .= "<div class='table-responsive'><table id='ex' class='table-default'><thead><tr class='list-header'>";

    // shows the title bar only for the administrator
    if ($is_editor) {
        $tool_content .= "
                <th>$langExerciseName</th>
                <th style='width: 190px;' class='text-center'>$langResults</th>
                <th class='text-center'>".icon('fa-gears')."</th>
              </tr>";
    } else { // student view
        $previousResultsAllowed = !(course_status($course_id) == COURSE_OPEN && $uid ==0);
        $resultsHeader = $previousResultsAllowed ? "<th class='text-center'>$langResults</th>" : "";
        $tool_content .= "
                <th>$langExerciseName</th>
                <th class='text-center'>$langStart / $langFinish</th>
                <th class='text-center'>$langExerciseConstrain</th>
                <th class='text-center'>$langExerciseAttemptsAllowed</th>
                $resultsHeader
              </tr>";
    }
    $tool_content .= "</thead><tbody>";
    // display exercise list    
    foreach ($result as $row) {
        $tool_content .= "<tr ".($is_editor && !$row->active ? "class='not_visible'" : "").">";
        $row->description = standard_text_escape($row->description);
        $exclamation_icon = '';
        $lock_icon = '';
        $link_class = '';
        if (isset($row->password_lock) || isset($row->ip_lock)) {
            $lock_description = "<ul>";
            if ($row->password_lock) {
                $lock_description .= "<li>$langPasswordUnlock</li>";
                $link_class = "password_protected";
            }
            if ($row->ip_lock) {
                $lock_description .= "<li>$langIPUnlock</li>";
            }
            $lock_description .= "</ul>";
            $exclamation_icon = "&nbsp;&nbsp;<span class='fa fa-exclamation-triangle space-after-icon' data-toggle='tooltip' data-placement='right' data-html='true' data-title='$lock_description'></span>";
        }
        if (!$row->public) {
            $lock_icon = "&nbsp;&nbsp;&nbsp;<span class='fa fa-lock'></span>";
        }
        // prof only
        if ($is_editor) {
            if (!empty($row->description)) {
                $descr = "<br/>$row->description";
            } else {
                $descr = '';
            }
            $tool_content .= "<td><a href='exercise_submit.php?course=$course_code&amp;exerciseId={$row->id}'>" . q($row->title) . "</a>$lock_icon$exclamation_icon$descr</td>";
            $eid = getIndirectReference($row->id);
            $NumOfResults = Database::get()->querySingle("SELECT COUNT(*) as count
                FROM exercise_user_record WHERE eid = ?d", $row->id)->count;
            if ($NumOfResults) {
                $tool_content .= "<td class='text-center'><a href='results.php?course=$course_code&amp;exerciseId=$eid'>$langViewShow</a> |
                <a href='csv.php?course=$course_code&amp;exerciseId=$eid'>$langExport</a>
                (<a href='csv.php?course=$course_code&amp;full=true&amp;exerciseId=$eid'>$langExportWithAnswers</a>)</td>";
            } else {
                $tool_content .= "<td class='text-center'>  &mdash; </td>";
            }
            $langModify_temp = htmlspecialchars($langModify);
            $langConfirmYourChoice_temp = addslashes(htmlspecialchars($langConfirmYourChoice));
            $langDelete_temp = htmlspecialchars($langDelete);

            $tool_content .= "<td class='option-btn-cell'>".action_button(array(
                    array('title' => $langEditChange,
                          'url' => "admin.php?course=$course_code&amp;exerciseId=$row->id",
                          'icon' => 'fa-edit'),
                    array('title' => $row->active ?  $langViewHide : $langViewShow,
                          'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;".($row->active ? "choice=disable" : "choice=enable")."&amp;exerciseId=" . $row->id,
                          'icon' => $row->active ? 'fa-eye-slash' : 'fa-eye' ),
                    array('title' => $row->public ? $langResourceAccessLock : $langResourceAccessUnlock,
                          'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;".($row->public ? "choice=limited" : "choice=public")."&amp;exerciseId=$row->id",
                          'icon' => $row->public ? 'fa-lock' : 'fa-unlock',
                          'show' => course_status($course_id) == COURSE_OPEN),
                    array('title' => $langUsage,
                          'url' => "exercise_stats.php?course=$course_code&amp;exerciseId=$row->id",
                          'icon' => 'fa-line-chart'),
                    array('title' => $langCreateDuplicate,
                          'icon-class' => 'warnLink',
                          'icon-extra' => "data-exerciseid='$row->id'",
                          'url' => "#",
                          'icon' => 'fa-copy'),
                    array('title' => $langPurgeExerciseResults,
                          'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;choice=purge&amp;exerciseId=$row->id",
                          'icon' => 'fa-eraser',
                          'confirm' => $langConfirmPurgeExerciseResults),
                    array('title' => $langDelete,
                          'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;choice=delete&amp;exerciseId=$row->id",
                          'icon' => 'fa-times',
                          'class' => 'delete',
                          'confirm' => $langConfirmPurgeExercise)
                    ))."</td></tr>";

        // student only
    } else {
            if (!resource_access($row->active, $row->public)) {
                continue;
            }

            $currentDate = new DateTime('NOW');
            $temp_StartDate = new DateTime($row->start_date);
            $temp_EndDate = isset($row->end_date) ? new DateTime($row->end_date) : null;

            if (($currentDate >= $temp_StartDate) && (!isset($temp_EndDate) || isset($temp_EndDate) && $currentDate <= $temp_EndDate)) {
                
            
                $paused_exercises = Database::get()->querySingle("SELECT eurid, attempt "
                                . "FROM exercise_user_record "
                                . "WHERE eid = ?d AND uid = ?d "
                                . "AND attempt_status = ?d", $row->id, $uid, ATTEMPT_PAUSED);
                if ($paused_exercises) {
                    $password_protected = isset($row->password_lock) && !$is_editor ? " password_lock": "";
                    $tool_content .= "<td><a class='paused_exercise $link_class' href='exercise_submit.php?course=$course_code&amp;exerciseId=$row->id&amp;eurId=$paused_exercises->eurid'>" . q($row->title) . "</a>"
                            . "&nbsp;&nbsp;<span class='fa fa-exclamation-triangle space-after-icon' data-toggle='tooltip' data-placement='right' data-html='true' data-title='$langAttemptPaused'>$lock_icon";
                } else {
                    $tool_content .= "<td><a class='$link_class' href='exercise_submit.php?course=$course_code&amp;exerciseId=$row->id'>" . q($row->title) . "</a>$lock_icon";
                }                                
                
             } elseif ($currentDate <= $temp_StartDate) { // exercise has not yet started
                $tool_content .= "<td class='not_visible'>" . q($row->title) . "$lock_icon&nbsp;&nbsp;";
            } else { // exercise has expired
                $tool_content .= "<td>" . q($row->title) . "$lock_icon&nbsp;&nbsp;(<font color='red'>$langHasExpiredS</font>)";
            }
            $tool_content .= $row->description . "</td><td class='smaller' align='center'>
                                " . nice_format(date("Y-m-d H:i", strtotime($row->start_date)), true) . " /
                                " . (isset($row->end_date) ? nice_format(date("Y-m-d H:i", strtotime($row->end_date)), true) : ' - ') . "</td>";
            if ($row->time_constraint > 0) {
                $tool_content .= "<td class='text-center'>{$row->time_constraint} $langExerciseConstrainUnit</td>";
            } else {
                $tool_content .= "<td class='text-center'> - </td>";
            }
            // how many attempts we have.
            $currentAttempt = Database::get()->querySingle("SELECT COUNT(*) AS count FROM exercise_user_record WHERE eid = ?d AND uid = ?d", $row->id, $uid)->count;
            if ($row->attempts_allowed > 0) {
                $tool_content .= "<td class='text-center'>$currentAttempt/$row->attempts_allowed</td>";
            } else {
                $tool_content .= "<td class='text-center'> - </td>";
            }
            if ($previousResultsAllowed) {
                if ($row->score) {
                    // user last exercise score
                    $attempts = Database::get()->querySingle("SELECT COUNT(*) AS count
                                                FROM exercise_user_record WHERE uid = ?d
                                                AND eid = ?d", $uid, $row->id)->count;
                    if ($attempts > 0) {
                        $eid = getIndirectReference($row->id);
                        $tool_content .= "<td class='text-center'><a href='results.php?course=$course_code&amp;exerciseId=$eid'>$langViewShow</a></td>";
                    } else {
                        $tool_content .= "<td class='text-center''>&dash;</td>";
                    }
                $tool_content .= "</tr>";
                } else {
                    $tool_content .= "<td class='text-center'>$langNotAvailable</td>";
                }
            }
        }        
    } // end while()
    $tool_content .= "</tbody></table></div>";
}
add_units_navigation(TRUE);
$head_content .= "<script type='text/javascript'>
    function password_bootbox(link) {
        bootbox.dialog({
            title: '$langPasswordModalTitle',
            message: '<form class=\"form-horizontal\" role=\"form\" action=\"'+link+'\" method=\"POST\" id=\"password_form\">'+
                        '<div class=\"form-group\">'+
                            '<div class=\"col-sm-12\">'+
                                '<input type=\"text\" class=\"form-control\" id=\"password\" name=\"password\">'+
                            '</div>'+
                        '</div>'+
                      '</form>',
            buttons: {
                cancel: {
                    label: '$langCancel',
                    className: 'btn-default'
                },
                success: {
                    label: '$langSubmit',
                    className: 'btn-success',
                    callback: function (d) {
                        var password = $('#password').val();
                        if(password != '') {
                            $('#password_form').submit();
                        } else {
                            $('#password').closest('.form-group').addClass('has-error');
                            $('#password').after('<span class=\"help-block\">$langTheFieldIsRequired</span>');
                            return false;
                        }
                    }
                }
            }
        });
    }
    $(document).ready(function(){
        $('.paused_exercise').click(function(e){
            e.preventDefault();
            var exercise = $(this);
            var link = $(this).attr('href');
            bootbox.confirm('$langTemporarySaveNotice2', function(result) {
                if(result) {
                    if(exercise.hasClass('password_lock')) {
                        password_bootbox(link);
                    } else {
                        window.location = link;
                    }
                }
            });
        });
        $('.password_protected').click(function(e){
            e.preventDefault();
            var link = $(this).attr('href');
            password_bootbox(link);
        });
    });";
if ($is_editor) {
    $my_courses = Database::get()->queryArray("SELECT a.course_id Course_id, b.title Title FROM course_user a, course b WHERE a.course_id = b.id AND a.course_id != ?d AND a.user_id = ?d AND a.status = 1", $course_id, $uid);
    $courses_options = "";
    foreach ($my_courses as $row) {
        $courses_options .= "'<option value=\"$row->Course_id\">".q($row->Title)."</option>'+";
    }
    $head_content .= "
        $(document).on('click', '.warnLink', function() {
            var exerciseid = $(this).data('exerciseid');
            bootbox.dialog({
                title: '$langCreateDuplicateIn',
                message: '<form action=\"$_SERVER[SCRIPT_NAME]\" method=\"POST\" id=\"clone_form\">'+
                            '<select class=\"form-control\" id=\"course_id\" name=\"clone_to_course_id\">'+
                                '<option value=\"$course_id\">--- $langCurrentCourse ---</option>'+
                                $courses_options
                            '</select>'+
                          '</form>',
                    buttons: {
                        cancel: {
                            label: '$langCancel',
                            className: 'btn-default'
                        },
                        success: {
                            label: '$langCreateDuplicate',
                            className: 'btn-success',
                            callback: function (d) {
                                $('#clone_form').attr('action', 'index.php?course=$course_code&choice=clone&exerciseId=' + exerciseid);
                                $('#clone_form').submit();
                            }
                        }
                    }
            });
        });";
}
$head_content .= "
</script>";
draw($tool_content, 2, null, $head_content);
