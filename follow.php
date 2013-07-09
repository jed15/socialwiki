<?php
	require_once($CFG->dirroot . '/mod/socialwiki/locallib.php');
	$pageid=required_param('pageid', PARAM_INT);
	$from=required_param('from',PARAM_TEXT);
	//get the author of the current page
	$page=socialwiki_get_wiki_page_version($pageid);
	$user2=$page->userid;
	//make sure the user isn't following themselves
	if($USER->id==$user2){
		print $OUTPUT->box(get_string("cannotfollow", 'socialwiki'), 'errorbox');
	}
	//check if the use is already following the author
	if(socialwiki_is_following($USER->id,$user2)){
		//delete the record if the user is already following the author
		socialwiki_unfollow($USER->id,$user2);
	}else{
		//if the user isn't following the author add a new follow
		$record=new StdClass();
		$record->userfromid=$USER->id;
		$record->usertoid=$user2;
		$DB->insert_record('socialwiki_follows',$record);
	}
	
	$url=new moodle_url($CFG->wwwroot.$from.'pageid='.$pageid);
	redirect($url);


