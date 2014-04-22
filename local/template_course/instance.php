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
	global  $OUTPUT, $PAGE, $COURSE;
        
        $id = optional_param('id', 0, PARAM_INT); // Course id.
	
        //LOGIN
        require_login();
        
        //require_once($CFG->dirroot.'/calendar/lib.php');    /// This is after login because it needs $USER
        
        //CATEGORIA
        $categoryid = -1; // Template
        $PAGE->set_category_by_id($categoryid);
        $PAGE->set_url(new moodle_url('/local/template_course/index.php'));
        $PAGE->set_pagetype('course-index-category');
        // And the object has been loaded for us no need for another DB call
        $category = $PAGE->category;
        
        //RENDERER
        $PAGE->set_pagelayout('coursecategory');
        //$courserenderer = $PAGE->get_renderer('core', 'course');
        //$content = $courserenderer->course_category($categoryid);
        
        //IMPRIMIM EL CONTINGUT        
        $site = get_site();
        $PAGE->set_title("$site->fullname: Cursos Plantilla");
        $PAGE->set_heading("Template Courses");
        
                
        echo $OUTPUT -> header();
      
       //CONTINGUT--------------------------------------------
        if(!$id){
            echo "<p> Template Courses: </p>";

            $courses = get_courses($categoryid);

            foreach($courses as $course){
                echo html_writer::label($course->fullname);
                echo $OUTPUT->single_button(new moodle_url('/local/template_course/instance.php', 
                    array('id' => $course->id, /*'edit' => 'on',*/ 'sesskey' => $USER->sesskey)), 'clone', get);
                //echo '<br/>';

            }
        }
        else {
            echo 'course' . $id;
            
            //agafem el curs
            $course = get_course($id);
            $courseformat = course_get_format($course)->get_course();
            $coursecontext = context_course::instance($courseformat->id);
            $editoroptions['context'] = $coursecontext;
            
            $category = $DB->get_record('course_categories', array('id'=>$course->category), '*', MUST_EXIST);
            
            $courseeditor = file_prepare_standard_editor($courseformat, 'summary', $editoroptions, $coursecontext, 'course', 'summary', 0);
            
            $editform = new instance_course_edit_form(array('course'=>$courseeditor, 'category'=>$category));
            
            //var_dump($course);
            
            if ($editform->is_cancelled()) {
                echo "cancelled";
                
            } else if ($data = $editform->get_data()) { //retorna NULL si no esta cancelat, si esta submit i si esta ben validat
            
                //echo "submit";
                //var_dump($data);
                //afegim les dades del curs plantilla
                $data = array_merge((array)$course, (array)$data); 
                $data['id'] = "";
                //echo "<br/>";
                //var_dump($data);
                $course = create_course((object)$data, array());
                
                redirect(new moodle_url('/enrol/users.php', 
            array('id' => $course->id, 'sesskey' => $USER->sesskey)));
            }
            $editform->display();
        }

        echo $OUTPUT -> footer();


 ?>


