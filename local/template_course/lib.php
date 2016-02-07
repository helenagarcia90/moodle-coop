<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/completionlib.php');
require_once($CFG->libdir.'/filelib.php');
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->dirroot.'/course/format/lib.php');
require_once($CFG->dirroot.'/course/renderer.php');

/**
 * Returns the list of all editing actions that current user can perform on the module
 *
 * @param cm_info $mod The module to produce editing buttons for
 * @param int $indent The current indenting (default -1 means no move left-right actions)
 * @param int $sr The section to link back to (used for creating the links)
 * @return array array of action_link or pix_icon objects
 */

function course_get_cm_edit_actions_reduced(cm_info $mod, $indent = -1, $sr = null) {
    global $COURSE, $SITE;

    static $str;

    $coursecontext = context_course::instance($mod->course);
    $modcontext = context_module::instance($mod->id);

    $editcaps = array('moodle/course:manageactivities', 'moodle/course:activityvisibility', 'moodle/role:assign');
    $dupecaps = array('moodle/backup:backuptargetimport', 'moodle/restore:restoretargetimport');

    //No permission to edit anything.
    if (!has_any_capability($editcaps, $modcontext) and !has_all_capabilities($dupecaps, $coursecontext)) {
        return array();
    }

    $hasmanageactivities = has_capability('moodle/course:manageactivities', $modcontext);

    if (!isset($str)) {
        $str = get_strings(array('delete', 'move', 'moveright', 'moveleft',
            'editsettings', 'duplicate', 'hide', 'show'), 'moodle');
        $str->assign         = get_string('assignroles', 'role');
        $str->groupsnone     = get_string('clicktochangeinbrackets', 'moodle', get_string("groupsnone"));
        $str->groupsseparate = get_string('clicktochangeinbrackets', 'moodle', get_string("groupsseparate"));
        $str->groupsvisible  = get_string('clicktochangeinbrackets', 'moodle', get_string("groupsvisible"));
    }

    $baseurl = new moodle_url('/course/mod.php', array('sesskey' => sesskey()));

    if ($sr !== null) {
        $baseurl->param('sr', $sr);
    }
    $actions = array();

    // Duplicate (require both target import caps to be able to duplicate and backup2 support, see modduplicate.php)
    // Note that restoring on front page is never allowed.
    if ($mod->course != SITEID && has_all_capabilities($dupecaps, $coursecontext) &&
            plugin_supports('mod', $mod->modname, FEATURE_BACKUP_MOODLE2)) {
        $actions['duplicate'] = new action_menu_link_secondary(
            new moodle_url($baseurl, array('duplicate' => $mod->id)),
            new pix_icon('t/copy', $str->duplicate, 'moodle', array('class' => 'iconsmall', 'title' => '')),
            $str->duplicate,
            array('class' => 'editing_duplicate', 'data-action' => 'duplicate', 'data-sr' => $sr)
        );
    }

    // Delete.
    if ($hasmanageactivities) {
        $actions['delete'] = new action_menu_link_secondary(
            new moodle_url($baseurl, array('delete' => $mod->id)),
            new pix_icon('t/delete', $str->delete, 'moodle', array('class' => 'iconsmall', 'title' => '')),
            $str->delete,
            array('class' => 'editing_delete', 'data-action' => 'delete')
        );
    }

    return $actions;
}

    /**
     * Updates format options for a course
     *
     * If $data does not contain property with the option name, the option will not be updated
     *
     * @param stdClass|array $data return value from {@link moodleform::get_data()} or array with data
     * @param stdClass $oldcourse if this function is called from {@link update_course()}
     *     this object contains information about the course before update
     * @return bool whether there were any changes to the options values
     */
    function update_course_format_options($data) {
        return $this->update_format_options($data);
    }
    
    /**
     * Updates format options for a course or section
     *
     * If $data does not contain property with the option name, the option will not be updated
     *
     * @param stdClass|array $data return value from {@link moodleform::get_data()} or array with data
     * @param null|int null if these are options for course or section id (course_sections.id)
     *     if these are options for section
     * @return bool whether there were any changes to the options values
     */
    function update_format_options($data, $sectionid = null) {
        global $DB;
        if (!$sectionid) {
            $allformatoptions = $this->course_format_options();
            $sectionid = 0;
        } else {
            $allformatoptions = $this->section_format_options();
        }
        if (empty($allformatoptions)) {
            // nothing to update anyway
            return false;
        }
        $defaultoptions = array();
        $cached = array();
        foreach ($allformatoptions as $key => $option) {
            $defaultoptions[$key] = null;
            if (array_key_exists('default', $option)) {
                $defaultoptions[$key] = $option['default'];
            }
            $cached[$key] = ($sectionid === 0 || !empty($option['cache']));
        }
        $records = $DB->get_records('course_format_options',
                array('courseid' => $this->courseid,
                      'format' => $this->format,
                      'sectionid' => $sectionid
                    ), '', 'name,id,value');
        $changed = $needrebuild = false;
        $data = (array)$data;
        foreach ($defaultoptions as $key => $value) {
            if (isset($records[$key])) {
                if (array_key_exists($key, $data) && $records[$key]->value !== $data[$key]) {
                    $DB->set_field('course_format_options', 'value',
                            $data[$key], array('id' => $records[$key]->id));
                    $changed = true;
                    $needrebuild = $needrebuild || $cached[$key];
                }
            } else {
                if (array_key_exists($key, $data) && $data[$key] !== $value) {
                    $newvalue = $data[$key];
                    $changed = true;
                    $needrebuild = $needrebuild || $cached[$key];
                } else {
                    $newvalue = $value;
                    // we still insert entry in DB but there are no changes from user point of
                    // view and no need to call rebuild_course_cache()
                }
                $DB->insert_record('course_format_options', array(
                    'courseid' => $this->courseid,
                    'format' => $this->format,
                    'sectionid' => $sectionid,
                    'name' => $key,
                    'value' => $newvalue
                ));
            }
        }
        if ($needrebuild) {
            rebuild_course_cache($this->courseid, true);
        }
        if ($changed) {
            // reset internal caches
            if (!$sectionid) {
                $this->course = false;
            }
            unset($this->formatoptions[$sectionid]);
        }
        return $changed;
    }

    /**
    *
    **/
    function count_course_days($courseid){
        global $DB;
        $sections = $DB->get_records('course_sections', array('course'=>$courseid));
        $total_duration = 0;
        foreach($sections as $section){
            $duration=$section->availablefrom;
            $total_duration+=$duration;
        }
        return $total_duration;
    }

