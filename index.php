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
 * @package   mod_ptogo
 * @copyright 2018 michael pollak <moodle@michaelpollak.org>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require('../../config.php');

$id = required_param('id', PARAM_INT); // Course id.

if (!$course = $DB->get_record('course', array('id'=> $id))) {
    redirect(new moodle_url(''), "Course ID is incorrect", null, \core\output\notification::NOTIFY_ERROR);
}

require_course_login($course, true);
$PAGE->set_pagelayout('incourse');

$strptogo         = get_string('modulename', 'ptogo');
$strptogos        = get_string('modulenameplural', 'ptogo');
$strname         = get_string('name');
$strintro        = get_string('moduleintro');
$strlastmodified = get_string('lastmodified');

$PAGE->set_url('/mod/ptogo/index.php', array('id' => $course->id));
$PAGE->set_title($course->shortname.': '.$strptogos);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($strptogos);

echo $OUTPUT->header();
// Check if there even are instances.
if (!$ptogos = get_all_instances_in_course('ptogo', $course)) {
    \core\notification::error(get_string('thereareno', 'moodle', $strptogos));
} else {
    echo $OUTPUT->heading($strptogos);
    $usesections = course_format_uses_sections($course->format);

    $table = new html_table();
    $table->attributes['class'] = 'generaltable mod_index';

    if ($usesections) {
        $strsectionname = get_string('sectionname', 'format_'.$course->format);
        $table->head  = array ($strsectionname, $strname, $strintro);
        $table->align = array ('center', 'left', 'left');
    } else {
        $table->head  = array ($strlastmodified, $strname, $strintro);
        $table->align = array ('left', 'left', 'left');
    }

    $modinfo = get_fast_modinfo($course);
    $currentsection = '';
    foreach ($ptogos as $ptogo) {
        $cm = $modinfo->cms[$ptogo->coursemodule];
        if ($usesections) {
            $printsection = '';
            if ($ptogo->section !== $currentsection) {
                if ($ptogo->section) {
                    $printsection = get_section_name($course, $ptogo->section);
                }
                if ($currentsection !== '') {
                    $table->data[] = 'hr';
                }
                $currentsection = $ptogo->section;
            }
        } else {
            $printsection = '<span class="smallinfo">'.userdate($ptogo->timemodified)."</span>";
        }

        $class = $ptogo->visible ? '' : 'class="dimmed"'; // Hidden modules are dimmed.

        $table->data[] = array (
            $printsection,
            "<a $class href=\"view.php?id=$cm->id\">".format_string($ptogo->name)."</a>",
            format_module_intro('ptogo', $ptogo, $cm->id));
    }

    echo html_writer::table($table);
}

echo $OUTPUT->footer();
