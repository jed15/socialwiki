<?php
//this file is used for ajax calls from search.js
require_once('../../config.php');
require_once($CFG->dirroot . '/mod/socialwiki/locallib.php');
$action=required_param('action',PARAM_TEXT);
$pageid=optional_param('pageid',null,PARAM_INT);
$userid=optional_param('uid',null,PARAM_INT);
	switch($action){
		case 'liked':
			if(isset($pageid)&&isset($userid)){
				echo json_encode(socialwiki_liked($userid,$pageid));
			}
			
			break;
		case 'time':
			if (isset($pageid)){
				$page=socialwiki_get_page($pageid);
				//return the time it was created divided by current time
				echo json_encode($page->timecreated/time());
			}
			break;
		//return nothing if no action is passed
		default:
			break;
	}