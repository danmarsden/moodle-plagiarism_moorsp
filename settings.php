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

require_once(dirname(dirname(__FILE__)) . '/../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/plagiarismlib.php');
require_once($CFG->dirroot.'/plagiarism/moorsp/lib.php');
require_once($CFG->dirroot.'/plagiarism/moorsp/plagiarism_form.php');

require_login();
admin_externalpage_setup('plagiarismmoorsp');

$context = context_system::instance();

require_capability('moodle/site:config', $context, $USER->id, true, "nopermissions");

require_once('plagiarism_form.php');
$mform = new plagiarism_setup_form();
$plagiarismplugin = new plagiarism_plugin_moorsp();

if ($mform->is_cancelled()) {
    redirect('');
}

echo $OUTPUT->header();

if (($data = $mform->get_data()) && confirm_sesskey()) {
    if (!isset($data->moorsp_use)) {
        $data->moorsp_use = 0;
    }
    if (!isset($data->moorsp_enable_mod_assign)) {
        $data->moorsp_enable_mod_assign = 0;
    }
    if (!isset($data->moorsp_enable_mod_assignment)) {
        $data->moorsp_enable_mod_assignment = 0;
    }
    if (!isset($data->moorsp_enable_mod_forum)) {
        $data->moorsp_enable_mod_forum = 0;
    }
    if (!isset($data->moorsp_enable_mod_workshop)) {
        $data->moorsp_enable_mod_workshop = 0;
    }
    foreach ($data as $field=>$value) {
        if (strpos($field, 'moorsp')===0) {
            $plugintype = $field == 'moorsp_use' ? 'plagiarism' : 'plagiarism_moorsp';
            set_config($field, $value, $plugintype);
        }
    }
    cache_helper::invalidate_by_definition('core', 'config', array(), 'plagiarism_moorsp');
    echo $OUTPUT->notification(get_string('savedconfigsuccess', 'plagiarism_moorsp'), 'notifysuccess');
}
$plagiarismsettings = array_merge((array)get_config('plagiarism'), (array)get_config('plagiarism_moorsp'));
$mform->set_data($plagiarismsettings);
    
echo $OUTPUT->box_start('generalbox boxaligncenter', 'intro');
$mform->display();
echo $OUTPUT->box_end();
echo $OUTPUT->footer();
