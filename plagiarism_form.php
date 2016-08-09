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
 * forms used by the moorsp plagiarism plugin.
 *
 * @package    plagiarism_moorsp
 * @copyright  2014 onwards Dan Marsden {@link http://danmarsden.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

require_once($CFG->dirroot.'/lib/formslib.php');

class plagiarism_setup_form extends moodleform {
    public function definition () {
        $mform =& $this->_form;

        $mform->addElement('html', get_string('moorspexplain', 'plagiarism_moorsp'));
        $mform->addElement('checkbox', 'moorsp_use', get_string('usemoorsp', 'plagiarism_moorsp'));

        $mform->addElement('textarea', 'moorsp_student_disclosure', get_string('studentdisclosure', 'plagiarism_moorsp'),
                           'wrap="virtual" rows="6" cols="50"');
        $mform->addHelpButton('moorsp_student_disclosure', 'studentdisclosure', 'plagiarism_moorsp');
        $mform->setDefault('moorsp_student_disclosure', get_string('studentdisclosuredefault', 'plagiarism_moorsp'));

        $mods = core_component::get_plugin_list('mod');
        foreach ($mods as $mod => $modname) {
            if (plugin_supports('mod', $mod, FEATURE_PLAGIARISM)) {
                $modstring = 'moorsp_enable_mod_' . $mod;
                $mform->addElement('checkbox', $modstring, get_string('moorsp_enableplugin', 'plagiarism_moorsp', $mod));
            }
        }

        $this->add_action_buttons(true);
    }
}

