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
 * @copyright  2014 Helena Garcia
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
    // Editing course
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
    
    //need permissions to be here
    require_capability('moodle/course:create', $coursecontext);

} else if ($categoryid == -1) { //REVISAR PERMISOS!!!!!
    // Creating new template course.
    $course = null;
    require_login();
    $category = $DB->get_record('course_categories', array('id'=>$categoryid), '*', MUST_EXIST);
    $catcontext = context_coursecat::instance($category->id);
    //need permissions to create
    require_capability('moodle/course:create', $catcontext);
    $PAGE->set_context($catcontext);

} else {
    require_login();
    print_error('needcoursecategroyid');
}

print $course->id;
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
    $editoroptions['context'] = $catcontext;
    $editoroptions['subdirs'] = 0;
    $course = file_prepare_standard_editor($course, 'summary', $editoroptions, null, 'course', 'summary', null);
    if ($overviewfilesoptions) {
        file_prepare_standard_filemanager($course, 'overviewfiles', $overviewfilesoptions, null, 'course', 'overviewfiles', 0);
    }
}

// First create the form.
$editform = new template_course_edit_form(NULL, array('course'=>$course, 'category'=>$category, 'editoroptions'=>$editoroptions, 'returnto'=>$returnto));

if ($editform->is_cancelled()) {
        print 'canceled';
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
                    $url = new moodle_url($CFG->wwwroot.'/local/template_course/view.php', array('id'=>$course->id, 'sesskey' => $USER->sesskey));
                } else {
                    $url = new moodle_url($CFG->wwwroot.'/local/template_course/');
                }
                break;
        }
        redirect($url);

} else if ($data = $editform->get_data()) { //retorna NULL si no esta cancelat, si esta submit i si esta ben validat
    // Process data if submitted.
    if (empty($id))
        $data->numsections = $data->theme;
    $data->shortname = $data->fullname;

    if (empty($course->id)) {
        $course = create_course($data, $editoroptions);

        //Add current user as the manager and the teacher of the course
        $enrol = $DB->get_record('enrol', array('courseid'=>$course->id, 'status'=>'manual'), '*', MUST_EXIST);
        $teacher = $DB->get_record('role', array('shortname'=>'editingteacher'), '*', MUST_EXIST);
        
        $user_enrol = new stdClass;
        $user_enrol->enrolid=$enrol->id;
        $user_enrol->userid=$USER->id;
        $user_enrol->modifierid=$USER->id;
        $user_enrol->timeend=0;
        $user_enrol->timecreated=time();
        $user_enrol->timemodified=time();
        $DB->insert_record('user_enrolments', $user_enrol, true, false);
        
        $rol = new stdClass;
        $coursecontext = context_course::instance($course->id);
        $rol->roleid=$teacher->id;
        $rol->contextid=$coursecontext->id;
        $rol->userid=$USER->id;
        $rol->timemodified=time();
        $rol->modifierid=$USER->id;
        $DB->insert_record('role_assignments', $rol, true, false);

    } else {
        // Save any changes to the files used in the editor.
        update_course($data, $editoroptions);
    }

    // Redirect user to newly created/updated course.
    redirect(new moodle_url('/local/template_course/view.php', 
            array('id' => $course->id, 'edit' => 'on', 'sesskey' => $USER->sesskey, 'edited' => true) ) );
}

// Print the form.

$site = get_site();

$streditcoursesettings = "Configuration du nouveau sujet";//get_string("edittemplatecoursesettings");
$straddnewcourse = "Ajouter nouveau sujet";
$stradministration = get_string("administration");
$strcategories = get_string("categories");

if (!empty($course->id)) {
    //$PAGE->navbar->add($streditcoursesettings);
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
