# Description

This Moodle platform was originally modified for a collaboration with l'École Superieure d'Informatique de Bobo-Dioulasso (Burkina Faso). 

The main features are the following:

* UI simplification, by creating a personalized theme which priorizes cleanliness and easy site navigation. 
* UX improvement, by reducing more used functionallity steps and adding buttons and other easy ways to move through the site.
* "Template course" local module added. It simplifies the course creation by adding a more abstract layer above the courses. 

A template course is like a subject structure, only with the skeleton, description and main resources, but without evaluations, dates and any student realted to it. Then, it is possible to instance a course through a template course, just adding an initial and ending date. Then, it looks like a normal moodle course.

# Configuration

(Should create config.php in the root directory with the content below (and adding properly user, password, etc)

```
<?php  // Moodle configuration file
unset($CFG);
global $CFG;
$CFG = new stdClass();
$CFG->dbtype    = 'mysqli';
$CFG->dblibrary = 'native';
$CFG->dbhost    = 'localhost';
$CFG->dbname    = 'moodle';
$CFG->dbuser    = 'user';
$CFG->dbpass    = 'password';
$CFG->prefix    = 'mdl_';
$CFG->dboptions = array (
  'dbpersist' => 0,
  'dbsocket' => 0,
);
$CFG->wwwroot   = 'http://localhost/moodle';
$CFG->dataroot  = 'C:\\xampp\\moodledata';
$CFG->admin     = 'admin';
$CFG->directorypermissions = 0777;
require_once(dirname(__FILE__) . '/lib/setup.php');
// There is no php closing tag in this file,
// it is intentional because it prevents trailing whitespace problems!
```
