<?php

	require_once ('../../config.php');
        require_once('lib.php');
        
        defined('MOODLE_INTERNAL') || die;
	global $CFG, $DB;
	require_once ($CFG->dirroot . '/lib/dmllib.php');
	require_once ($CFG->dirroot . '/lib/weblib.php');
	require_once ($CFG->dirroot . '/lib/moodlelib.php');
	global  $OUTPUT, $PAGE, $COURSE;
	
        //LOGIN
        require_login();
        
        //require_once($CFG->dirroot.'/calendar/lib.php');    /// This is after login because it needs $USER
        
        //CATEGORIA
        $categoryid = 1; // Template
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
        $PAGE->set_heading("$site->fullname");
        echo $OUTPUT -> header();
      
       //CONTINGUT--------------------------------------------
        //echo $content;
        echo "<p> The courses taught are: </p>";

///Display Course Categories
$query_catetories = mysql_query('SELECT cc.id, cc.parent, cc.name FROM   mdl_course_categories cc ');
$categories = mysql_fetch_all($query_catetories);

$tmp_categories = array();

foreach ($categories AS $row) {

    $row['id'] = (int) $row['id'];
    $row['parent'] = (int) $row['parent'];
    if (!$tmp_categories[$row['parent']])
        $tmp_categories[$row['parent']] = array();
    $tmp_categories[$row['parent']][] = $row;
}

$course_catetories = buildNode($tmp_categories);

echo '<ul>';
foreach ($course_catetories as $course_catetory) {
    print_category_child($course_catetory);
}
echo '</ul>';

echo '<li>' . $category['name'];
if (array_key_exists('children', $category)) {
    echo '<ul>';
    foreach ($category['children'] as $child) {
        print_category_child($child);
    }
    echo '</ul>';
}
echo '</li>';

print 'hola';

echo $OUTPUT -> footer();


 ?>


