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


include 'exercise.class.php';
include 'question.class.php';
include 'answer.class.php';

$require_editor = TRUE;
$require_current_course = TRUE;

include '../../include/baseTheme.php';
require_once 'imsqtilib.php';

load_js('datatables');

$head_content .= "<script type='text/javascript'>
        $(document).ready(function() {
            $('#questions').DataTable ({
                'fnDrawCallback': function (settings) { typeof MathJax !== 'undefined' && MathJax.typeset(); },
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
                          class : 'form-control input-sm ms-0 mb-3',
                          placeholder : '$langSearch...'
                        });
        });
    </script>";

$head_content .= "
    <script>
        $(function() {
            $(document).on('click', '.warnLink', function(e){
                var modifyAllLink = $(this).attr('href');
                var modifyOneLink = modifyAllLink.concat('&clone=true');
                $('a#modifyAll').attr('href', modifyAllLink);
                $('a#modifyOne').attr('href', modifyOneLink);
            });
        });
    </script>";

$my_courses = Database::get()->queryArray("SELECT a.course_id Course_id, b.title Title FROM course_user a, course b
                              WHERE a.course_id = b.id
                                  AND a.course_id != ?d
                                  AND a.user_id = ?d
                                  AND a.status = " .USER_TEACHER . "", $course_id, $uid);
$courses_options = "";
foreach ($my_courses as $row) {
    $courses_options .= "'<option value=\"$row->Course_id\">".q($row->Title)."</option>'+";
}

$head_content .= "<script>
            $(function() {
                $('.warnDup').on('click', function(e) {
                    e.preventDefault();
                    bootbox.dialog({
                        title: '" . js_escape($langCreateDuplicateIn) . "',
                        message: '<form action=\"$_SERVER[SCRIPT_NAME]\" method=\"POST\" id=\"clone_pool_form\">'+
                                    '<select class=\"form-control\" id=\"course_id\" name=\"clone_pool_to_course_id\">'+
                                        $courses_options
                                    '</select>'+
                                  '</form>',
                        buttons: {
                            cancel: {
                                label: '" . js_escape($langCancel) . "',
                                className: 'btn-default'
                            },
                            success: {
                                label: '" . js_escape($langCreateDuplicate) . "',
                                className: 'btn-success',
                                callback: function (d) {
                                    $('#clone_pool_form').attr('action', '$_SERVER[SCRIPT_NAME]?course=$course_code&clone_pool=1');
                                    $('#clone_pool_form').submit();
                                }
                            }
                        }
                    });
                });
            });
    </script>";

$tool_content .= "<div id='dialog' style='display:none;'>$langUsedInSeveralExercises</div>";

$toolName = $langQuestionPool;
$navigation[] = array("url" => "index.php?course=$course_code", "name" => $langExercices);


if (isset($_GET['fromExercise'])) {
    $objExercise = new Exercise();
    $fromExercise = intval($_GET['fromExercise']);
    $objExercise->read($fromExercise);
    $navigation[] = array("url" => "admin.php?course=$course_code&amp;exerciseId=$fromExercise", "name" => $langExerciseManagement);
}

if (isset($_GET['exerciseId'])) {
    $exerciseId = intval($_GET['exerciseId']);
}
if (isset($_GET['difficultyId'])) {
    $difficultyId = intval($_GET['difficultyId']);
}
if (isset($_GET['categoryId'])) {
    $categoryId = intval($_GET['categoryId']);
}

    // deletes a question from the data base and all exercises
if (isset($_GET['delete'])) {
    $delete = intval($_GET['delete']);
    // construction of the Question object
    $objQuestionTmp = new Question();
    // if the question exists
    if ($objQuestionTmp->read($delete)) {
        // deletes the question from all exercises
        $objQuestionTmp->delete();
    }
    // destruction of the Question object
    unset($objQuestionTmp);
    redirect_to_home_page("modules/exercise/question_pool.php?course=$course_code".(isset($fromExercise) ? "&amp;fromExercise=$fromExercise" : "")."&exerciseId=$exerciseId");
}
// gets an existing question and copies it into a new exercise
elseif (isset($_GET['recup']) && isset($fromExercise)) {
    $recup = intval($_GET['recup']);
    // construction of the Question object
    $objQuestionTmp = new Question();
    // if the question exists, add it into the list of questions for the
    // current exercise
    if ($objQuestionTmp->read($recup) and $objExercise->addToList($recup)) {
        //Session::Messages($langQuestionReused, 'alert-success');
        Session::flash('message',$langQuestionReused); 
        Session::flash('alert-class', 'alert-success');
        $objExercise->save();
    }
    redirect_to_home_page("modules/exercise/question_pool.php?course=$course_code".(isset($fromExercise) ? "&fromExercise=$fromExercise" : "")."&exerciseId=$exerciseId");
} elseif (isset($_REQUEST['clone_pool'])) {
    clone_question_pool($_POST['clone_pool_to_course_id']);
    //Session::Messages($langCopySuccess, 'alert-success');
    Session::flash('message',$langCopySuccess); 
    Session::flash('alert-class', 'alert-success');
    redirect_to_home_page('modules/exercise/index.php?course='.$course_code);
} elseif (isset($_REQUEST['purge'])) {
    purge_question_pool($course_id);
    //Session::Messages($langQuestionPoolPurgeSuccess, 'alert-success');
    Session::flash('message',$langQuestionPoolPurgeSuccess); 
    Session::flash('alert-class', 'alert-success');
    redirect_to_home_page('modules/exercise/index.php?course='.$course_code);
}

if (isset($fromExercise)) {
    $action_bar_options[] = array('title' => $langGoBackToEx,
            'url' => "admin.php?course=$course_code&amp;exerciseId=$fromExercise",
            'icon' => 'fa-reply',
            'level' => 'primary-label'
     );
} else {
    $action_bar_options = array(
        array('title' => $langNewQu,
              'url' => "admin.php?course=$course_code&amp;newQuestion=yes",
              'icon' => 'fa-plus-circle',
              'level' => 'primary-label',
              'button-class' => 'btn-success'),
        array('title' => $langCreateDuplicate,
              'url' => "question_pool.php?course=$course_code&amp;dup=yes",
              'icon' => 'fa-copy',
              'level' => 'primary-label',
              'class' => 'warnDup',
              'button-class' => 'btn-success'),
        array('title' => $langQuestionPoolPurge,
              'url' => "question_pool.php?course=$course_code&amp;purge=yes",
              'icon' => 'fa-eraser',
              'class' => 'delete',
              'confirm' => $langConfirmQuestionPoolPurge),
        array('title' => $langImportQTI,
              'url' => "admin.php?course=$course_code&amp;importIMSQTI=yes",
              'icon' => 'fa-download',
              'button-class' => 'btn-success'),
        array('title' => $langExportQTI,
              'url' => "question_pool.php?". $_SERVER['QUERY_STRING'] . "&amp;exportIMSQTI=yes",
              'icon' => 'fa-upload',
              'button-class' => 'btn-success')
     );
}

$tool_content .= action_bar($action_bar_options);
$tool_content .= "<div class='row p-2'></div>";

if (isset($fromExercise)) {
    $result = Database::get()->queryArray("SELECT id, title FROM `exercise` WHERE course_id = ?d AND id <> ?d ORDER BY id", $course_id, $fromExercise);
} else {
    $result = Database::get()->queryArray("SELECT id, title FROM `exercise` WHERE course_id = ?d ORDER BY id", $course_id);
}
$exercise_options = "<option value = '0'>-- $langAllExercises --</option>\n
                    <option value = '-1' ".(isset($exerciseId) && $exerciseId == -1 ? "selected='selected'": "").">-- $langOrphanQuestions --</option>\n";
foreach ($result as $row) {
    $exercise_options .= "
         <option value='" . $row->id . "' ".(isset($exerciseId) && $exerciseId == $row->id ? "selected='selected'":"").">$row->title</option>\n";
}
//Create exercise category options
$q_cats = Database::get()->queryArray("SELECT * FROM exercise_question_cats WHERE course_id = ?d", $course_id);
$q_cat_options = "<option value='-1' ".(isset($categoryId) && $categoryId == -1 ? "selected": "").">-- $langQuestionAllCats --</option>\m
                  <option value='0' ".(isset($categoryId) && $categoryId == 0 ? "selected": "").">-- $langQuestionWithoutCat --</option>\n";
foreach ($q_cats as $q_cat) {
    $q_cat_options .= "<option value='" . $q_cat->question_cat_id . "' ".(isset($categoryId) && $categoryId == $q_cat->question_cat_id ? "selected":"").">$q_cat->question_cat_name</option>\n";
}
//Start of filtering Component
$tool_content .= "
<div class='col-xxl-12 col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12'>
    <div class='form-wrapper shadow-sm p-3 rounded'><form class='form-inline' role='form' name='qfilter' method='get' action='$_SERVER[REQUEST_URI]'><input type='hidden' name='course' value='$course_code'>
                    ".(isset($fromExercise)? "<input type='hidden' name='fromExercise' value='$fromExercise'>" : "")."
                    <div class='form-group mt-3'>
                        <select onChange = 'document.qfilter.submit();' name='exerciseId' class='form-select'>
                            $exercise_options
                        </select>
                </div>
                <div class='form-group mt-3'>
                    <select onChange = 'document.qfilter.submit();' name='difficultyId' class='form-select'>
                        <option value='-1' ".(isset($difficultyId) && $difficultyId == -1 ? "selected='selected'": "").">-- $langQuestionAllDiffs --</option>
                        <option value='0' ".(isset($difficultyId) && $difficultyId == 0 ? "selected='selected'": "").">-- $langQuestionNotDefined --</option>
                        <option value='1' ".(isset($difficultyId) && $difficultyId == 1 ? "selected='selected'": "").">$langQuestionVeryEasy</option>
                        <option value='2' ".(isset($difficultyId) && $difficultyId == 2 ? "selected='selected'": "").">$langQuestionEasy</option>
                        <option value='3' ".(isset($difficultyId) && $difficultyId == 3 ? "selected='selected'": "").">$langQuestionModerate</option>
                        <option value='4' ".(isset($difficultyId) && $difficultyId == 4 ? "selected='selected'": "").">$langQuestionDifficult</option>
                        <option value='5' ".(isset($difficultyId) && $difficultyId == 5 ? "selected='selected'": "").">$langQuestionVeryDifficult</option>
                    </select>
                </div>
                <div class='form-group mt-3'>
                    <select onChange = 'document.qfilter.submit();' name='categoryId' class='form-select'>
                        $q_cat_options
                    </select>
                </div>
            </form>
        </div>
</div>";
//End of filtering Component

if (isset($fromExercise)) {
    $tool_content .= "<input type='hidden' name='fromExercise' value='$fromExercise'>";
}

$tool_content .= "<table class='table-default' id='questions'>";

//START OF BUILDING QUERIES AND QUERY VARS
if (isset($exerciseId) && $exerciseId > 0) { //If user selected specific exercise
    //Building query vars and query
    $result_query_vars = array($course_id, $exerciseId);
    $extraSql = "";
    if(isset($difficultyId) && $difficultyId!=-1) {
        $result_query_vars[] = $difficultyId;
        $extraSql .= " AND difficulty = ?d";
    }
    if(isset($categoryId) && $categoryId!=-1) {
        $result_query_vars[] = $categoryId;
        $extraSql .= " AND category = ?d";
    }
    $result_query_vars = isset($fromExercise) ? array_merge($result_query_vars, array($fromExercise, $fromExercise)) : $result_query_vars;
    if (isset($fromExercise)) {
        $result_query = "SELECT exercise_question.id FROM `exercise_question` LEFT JOIN `exercise_with_questions`
                        ON question_id = exercise_question.id WHERE course_id = ?d  AND exercise_id = ?d$extraSql AND (exercise_id IS NULL OR exercise_id <> ?d AND
                        question_id NOT IN (SELECT question_id FROM `exercise_with_questions` WHERE exercise_id = ?d))
                        GROUP BY exercise_question.id ORDER BY question";
    } else {
        $result_query = "SELECT exercise_question.id FROM `exercise_with_questions`, `exercise_question`
                        WHERE course_id = ?d AND question_id = exercise_question.id AND exercise_id = ?d$extraSql
                        ORDER BY q_position";
    }
} else { // if user selected either Orphan Question or All Questions
    $result_query_vars[] = $course_id;
    $extraSql = "";
    if(isset($difficultyId) && $difficultyId!=-1) {
        $result_query_vars[] = $difficultyId;
        $extraSql .= " AND difficulty = ?d";
    }
    if(isset($categoryId) && $categoryId!=-1) {
        $result_query_vars[] = $categoryId;
        $extraSql .= " AND category = ?d";
    }
    // If user selected All question and comes to question pool from an exercise
    if ((!isset($exerciseId) || $exerciseId == 0) && isset($fromExercise)) {
        $result_query_vars = array_merge($result_query_vars, array($fromExercise, $fromExercise));
    }
    //When user selected orphan questions
    if (isset($exerciseId) && $exerciseId == -1) {
        $result_query = "SELECT exercise_question.id, question, `type` FROM `exercise_question` LEFT JOIN `exercise_with_questions`
                        ON question_id = exercise_question.id WHERE course_id = ?d AND exercise_id IS NULL$extraSql ORDER BY question";
    } else { // if user selected all questions
        if (isset($fromExercise)) { // if is coming to question pool from an exercise
            $result_query = "SELECT exercise_question.id, question, `type` FROM `exercise_question` LEFT JOIN `exercise_with_questions`
                            ON question_id = exercise_question.id WHERE course_id = ?d$extraSql AND (exercise_id IS NULL OR exercise_id <> ?d AND
                            question_id NOT IN (SELECT question_id FROM `exercise_with_questions` WHERE exercise_id = ?d))
                            GROUP BY exercise_question.id, question, `type` ORDER BY question";
        } else {
            $result_query = "SELECT exercise_question.id, question, `type` FROM `exercise_question` LEFT JOIN `exercise_with_questions`
                            ON question_id = exercise_question.id WHERE course_id = ?d$extraSql
                            GROUP BY exercise_question.id, question, type ORDER BY question";
        }
        // forces the value to 0
        $exerciseId = 0;
    }
}

if (isset($_GET['exportIMSQTI'])) { // export to IMS QTI xml format
    $result = Database::get()->queryArray($result_query, $result_query_vars);
    header('Content-type: text/xml');
    header('Content-Disposition: attachment; filename="exportQTI.xml"');
    exportIMSQTI($result);
    exit();

} else {
    $result = Database::get()->queryArray($result_query, $result_query_vars);
    $tool_content .= "<thead class='notes_thead'>
    <tr>
      <th class='text-white'>$langQuesList</th>
      <th class='text-white text-end'>".icon('fa-cogs')."</th>
    </tr></thead><tbody>";
    foreach ($result as $row) {
        $question_temp = new Question();
        $question_temp->read($row->id);
        $question_title = q_math($question_temp->selectTitle());
        $question_difficulty_legend = $question_temp->selectDifficultyIcon($question_temp->selectDifficulty());
        $question_category_legend = $question_temp->selectCategoryName($question_temp->selectCategory());
        $question_type_legend = $question_temp->selectTypeLegend($question_temp->selectType());
        $exercise_ids = $question_temp->selectExerciseList();
        $exercises_used_in = '';
        foreach ($exercise_ids as $ex_id) {
            $q = Database::get()->querySingle("SELECT title FROM exercise WHERE id = ?d", $ex_id);
            $exercises_used_in .= "<span class='help-block' style='margin-bottom: 0px;'>" . q($q->title) . "</span>";
        }
        if (isset($fromExercise) || !is_object(@$objExercise) || !$objExercise->isInList($row->id)) {
            $tool_content .= "<tr>";
            if (!isset($fromExercise)) {
                $tool_content .= "<td><a ".((count($exercise_ids)>0)? "class='warnLink' data-bs-toggle='modal' data-bs-target='#modalWarning' data-remote='false'" : "")." href=\"admin.php?course=$course_code&amp;modifyAnswers=" . $row->id . "&amp;fromExercise=\">" . $question_title . "</a>
                    <br>
                    <small>" . $question_type_legend . " " . $question_difficulty_legend . " " . $question_category_legend  . " " . $exercises_used_in . "</small>
                    </td>";
            } else {
                $tool_content .= "<td><a href=\"admin.php?course=$course_code&amp;modifyAnswers=" . $row->id . "&amp;fromExercise=" . $fromExercise . "\">" . $question_title . "</a>
                    <br>
                    <small>" . $question_type_legend . " " . $question_difficulty_legend . " " . $question_category_legend . $exercises_used_in ."</small>
                    </td>";
            }

            $tool_content .= "<td style='float:right' class='option-btn-cell'>".
                action_button(array(
                    array('title' => $langEditChange,
                          'url' => "admin.php?course=$course_code&amp;modifyAnswers=" . $row->id,
                          'icon-class' => 'warnLink',
                          'icon-extra' => ((count($exercise_ids)>0)?
                                " data-bs-toggle='modal' data-bs-target='#modalWarning' data-remote='false'" : ""),
                          'icon' => 'fa-edit',
                          'show' => !isset($fromExercise)),
                    array('title' => $langReuse,
                          'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;recup=$row->id&amp;fromExercise=" .
                                (isset($fromExercise) ? $fromExercise : '') .
                                "&amp;exerciseId=$exerciseId",
                          'level' => 'primary',
                          'icon' => 'fa-plus-square',
                          'show' => isset($fromExercise)),
                    array('title' => $langDelete,
                          'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;exerciseId=$exerciseId&amp;delete=$row->id",
                          'icon' => 'fa-times',
                          'class' => 'delete',
                          'confirm' => $langConfirmYourChoice,
                          'show' => !isset($fromExercise))
                 )) .
                 "</td></tr>";
        }
        unset($question_temp);
    }
    $tool_content .= "</tbody></table>";
}

$tool_content .= "
<!-- Modal -->
<div class='modal fade' id='modalWarning' tabindex='-1' role='dialog' aria-labelledby='modalWarningLabel' aria-hidden='true'>
  <div class='modal-dialog'>
    <div class='modal-content'>
      <div class='modal-header'>
        <button type='button' class='close' data-bs-dismiss='modal'><span aria-hidden='true'>&times;</span><span class='sr-only'>Close</span></button>
      </div>
      <div class='modal-body'>
        $langUsedInSeveralExercises
      </div>
      <div class='modal-footer'>
        <a href='#' id='modifyAll' class='btn btn-primary'>$langModifyInAllExercises</a>
        <a href='#' id='modifyOne' class='btn btn-success'>$langModifyInQuestionPool</a>
      </div>
    </div>
  </div>
</div>
";

draw($tool_content, 2, null, $head_content);


/**
 * @brief clone question pool to new course
 * @param $new_course_id
 */
function clone_question_pool($clone_course_id)
{
    global $course_code, $course_id;

    $old_path = "courses/$course_code/image/quiz-";
    $new_path = 'courses/' . course_id_to_code($clone_course_id) . '/image/quiz-';
    Database::get()->queryFunc("SELECT id FROM exercise_question WHERE course_id = ?d",
        function ($question) use ($clone_course_id, $old_path, $new_path) {
            $question_clone_id = Database::get()->query("INSERT INTO exercise_question
                    (course_id, question, description, weight, type, difficulty, category)
                    SELECT ?d, question, description, weight, type, difficulty, 0
                        FROM `exercise_question` WHERE id = ?d", $clone_course_id, $question->id)->lastInsertID;
            Database::get()->query("INSERT INTO exercise_answer
                    (question_id, answer, correct, comment, weight, r_position)
                    SELECT ?d, answer, correct, comment, weight, r_position FROM exercise_answer
                        WHERE question_id = ?d",
                $question_clone_id, $question->id);
            $old_image_path = $old_path . $question->id;
            if (file_exists($old_image_path)) {
                copy($old_image_path, $new_path . $question_clone_id);
            }
        },
    $course_id);
}


/**
 * @brief purge orphan questions in question pool
 * @param $course_id
 */
function purge_question_pool($course_id) {

    Database::get()->query("DELETE FROM exercise_question
            WHERE exercise_question.course_id = ?d
            AND exercise_question.id NOT IN
              (SELECT question_id FROM exercise_with_questions)", $course_id);

}
