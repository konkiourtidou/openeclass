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


// Check if user is administrator and if yes continue
// Othewise exit with appropriate message
$require_admin = true;
require_once '../../include/baseTheme.php';
require_once 'modules/auth/auth.inc.php';
require_once 'modules/admin/extconfig/externals.php';
require_once 'modules/admin/extconfig/ltipublishapp.php';
$navigation[] = array('url' => 'index.php', 'name' => $langAdmin);
$available_themes = active_subdirs("$webDir/template", 'theme.html');

load_js('select2');

$head_content .= "<script type='text/javascript'>
    function doSelectedCourses() {
        let selectedVals = $('#select-courses').val();
        let i = 0;
        let csvSelection = '';
        
        // comma separate selection and update field
        while (i < selectedVals.length) {
            if (csvSelection.length > 0) {
                csvSelection = csvSelection.concat(',', selectedVals[i]);
            } else {
                csvSelection = csvSelection.concat(selectedVals[i]);
            }
            i++;
        }
        $('#enabled-courses').val(csvSelection);
        
        // remove allcourses selection when selected other courses
        if (selectedVals.length > 1) {
            let index = selectedVals.indexOf('0');
            if (index > -1) {
                selectedVals.splice(index, 1);
                $('#select-courses').val(selectedVals).trigger('change');
            }
        }
        // restore allcourses selection when deselected all other courses
        if (selectedVals.length <= 0) {
            selectedVals.push(0);
            $('#select-courses').val(selectedVals).trigger('change');
        }
    }
    
    $(document).ready(function () {                
        $('#select-courses').select2();
        $('#selectAll').click(function(e) {
            e.preventDefault();
            let stringVal = [];
            $('#select-courses').find('option').each(function(){
                if ($(this).val() != 0) {
                    stringVal.push($(this).val());
                }
            });
            $('#select-courses').val(stringVal).trigger('change');
        });
        $('#removeAll').click(function(e) {
            e.preventDefault();
            let stringVal = [];
            stringVal.push(0);
            $('#select-courses').val(stringVal).trigger('change');
        });
        $('#select-courses').change(function(e) {
            doSelectedCourses();
        });
        doSelectedCourses();
    });
</script>";

// code from extapp.php

$app = ExtAppManager::getApp('ltipublish');

if (isset($_POST['submit'])) {
    if (!isset($_POST['token']) || !validate_csrf_token($_POST['token'])) csrf_token_error();
    if ($_POST['submit'] == 'clear') {
        foreach ($app->getParams() as $param) {
            $param->setValue('');
            $param->persistValue();
        }
        //Session::Messages($langFileUpdatedSuccess, 'alert-info');
        Session::flash('message',$langFileUpdatedSuccess); 
        Session::flash('alert-class', 'alert-info');
    } else {
        $result = $app->storeParams();
        if ($result) {
            //Session::Messages($result, 'alert-danger');
            Session::flash('message',$result); 
            Session::flash('alert-class', 'alert-danger');
        } else {
            //Session::Messages($langFileUpdatedSuccess, 'alert-success');
            Session::flash('message',$langFileUpdatedSuccess); 
            Session::flash('alert-class', 'alert-success');
        }
    }

    redirect_to_home_page($app->getConfigUrl());
}

$navigation[] = array('url' => 'extapp.php', 'name' => $langExtAppConfig);
$pageName = $langModify . ' ' . $app->getDisplayName();
$tool_content .= action_bar(array(
    array('title' => $langBack,
        'url' => 'extapp.php',
        'icon' => 'fa-reply',
        'level' => 'primary-label')));

$boolean_field = "";
$tool_content .= "
    <div class='extapp'><div class='col-12'>
      <div class='form-wrapper shadow-sm p-3 rounded'>
        <form class='form-horizontal' role='form' action='$_SERVER[SCRIPT_NAME]' method='post'>";

