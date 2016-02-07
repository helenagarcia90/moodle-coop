<?php
	require_once ('../../config.php');
    require_once('lib.php');
    require_once('renderer.php');
        
    defined('MOODLE_INTERNAL') || die;
	global $CFG, $DB;
	require_once ($CFG->dirroot . '/lib/dmllib.php');
	require_once ($CFG->dirroot . '/lib/weblib.php');
	require_once ($CFG->dirroot . '/lib/moodlelib.php');
	global  $OUTPUT, $PAGE, $COURSE, $USER;
	
        //LOGIN
        require_login();
        $PAGE->set_context(context_system::instance());
        if(!has_capability('moodle/course:create', $PAGE->context)) { 
             redirect(new moodle_url("/course/index.php"));
        }
        
        require_once($CFG->dirroot.'/calendar/lib.php');    /// This is after login because it needs $USER
        
        //CATEGORIA
        $categoryid = -1; // Template
        $PAGE->set_category_by_id($categoryid);
        $PAGE->set_url(new moodle_url('/local/template_course/index.php'));
        $PAGE->set_pagetype('course-index-category');
        
        //RENDERER
        $PAGE->set_pagelayout('coursecategory');
        $courserenderer = new template_course_renderer($PAGE, "general");
        //$category = $DB->get_record('course_categories', array('id'=>$categoryid), '*', MUST_EXIST);
        $content = $courserenderer->course_category($categoryid);
        
        //IMPRIMIM EL CONTINGUT        
        $site = get_site();
        $PAGE->set_title("$site->fullname: Matières");
        $PAGE->set_heading("$site->fullname");
        echo $OUTPUT -> header();
      
       //CONTINGUT--------------------------------------------
        echo $content;

        echo $OUTPUT -> footer();
 ?>

