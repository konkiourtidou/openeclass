<?php

/* ========================================================================
 * Open eClass 3.4
 * E-learning and Course Management System
 * ========================================================================
 * Copyright 2003-2016  Greek Universities Network - GUnet
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
 * @file form.php
 * @brief display form for creating graph statistics
 */
$require_login = true;

$statsIntervalOptions = '<option value="1" >' . $langPerDay . "</option>" .
        '<option value="7">' . $langPerWeek . "</option>" .
        '<option value="30" selected>' . $langPerMonth . "</option>" .
        '<option value="365">' . $langPerYear . "</option>";

if($stats_type == 'course'){
    if (isset($_GET['id'])) { // get specific user
        $u = Database::get()->querySingle("SELECT u.id, CONCAT(givenname,' ',surname,' (',username,')') name FROM course_user cu
                                                                JOIN user u ON cu.user_id=u.id
                                                            WHERE course_id=?d AND cu.user_id=?d", $course_id, $_GET['id']);
        $statsUserOptions = '<option value="'.$u->id.'" >' . $u->name . "</option>";
    } else { /**Get users of course**/
        $result = Database::get()->queryArray("SELECT u.id, CONCAT(givenname,' ',surname,' (',username,')') name FROM course_user cu
                                                                JOIN user u ON cu.user_id=u.id
                                                            WHERE course_id=?d
                                                            ORDER BY surname, givenname", $course_id);
        $statsUserOptions = '<option value="0" >' . $langUsers . "</option>";
        foreach($result as $u){
            $statsUserOptions .= '<option value="'.$u->id.'" >' . $u->name . "</option>";
        }
    }

    $mod_opts = '<option value="-1">' . $langAllModules . "</option>";
    $result = Database::get()->queryArray("SELECT module_id id FROM course_module WHERE visible = 1 AND course_id = ?d", $course_id);
    foreach($result as $m){
       $mod_opts .= '<option value="'.$m->id.'" >' . which_module($m->id) . "</option>";
    }
}
elseif($stats_type == 'admin'){
    /**Get course departments/categories**/
    $result = Database::get()->queryArray("SELECT chid id, chname name, IF(parent=-1,0,pars) depth  FROM "
            . "(SELECT chid, chname, chlft, COUNT(DISTINCT parid) pars, MAX(parid) parent FROM (SELECT IFNULL(h1.id,-1) parid, h1.name parname, h2.name chname, h2.id chid, h1.lft parlft, h2.lft chlft from hierarchy h1 right "
            . "  JOIN hierarchy h2 ON h1.lft<h2.lft and h1.rgt>=h2.rgt order by h1.id, h2.id) x "
            . "  group by chid) y "
            . "LEFT JOIN hierarchy h ON y.parent=h.id ORDER BY chlft");
    $statsDepOptions = "";
    foreach($result as $d){
       $indentation = "";
       for($i=0;$i<$d->depth;$i++){
           $indentation .= "&nbsp;&nbsp;";
       }
       $statsDepOptions .= '<option value="'.$d->id.'" >' . $indentation.hierarchy::unserializeLangField($d->name) . "</option>\n";
    }
}
else{
    /**Get courses of user**/
    if (isset($_GET['u'])) {
        $result = Database::get()->queryArray("SELECT c.id, c.code, c.title FROM course_user cu JOIN course c ON cu.course_id=c.id WHERE user_id=?d", $_GET['u']);
    } else {
        $result = Database::get()->queryArray("SELECT c.id, c.code, c.title FROM course_user cu JOIN course c ON cu.course_id=c.id WHERE user_id=?d", $uid);
    }
    $statsCourseOptions = '<option value="0" >' . $langAllCourses . "</option>\n";
    foreach($result as $c){
       $statsCourseOptions .= '<option value="'.$c->id.'" >' . $c->title . "</option>\n";
    }
}


$tool_content .= "<div class='col-xxl-12 col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 mt-5'>";
$tool_content .= '<div class="form-wrapper shadow-sm p-3 rounded" data-placement="top">';
$tool_content .= '<div class="form-group" data-placement="top">';

$endDate_obj = new DateTime();
$enddate = $endDate_obj->format('d-m-Y');
$showUntil = q($enddate);
$startDate_obj = $endDate_obj->sub(new DateInterval('P6M'));
$startdate = $startDate_obj->format('d-m-Y');
$showFrom = q($startdate);

$tool_content .= "<label class='pull-left control-label-notes'>$langFrom</label>
        <div class='col-12 col-sm-12'>
            <input class='form-control flex-fill' name='startdate' id='startdate' type='text' value = '$showFrom'>
        </div>";
$tool_content .= "<label class='pull-left control-label-notes pt-3'>$langUntil</label>
            <div class='col-12 col-sm-12'>
                <input class='form-control' name='enddate' id='enddate' type='text' value = '$showUntil'>
            </div>";
$tool_content .= '<div class="col-sm-12 col-12 pt-4"><select name="interval" id="interval" class="form-control">' . $statsIntervalOptions . '</select></div>';

//$tool_content .= "<a id='toggle-view'><i class='fa fa-list' data-toggle='tooltip' data-placement='top' title data-original-title='lala'></i></a>";

if($stats_type == 'course'){

    $tool_content .= '
    <div class="col-sm-12 col-12 pt-4" style="display:none;"><select name="module" id="module" class="form-control">' . $mod_opts . '</select></div>';

    $tool_content .= '
    <div class="col-sm-12 col-12 pt-4"><select name="user" id="user" class="form-control">' . $statsUserOptions . '</select></div>';
}
elseif($stats_type == 'admin'){
    $tool_content .= '
    <div class="col-sm-12 col-12 pt-4"><select name="department" id="department" class="form-control">' . $statsDepOptions . '</select></div>';
}
elseif($stats_type == 'user'){
    $tool_content .= '
    <div class="col-sm-12 col-12 pt-4"><select name="course" id="course" class="form-control">' . $statsCourseOptions . '</select></div>';
}
//<a id="list-view" class="btn btn-default"  data-placement="top" title="'.$langDetails.'" data-toggle="tooltip" data-original-title="'.$langDetails.'"><span class="fa fa-list"  data-toggle="tooltip" data-placement="top"></span></a>

$tool_content .= '<div class="pull-right pt-4">
    <div id="toggle-view" class="btn-group">
        <a id="plots-view" class="btn btn-info active"  data-bs-placement="top" title="'.$langPlots.'" data-bs-toggle="tooltip" data-original-title="'.$langPlots.'"><span class="fa fa-bar-chart"  data-bs-toggle="tooltip" data-bs-placement="top"></span></a>
        <a id="list-view" class="btn btn-info"  data-bs-placement="top" title="'.$langDetails.'" data-bs-toggle="tooltip" data-original-title="'.$langDetails.'"><span class="fa fa-list"  data-bs-toggle="tooltip" data-bs-placement="top"></span></a>';
$tool_content .= ($stats_type == 'course')? '<a id="logs-view" class="btn btn-primary"  data-bs-placement="top" title="'.$langUsersLog.'" data-bs-toggle="tooltip" data-original-title="'.$langUsersLog.'"><span class="fa fa-list-alt"  data-bs-toggle="tooltip" data-bs-placement="top"></span></a>':'';

$tool_content .= '</div>
</div>';

$tool_content .= '</div><div style="clear:both;"></div></div></div>';
