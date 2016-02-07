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
        
        // Categoria Template
        $mform->addElement('hidden', 'category', $category->id);
        $mform->setType('category', PARAM_INT);
        $mform->setDefault('category', $category->id);        
        
        //Sections
        $mform->addElement('hidden', 'theme', '1');    

        // Description.
        $mform->addElement('header', 'descriptionhdr', get_string('description'));
        $mform->setExpanded('descriptionhdr');
        $mform->addElement('editor','summary_editor', get_string('coursesummary'), null, $editoroptions);
        $mform->addHelpButton('summary_editor', 'coursesummary');
        $mform->setType('summary_editor', PARAM_RAW);
        
        // Hidden elements, we assign default values
        //$mform->addElement('hidden','idnumber', $course->id); //save template id
        //$mform->addElement('hidden', 'lang', 'fr');
        $mform->addElement('hidden', 'visible', $courseconfig->hidden);
        $mform->addElement('hidden', 'overviewfiles_filemanager', 0);
        $mform->addElement('hidden', 'format', 'topics');
        $mform->addElement('hidden', 'numsections', 0);
        $mform->addElement('hidden', 'addcourseformatoptionshere', 0);
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
        
        // FINAL. Assign data
        
        $this->add_action_buttons();
        $mform->addElement('hidden', 'id', $course->id);
        $mform->setType('id', PARAM_INT);
        $this->set_data($course);
    }
        
    /**
     * Fill in the current page data for this course.
     */
    function definition_after_data() {               
        $mform = $this->_form;
        
        // add course format options
        $formatvalue = $mform->getElementValue('format');
        if (is_array($formatvalue) && !empty($formatvalue)) {
            $courseformat = course_get_format((object)array('format' => $formatvalue[0]));

            $elements = $courseformat->create_edit_form_elements($mform);
            //var_dump($elements);
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

        //$errors = parent::validation($data, $files);

        // Add field validation check for duplicate shortname.
        if ($course = $DB->get_record('course', array('shortname' => $data['shortname']), '*', IGNORE_MULTIPLE)) {
            if (empty($data['id']) || $course->id != $data['id']) {
                $errors['shortname'] = get_string('shortnametaken', '', $course->fullname);
            }
        }

        $errors = array_merge($errors, enrol_course_edit_validation($data, $this->context));

        $courseformat = course_get_format((object)array('format' => $data['format']));
        $formaterrors = $courseformat->edit_form_validation($data, $files, $errors);
        if (!empty($formaterrors) && is_array($formaterrors)) {
            $errors = array_merge($errors, $formaterrors);
        }       
        return $errors;
    }
}

class instance_course_edit_form extends moodleform {
    
    protected $course;
    protected $context;
    
    /**
     * Form definition.
     */
    function definition() {
        global $CFG, $PAGE;

        $mform = $this->_form;
        $PAGE->requires->yui_module('moodle-course-formatchooser', 'M.course.init_formatchooser',
                array(array('formid' => $mform->getAttribute('id'))));

        // recollim elements per configurar el formulari
        $course        = $this->_customdata['course']; // this contains the data of this form
        $categorycontext = context_coursecat::instance(-1); //template

        if (!empty($course->id)) {
            $coursecontext = context_course::instance($course->id);
            $context = $coursecontext;
        } else {
            $coursecontext = null;
            $context = $categorycontext;
        }
        $this->course  = $course;
        $this->context = $context;
        
        // Form definition ---------------------------------
        
        $mform->addElement('header','instance', get_string('general', 'form'));

        $mform->addElement('text','fullname', get_string('fullnamecourse'),'maxlength="254" size="50"');
        $mform->addHelpButton('fullname', 'fullnamecourse');
        $mform->addRule('fullname', get_string('missingfullname'), 'required', null, 'client');
        $mform->setType('fullname', PARAM_TEXT);
        if (!empty($course->id) and !has_capability('moodle/course:changefullname', $coursecontext)) {
            $mform->hardFreeze('fullname');
            $mform->setConstant('fullname', $course->fullname);
        }
        $mform->setDefault('fullname', $course->fullname . date('YY'));

        //short name
        $mform->addElement('hidden', 'shortname', get_string('shortnamecourse'));

        //category
        //$mform->addElement('hidden', 'category', 1);
        // Verify permissions to change course category or keep current.
        if (empty($course->id)) {
            if (has_capability('moodle/course:create', $categorycontext)) {
                $displaylist = coursecat::make_categories_list('moodle/course:create');
                $mform->addElement('select', 'category', get_string('coursecategory'), $displaylist);
                $mform->addHelpButton('category', 'coursecategory');
                $mform->setDefault('category', $category->id);
            } else {
                $mform->addElement('hidden', 'category', null);
                $mform->setType('category', PARAM_INT);
                $mform->setConstant('category', $category->id);
            }
        } else {
            if (has_capability('moodle/course:changecategory', $coursecontext)) {
                $displaylist = coursecat::make_categories_list('moodle/course:create');
                if (!isset($displaylist[$course->category])) {
                    //always keep current
                    $displaylist[$course->category] = coursecat::get($course->category, MUST_EXIST, true)->get_formatted_name();
                }
                $mform->addElement('select', 'category', get_string('coursecategory'), $displaylist);
                $mform->addHelpButton('category', 'coursecategory');
            } else {
                //keep current
                $mform->addElement('hidden', 'category', null);
                $mform->setType('category', PARAM_INT);
                $mform->setConstant('category', $course->category);
            }
        }
        
        //start date
        $mform->addElement('date_selector', 'startdate', get_string('startdate'));
        $mform->setDefault('startdate', time());

        $days = count_course_days($course->id);

        //duration hint
        $mform->addHelpButton('duration', 'Rappelez-vous la dur&eacute;e des themes est de: '. $days .' jours');
        
        //end date proposada
        $mform->addElement('date_selector', 'enddate', 'Date de finalisation');
        $mform->setDefault('enddate', time()+$days*24*3600);

        //make the course visible
        $mform->addElement('hidden', 'visible', 1);
        
        $this->add_action_buttons();
        $mform->addElement('hidden', 'format', 'topics');

        //we need to put an id, so we keep the old one, for the moment
        $mform->addElement('hidden', 'id', $course->id);
        $mform->setType('id', PARAM_INT);     
        
        $this->set_data($course);
    }
    
    function definition_after_data() {                
        $mform = $this->_form;
        $mform->addElement('hidden', 'numsections', $mform->getElementValue('lang'));
    }
    
    function validation($data, $files) {

        if ( $data['startdate'] >= $data['enddate']) {
            $errors['enddate'] = get_string('La date de finalisation dois etre plus tard que la de d&eacute;but.');
        }
          
        $errors = array_merge($errors, enrol_course_edit_validation($data, $this->context));

        $courseformat = course_get_format((object)array('format' => $data['format']));
        $formaterrors = $courseformat->edit_form_validation($data, $files, $errors);
        if (!empty($formaterrors) && is_array($formaterrors)) {
            $errors = array_merge($errors, $formaterrors);
        }

        return $errors;
        
    }
    
}

