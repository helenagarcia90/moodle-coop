<?php

	require_once ('../../config.php');
        require_once('lib.php');
        
        defined('MOODLE_INTERNAL') || die;
	global $CFG, $DB;
	require_once ($CFG->dirroot . '/lib/dmllib.php');
	require_once ($CFG->dirroot . '/lib/weblib.php');
	require_once ($CFG->dirroot . '/lib/moodlelib.php');
        require_once ($CFG->dirroot . '/lib/outputcomponents.php');
        require_once('edit_form.php');
	global  $OUTPUT, $PAGE, $COURSE, $SITE;
        
        $id = optional_param('id', 0, PARAM_INT); // Course id.
        $returnto = optional_param('returnto', 0, PARAM_ALPHANUM); // Generic navigation return page switch.
        $pageparams = array('id'=>$id);
	
        //LOGIN
        require_login();
        
        require_once($CFG->dirroot.'/calendar/lib.php');    /// This is after login because it needs $USER
        
        //CATEGORIA
        $categoryid = -1; // Template
        $PAGE->set_category_by_id($categoryid);
        $PAGE->set_url(new moodle_url('/local/template_course/index.php'));
        $PAGE->set_pagetype('course-index-category');
        // And the object has been loaded for us no need for another DB call
        $category = $PAGE->category;
        
        //RENDERER
        $PAGE->set_pagelayout('coursecategory');
        
        //IMPRIMIM EL CONTINGUT        
        $site = get_site();
        $PAGE->set_title("$site->fullname: Liste de Sujets");
        $PAGE->set_heading("Sujets");
        
                
        echo $OUTPUT -> header();
      
       //CONTINGUT--------------------------------------------
        if(!$id){
            echo "<p><b> Nouveau sújet: </b></p>";

            $courses = get_courses($categoryid);

            foreach($courses as $course){
                echo html_writer::start_div();
                echo html_writer::span($course->fullname);
                echo $OUTPUT->single_button(new moodle_url('/local/template_course/instance.php', 
                    array('id' => $course->id, /*'edit' => 'on',*/ 'sesskey' => $USER->sesskey)), 'Creer Course', get);
            
                echo html_writer::end_div();
            }
        }
        else { //hi ha id, mostrem el formulari d'instanciar curs
                        
            //agafem el curs
            $course = get_course($id);
            $courseformat = course_get_format($course)->get_course();
            $coursecontext = context_course::instance($courseformat->id);
            $editoroptions['context'] = $coursecontext;
            
            $category = $DB->get_record('course_categories', array('id'=>$course->category), '*', MUST_EXIST);            
            $courseeditor = file_prepare_standard_editor($courseformat, 'summary', $editoroptions, $coursecontext, 'course', 'summary', 0);
            $editform = new instance_course_edit_form(null, array('course'=>$courseeditor, 'category'=>$category, 'editoroptions'=>$editoroptions, 'returnto'=>$returnto) );
         
            if ($editform->is_cancelled()) {
                print 'cancelled';
                redirect(new moodle_url('/local/template_course/instance.php'));
                
            } else if ($data = $editform->get_data()) { //retorna NULL si no esta cancelat, si esta submit i si esta ben validat
                //afegim les dades del curs plantilla
                $data->id='';
                $data->shortname=$data->fullname;
                $data->category=1;
                $data->idnumber='';
                //barregem
                $data = array_merge((array)$course, (array)$data);
                
                
                $course = create_course((object)$data, array());
                
                //copy sections
                $sections = $DB->get_records('course_sections', array('course'=>$id));
                foreach ($sections as $section){
                    if($section->section > 0){
                        $section->course = $course->id;
                        unset($section->id);
                        $DB->insert_record('course_sections', $section);
                    }
                }

                //copy events
                $startdate = time();
                $events = $DB->get_records('event', array('course'=>$id));
                foreach ($events as $event){
                    $event->courseid = $course->id;
                    $event->timestart = $startdate;
                    unset($event->id);
                    //we calculate next startdate
                    $startdate = $event->timestart + $event->timeduration;
                    $DB->insert_record('event', $event);
                }
                
                //afegir usuaris
                //redirect(new moodle_url('/enrol/users.php', array('id' => $course->id, 'sesskey' => $USER->sesskey)));
                redirect(new moodle_url('/course/view.php', array('id' => $course->id, 'sesskey' => $USER->sesskey)));
            }
            $editform->display();
        }
        echo $OUTPUT -> footer();
 ?>


