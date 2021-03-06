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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Wiki files management
 *
 * @package mod-wiki-2.0
 * @copyrigth 2011 Dongsheng Cai <dongsheng@moodle.com>
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/socialwiki/lib.php');
require_once($CFG->dirroot . '/mod/socialwiki/locallib.php');

$pageid       = required_param('pageid', PARAM_INT); // Page ID
$wid          = optional_param('wid', 0, PARAM_INT); // Wiki ID
$currentgroup = optional_param('group', 0, PARAM_INT); // Group ID
$userid       = optional_param('uid', 0, PARAM_INT); // User ID
$groupanduser = optional_param('groupanduser', null, PARAM_TEXT);

if (!$page = socialwiki_get_page($pageid)) {
    print_error('incorrectpageid', 'socialwiki');
}

if ($groupanduser) {
    list($currentgroup, $userid) = explode('-', $groupanduser);
    $currentgroup = clean_param($currentgroup, PARAM_INT);
    $userid       = clean_param($userid, PARAM_INT);
}

if ($wid) {
    // in group mode
    if (!$wiki = socialwiki_get_wiki($wid)) {
        print_error('incorrectwikiid', 'socialwiki');
    }
    if (!$subwiki = socialwiki_get_subwiki_by_group($wiki->id, $currentgroup, $userid)) {
        // create subwiki if doesn't exist
        $subwikiid = socialwiki_add_subwiki($wiki->id, $currentgroup, $userid);
        $subwiki = socialwiki_get_subwiki($subwikiid);
    }
} else {
    // no group
    if (!$subwiki = socialwiki_get_subwiki($page->subwikiid)) {
        print_error('incorrectsubwikiid', 'socialwiki');
    }

    // Checking wiki instance of that subwiki
    if (!$wiki = socialwiki_get_wiki($subwiki->wikiid)) {
        print_error('incorrectwikiid', 'socialwiki');
    }
}

// Checking course module instance
if (!$cm = get_coursemodule_from_instance("socialwiki", $subwiki->wikiid)) {
    print_error('invalidcoursemodule');
}

// Checking course instance
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

$context = context_module::instance($cm->id);


$PAGE->set_url('/mod/socialwiki/files.php', array('pageid'=>$pageid));
require_login($course, true, $cm);
$PAGE->set_context($context);
$PAGE->set_title(get_string('wikifiles', 'socialwiki'));
$PAGE->set_heading(get_string('wikifiles', 'socialwiki'));
$PAGE->navbar->add(format_string(get_string('wikifiles', 'socialwiki')));
echo $OUTPUT->header();

$renderer = $PAGE->get_renderer('mod_socialwiki');

echo $renderer->pretty_navbar($pageid);


echo $OUTPUT->box_start('generalbox');
if (has_capability('mod/socialwiki:viewpage', $context)) {
    echo $renderer->socialwiki_print_subwiki_selector($PAGE->activityrecord, $subwiki, $page, 'files');
    echo $renderer->socialwiki_files_tree($context, $subwiki);
} else {
    echo $OUTPUT->notification(get_string('cannotviewfiles', 'socialwiki'));
}
echo $OUTPUT->box_end();

if (has_capability('mod/socialwiki:managefiles', $context)) {
    echo $OUTPUT->single_button(new moodle_url('/mod/socialwiki/filesedit.php', array('subwiki'=>$subwiki->id, 'pageid'=>$pageid)), get_string('editfiles', 'socialwiki'), 'get');
}
echo $OUTPUT->footer();
