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

define('MOORSP_STUDENT_DISCLOSURE', get_string('studentdisclosure', 'plagiarism_moorsp'));

class plagiarism_moorsp_class_functions_testcase extends advanced_testcase {
    protected $course = null;
    protected $assignment = null;
    protected $configoptions = array('use_moorsp', 'moorsp_show_student_plagiarism_info',
'moorsp_draft_submit');

    /**
     * Function to set up a course and assignment for the tests.
     */
    protected function setUp() {
        global $DB;
        $tomorrow = time() + 24 * 60 * 60;
        $this->setAdminUser();
        // Need to enable Moorsp first.
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
        // Create a course.
        $this->course = $this->getDataGenerator()->create_course();
        $this->assignment = $this->create_assign_instance($this->course, array('duedate' => $tomorrow));
        // Enable Moorsp for this context module.
        $plagiarismenabledcm = new stdClass();
        $plagiarismenabledcm->cm = $this->assignment->instanceid;
        $plagiarismenabledcm->name = 'use_moorsp';
        $plagiarismenabledcm->value = 1;
        $DB->insert_record('plagiarism_moorsp_config', $plagiarismenabledcm);

    }
    public function test_print_disclosure() {
        global $OUTPUT;
        $this->resetAfterTest(true);
        $moorsp = new plagiarism_plugin_moorsp();
        $plagiarismsettings = array_merge((array)get_config('plagiarism'),
            (array)get_config('plagiarism_moorsp'));

        if (!empty($this->assignment->instanceid)) {
            $outputhtml = '';
            $outputhtml .= $OUTPUT->box_start('generalbox boxaligncenter plagiarism_disclosure', 'intro');
            $formatoptions = new stdClass;
            $formatoptions->noclean = true;
            $outputhtml .= format_text($plagiarismsettings['moorsp_student_disclosure'], FORMAT_MOODLE, $formatoptions);
            $outputhtml .= $OUTPUT->box_end();
        }
        $this->assertEquals($outputhtml, $moorsp->print_disclosure($this->assignment->instanceid));
    }
    public function test_get_settings() {
        $this->resetAfterTest(true);
        $moorsp = new plagiarism_plugin_moorsp();
        $plagiarismsettings = array_merge((array)get_config('plagiarism'),
            (array)get_config('plagiarism_moorsp'));
        $this->assertEquals($plagiarismsettings, $moorsp->get_settings());
    }
    public function test_is_moorsp_used() {
        global $DB;
        $this->resetAfterTest(true);
        $moorsp = new plagiarism_plugin_moorsp();
        $plagiarismsettings = $DB->get_records_menu('plagiarism_moorsp_config',
            array('cm' => $this->assignment->instanceid), '', 'name, value');
        $expected = $DB->record_exists('course_modules', array('id' => $this->assignment->instanceid))
            && $plagiarismsettings['use_moorsp'];
        $this->assertEquals($expected, $moorsp->is_moorsp_used($this->assignment->instanceid));
    }
    public function test_update_plagiarism_file() {
        global $DB, $USER;
        $this->resetAfterTest(true);
        $moorsp = new plagiarism_plugin_moorsp();
        $file = new stdClass();
        $file->filename = "Test file";
        $file->identifier = md5("Test file content");
        $result = $moorsp->update_plagiarism_file($this->assignment->instanceid, $USER->id, $file);
        $this->assertTrue($result);
        $plagiarismfile = $DB->get_record_sql(
            "SELECT * FROM {plagiarism_moorsp_files}
                                 WHERE cm = ? AND userid = ? AND " .
            "identifier = ?",
            array($this->assignment->instanceid, $USER->id, $file->identifier));
        $this->assertNotEmpty($plagiarismfile);
        $this->assertEquals($file->filename, $plagiarismfile->filename);
    }
    public function test_handle_onlinetext() {
        global $DB, $USER;
        $this->resetAfterTest(true);
        $moorsp = new plagiarism_plugin_moorsp();
        $content = "Test content";
        $contentmd5 = md5("Test content");
        $result = $moorsp->handle_onlinetext($this->assignment->instanceid, $USER->id, $content);
        $this->assertTrue($result);
        $contentrepresentation = $DB->get_record_sql(
            "SELECT * FROM {plagiarism_moorsp_files}
                                 WHERE cm = ? AND userid = ? AND " .
            "identifier = ?",
            array($this->assignment->instanceid, $USER->id, $contentmd5));
        $this->assertNotEmpty($contentrepresentation);
        $this->assertEquals("content_" . $contentmd5, $contentrepresentation->filename);
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
