<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * EDIT TEMPLATE COURSE
 *
 * @package    core_course
 * @copyright  1999 onwards Martin Dougiamas (http://dougiamas.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('lib.php');
require_once('edit_form.php');

global $SITE;

$id = optional_param('id', 0, PARAM_INT); // Course id.
$categoryid = -1; // Course category = template category
$returnto = optional_param('returnto', 0, PARAM_ALPHANUM); // Generic navigation return page switch.

$PAGE->set_pagelayout('admin');
$pageparams = array('id'=>$id);

//if id is not set = new template
if (empty($id)) {
    $pageparams = array('category'=>$categoryid);
}
$PAGE->set_url('/local/template_course/edit.php', $pageparams);

// Basic access control checks.
if ($id) {
    // Editing course.
    print "curs: " + $id;
    if ($id == SITEID){
        // Don't allow editing of  'site course' using this from.
        print_error('cannoteditsiteform');
    }
    // Login to the course and retrieve also all fields defined by course format.
    $course = get_course($id);
    require_login($course);
    $course = course_get_format($course)->get_course();

    $category = $DB->get_record('course_categories', array('id'=>$course->category), '*', MUST_EXIST);
    $coursecontext = context_course::instance($course->id);
    require_capability('moodle/course:update', $coursecontext);

} else if ($categoryid == -1) { //REVISAR PERMISOS!!!!!
    // Creating new template course.
    print "categoria 1!!!!";
    $course = null;
    require_login();
    $category = $DB->get_record('course_categories', array('id'=>$categoryid), '*', MUST_EXIST);
    //var_dump($category);
    $catcontext = context_coursecat::instance($category->id);
    require_capability('moodle/course:create', $catcontext);
    $PAGE->set_context($catcontext);

} else {
    require_login();
    print_error('needcoursecategroyid');
}


// Prepare course and the editor.
$editoroptions = array('maxfiles' => EDITOR_UNLIMITED_FILES, 'maxbytes'=>$CFG->maxbytes, 'trusttext'=>false, 'noclean'=>true);

if (!empty($course)) {
    $overviewfilesoptions = course_overviewfiles_options($course);
    // Add context for editor.
    $editoroptions['context'] = $coursecontext;
    $editoroptions['subdirs'] = file_area_contains_subdirs($coursecontext, 'course', 'summary', 0);
    $course = file_prepare_standard_editor($course, 'summary', $editoroptions, $coursecontext, 'course', 'summary', 0);
    if ($overviewfilesoptions) {
        file_prepare_standard_filemanager($course, 'overviewfiles', $overviewfilesoptions, $coursecontext, 'course', 'overviewfiles', 0);
    }

    // Inject current aliases.
    $aliases = $DB->get_records('role_names', array('contextid'=>$coursecontext->id));
    foreach($aliases as $alias) {
        $course->{'role_'.$alias->roleid} = $alias->name;
    }

} else {
    // Editor should respect category context if course context is not set.
    print 'curs buit';
    $editoroptions['context'] = $catcontext;
    $editoroptions['subdirs'] = 0;
    $course = file_prepare_standard_editor($course, 'summary', $editoroptions, null, 'course', 'summary', null);
    print 'aaa';
    var_dump($course);
    if ($overviewfilesoptions) {
        file_prepare_standard_filemanager($course, 'overviewfiles', $overviewfilesoptions, null, 'course', 'overviewfiles', 0);
    }
}

// First create the form.
$editform = new template_course_edit_form(NULL, array('course'=>$course, 'category'=>$category, 'editoroptions'=>$editoroptions, 'returnto'=>$returnto));
print 
$category->id;
print 'bbb';

if ($editform->is_cancelled()) {
        switch ($returnto) {
            case 'category':
                $url = new moodle_url($CFG->wwwroot.'/course/index.php', array('categoryid' => $categoryid));
                break;
            case 'catmanage':
                $url = new moodle_url($CFG->wwwroot.'/course/management.php', array('categoryid' => $categoryid));
                break;
            case 'topcatmanage':
                $url = new moodle_url($CFG->wwwroot.'/course/management.php');
                break;
            case 'topcat':
                $url = new moodle_url($CFG->wwwroot.'/course/');
                break;
            default:
                if (!empty($course->id)) {
                    $url = new moodle_url($CFG->wwwroot.'/course/view.php', array('id'=>$course->id));
                } else {
                    $url = new moodle_url($CFG->wwwroot.'/course/');
                }
                break;
        }
        redirect($url);

} else if ($data = $editform->get_data()) { //retorna NULL si no esta cancelat, si esta submit i si esta ben validat
    // Process data if submitted.
    if (empty($course->id)) {
        // In creating the course.
        print '<br/>ARRIBO!!!!!!!!!!!!!';
        var_dump($data);
        $course = create_course($data, $editoroptions);
        
        
    /* AL LORO: NO VOLEM USUARIS ADJUNTS  
        // Get the context of the newly created course. 
        $context = context_course::instance($course->id, MUST_EXIST);

        if (!empty($CFG->creatornewroleid) and !is_viewing($context, NULL, 'moodle/role:assign') and !is_enrolled($context, NULL, 'moodle/role:assign')) {
            // Deal with course creators - enrol them internally with default role.
            enrol_try_internal_enrol($course->id, $USER->id, $CFG->creatornewroleid);
        }
        if (!is_enrolled($context)) {
            // Redirect to manual enrolment page if possible.
            $instances = enrol_get_instances($course->id, true);
            foreach($instances as $instance) {
                if ($plugin = enrol_get_plugin($instance->enrol)) {
                    if ($plugin->get_manual_enrol_link($instance)) {
                        // We know that the ajax enrol UI will have an option to enrol.
                        redirect(new moodle_url('/enrol/users.php', array('id'=>$course->id)));
                    }
                }
            }
        }
    */
        
    } else {
        // Save any changes to the files used in the editor.
        print 'update';
        update_course($data, $editoroptions);
    }

    // Redirect user to newly created/updated course.
    redirect(new moodle_url('/local/template_course/view.php', array('id' => $course->id)));
}

// Print the form.

print 'imprimeixo el formulari';

$site = get_site();

$streditcoursesettings = get_string("edittemplatecoursesettings");
$straddnewcourse = get_string("addnewtemplatecourse");
$stradministration = get_string("administration");
$strcategories = get_string("categories");

if (!empty($course->id)) {
    $PAGE->navbar->add($streditcoursesettings);
    $title = $streditcoursesettings;
    $fullname = $course->fullname;
} else {
    $PAGE->navbar->add($stradministration, new moodle_url('/admin/index.php'));
    //$PAGE->navbar->add($strcategories, new moodle_url('/course/index.php'));
    $PAGE->navbar->add($straddnewcourse);
    $title = "$site->shortname: $straddnewcourse";
    $fullname = $site->fullname;
}

$PAGE->set_title($title);
$PAGE->set_heading($fullname);

echo $OUTPUT->header();

echo $OUTPUT->heading($streditcoursesettings);

$editform->display();


echo $OUTPUT->footer();
