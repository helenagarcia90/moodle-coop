<?php

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $options = array('all'=>get_string('allcourses', 'block_course_creation'), 'own'=>get_string('owncourses', 'block_course_creation'));

    $settings->add(new admin_setting_configselect('block_course_creation_adminview', get_string('adminview', 'block_course_creation'),
                       get_string('configadminview', 'block_course_creation'), 'all', $options));

    $settings->add(new admin_setting_configcheckbox('block_course_creation_hideallcourseslink', get_string('hideallcourseslink', 'block_course_creation'),
                       get_string('confighideallcourseslink', 'block_course_creation'), 0));
}


