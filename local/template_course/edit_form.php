<?php

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->libdir.'/completionlib.php');
require_once($CFG->libdir. '/coursecatlib.php');

/**
 * The form for handling editing a template course.
 */
class template_course_edit_form extends moodleform {
    protected $course;
    protected $context;

    /**
     * Form definition.
     */
    function definition() {
        global $CFG, $PAGE;

        $mform    = $this->_form;
        $PAGE->requires->yui_module('moodle-course-formatchooser', 'M.course.init_formatchooser',
                array(array('formid' => $mform->getAttribute('id'))));

        $course = $this->_customdata['course']; // this contains the data of this form
        $category = $this->_customdata['category']; //template course
        $editoroptions = $this->_customdata['editoroptions'];
        $returnto = $this->_customdata['returnto'];

        $systemcontext   = context_system::instance();
        $categorycontext = context_coursecat::instance($category->id);

        if (!empty($course->id)) {
            $coursecontext = context_course::instance($course->id);
            $context = $coursecontext;
        } else {
            $coursecontext = null;
            $context = $categorycontext;
        }

        $courseconfig = get_config('moodlecourse');

        $this->course  = $course;
        $this->context = $context;

        // Form definition with new course defaults.
        $mform->addElement('header','general', get_string('general', 'form'));

        $mform->addElement('hidden', 'returnto', null);
        $mform->setType('returnto', PARAM_ALPHANUM);
        $mform->setConstant('returnto', $returnto);

        $mform->addElement('text','fullname', get_string('fullnamecourse'),'maxlength="254" size="50"');
        $mform->addHelpButton('fullname', 'fullnamecourse');
        $mform->addRule('fullname', get_string('missingfullname'), 'required', null, 'client');
        $mform->setType('fullname', PARAM_TEXT);
        if (!empty($course->id) and !has_capability('moodle/course:changefullname', $coursecontext)) {
            $mform->hardFreeze('fullname');
            $mform->setConstant('fullname', $course->fullname);
        }

        $mform->addElement('text', 'shortname', get_string('shortnamecourse'), 'maxlength="100" size="20"');
        $mform->addHelpButton('shortname', 'shortnamecourse');
        $mform->addRule('shortname', get_string('missingshortname'), 'required', null, 'client');
        $mform->setType('shortname', PARAM_TEXT);
        if (!empty($course->id) and !has_capability('moodle/course:changeshortname', $coursecontext)) {
            $mform->hardFreeze('shortname');
            $mform->setConstant('shortname', $course->shortname);
        }
        
        //Categoria Template
        $mform->addElement('hidden', 'category', $category->id);
        $mform->setType('category', PARAM_INT);
        $mform->setDefault('category', $category->id);

        $mform->addElement('hidden', 'visible', $courseconfig->hidden);

        
        //DATA = PERIODEEEEEE
        $mform->addElement('duration', 'startdate', 'Course duration');
        //$mform->addHelpButton('startdate', 'startdate');
        $mform->setDefault('startdate', + 3600 * 24);

        $mform->addElement('text','idnumber', get_string('idnumbercourse'),'maxlength="100"  size="10"');
        //$mform->addHelpButton('idnumber', 'idnumbercourse');
        $mform->setType('idnumber', PARAM_RAW);
        if (!empty($course->id) and !has_capability('moodle/course:changeidnumber', $coursecontext)) {
            $mform->hardFreeze('idnumber');
            $mform->setConstants('idnumber', $course->idnumber);
        }

        // Description.
        $mform->addElement('header', 'descriptionhdr', get_string('description'));
        $mform->setExpanded('descriptionhdr');

        $mform->addElement('editor','summary_editor', get_string('coursesummary'), null, $editoroptions);
        $mform->addHelpButton('summary_editor', 'coursesummary');
        $mform->setType('summary_editor', PARAM_RAW);
        $summaryfields = 'summary_editor';

        if ($overviewfilesoptions = course_overviewfiles_options($course)) {
            $mform->addElement('filemanager', 'overviewfiles_filemanager', get_string('courseoverviewfiles'), null, $overviewfilesoptions);
            $mform->addHelpButton('overviewfiles_filemanager', 'courseoverviewfiles');
            $summaryfields .= ',overviewfiles_filemanager';
        }

        if (!empty($course->id) and !has_capability('moodle/course:changesummary', $coursecontext)) {
            // Remove the description header it does not contain anything any more.
            $mform->removeElement('descriptionhdr');
            $mform->hardFreeze($summaryfields);
        }

        // Course format.
        $mform->addElement('header', 'courseformathdr', get_string('type_format', 'plugin'));

        $courseformats = get_sorted_course_formats(true);
        $formcourseformats = array();
        foreach ($courseformats as $courseformat) {
            $formcourseformats[$courseformat] = get_string('pluginname', "format_$courseformat");
        }
        if (isset($course->format)) {
            $course->format = course_get_format($course)->get_format(); // replace with default if not found
            if (!in_array($course->format, $courseformats)) {
                // this format is disabled. Still display it in the dropdown
                $formcourseformats[$course->format] = get_string('withdisablednote', 'moodle',
                        get_string('pluginname', 'format_'.$course->format));
            }
        }

        $mform->addElement('hidden', 'format', 'topics');

        // Button to update format-specific options on format change (will be hidden by JavaScript).
        //$mform->registerNoSubmitButton('updatecourseformat');
        //$mform->addElement('submit', 'updatecourseformat', get_string('courseformatudpate'));

        // Just a placeholder for the course format options.
        $mform->addElement('hidden', 'addcourseformatoptionshere', 'numsections=>0');
        $mform->setType('addcourseformatoptionshere', PARAM_BOOL);

        // Appearance.
        //$mform->addElement('header', 'appearancehdr', get_string('appearance'));
        $mform->addElement('hidden', 'lang', $courseconfig->lang);
        $mform->addElement('hidden', 'newsitems', 0);
        $mform->addElement('hidden', 'showgrades', 1);
        $mform->addElement('hidden', 'showreports', 1);

        // Files and uploads.
        //$mform->addElement('header', 'filehdr', get_string('filesanduploads'));
        $mform->addElement('hidden', 'legacyfiles', 0);
        $mform->addElement('hidden', 'maxbytes', 0);

        //$mform->addElement('header', 'completionhdr', get_string('completion', 'completion'));
        $mform->addElement('hidden', 'enablecompletion', 0);

        enrol_course_edit_form($mform, $course, $context);

        //$mform->addElement('header','groups', get_string('groupsettingsheader', 'group'));
        $mform->addElement('hidden', 'groupmode', 0);
        $mform->addElement('hidden', 'groupmodeforce', 0);        //default groupings selector
        $mform->addElement('hidden', 'defaultgroupingid', 0);

        // ROLES
        //$mform->addElement('header','rolerenaming', get_string('rolerenaming'));
        //$mform->addHelpButton('rolerenaming', 'rolerenaming');

        if ($roles = get_all_roles()) {
            $roles = role_fix_names($roles, null, ROLENAME_ORIGINAL);
            foreach ($roles as $role) {
                $mform->addElement('hidden', 'role_'.$role->id, "");
                $mform->setType('role_'.$role->id, PARAM_TEXT);
            }
        }
        
        //FINAL
        $this->add_action_buttons();

        $mform->addElement('hidden', 'id', null);
        $mform->setType('id', PARAM_INT);

        // Finally set the current form data
        $this->set_data($course);
    }
        
