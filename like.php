<?php
	require_once('../../config.php');
	require_once($CFG->dirroot . '/mod/socialwiki/locallib.php');
	$pageid=required_param('pageid', PARAM_INT);
	$from=required_param('from',PARAM_RAW);
	
	if(socialwiki_liked($USER->id,$pageid)){
		socialwiki_delete_like($USER->id,$pageid);
	}else{
		socialwiki_add_like($USER->id,$pageid);
	}
	redirect($from);
