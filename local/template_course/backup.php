<?php
    define('CLI_SCRIPT', 1);
    require_once('../../config.php');
    require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
    require_once('lib.php');	
 
	// Login

    require_login();
        
    // Backup

    $course_module_to_backup = $course->id; // Set this to one existing choice cmid in your dev site
    $user_doing_the_backup   = $USER->id; // Set this to the id of your admin accouun
 
    $bc = new backup_controller(backup::TYPE_1COURSE, $course_module_to_backup, backup::FORMAT_MOODLE,
                            backup::INTERACTIVE_NO, backup::MODE_GENERAL, $user_doing_the_backup);
    $bc->execute_plan();

    require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
    
    // Restore

    // Transaction.
    $transaction = $DB->start_delegated_transaction();
     
    // Create new course.
    $folder = $bc->get_backupid();

    $userdoingrestore = $USER->id; // e.g. 2 == admin
    $courseid = restore_dbops::create_new_course('', '', $categoryid);


    // Restore backup into course.
    $controller = new restore_controller($folder, $courseid, 
            backup::INTERACTIVE_NO, backup::MODE_SAMESITE, $userdoingrestore,
            backup::TARGET_NEW_COURSE);
    $controller->execute_precheck();
    $controller->execute_plan();
     
    // Commit.
    $transaction->allow_commit();
 ?>


