<?php
/**
 * @file functions.php
 * @brief Units utility functions
 */

require_once 'include/lib/mediaresource.factory.php';
require_once 'include/lib/multimediahelper.class.php';

/**
 * @brief  Process resource actions
 * @global type $tool_content
 * @global type $id
 * @global type $langResourceCourseUnitDeleted
 * @global type $langResourceUnitModified
 * @global type $course_id
 * @global type $course_code
 * @return string
 */
function process_actions() {
    global $tool_content, $id, $langResourceCourseUnitDeleted,
        $langResourceUnitModified, $course_id, $course_code, $webDir,
        $head_content, $langBack, $urlAppend, $navigation, $pageName,
        $langEditChange, $langViMod;

    // update index and refresh course metadata
    require_once 'modules/search/indexer.class.php';
    require_once 'modules/course_metadata/CourseXML.php';
    if (isset($_REQUEST['edit'])) {
        $res_id = intval($_GET['edit']);
        if (check_admin_unit_resource($res_id)) {
            $q = Database::get()->querySingle("SELECT title FROM course_units
                WHERE id = ?d AND course_id = ?d", $id, $course_id);
            $navigation[] = array('url' => "index.php?course=$course_code&amp;id=$id", 'name' => $q->title);
            $pageName = $langEditChange;
            $tool_content .= action_bar(array(
                array('title' => $langBack,
                      'url' => "{$urlAppend}modules/units/index.php?course=$course_code&amp;id=$id",
                      'icon' => 'fa-reply',
                      'level' => 'primary-label')));
            $tool_content .= edit_res($res_id);
            draw($tool_content, 2, null, $head_content);
            exit;
        }
    } elseif (isset($_REQUEST['edit_res_submit'])) { // edit resource
        $res_id = intval($_REQUEST['resource_id']);
        if (check_admin_unit_resource($res_id)) {
            if (!isset($_REQUEST['restitle'])) {
                $restitle = '';
            } else {
                $restitle = $_REQUEST['restitle'];
            }
            $rescomments = purify($_REQUEST['rescomments']);
            Database::get()->query("UPDATE unit_resources SET
                                        title = ?s,
                                        comments = ?s
                                        WHERE unit_id = ?d AND id = ?d", $restitle, $rescomments, $id, $res_id);
            Indexer::queueAsync(Indexer::REQUEST_STORE, Indexer::RESOURCE_UNITRESOURCE, $res_id);
            Indexer::queueAsync(Indexer::REQUEST_STORE, Indexer::RESOURCE_COURSE, $course_id);
            CourseXMLElement::refreshCourse($course_id, $course_code);
        }
       // Session::Messages($langResourceUnitModified, 'alert-success');
        Session::flash('message',$langResourceUnitModified); 
        Session::flash('alert-class', 'alert-success');
        redirect_to_home_page('modules/units/index/php?course=' . $course_code . '&id=' . $id);
    } elseif (isset($_REQUEST['del'])) { // delete resource from course unit
        $res_id = intval($_GET['del']);
        if (check_admin_unit_resource($res_id)) {
            Database::get()->query("DELETE FROM unit_resources WHERE id = ?d", $res_id);
            Indexer::queueAsync(Indexer::REQUEST_REMOVE, Indexer::RESOURCE_UNITRESOURCE, $res_id);
            Indexer::queueAsync(Indexer::REQUEST_STORE, Indexer::RESOURCE_COURSE, $course_id);
            CourseXMLElement::refreshCourse($course_id, $course_code);
           // Session::Messages($langResourceCourseUnitDeleted, 'alert-success');
            Session::flash('message',$langResourceCourseUnitDeleted); 
            Session::flash('alert-class', 'alert-success');
            redirect_to_home_page('modules/units/index.php?course=' . $course_code . '&id=' . $id);
        }
    } elseif (isset($_REQUEST['vis'])) { // modify visibility in text resources only
        $res_id = intval($_REQUEST['vis']);
        if (check_admin_unit_resource($res_id)) {
            $vis = Database::get()->querySingle("SELECT `visible` FROM unit_resources WHERE id = ?d", $res_id)->visible;
            $newvis = ($vis == 1) ? 0 : 1;
            Database::get()->query("UPDATE unit_resources SET visible = '$newvis' WHERE id = ?d", $res_id);
            Indexer::queueAsync(Indexer::REQUEST_STORE, Indexer::RESOURCE_UNITRESOURCE, $res_id);
            Indexer::queueAsync(Indexer::REQUEST_STORE, Indexer::RESOURCE_COURSE, $course_id);
            CourseXMLElement::refreshCourse($course_id, $course_code);
            //Session::Messages($langViMod, 'alert-success');
            Session::flash('message',$langViMod); 
            Session::flash('alert-class', 'alert-success');
            redirect_to_home_page('modules/units/index.php?course=' . $course_code . '&id=' . $id);
        }
    } elseif (isset($_REQUEST['down'])) { // change order down
        $res_id = intval($_REQUEST['down']);
        if (check_admin_unit_resource($res_id)) {
            move_order('unit_resources', 'id', $res_id, 'order', 'down', "unit_id=$id");
        }
    } elseif (isset($_REQUEST['up'])) { // change order up
        $res_id = intval($_REQUEST['up']);
        if (check_admin_unit_resource($res_id)) {
            move_order('unit_resources', 'id', $res_id, 'order', 'up', "unit_id=$id");
        }
    }
    return '';
}

/**
 * @brief Check that a specified resource id belongs to a resource in the
          current course, and that the user is an admin in this course.
          Return the id of the unit or false if user is not an admin
 * @global type $course_id
 * @global type $is_editor
 * @param type $resource_id
 * @return boolean
 */
function check_admin_unit_resource($resource_id) {
    global $course_id, $is_editor;

    if ($is_editor) {
        $q = Database::get()->querySingle("SELECT course_units.id AS cuid FROM course_units,unit_resources WHERE
            course_units.course_id = ?d AND course_units.id = unit_resources.unit_id
            AND unit_resources.id = ?d", $course_id, $resource_id);
        if ($q) {
            return true;
        } else {
            return false;
        }
    } else {
        return false;
    }
}


/**
 * @brief Display resources for unit with id=$id
 * @global type $tool_content
 * @global type $max_resource_id
 * @param type $unit_id
 */
function show_resources($unit_id) {
    global $max_resource_id,
           $head_content, $langDownload, $langPrint, $langCancel,
           $langFullScreen, $langNewTab;

    $html = '';
    $req = Database::get()->queryArray("SELECT * FROM unit_resources WHERE unit_id = ?d AND `order` >= 0 ORDER BY `order`", $unit_id);
    if (count($req) > 0) {
        load_js('screenfull/screenfull.min.js');
        $head_content .= "<script>
        $(document).ready(function(){
            Sortable.create(unitResources,{
                handle: '.fa-arrows',
                animation: 150,
                onEnd: function (evt) {

                var itemEl = $(evt.item);

                var idReorder = itemEl.attr('data-id');
                var prevIdReorder = itemEl.prev().attr('data-id');

                $.ajax({
                  type: 'post',
                  dataType: 'text',
                  data: {
                          toReorder: idReorder,
                          prevReorder: prevIdReorder,
                        }
                    });
                }
            });
        });
        $(function(){
            $('.fileModal').click(function (e)
            {
                e.preventDefault();
                var fileURL = $(this).attr('href');
                var downloadURL = $(this).prev('input').val();
                var fileTitle = $(this).attr('title');
                var buttons = {};
                if (downloadURL) {
                    buttons.download = {
                            label: '<i class=\"fa fa-download\"></i> $langDownload',
                            className: 'btn-success',
                            callback: function (d) {
                                window.location = downloadURL;
                            }
                    };
                }
                buttons.print = {
                            label: '<i class=\"fa fa-print\"></i> $langPrint',
                            className: 'btn-primary',
                            callback: function (d) {
                                var iframe = document.getElementById('fileFrame');
                                iframe.contentWindow.print();
                            }
                        };
                if (screenfull.enabled) {
                    buttons.fullscreen = {
                        label: '<i class=\"fa fa-arrows-alt\"></i> $langFullScreen',
                        className: 'btn-primary',
                        callback: function() {
                            screenfull.request(document.getElementById('fileFrame'));
                            return false;
                        }
                    };
                }
                buttons.newtab = {
                    label: '<i class=\"fa fa-plus\"></i> $langNewTab',
                    className: 'btn-primary',
                    callback: function() {
                        window.open(fileURL);
                        return false;
                    }
                };
                buttons.cancel = {
                            label: '$langCancel',
                            className: 'btn-default'
                        };
                bootbox.dialog({
                    size: 'large',
                    title: fileTitle,
                    message: '<div class=\"row\">'+
                                '<div class=\"col-sm-12\">'+
                                    '<div class=\"iframe-container\"><iframe id=\"fileFrame\" src=\"'+fileURL+'\"></iframe></div>'+
                                '</div>'+
                            '</div>',
                    buttons: buttons
                });
            });
        });

        </script>";
        $max_resource_id = Database::get()->querySingle("SELECT id FROM unit_resources
                                WHERE unit_id = ?d ORDER BY `order` DESC LIMIT 1", $unit_id)->id;
        $html .= "<div class='table-responsive'>";
        $html .= "<table class='table table-striped table-hover'><tbody id='unitResources'>";
        foreach ($req as $info) {
            $info->comments = standard_text_escape($info->comments);
            $html .= show_resource($info);
        }
        $html .= "</tbody></table>";
        $html .= "</div>";
    }
    return $html;
}

/**
 * @brief display unit resources
 * @global type $tool_content
 * @global type $langUnknownResType
 * @global type $is_editor
 * @param type $info
 * @return type
 */
function show_resource($info) {
    global $langUnknownResType, $is_editor;

    $html = '';
    if ($info->visible == 0 and $info->type != 'doc' and ! $is_editor) { // special case handling for old unit resources with type 'doc' .
        return;
    }
    switch ($info->type) {
        case 'doc':
            $html .= show_doc($info->title, $info->comments, $info->id, $info->res_id);
            break;
        case 'text':
            $html .= show_text($info->comments, $info->id, $info->visible);
            break;
        case 'description':
            $html .= show_description($info->title, $info->comments, $info->id, $info->res_id, $info->visible);
            break;
        case 'lp':
            $html .= show_lp($info->title, $info->comments, $info->id, $info->res_id);
            break;
        case 'video':
        case 'videolink':
            $html .= show_video($info->type, $info->title, $info->comments, $info->id, $info->res_id, $info->visible);
            break;
        case 'videolinkcategory':
            $html .= show_videocat($info->title, $info->comments, $info->id, $info->res_id, $info->visible);
            break;
        case 'exercise':
            $html .= show_exercise($info->title, $info->comments, $info->id, $info->res_id, $info->visible);
            break;
        case 'work':
            $html .= show_work($info->title, $info->comments, $info->id, $info->res_id, $info->visible);
            break;
        case 'topic':
        case 'forum':
            $html .= show_forum($info->type, $info->title, $info->comments, $info->id, $info->res_id, $info->visible);
            break;
        case 'wiki':
            $html .= show_wiki($info->title, $info->comments, $info->id, $info->res_id, $info->visible);
            break;
        case 'poll':
            $html .= show_poll($info->title, $info->comments, $info->id, $info->res_id, $info->visible);
            break;
        case 'link':
            $html .= show_link($info->title, $info->comments, $info->id, $info->res_id, $info->visible);
            break;
        case 'linkcategory':
            $html .= show_linkcat($info->title, $info->comments, $info->id, $info->res_id, $info->visible);
            break;
        case 'ebook':
            $html .= show_ebook($info->title, $info->comments, $info->id, $info->res_id, $info->visible);
            break;
        case 'section':
            $html .= show_ebook_section($info->title, $info->comments, $info->id, $info->res_id, $info->visible);
            break;
        case 'subsection':
            $html .= show_ebook_subsection($info->title, $info->comments, $info->id, $info->res_id, $info->visible);
            break;
        case 'chat':
            $html .= show_chat($info->title, $info->comments, $info->id, $info->res_id, $info->visible);
            break;
        case 'tc':
            $html .= show_tc($info->title, $info->comments, $info->id, $info->res_id, $info->visible);
            break;
        default:
            $html .= $langUnknownResType;
    }
    return $html;
}

/**
 * @brief display resource documents
 * @global type $is_editor
 * @global type $course_id
 * @global type $langWasDeleted
 * @global type $urlServer
 * @global type $id
 * @global type $course_code
 * @param type $title
 * @param type $comments
 * @param type $resource_id
 * @param type $file_id
 * @return string
 */
function show_doc($title, $comments, $resource_id, $file_id) {
    global $can_upload, $course_id, $langWasDeleted, $urlServer,
           $id, $course_code;

    $file = Database::get()->querySingle("SELECT * FROM document WHERE course_id = ?d AND id = ?d", $course_id, $file_id);

    if (!$file) {
        $download_hidden_link = '';
        if (!$can_upload) {
            return '';
        }
        $status = 'del';
        $image = 'fa-times';
        $link = "<span class='not_visible'>" . q($title) . " ($langWasDeleted)</span>";
    } else {
        $status = $file->visible;
        if (!$can_upload and (!resource_access($file->visible, $file->public))) {
            return '';
        }
        if ($file->format == '.dir') {
            $image = 'fa-folder-o';
            $download_hidden_link = '';
            $link = "<a href='{$urlServer}modules/document/index.php?course=$course_code&amp;openDir=$file->path&amp;unit=$id'>" .
                q($title) . "</a>";
        } else {
            $file->title = $title;
            $image = choose_image('.' . $file->format);
            $download_url = "{$urlServer}modules/document/index.php?course=$course_code&amp;download=$file->path";
            $download_hidden_link = ($can_upload || visible_module(MODULE_ID_DOCS))?
                "<input type='hidden' value='$download_url'>" : '';
            $file_obj = MediaResourceFactory::initFromDocument($file);
            $file_obj->setAccessURL(file_url($file->path, $file->filename));
            $file_obj->setPlayURL(file_playurl($file->path, $file->filename));
            $link = MultimediaHelper::chooseMediaAhref($file_obj);
        }
    }
    $class_vis = ($status == '0' or $status == 'del') ? ' class="not_visible"' : '';
    if (!empty($comments)) {
        $comment = '<br />' . $comments;
    } else {
        $comment = '';
    }

    return "
        <tr$class_vis data-id='$resource_id'>
          <td width='1'>" . icon($image, '') . "</td>
          <td class='text-left'>$download_hidden_link$link$comment</td>" .
            actions('doc', $resource_id, $status) .
            '</tr>';
}

/**
 * @brief display resource text
 * @global string $tool_content
 * @param type $comments
 * @param type $resource_id
 * @param type $visibility
 */
function show_text($comments, $resource_id, $visibility) {

    $class_vis = ($visibility == 0) ? ' class="not_visible"' : ' ';
    $comments = mathfilter($comments, 12, "../../courses/mathimg/");
    return "
        <tr$class_vis data-id='$resource_id'>
          <td colspan='2'>$comments</td>" .
            actions('text', $resource_id, $visibility) .
            "
        </tr>";
}

/**
 * @brief display course description resource
 * @global string $tool_content
 * @param type $title
 * @param type $comments
 * @param type $id
 * @param type $res_id
 * @param type $visibility
 */
function show_description($title, $comments, $id, $res_id, $visibility) {

    $comments = mathfilter($comments, 12, "../../courses/mathimg/");
    return "
        <tr>
          <td colspan='2'>
            <div class='title'>" . q($title) . "</div>
            <div class='content'>$comments</div>
          </td>" . actions('description', $id, $visibility, $res_id) . "
        </tr>";
}

/**
 * @brief display resource learning path
 * @global type $id
 * @global type $urlAppend
 * @global type $course_id
 * @global type $is_editor
 * @global type $langWasDeleted
 * @global type $course_code
 * @global type $langInactiveModule
 * @param type $title
 * @param type $comments
 * @param type $resource_id
 * @param type $lp_id
 * @return string
 */
function show_lp($title, $comments, $resource_id, $lp_id) {
    global $id, $urlAppend, $course_id, $is_editor,
    $langWasDeleted, $course_code, $langInactiveModule;

    $module_visible = visible_module(MODULE_ID_LP); // checks module visibility
    if (!$module_visible and ! $is_editor) {
        return '';
    }

    $class_vis = (!$module_visible) ?
            ' class="not_visible"' : ' ';

    $title = q($title);
    $lp = Database::get()->querySingle("SELECT * FROM lp_learnPath WHERE course_id = ?d AND learnPath_id = ?d", $course_id, $lp_id);
    if (!$lp) { // check if lp was deleted
        if (!$is_editor) {
            return '';
        } else {
            $status = 'del';
            $imagelink = icon('fa-times');
            $link = "<span class='not_visible'>$title ($langWasDeleted)</span>";
        }
    } else {
        $status = $lp->visible;
        if ($is_editor) {
            $module_id = Database::get()->querySingle("SELECT module_id FROM lp_rel_learnPath_module WHERE learnPath_id = ?d ORDER BY `rank` LIMIT 1", $lp_id)->module_id;
            $link = "<a href='${urlAppend}modules/learnPath/viewer.php?course=$course_code&amp;path_id=$lp_id&amp;module_id=$module_id&amp;unit=$id'>";
            if (!$module_visible) {
                $link .= " <i>($langInactiveModule)</i> ";
            }
        } else {
            if ($status == 0) {
                return '';
            }
            $module_id = Database::get()->querySingle("SELECT module_id FROM lp_rel_learnPath_module WHERE learnPath_id = ?d ORDER BY `rank` LIMIT 1", $lp_id)->module_id;
            $link = "<a href='${urlAppend}modules/learnPath/viewer.php?course=$course_code&amp;path_id=$lp_id&amp;module_id=$module_id&amp;unit=$id'>";
        }
        $imagelink = icon('fa-ellipsis-h');
    }

    if (!empty($comments)) {
        $comment_box = "<br>$comments";
    } else {
        $comment_box = '';
    }
    return "
        <tr$class_vis data-id='$resource_id'>
          <td width='1'>$imagelink</a></td>
          <td>$link$title</a>$comment_box</td>" .
            actions('lp', $resource_id, $status) . '
        </tr>';
}

/**
 * @brief display resource video
 * @global type $is_editor
 * @global type $course_id
 * @global string $tool_content
 * @param type $table
 * @param type $title
 * @param type $comments
 * @param type $resource_id
 * @param type $video_id
 * @param string $visibility
 * @return string
 */
function show_video($table, $title, $comments, $resource_id, $video_id, $visibility) {
    global $is_editor, $course_id, $tool_content, $urlServer, $course_code, $id;


    $row = Database::get()->querySingle("SELECT * FROM `$table` WHERE course_id = ?d AND id = ?d", $course_id, $video_id);
    if ($row) {
        $row->title = $title;
        $status = $row->public;
        if ($table == 'video') {
            $videoplayurl = "${urlServer}modules/units/view.php?course=$course_code&amp;res_type=video&amp;id=$video_id&amp;unit=$id";
            $vObj = MediaResourceFactory::initFromVideo($row);
            $vObj->setPlayURL($videoplayurl);
            $videolink = MultimediaHelper::chooseMediaAhref($vObj);
        } else {
            $videoplayurl = "${urlServer}modules/units/view.php?course=$course_code&amp;res_type=videolink&amp;id=$video_id&amp;unit=$id";
            $vObj = MediaResourceFactory::initFromVideoLink($row);
            $vObj->setPlayURL($videoplayurl);
            $videolink = MultimediaHelper::chooseMedialinkAhref($vObj);
        }
        $imagelink = "fa-film";
    } else { // resource was deleted
        if (!$is_editor) {
            return;
        }
        $videolink = $title;
        $imagelink = "fa-times";
        $visibility = 'del';
    }

    if (!empty($comments)) {
        $comment_box = "<p>$comments";
    } else {
        $comment_box = "";
    }
    $class_vis = ($visibility == 0 or $status == 'del') ? ' class="not_visible"' : ' ';
    $tool_content .= "
        <tr$class_vis data-id='$resource_id'>
          <td width='1'>".icon($imagelink)."</td>
          <td> $videolink $comment_box</td>" . actions('video', $resource_id, $visibility) . "
        </tr>";
}

/**
 * @brief display resource video category
 * @global type $is_editor
 * @global type $course_id
 * @global type $langInactiveModule
 * @param type $title
 * @param type $comments
 * @param type $resource_id
 * @param type $videolinkcat_id
 * @param type $visibility
 * @return string
 */
function show_videocat($title, $comments, $resource_id, $videolinkcat_id, $visibility)
{
    global $is_editor, $course_id, $langInactiveModule;

    $module_visible = visible_module(MODULE_ID_VIDEO); // checks module visibility

    if (!$module_visible and !$is_editor) {
        return '';
    }
    $linkcontent = $imagelink = $link = '';
    $class_vis = (!$module_visible)?
                 ' class="not_visible"': ' ';
    $vlcat = Database::get()->querySingle("SELECT * FROM video_category WHERE id = ?d AND course_id = ?d", $videolinkcat_id, $course_id);
    $content = "
        <tr$class_vis data-id='$resource_id'>
          <td width='1'>".icon('fa-folder-o')."</td>
          <td>" . q($title);

    if (!empty($comments)) {
        $content .= "<br>$comments";
    } elseif (!empty($vlcat->description)) {
        $content .= '<p>' . q($vlcat->description) . '</p>';
    }
    foreach (array('video', 'videolink') as $table) {
        $sql2 = Database::get()->queryArray("SELECT * FROM $table WHERE category = ?d AND course_id = ?d", $vlcat->id, $course_id);
        foreach ($sql2 as $row) {
            if (!$is_editor) {
                if (!resource_access(1, $row->public) or $row->visible == 0) {
                    continue;
                }
            }

            if ($table == 'video') {
                $vObj = MediaResourceFactory::initFromVideo($row);
                $videolink = MultimediaHelper::chooseMediaAhref($vObj);
            } else {
                $vObj = MediaResourceFactory::initFromVideoLink($row);
                $videolink = MultimediaHelper::chooseMedialinkAhref($vObj);
            }
            if (!$module_visible) {
                $videolink .= " <i>($langInactiveModule)</i>";
            }

            $class_vis = ($row->visible == 0 or !$module_visible)? ' class="not_visible"': ' ';
            $linkcontent .= "<div $class_vis>" . icon('fa-film') . "&nbsp;&nbsp;$videolink</div>";
        }
    }

    return $content . $linkcontent .'
           </td>'. actions('videolinkcategory', $resource_id, $visibility) .
        '</tr>';
}


/**
 * @brief display resource assignment (aka work)
 * @global type $id
 * @global type $urlServer
 * @global type $is_editor
 * @global type $langWasDeleted
 * @global type $course_id
 * @global type $course_code
 * @param type $title
 * @param type $comments
 * @param type $resource_id
 * @param type $work_id
 * @param type $visibility
 * @return string
 */
function show_work($title, $comments, $resource_id, $work_id, $visibility) {
    global $id, $urlServer, $is_editor,
    $langWasDeleted, $course_id, $course_code;

    $title = q($title);
    $work = Database::get()->querySingle("SELECT * FROM assignment WHERE course_id = ?d AND id = ?d", $course_id, $work_id);
    if (!$work) { // check if it was deleted
        if (!$is_editor) {
            return '';
        } else {
            $imagelink = icon('fa-times');
            $exlink = "<span class='not_visible'>$title ($langWasDeleted)</span>";
        }
    } else {
        $link = "<a href='${urlServer}modules/units/view.php?course=$course_code&amp;res_type=assignment&amp;id=$work_id&amp;unit=$id'>";
        $exlink = $link . "$title</a>";
        $imagelink = $link . "</a>".icon('fa-flask')."";
    }

    if (!empty($comments)) {
        $comment_box = "<br>$comments";
    } else {
        $comment_box = '';
    }
    return "
        <tr data-id='$resource_id'>
          <td width='1'>$imagelink</td>
          <td>$exlink $comment_box</td>" .
            actions('lp', $resource_id, $visibility) . '
        </tr>';
}

/**
 * @brief display resource exercise
 * @global type $id
 * @global type $urlServer
 * @global type $is_editor
 * @global type $langWasDeleted
 * @global type $course_id
 * @global type $course_code
 * @param type $title
 * @param type $comments
 * @param type $resource_id
 * @param type $exercise_id
 * @param type $visibility
 * @return string
 */
function show_exercise($title, $comments, $resource_id, $exercise_id, $visibility) {
    global $id, $urlServer, $is_editor, $langWasDeleted, $course_id, $course_code;

    $title = q($title);
    $exercise = Database::get()->querySingle("SELECT * FROM exercise WHERE course_id = ?d AND id = ?d", $course_id, $exercise_id);
    if (!$exercise) { // check if it was deleted
        if (!$is_editor) {
            return '';
        } else {
            $status = 'del';
            $imagelink = icon('fa-times');
            $exlink = "<span class='not_visible'>$title ($langWasDeleted)</span>";
        }
    } else {
        $status = $exercise->active;
        if (!$is_editor and ( !resource_access($exercise->active, $exercise->public))) {
            return '';
        }
        $link = "<a href='${urlServer}modules/units/view.php?course=$course_code&amp;res_type=exercise&amp;exerciseId=$exercise_id&amp;unit=$id'>";
        $exlink = $link . "$title</a>";
        $imagelink = $link . "</a>" . icon('fa-pencil-square-o'). "";
    }
    $class_vis = ($status == '0' or $status == 'del') ? ' class="not_visible"' : ' ';

    if (!empty($comments)) {
        $comment_box = "<br />$comments";
    } else {
        $comment_box = "";
    }

    return "
        <tr$class_vis data-id='$resource_id'>
          <td width='3'>$imagelink</td>
          <td>$exlink $comment_box</td>" . actions('lp', $resource_id, $visibility) . "
        </tr>";
}

/**
 * @brief display resource forum
 * @global type $id
 * @global type $urlServer
 * @global type $is_editor
 * @global type $course_id
 * @global type $course_code
 * @global type $langWasDeleted
 * @param type $type
 * @param type $title
 * @param type $comments
 * @param type $resource_id
 * @param type $ft_id
 * @param type $visibility
 * @return string
 */
function show_forum($type, $title, $comments, $resource_id, $ft_id, $visibility) {
    global $id, $urlServer, $is_editor, $course_code, $langWasDeleted;

    $class_vis = ($visibility == 0) ? ' class="not_visible"' : ' ';
    $title = q($title);
    if ($type == 'forum') {
        $link = "<a href='${urlServer}modules/units/view.php?course=$course_code&amp;res_type=forum&amp;forum=$ft_id&amp;unit=$id'>";
        $forumlink = $link . "$title</a>";
        $imagelink = icon('fa-comments');
    } else {
        $r = Database::get()->querySingle("SELECT forum_id FROM forum_topic WHERE id = ?d", $ft_id);
        if (!$r) { // check if it was deleted
            if (!$is_editor) {
                return '';
            } else {
                $imagelink = icon('fa-times');
                $forumlink = "<span class='not_visible'>$title ($langWasDeleted)</span>";
            }
        } else {
            $forum_id = $r->forum_id;
            $link = "<a href='${urlServer}modules/units/view.php?course=$course_code&amp;res_type=forum_topic&amp;topic=$ft_id&amp;forum=$forum_id&amp;unit=$id'>";
            $forumlink = $link . "$title</a>";
            $imagelink = icon('fa-comments'). "";
        }
    }

    if (!empty($comments)) {
        $comment_box = "<br />$comments";
    } else {
        $comment_box = '';
    }

    return "
        <tr$class_vis data-id='$resource_id'>
          <td width='1'>$imagelink</td>
          <td>$forumlink $comment_box</td>" .
            actions('forum', $resource_id, $visibility) . '
        </tr>';
}


/**
 * @brief display resource poll
 * @param type $type
 * @param type $title
 * @param type $resource_id
 * @param type $poll_id
 * @param type $visibility
 * @return string
 */
function show_poll($title, $comments, $resource_id, $poll_id, $visibility) {

    global $course_id, $course_code, $is_editor, $urlServer, $id, $langWasDeleted;

    $class_vis = ($visibility == 0 ) ? ' class="not_visible"' : ' ';
    $title = q($title);
    $poll = Database::get()->querySingle("SELECT * FROM poll WHERE course_id = ?d AND pid = ?d", $course_id, $poll_id);
    if (!$poll) { // check if it was deleted
        if (!$is_editor) {
            return '';
        } else {
            $imagelink = icon('fa-times');
            $polllink = "<span class='not_visible'>$title ($langWasDeleted)</span>";
        }
    } else {
        $link = "<a href='${urlServer}modules/units/view.php?course_code=$course_code&amp;res_type=questionnaire&amp;pid=$poll_id&amp;UseCase=1&amp;unit_id=$id'>";
        $polllink = $link . $title . '</a>';
        $imagelink = $link . "</a>" . icon('fa-question-circle') . "";
    }

    if (!empty($comments)) {
        $comment_box = "<br>$comments";
    } else {
        $comment_box = '';
    }
    return "
        <tr$class_vis data-id='$resource_id'>
          <td width='1'>$imagelink</td>
          <td>$polllink $comment_box</td>" .
            actions('poll', $resource_id, $visibility) . '
        </tr>';


}
/**
 * @brief display resource wiki
 * @global type $id
 * @global type $mysqlMainDb
 * @global type $urlServer
 * @global type $is_editor
 * @global type $langWasDeleted
 * @global type $langInactiveModule
 * @global type $course_id
 * @global type $course_code
 * @param type $title
 * @param type $comments
 * @param type $resource_id
 * @param type $wiki_id
 * @param type $visibility
 * @return string
 */
function show_wiki($title, $comments, $resource_id, $wiki_id, $visibility) {
    global $id, $urlServer, $is_editor,
    $langWasDeleted, $langInactiveModule, $course_id, $course_code;

    $module_visible = visible_module(MODULE_ID_WIKI); // checks module visibility

    if (!$module_visible and ! $is_editor) {
        return '';
    }

    $class_vis = ($visibility == 0 or ! $module_visible) ?
            ' class="not_visible"' : ' ';
    $title = q($title);
    $wiki = Database::get()->querySingle("SELECT * FROM wiki_properties WHERE course_id = ?d AND id = ?d", $course_id, $wiki_id);
    if (!$wiki) { // check if it was deleted
        if (!$is_editor) {
            return '';
        } else {
            $imagelink = icon('fa-times');
            $wikilink = "<span class='not_visible'>$title ($langWasDeleted)</span>";
        }
    } else {
        $link = "<a href='${urlServer}modules/wiki/page.php?course=$course_code&amp;wikiId=$wiki_id&amp;action=show&amp;unit=$id'>";
        $wikilink = $link . "$title</a>";
        if (!$module_visible) {
            $wikilink .= " <i>($langInactiveModule)</i>";
        }
        $imagelink = $link . "</a>" .icon('fa-wikipedia-w') . "";
    }

    if (!empty($comments)) {
        $comment_box = "<br />$comments";
    } else {
        $comment_box = '';
    }
    return "
        <tr$class_vis data-id='$resource_id'>
          <td width='1'>$imagelink</td>
          <td>$wikilink $comment_box</td>" .
            actions('wiki', $resource_id, $visibility) . '
        </tr>';
}

/**
 * @brief display resource link
 * @global type $is_editor
 * @global type $langWasDeleted
 * @global type $course_id
 * @param type $title
 * @param type $comments
 * @param type $resource_id
 * @param type $link_id
 * @param type $visibility
 * @return string
 */
function show_link($title, $comments, $resource_id, $link_id, $visibility) {

    global $is_editor, $langWasDeleted, $course_id;

    $class_vis = ($visibility == 0) ? ' class="not_visible"' : ' ';
    $l = Database::get()->querySingle("SELECT * FROM link WHERE course_id = ?d AND id = ?d", $course_id, $link_id);
    if (!$l) { // check if it was deleted
        if (!$is_editor) {
            return '';
        } else {
            $imagelink = icon('fa-times');
            $exlink = "<span class='not_visible'>" . q($title) . " ($langWasDeleted)</span>";
        }
    } else {
        if ($title == '') {
            $title = q($l->url);
        } else {
            $title = q($title);
        }
        $link = "<a href='" . q($l->url) . "' target='_blank'>";
        $exlink = $link . "$title</a>";
        $imagelink = icon('fa-link');
    }

    if (!empty($comments)) {
        $comment_box = '<br />' . standard_text_escape($comments);
    } else {
        $comment_box = '';
    }

    return "
        <tr$class_vis data-id='$resource_id'>
          <td width='1'>$imagelink</td>
          <td>$exlink $comment_box</td>" . actions('link', $resource_id, $visibility) . "
        </tr>";
}

/**
 * @brief display resource link category
 * @global type $is_editor
 * @global type $langWasDeleted
 * @global type $course_id
 * @param type $title
 * @param type $comments
 * @param type $resource_id
 * @param type $linkcat_id
 * @param type $visibility
 * @return string
 */
function show_linkcat($title, $comments, $resource_id, $linkcat_id, $visibility) {

    global $is_editor, $langWasDeleted, $course_id;

    $content = $linkcontent = $comment_box = $class_vis = $link = '';
    $class_vis = ($visibility == 0) ? ' class="not_visible"' : ' ';
    $sql = Database::get()->queryArray("SELECT * FROM link_category WHERE course_id = ?d AND id = ?d", $course_id, $linkcat_id);
    if (!$sql) { // check if it was deleted
        if (!$is_editor) {
            return '';
        } else {
            $content = "<tr class='not_visible' data-id='$resource_id'>
                        <td width='1'>" . icon('fa-folder-o') . "</td><td>" . q($title) . " ($langWasDeleted)";
        }
    } else {
        foreach ($sql as $lcat) {
            $content .= "
                        <tr$class_vis data-id='$resource_id'>
                          <td width='1'>".icon('fa-folder-o')."</td>
                          <td>" . q($lcat->name);
            if (!empty($lcat->description)) {
                $comment_box = "<br><small>$lcat->description</small>";
            }

            $sql2 = Database::get()->queryArray("SELECT * FROM link WHERE course_id = ?d AND category = $lcat->id", $course_id);
            foreach ($sql2 as $l) {
                $imagelink = icon('fa-link');
                $ltitle = q(($l->title == '') ? $l->url : $l->title);
                $linkcontent .= "<br>$imagelink&nbsp;&nbsp;<a href='" . q($l->url) ."' target='_blank'>$ltitle</a>";
            }
        }
    }
    return $content . $comment_box . $linkcontent . '
           </td>' . actions('linkcategory', $resource_id, $visibility) .
            '</tr>';
}

/**
 * @brief display resource ebook
 * @global type $id
 * @global type $urlServer
 * @global type $is_editor
 * @global type $langWasDeleted
 * @global type $course_code
 * @global type $langInactiveModule
 * @param type $title
 * @param type $comments
 * @param type $resource_id
 * @param type $ebook_id
 * @param type $visibility
 * @return string
 */
function show_ebook($title, $comments, $resource_id, $ebook_id, $visibility) {
    global $id, $urlServer, $is_editor,
    $langWasDeleted, $course_code, $langInactiveModule;

    $module_visible = visible_module(MODULE_ID_EBOOK); // checks module visibility

    if (!$module_visible and ! $is_editor) {
        return '';
    }

    $class_vis = ($visibility == 0 or ! $module_visible) ?
            ' class="not_visible"' : ' ';
    $title = q($title);
    $r = Database::get()->querySingle("SELECT * FROM ebook WHERE id = ?d", $ebook_id);
    if (!$r) { // check if it was deleted
        if (!$is_editor) {
            return '';
        } else {
            $imagelink = icon('fa-times');
            $exlink = "<span class='not_visible'>$title ($langWasDeleted)</span>";
        }
    } else {
        $link = "<a href='${urlServer}modules/ebook/show.php/$course_code/$ebook_id/unit=$id'>";
        $exlink = $link . "$title</a>";
        if (!$module_visible) {
            $exlink .= " <i>($langInactiveModule)</i>";
        }
        $imagelink = $link . "</a>" .icon('fa-book') . "";
    }

    if (!empty($comments)) {
        $comment_box = "<br />$comments";
    } else {
        $comment_box = "";
    }

    return "
        <tr$class_vis data-id='$resource_id'>
          <td width='3'>$imagelink</td>
          <td>$exlink $comment_box</td>" . actions('ebook', $resource_id, $visibility) . "
        </tr>";
}

/**
 * @brief display ebook section
 * @global type $course_id
 * @param type $title
 * @param type $comments
 * @param type $resource_id
 * @param type $section_id
 * @param type $visibility
 * @return type
 */
function show_ebook_section($title, $comments, $resource_id, $section_id, $visibility) {
    global $course_id;

    $data = Database::get()->querySingle("SELECT ebook.id AS ebook_id, ebook_subsection.id AS ssid
                FROM ebook, ebook_section, ebook_subsection
                WHERE ebook.course_id = ?d AND
                    ebook_section.ebook_id = ebook.id AND
                    ebook_section.id = ebook_subsection.section_id AND
                    ebook_section.id = ?d
                ORDER BY CONVERT(ebook_subsection.public_id, UNSIGNED), ebook_subsection.public_id
                LIMIT 1", $course_id, $section_id);
    if (!$data) { // check if it was deleted
        $deleted = true;
        $display_id = $ebook_id = false;
    } else {
        $deleted = false;
        $ebook_id = $data->ebook_id;
        $display_id = $section_id . ',' . $data->ssid;
    }
    return show_ebook_resource($title, $comments, $resource_id, $ebook_id, $display_id, $visibility, $deleted);
}

/**
 * @brief display ebook subsection
 * @global type $course_id
 * @param type $title
 * @param type $comments
 * @param type $resource_id
 * @param type $subsection_id
 * @param type $visibility
 * @return type
 */
function show_ebook_subsection($title, $comments, $resource_id, $subsection_id, $visibility) {
    global $course_id;

    $data = Database::get()->querySingle("SELECT ebook.id AS ebook_id, ebook_section.id AS sid
                FROM ebook, ebook_section, ebook_subsection
                WHERE ebook.course_id = ?d AND
                    ebook_section.ebook_id = ebook.id AND
                    ebook_section.id = ebook_subsection.section_id AND
                    ebook_subsection.id = ?d
                LIMIT 1", $course_id, $subsection_id);
    if (!$data) { // check if it was deleted
        $deleted = true;
        $display_id = $ebook_id = false;
    } else {
        $deleted = false;
        $ebook_id = $data->ebook_id;
        $display_id = $data->sid . ',' . $subsection_id;
    }
    return show_ebook_resource($title, $comments, $resource_id, $ebook_id, $display_id, $visibility, $deleted);
}

/**
 * @brief display resource ebook subsection
 * @global type $id
 * @global type $urlServer
 * @global type $is_editor
 * @global type $langWasDeleted
 * @global type $course_code
 * @global type $langInactiveModule
 * @param type $title
 * @param type $comments
 * @param type $resource_id
 * @param type $ebook_id
 * @param type $display_id
 * @param type $visibility
 * @param type $deleted
 * @return string
 */
function show_ebook_resource($title, $comments, $resource_id, $ebook_id, $display_id, $visibility, $deleted) {
    global $id, $urlServer, $is_editor,
    $langWasDeleted, $course_code, $langInactiveModule;

    $module_visible = visible_module(MODULE_ID_EBOOK); // checks module visibility

    if (!$module_visible and ! $is_editor) {
        return '';
    }

    $class_vis = ($visibility == 0 or ! $module_visible) ?
            ' class="not_visible"' : ' ';
    if ($deleted) {
        if (!$is_editor) {
            return '';
        } else {
            $status = 'del';
            $imagelink = icon('fa-times');
            $exlink = "<span class='not_visible'>$title ($langWasDeleted)</span>";
        }
    } else {
        $link = "<a href='${urlServer}modules/ebook/show.php/$course_code/$ebook_id/$display_id/unit=$id'>";
        $exlink = $link . q($title) . '</a>';
        if (!$module_visible) {
            $exlink .= " <i>($langInactiveModule)</i>";
        }
        $imagelink = $link . "</a>" .icon('fa-book'). "";
    }

    if (!empty($comments)) {
        $comment_box = "<br />$comments";
    } else {
        $comment_box = "";
    }

    return "
        <tr$class_vis data-id='$resource_id'>
          <td width='3'>$imagelink</td>
          <td>$exlink $comment_box</td>" . actions('section', $resource_id, $visibility) . "
        </tr>";
}

/**
 * @brief display chat resources
 * @param $title
 * @param $comments
 * @param $resource_id
 * @param $chat_id
 * @param $visibility
 * @return string
 */
function show_chat($title, $comments, $resource_id, $chat_id, $visibility) {
    global $urlServer, $is_editor, $langWasDeleted, $course_id, $course_code, $id;

    $comment_box = '';
    $title = q($title);
    $chat = Database::get()->querySingle("SELECT * FROM conference WHERE course_id = ?d AND conf_id = ?d", $course_id, $chat_id);
    if (!$chat) { // check if it was deleted
        if (!$is_editor) {
            return '';
        } else {
            $imagelink = icon('fa-times');
            $chatlink = "<span class='not_visible'>$title ($langWasDeleted)</span>";
        }
    } else {
        if (!$is_editor and $chat->status == 'inactive') {
            return '';
        }
        $link = "<a href='${urlServer}modules/units/view.php?course=$course_code&amp;res_type=chat&amp;conference_id=$chat_id&amp;unit=$id'>";
        $chatlink = $link . "$title</a>";
        $imagelink = $link . "</a>" .icon('fa-exchange') . "";
    }

    if (!empty($comments)) {
        $comment_box = "<br />$comments";
    }
    $class_vis = ($chat->status == 'inactive') ?
        ' class="not_visible"' : ' ';
    return "
        <tr$class_vis data-id='$resource_id'>
          <td width='1'>$imagelink</td>
          <td>$chatlink $comment_box</td>" .
        actions('chat', $resource_id, $visibility) . '
        </tr>';
}


/**
 * @brief display tc resources
 * @param $title
 * @param $comments
 * @param $resource_id
 * @param $tc_id
 * @param $visibility
 * @return string
 */
function show_tc($title, $comments, $resource_id, $tc_id, $visibility) {
    global $urlServer, $is_editor,
           $langWasDeleted, $langInactiveModule, $course_id, $course_code;

    $module_visible = visible_module(MODULE_ID_TC); // checks module visibility

    if (!$module_visible and !$is_editor) {
        return '';
    }

    $tc = Database::get()->querySingle("SELECT * FROM tc_session WHERE course_id = ?d AND id = ?d", $course_id, $tc_id);
    if (!$tc) { // check if it was deleted
        if (!$is_editor) {
            return '';
        } else {
            $imagelink = icon('fa-times');
            $tclink = "<span class='not_visible'>" .q($title) ." ($langWasDeleted)</span>";
        }
    } else {
        if (!$is_editor and !$tc->active) {
            return '';
        }
        $tclink = q($title);
        if (!$module_visible) {
            $tclink .= " <i>($langInactiveModule)</i>";
        }
        $imagelink = icon('fa-exchange');
    }

    if (!empty($comments)) {
        $comment_box = "<br>$comments";
    } else {
        $comment_box = '';
    }
    $class_vis = (!$tc->active or !$module_visible) ?
        ' class="not_visible"' : ' ';
    return "
        <tr$class_vis data-id='$resource_id'>
          <td width='1'>$imagelink</td>
          <td>$tclink $comment_box</td>" .
        actions('tc', $resource_id, $visibility) . '
        </tr>';
}

/**
 * @brief resource actions
 * @global type $is_editor
 * @global type $langEdit
 * @global type $langDelete
 * @global type $langVisibility
 * @global type $langAddToCourseHome
 * @global type $langDown
 * @global type $langUp
 * @global type $langConfirmDelete
 * @global type $course_code
 * @staticvar boolean $first
 * @param type $res_type
 * @param type $resource_id
 * @param type $status
 * @param type $res_id
 * @return string
 */
function actions($res_type, $resource_id, $status, $res_id = false) {
    global $is_editor, $langEditChange, $langDelete,
    $langAddToCourseHome, $langConfirmDelete, $course_code,
    $langViewHide, $langViewShow, $langReorder;

    static $first = true;

    if (!$is_editor) {
        return '';
    }

    if ($res_type == 'description') {
        $icon_vis = ($status == 1) ? 'fa-send' : 'fa-send-o';
        $edit_link = "edit.php?course=$course_code&amp;id=$_GET[id]&amp;numBloc=$res_id";
    } else {
        $showorhide = ($status == 1) ? $langViewHide : $langViewShow;
        $icon_vis = ($status == 1) ? 'fa-eye-slash' : 'fa-eye';
        $edit_link = "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;id=$_GET[id]&amp;edit=$resource_id";
    }

    $content = "<td style='padding: 10px 0; width: 85px;'>
                    <div class='reorder-btn pull-left ps-4 pe-3' style='padding:5px 10px 0; font-size: 16px; cursor: pointer; vertical-align: bottom;'>
                        <span class='fa fa-arrows' data-bs-toggle='tooltip' data-bs-placement='top' title='$langReorder'></span>
                    </div>
                <div class='pull-left mt-3 ps-3 pe-3'>";
    $content .= action_button(array(
                array('title' => $langEditChange,
                      'url' => $edit_link,
                      'icon' => 'fa-edit',
                      'show' => $status != 'del'),
                array('title' => $showorhide,
                      'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;id=$_GET[id]&amp;vis=$resource_id",
                      'icon' => $icon_vis,
                      'show' => $status != 'del' and in_array($res_type, array('text', 'video', 'forum', 'topic'))),
                array('title' => $langAddToCourseHome,
                      'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;id=$_GET[id]&amp;vis=$resource_id",
                      'icon' => $icon_vis,
                      'show' => $status != 'del' and in_array($res_type, array('description'))),
                array('title' => $langDelete,
                      'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;id=$_GET[id]&amp;del=$resource_id",
                      'icon' => 'fa-times',
                      'confirm' => $langConfirmDelete,
                      'class' => 'delete')
            ));

    $content .= "</div>";

    $first = false;
    return $content;
}

/**
 * @brief edit resource
 * @global type $id
 * @global type $urlServer
 * @global type $langTitle
 * @global type $langDescription
 * @global type $langContents
 * @global type $langModify
 * @global type $course_code
 * @param type $resource_id
 * @return string
 */
function edit_res($resource_id) {
    global $id, $urlServer, $langTitle, $langDescription, $langContents, $langModify, $course_code;

    $ru = Database::get()->querySingle("SELECT id, title, comments, type FROM unit_resources WHERE id = ?d", $resource_id);
    $restitle = " value='" . htmlspecialchars($ru->title, ENT_QUOTES) . "'";
    $rescomments = $ru->comments;
    $resource_id = $ru->id;
    $resource_type = $ru->type;
    $content = "<div class='col-xxl-12 col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12'><div class='form-wrapper shadow-sm p-3 rounded'>";
    $content .= "<form class='form-horizontal' role='form' method='post' action='${urlServer}modules/units/index.php?course=$course_code'>" .
            "<input type='hidden' name='id' value='$id'>" .
            "<input type='hidden' name='resource_id' value='$resource_id'>";
    if ($resource_type != 'text') {
        $content .= "<div class='form-group mt-3'>
                <label class='col-sm-6 control-label-notes'>$langTitle:</label>
                <div class='col-sm-12'><input class='form-control' type='text' name='restitle' size='50' maxlength='255' $restitle></div>
                </div>";
        $message = $langDescription;
    } else {
        $message = $langContents;
    }
    $content .= "
                <div class='form-group mt-3'>
                    <label class='col-sm-6 control-label-notes'>$message:</label>
                    <div class='col-sm-12'>" . rich_text_editor('rescomments', 4, 20, $rescomments) . "</div>
                </div>
                <div class='col-sm-offset-2 col-sm-10 mt-3'>
                    <input class='btn btn-primary' type='submit' name='edit_res_submit' value='$langModify'>
                </div>                
            </form></div>
        </div>";
    return $content;
}