foreach ($app->getParams() as $param) {
    if ($param->getType() == ExtParam::TYPE_BOOLEAN) {
        $checked = $param->value() == 1 ? "checked" : "";
        $boolean_field .= "<div class='form-group'><div class='col-sm-offset-2 col-sm-10'><div class='checkbox'>";
        $boolean_field .= "<label><input type='checkbox' name='" . $param->name() . "' value='1' $checked>" . $param->display() . "</label>";
        $boolean_field .= "</div></div></div>";
    } else if ($param->name() == LtiPublishApp::FRAMEANCESTORS) {
        $tool_content .= "<div class='form-group mt-3'>";
        $tool_content .= "<label for='" . $param->name() . "' class='col-sm-6 control-label-notes'>" . $param->display() . "&nbsp;&nbsp;";
        $tool_content .= "<span class='fa fa-info-circle' data-bs-toggle='tooltip' data-bs-placement='bottom' title data-bs-original-title='$langLtiPublishFrameAncestorsTooltip'></span></label>";
        $tool_content .= "<div class='col-sm-12'><input class='form-control' type='text' name='" . $param->name() . "' value='" . q($param->value()) . "' placeholder='https://url1, https://url2'></div>";
        $tool_content .= "</div>";
    } else if ($param->name() == LtiPublishApp::ENABLEDCOURSES) {
        $courses_list = Database::get()->queryArray("SELECT id, code, title FROM course
                                                                WHERE visible != " . COURSE_INACTIVE . "
                                                             ORDER BY title");
        $csvSelection = $param->value();
        $selections = array();
        if (!empty($csvSelection) && strlen($csvSelection) > 0) {
            $selections = explode(",", $csvSelection);
            $selected = in_array("0", $selections) ? "selected" : "";
        } else {
            $selected = "selected";
        }
        $tool_content .= "<div class='form-group mt-3' id='courses-list'>";
        $tool_content .= "<label for='" . $param->name() . "' class='col-sm-6 control-label-notes'>$langUseOfApp&nbsp;&nbsp;";
        $tool_content .= "<span class='fa fa-info-circle' data-bs-toggle='tooltip' data-bs-placement='bottom' title data-bs-original-title='$langUseOfAppInfo'></span></label>";
        $tool_content .= "<div class='col-sm-12'><select id='select-courses' class='form-control' name='lti_courses[]' multiple>";
        $tool_content .= "<option value='0' $selected><h2>$langToAllCourses</h2></option>";
        foreach($courses_list as $c) {
            $selected = in_array($c->id, $selections) ? "selected" : "";
            $tool_content .= "<option value='$c->id' $selected>" . q($c->title) . " (" . q($c->code) . ")</option>";
        }
        $tool_content .= "</select><a href='#' id='selectAll'>$langJQCheckAll</a> | <a href='#' id='removeAll'>$langJQUncheckAll</a></div></div>";
        $tool_content .= "<input type='hidden' id='enabled-courses' name='" . $param->name() . "'>";
    } else {
        $tool_content .= "<div class='form-group mt-3'>";
        $tool_content .= "<label for='" . $param->name() . "' class='col-sm-6 control-label-notes'>" . $param->display() . "</label>";
        $tool_content .= "<div class='col-sm-12'><input class='form-control' type='text' name='" . $param->name() . "' value='" . q($param->value()) . "'></div>";
        $tool_content .= "</div>";
    }
}

$tool_content .= $boolean_field;
$tool_content .= "
            <div class='form-group mt-3'>
              <div class='col-sm-offset-2 col-sm-10'>
                <button class='btn btn-primary' type='submit' name='submit'>$langSubmit</button>
                <button class='btn btn-danger' type='submit' name='submit' value='clear'>$langClearSettings</button>
                <a href='extapp.php' class='btn btn-default'>$langCancel</a>
              </div>
            </div>" .
          generate_csrf_token_form_field() . "
        </form>
      </div>
    </div>
  </div>";

draw($tool_content, 3, null, $head_content);
