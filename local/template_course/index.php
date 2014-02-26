<?php
	require_once ('../../config.php');
        //require_once('lib.php');
        defined('MOODLE_INTERNAL') || die;
	global $CFG, $DB;
	require_once ($CFG->dirroot . '/lib/dmllib.php');
	require_once ($CFG->dirroot . '/lib/weblib.php');
	require_once ($CFG->dirroot . '/lib/moodlelib.php');
	global  $OUTPUT, $PAGE, $COURSE;
	
        //LOGIN
        require_login($COURSE->id);    
        $context = context_course::instance($COURSE->id, MUST_EXIST);
        
        require_once($CFG->dirroot.'/calendar/lib.php');    /// This is after login because it needs $USER
        // Remove any switched roles before checking login
        if ($switchrole == 0 && confirm_sesskey()) {
            role_switch(-1, $COURSE->context); //AL LOROOOOOOOOOOOO
        }
        
        //IMPRIMIM EL CONTINGUT
        
        $site = get_site();
        $PAGE->set_title("$site->fullname: Cursos Plantilla");
        $PAGE->set_heading("$site->fullname");
        echo $OUTPUT -> header();
      

        //exemple1
	/*if ($DB->record_exists('user', array('username' => 'helena.garcia'))){
	 	print 'helena is an existing username';
        }
	else {
	 	print 'helena is not an existing username';
	}*/
        
        //CONTINGUT--------------------------------------------
        print '<br>Aqui va el contingut';

        echo $OUTPUT -> footer();
?>