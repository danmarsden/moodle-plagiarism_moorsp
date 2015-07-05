<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Moorsp plugin related unit tests
 *
 * @package    plugin_moorsp
 * @copyright  2015 Ramindu Deshapriya
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/plagiarism/moorsp/lib.php');

define('TEST_CONTENTHASH', '259e36493fc60699602b3e5d3593030022a0b29c');
define('MOORSP_STUDENT_DISCLOSURE', get_string('studentdisclosure','plagiarism_moorsp'));

class plagiarism_moorsp_event_handler_testcase extends advanced_testcase {
    protected $eventdata = array();
    protected $course = null;
    protected $assignment = null;
    protected $config_options = array('use_moorsp', 'moorsp_show_student_plagiarism_info',
        'moorsp_draft_submit');
    /**
     * Function to set up a course and assignment for the tests.
     */
    protected function setUp(){
        global $DB;
        $tomorrow = time() + 24*60*60;
        $this->setAdminUser();
        // Need to enable Moorsp first
        $setting = new stdClass();
        $setting->plugin = 'plagiarism';
        $setting->name = 'moorsp_use';
        $setting->value = 1;
        $DB->insert_record('config_plugins', $setting);
        $setting->plugin = 'plagiarism_moorsp';
        $setting->name = 'moorsp_student_disclosure';
        $setting->value = MOORSP_STUDENT_DISCLOSURE;
        $DB->insert_record('config_plugins', $setting);
        $setting->plugin = 'plagiarism_moorsp';
        $setting->name = 'moorsp_enable_mod_assign';
        $setting->value = 1;
        $DB->insert_record('config_plugins', $setting);
        $setting->plugin = 'plagiarism_moorsp';
        $setting->name = 'moorsp_enable_mod_forum';
        $setting->value = 1;
        $DB->insert_record('config_plugins', $setting);
        $setting->plugin = 'plagiarism_moorsp';
        $setting->name = 'moorsp_enable_mod_workshop';
        $setting->value = 1;
        $DB->insert_record('config_plugins', $setting);
        // Create a course
        $this->course = $this->getDataGenerator()->create_course();
        $this->assignment = $this->create_assign_instance($this->course, array('duedate'=>$tomorrow));
        // Enable Moorsp for this context module
        $plagiarismenabledcm = new stdClass();
        $plagiarismenabledcm->cm = $this->assignment->id;
        $plagiarismenabledcm->name = 'use_moorsp';
        $plagiarismenabledcm->value = 1;
        $DB->insert_record('plagiarism_moorsp_config', $plagiarismenabledcm);

    }
    public function test_assignsubmission_file_event() {
        global $DB, $USER;
        $fs = get_file_storage();
        $this->resetAfterTest(true);
        // Prepare file record object
        $fileinfo = array(
            'contextid' => $this->assignment->id,
            'component' => 'assignsubmission_file',
            'filearea' => 'submission_files',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => 'testfile.txt');
        $testfile = $fs->create_file_from_string($fileinfo, 'Test content');
        $eventdata = array(
            'eventname'         => '\assignsubmission_file\event\assessable_uploaded',
            'component'         => 'assignsubmission_file',
            'action'            => 'uploaded',
            'target'            => 'assessable',
            'objecttable'       => 'assign_submission',
            'objectid'          => 1,
            'crud'              => 'c',
            'edulevel'          => 2,
            'contextid'         => $this->course->id,
            'contextlevel'      => 70,
            'contextinstanceid' => $this->assignment->id,
            'userid'            => $USER->id,
            'courseid'          => $this->course->id,
            'relateduserid'     => null,
            'anonymous'         => 0,
            'other'             => array(
                                    'content' => '',
                                    'pathnamehashes' => array(
                                        $testfile->get_contenthash()
                                    )
            ),
            'timecreated' => time()


        );
        $result = moorsp_handle_event($eventdata);
        $this->assertTrue($result);
        $plagiarismfile = $DB->get_record_sql(
            "SELECT * FROM {plagiarism_moorsp_files}
                                 WHERE cm = ? AND userid = ? AND " .
            "identifier = ?",
            array($this->assignment->id, $USER->id, $testfile->get_contenthash()));
        $this->assertNotEmpty($plagiarismfile);
        $this->assertEquals($testfile->get_filename(), $plagiarismfile->filename);

    }
    /**
     * Convenience function to create a testable instance of an assignment.
     *
     * @param array $params Array of parameters to pass to the generator
     * @return testable_assign Testable wrapper around the assign class.
     */
    protected function create_assign_instance($course, $params=array()) {
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_assign');
        $params['course'] = $course->id;
        $instance = $generator->create_instance($params);
        $cm = get_coursemodule_from_instance('assign', $instance->id);
        $context = context_module::instance($cm->id);
        return $context;
    }
}