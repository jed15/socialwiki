<?php
	require_once('../../config.php');
	require_once($CFG->dirroot . '/mod/socialwiki/pagelib.php');
	require_once($CFG->dirroot . '/mod/socialwiki/locallib.php');
	$pageid=required_param('pageid',PARAM_INT);
	$userid=required_param('userid',PARAM_INT);
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
	$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
	
	require_login($course, true, $cm);
	
	$viewuserpage=new page_socialwiki_viewuserpages($wiki,$subwiki,$cm);
	$viewuserpage->set_uid($userid);
	$viewuserpage->set_page($page);
	$viewuserpage->print_header();
	
	$viewuserpage->print_content();
	
	$viewuserpage->print_footer();

