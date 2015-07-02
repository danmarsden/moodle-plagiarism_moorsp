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
require_once($CFG->libdir . '/formslib.php');

define('MOORSP_STUDENT_DISCLOSURE', get_string('studentdisclosure','plagiarism_moorsp'));

class plagiarism_moorsp_class_functions_testcase extends advanced_testcase {
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
        $course = $this->getDataGenerator()->create_course();
        $this->assignment = $this->create_assign_instance($course, array('duedate'=>$tomorrow));
        // Enable Moorsp for this context module
        $plagiarismenabledcm = new stdClass();
        $plagiarismenabledcm->cm = $this->assignment->id;
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

        if (!empty($this->assignment->id)) {
            $outputhtml = '';
            $outputhtml .= $OUTPUT->box_start('generalbox boxaligncenter', 'intro');
            $formatoptions = new stdClass;
            $formatoptions->noclean = true;
            $outputhtml .= format_text($plagiarismsettings['moorsp_student_disclosure'], FORMAT_MOODLE, $formatoptions);
            $outputhtml .= $OUTPUT->box_end();
        }
        $this->assertEquals($outputhtml, $moorsp->print_disclosure($this->assignment->id));
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
        $plagiarismsettings = $DB->get_records_menu('plagiarism_moorsp_config', array('cm' => $this->assignment->id), '', 'name, value');
        $expected = $DB->record_exists('course_modules', array('id' => $this->assignment->id))
            && $plagiarismsettings['moorsp_use'];
        $this->assertEquals($expected, $moorsp->is_moorsp_used($this->assignment->id));
    }
    public function test_get_form_elements_module() {
        global $DB;
        $this->resetAfterTest(true);
        $ynoptions = array( 0 => get_string('no'), 1 => get_string('yes'));
        $studentshowoptions = array(0 => get_string("never"), 1 => get_string("always"));
        $moorspdraftoptions = array(
            PLAGIARISM_MOORSP_DRAFTSUBMIT_IMMEDIATE => get_string("submitondraft", "plagiarism_moorsp"),
            PLAGIARISM_MOORSP_DRAFTSUBMIT_FINAL => get_string("submitonfinal", "plagiarism_moorsp")
        );
        $moorsp = new plagiarism_plugin_moorsp();
        $expectedform = new moorsp_test_form();
        $plagiarismdefaults = $DB->get_records_menu('plagiarism_moorsp_config', array('cm' => 0), '', 'name, value');
        $plagiarismelements = $this->config_options;

        if (has_capability('plagiarism/moorsp:enable', $this->assignment)) {
            $expectedform->addElement('header', 'plagiarismdesc', get_string('pluginname', 'plagiarism_moorsp'));
            $expectedform->addElement('select', 'use_moorsp', get_string('usemoorsp', 'plagiarism_moorsp'), $ynoptions);
            $expectedform->addElement('select', 'moorsp_show_student_plagiarism_info',
                get_string("moorsp_show_student_plagiarism_info", "plagiarism_moorsp"), $studentshowoptions);
            if ($expectedform->elementExists('submissiondrafts')) {
                $expectedform->addElement('select', 'moorsp_draft_submit',
                    get_string("moorsp_draft_submit", "plagiarism_moorsp"), $moorspdraftoptions);
            }
            if ($expectedform->elementExists('moorsp_draft_submit')) {
                if ($expectedform->elementExists('submissiondrafts')) {
                    $expectedform->disabledIf('moorsp_draft_submit', 'submissiondrafts', 'eq', 0);
                }
            }
            // Disable all plagiarism elements if use_plagiarism eg 0.
            foreach ($plagiarismelements as $element) {
                if ($element <> 'use_moorsp') { // Ignore this var.
                    $expectedform->disabledIf($element, 'use_moorsp', 'eq', 0);
                }
            }
        } else { // Add plagiarism settings as hidden vars.
            foreach ($plagiarismelements as $element) {
                $expectedform->addElement('hidden', $element);
                $expectedform->setType('use_moorsp', PARAM_INT);
                $expectedform->setType('moorsp_show_student_plagiarism_info', PARAM_INT);
                $expectedform->setType('moorsp_draft_submit', PARAM_INT);
            }
        }
        // Now set defaults.
        foreach ($plagiarismelements as $element) {
            if (isset($plagiarismvalues[$element])) {
                $expectedform->setDefault($element, $plagiarismvalues[$element]);
            } else if (isset($plagiarismdefaults[$element])) {
                $expectedform->setDefault($element, $plagiarismdefaults[$element]);
            }
        }

        $actualform = new moorsp_test_form();
        $moorsp->get_form_elements_module($actualform, $this->assignment, 'assign');
        $this->assertTrue($actualform->elementExists('plagiarismdesc'));
        $this->assertTrue($actualform->elementExists('use_moorsp'));
        $this->assertTrue($actualform->elementExists('moorsp_show_student_plagiarism_info'));
        $this->assertEquals($expectedform, $actualform);





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
class moorsp_test_form extends moodleform {
    public function definition() {
         $mform =& $this->_form;
    }
}