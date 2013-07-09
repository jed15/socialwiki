<?php
	require_once($CFG->dirroot . '/mod/socialwiki/locallib.php');
	$pageid=required_param('pageid', PARAM_INT);
	$from=required_param('from',PARAM_TEXT);
	
	if(socialwiki_liked($USER->id,$pageid){
		socialwiki_delete_like($USER->id,$pageid);
	}else{
		socialwiki_add_like($USER->id,$pageid);
	}
	
	$url=new moodle_url($CFG->wwwroot.$from.'pageid='.$pageid);
	redirect($url);

	
	
	protected function print_pagetitle() {
        global $OUTPUT,$PAGE;
		$user = socialwiki_get_user_info($this->page->userid);
		$userlink = new moodle_url('/user/view.php', array('id' => $user->id, 'course' => $PAGE->cm->course));
		$html = '';

        $html .= $OUTPUT->container_start('','socialwiki_title');
        $html .= $OUTPUT->heading(format_string($this->title), 2, 'socialwiki_headingtitle','viewtitle');
		$html .=$OUTPUT->container_start('userinfo','author');
		$html.=html_writer::link($userlink->out(false),fullname($user));
		$html .= $OUTPUT->container_end();
		$html .= $OUTPUT->container_end();
        echo $html;
    }