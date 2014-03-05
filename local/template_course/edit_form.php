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
        
        // recollim elements per configurar el formulari
        
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

        // Form definition ---------------------------------
        
        $mform->addElement('header','general', get_string('general', 'form'));

        // variable de retorn
        $mform->addElement('hidden', 'returnto', null);
        $mform->setType('returnto', PARAM_ALPHANUM);
        $mform->setConstant('returnto', $returnto);
        
        // fullname
        $mform->addElement('text','fullname', get_string('fullnamecourse'),'maxlength="254" size="50"');
        $mform->addHelpButton('fullname', 'fullnamecourse');
        $mform->addRule('fullname', get_string('missingfullname'), 'required', null, 'client');
        $mform->setType('fullname', PARAM_TEXT);
        if (!empty($course->id) and !has_capability('moodle/course:changefullname', $coursecontext)) {
            $mform->hardFreeze('fullname');
            $mform->setConstant('fullname', $course->fullname);
        }
        
        // shortname
        $mform->addElement('text', 'shortname', get_string('shortnamecourse'), 'maxlength="100" size="20"');
        $mform->addHelpButton('shortname', 'shortnamecourse');
        $mform->addRule('shortname', get_string('missingshortname'), 'required', null, 'client');
        $mform->setType('shortname', PARAM_TEXT);
        if (!empty($course->id) and !has_capability('moodle/course:changeshortname', $coursecontext)) {
            $mform->hardFreeze('shortname');
            $mform->setConstant('shortname', $course->shortname);
        }
        
        // Categoria Template
        $mform->addElement('hidden', 'category', $category->id);
        $mform->setType('category', PARAM_INT);
        $mform->setDefault('category', $category->id);        
        
        //DATA = PERIODE
        $mform->addElement('duration', 'startdate', 'Course duration');
        $mform->setDefault('startdate', + 3600 * 24);       

        // Description.
        $mform->addElement('header', 'descriptionhdr', get_string('description'));
        $mform->setExpanded('descriptionhdr');
        $mform->addElement('editor','summary_editor', get_string('coursesummary'), null, $editoroptions);
        $mform->addHelpButton('summary_editor', 'coursesummary');
        $mform->setType('summary_editor', PARAM_RAW);
        
        // Elements amagats, assignem valors per defecte
        $mform->addElement('hidden','idnumber', "");
        $mform->addElement('hidden', 'visible', $courseconfig->hidden);
        $mform->addElement('hidden', 'overviewfiles_filemanager', 0);
        $mform->addElement('hidden', 'format', 'topics');
        $mform->addElement('hidden', 'numsections', 0);
        $mform->addElement('hidden', 'addcourseformatoptionshere', 0);
        $mform->addElement('hidden', 'lang', $courseconfig->lang);
        $mform->addElement('hidden', 'newsitems', 0);
        $mform->addElement('hidden', 'showgrades', 1);
        $mform->addElement('hidden', 'showreports', 1);
        $mform->addElement('hidden', 'legacyfiles', 0);
        $mform->addElement('hidden', 'maxbytes', 0);
        $mform->addElement('hidden', 'enablecompletion', 0);
        $mform->addElement('hidden', 'groupmode', 0);
        $mform->addElement('hidden', 'groupmodeforce', 0);        //default groupings selector
        $mform->addElement('hidden', 'defaultgroupingid', 0);

        if ($roles = get_all_roles()) {
            $roles = role_fix_names($roles, null, ROLENAME_ORIGINAL);
            foreach ($roles as $role) {
                $mform->addElement('hidden', 'role_'.$role->id, "");
                $mform->setType('role_'.$role->id, PARAM_TEXT);
            }
        }
        
        // FINAL. Assignem les dades
        
        $this->add_action_buttons();
        $mform->addElement('hidden', 'id', null);
        $mform->setType('id', PARAM_INT);
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
            var_dump($elements);
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

