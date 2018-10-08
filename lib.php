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
defined('MOODLE_INTERNAL') || die();

/**
 * https://github.com/moodle/moodle/blob/master/lib/moodlelib.php#L390
 * @uses FEATURE_MOD_INTRO
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, null if doesn't know
 */
function ptogo_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_INTRO:               return true;;
        case FEATURE_SHOW_DESCRIPTION:        return true;
        default: return null;
        // case FEATURE_BACKUP_MOODLE2:          return true; // TODO: Get funding for backup functionality.
    }
}

function ptogo_add_instance($data) {
    global $DB, $CFG;

    $response = $DB->get_field('ptogo_items', 'MAX(video_id)', array(), $strictness=IGNORE_MULTIPLE);
    $videoid = $response + 1;

    // TODO: Form field is named incorrect, maybe change.
    $data->additional_query = $data->baseQuery;

    if($data->listitem === "list") {
        $data->video_id = null;
    } else {
        $items = explode(';', $data->ptogo_item_id);
        $data->video_id = $videoid;
        for($i=0;$i<count($items);$i++){
            $item = new stdClass();
            $item->item_id = $items[$i];
            $item->video_id = $videoid;
            $DB->insert_record('ptogo_items', $item);
        }
    }

    return $DB->insert_record('ptogo', $data);
}

function ptogo_update_instance($data) {
    global $DB, $CFG;

    $result = new stdClass();
    $result->repository_id = $data->repository_id;
    $result->additional_query = $data->baseQuery;
    $result->title = $data->title;
    $result->video_id = null;
    $result->course = $data->course;
    $result->name = $data->name;
    $result->intro = $data->intro;
    $result->introformat = FORMAT_HTML;
    $result->id = $data->id;
    $result->showinlisting = $data->showinlisting;

    if($data->listitem === "list") {
        $result->video_id = null; // If we want to display a list we don't want a video_id.
    } else {
        // Check if current instance has already a video_id.
        $currentvalues = $DB->get_record('ptogo', array('id'=>$result->id), $fields='video_id', $strictness=IGNORE_MISSING);
        $videoid = $currentvalues->video_id;

        if (isset($videoid)) {
            // Remove everything from ptogo_items where video_id == $videoid.
            $DB->delete_records('ptogo_items', array('video_id'=>$videoid));
        } else {
            $response = $DB->get_field('ptogo_items', 'MAX(video_id)', array(), $strictness=IGNORE_MULTIPLE);
            $videoid = $response + 1;
        }

        $items = explode(';', $data->ptogo_item_id);

        // Run through all items we selected and update with $videoid in ptogo_items.
        foreach($items as $item) {
            $newitem = new stdClass();
            $newitem->item_id = $item;
            $newitem->video_id = $videoid;
            $DB->insert_record('ptogo_items', $newitem);
        }

        $result->video_id = $videoid;

    }

    return $DB->update_record('ptogo', $result);
}

function ptogo_delete_instance($id) {
    global $DB;
    // Find video_id that is connected.
    $currentvalues = $DB->get_record('ptogo', array('id'=>$id), $fields='video_id', $strictness=IGNORE_MISSING);
    $DB->delete_records('ptogo_items', array('video_id'=>$currentvalues->video_id));
    return $DB->delete_records('ptogo', array('id'=>$id));
}


/**
 * Given a course_module object, this function returns any
 * "extra" information that may be needed when printing
 * this activity in a course listing.
 * See get_array_of_activities() in course/lib.php
 *
 * @global object
 * @param object $coursemodule
 * @return cached_cm_info|null
 */
function ptogo_get_coursemodule_info($coursemodule) {
    global $DB, $CFG;

    $result = new cached_cm_info();

    $fields = 'id, title, intro, course, showinlisting';
    if (!$ptogo = $DB->get_record('ptogo', array('id' => $coursemodule->instance), $fields)) {
        return false;
    }

    $result->name = $ptogo->title;
    // Print intro in course listing, this is cached.
    if ($coursemodule->showdescription && !$ptogo->showinlisting) {
        $result->content = $ptogo->intro;
    }

    return $result;
}

/**
 * When we print in course listing but don't want stuff cached.
 * @param object $coursemodule
 */
function ptogo_cm_info_view($coursemodule) {
    global $CFG, $DB;

    $fields = 'id, title, intro, course, showinlisting';
    if (!$ptogo = $DB->get_record('ptogo', array('id' => $coursemodule->instance), $fields)) {
        return false;
    }

    // Add videos directly to course view. This takes a really long time...
    if($ptogo->showinlisting) {
        $result = '';
        require_once("$CFG->dirroot/mod/ptogo/locallib.php");
        $result = ptogo_process_response($ptogo->course, $ptogo->id, 'embed', 3);
        $coursemodule->set_after_link($result);
    }
}