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

	require_once('../../config.php');
	require_once($CFG->dirroot . '/mod/socialwiki/locallib.php');
	
	$from=required_param('from',PARAM_TEXT); //the url of the previous page
	$pageid=optional_param('pageid',-1, PARAM_INT);
	$user2=optional_param('user2',-1,PARAM_INT);
    $swid = optional_param('swid', -1, PARAM_INT);
        
        if ($swid != -1)
        {
                $subwiki = socialwiki_get_subwiki($swid);
        }
        
	if($pageid>-1){
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

		//get the author of the current page
		$page=socialwiki_get_wiki_page_version($pageid,0);
		$user2=$page->userid;
		//check if the user is following themselves
		if($USER->id==$user2){
			//display error with a link back to the page they came from
			$PAGE->set_context($context);
			$PAGE->set_cm($cm);
			$PAGE->set_url('/mod/socialwiki/follow.php');
			echo $OUTPUT->header();
			echo $OUTPUT->box_start('generalbox','socialwiki_followerror');
                        echo '<p>'.get_string("cannotfollow", 'socialwiki').'</p>'.'<br/>';
                        echo html_writer::link($from,'Go back');
			echo $OUTPUT->box_end();
			echo $OUTPUT->footer();
		}else{
			//check if the use is already following the author
			if(socialwiki_is_following($USER->id,$user2,$subwiki->id)){
				//delete the record if the user is already following the author
				socialwiki_unfollow($USER->id,$user2, $subwiki->id);
                                redirect($from);
			}else{
				//if the user isn't following the author add a new follow
				$record=new StdClass();
				$record->userfromid=$USER->id;
				$record->usertoid=$user2;
				$record->subwikiid=$subwiki->id;
				$DB->insert_record('socialwiki_follows',$record);	
                                //go back to the page you came from
                                redirect($from);
			}
		}
		
	}elseif($user2!=-1){

		//check if the use is already following the author
		if(socialwiki_is_following($USER->id,$user2,$subwiki->id)){
			//delete the record if the user is already following the author
			socialwiki_unfollow($USER->id,$user2, $subwiki->id);
                        redirect($from);
		}else{
			//if the user isn't following the author add a new follow
			$record=new StdClass();
			$record->userfromid=$USER->id;
			$record->usertoid=$user2;
			$record->subwikiid=$subwiki->id;
			$DB->insert_record('socialwiki_follows',$record);
                        //go back to the page you came from
                        redirect($from);
		}
	}else{
		print_error('nouser','socialwiki');
	}
	