    /**
     * Fill in the current page data for this course.
     */
    function definition_after_data() {
        global $DB;

        $mform = $this->_form;

        // add available groupings
        if ($courseid = $mform->getElementValue('id') and $mform->elementExists('defaultgroupingid')) {
            $options = array();
            if ($groupings = $DB->get_records('groupings', array('courseid'=>$courseid))) {
                foreach ($groupings as $grouping) {
                    $options[$grouping->id] = format_string($grouping->name);
                }
            }
            core_collator::asort($options);
            $gr_el =& $mform->getElement('defaultgroupingid');
            $gr_el->load($options);
        }

        // add course format options
        $formatvalue = $mform->getElementValue('format');
        if (is_array($formatvalue) && !empty($formatvalue)) {
            $courseformat = course_get_format((object)array('format' => $formatvalue[0]));

            $elements = $courseformat->create_edit_form_elements($mform);
            for ($i = 0; $i < count($elements); $i++) {
                $mform->insertElementBefore($mform->removeElement($elements[$i]->getName(), false),
                        'addcourseformatoptionshere');
            }
        }
    }

    /**
     * Validation.
     *
     * @param array $data
     * @param array $files
     * @return array the errors that were found
     */
    function validation($data, $files) {
        global $DB;

        $errors = parent::validation($data, $files);

        // Add field validation check for duplicate shortname.
        if ($course = $DB->get_record('course', array('shortname' => $data['shortname']), '*', IGNORE_MULTIPLE)) {
            if (empty($data['id']) || $course->id != $data['id']) {
                $errors['shortname'] = get_string('shortnametaken', '', $course->fullname);
            }
        }

        // Add field validation check for duplicate idnumber.
        if (!empty($data['idnumber']) && (empty($data['id']) || $this->course->idnumber != $data['idnumber'])) {
            if ($course = $DB->get_record('course', array('idnumber' => $data['idnumber']), '*', IGNORE_MULTIPLE)) {
                if (empty($data['id']) || $course->id != $data['id']) {
                    $errors['idnumber'] = get_string('courseidnumbertaken', 'error', $course->fullname);
                }
            }
        }

        $errors = array_merge($errors, enrol_course_edit_validation($data, $this->context));

        $courseformat = course_get_format((object)array('format' => $data['format']));
        $formaterrors = $courseformat->edit_form_validation($data, $files, $errors);
        if (!empty($formaterrors) && is_array($formaterrors)) {
            $errors = array_merge($errors, $formaterrors);
        }
        print '<br/>ERRORS: <br/>';
        var_dump($errors);
        
        return $errors;
    }
}

