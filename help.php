<?php

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/socialwiki/locallib.php');
$pageid=required_param('pageid', PARAM_INT);
	
 if (!$page = socialwiki_get_page($pageid)) {
	 print_error('incorrectpageid', 'socialwiki');
 }

 if (!$subwiki = socialwiki_get_subwiki($page->subwikiid)) {
	 print_error('incorrectsubwikiid', 'socialwiki');
 }
 if (!$wiki = socialwiki_get_wiki($subwiki->wikiid)) {
	 print_error('incorrectwikiid', 'socialwiki');
 }

 if (!$cm = get_coursemodule_from_instance('socialwiki', $wiki->id)) {
	 print_error('invalidcoursemodule');
 }

$context = get_context_instance(CONTEXT_MODULE, $cm->id);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

require_login($course, true, $cm);

$url=new moodle_url('/mod/socialwiki/help.php?pageid='.$pageid);
$PAGE->set_url($url);
$PAGE->requires->css(new moodle_url("/mod/socialwiki/".$wiki->style."_style.css"));
$PAGE->set_context($context);
$PAGE->set_cm($cm);

$helpout=$PAGE->get_renderer('mod_socialwiki');

echo $OUTPUT->header();

echo $helpout->help_area_start();
echo $OUTPUT->heading('Help Page',1);
echo $helpout->help_content('Links',get_string('links_help','socialwiki'));
echo $helpout->help_area_end();

echo $OUTPUT->footer();
