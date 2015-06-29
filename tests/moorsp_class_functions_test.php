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

class moorsp_class_functions_testcase extends advanced_testcase {
    public function test_print_disclosure() {
        global $OUTPUT, $DB;
        $moorsp = new plagiarism_plugin_moorsp();
        $plagiarismsettings = $moorsp->get_settings();
        $plagiarismenabledcm = $DB->get_record_sql(
            "SELECT * FROM {plagiarism_moorsp_config}"
            );
        if (!empty($plagiarismenabledcm)) {
            $outputhtml = '';
            $outputhtml .= $OUTPUT->box_start('generalbox boxaligncenter', 'intro');
            $formatoptions = new stdClass;
            $formatoptions->noclean = true;
            $outputhtml .= format_text($plagiarismsettings['moorsp_student_disclosure'], FORMAT_MOODLE, $formatoptions);
            $outputhtml .= $OUTPUT->box_end();
        }
        $this->assertEquals($outputhtml, $moorsp->print_disclosure($plagiarismenabledcm));
    }
}