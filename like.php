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
	$pageid=required_param('pageid', PARAM_INT);
	$from=required_param('from',PARAM_RAW);
	
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
	$context = context_module::instance($cm->id);
	if (!is_enrolled($context, $USER->id)) {
			//must be an enrolled user to like a page
		print_error('connotlike','socialwiki');
	}
	
	if(socialwiki_liked($USER->id,$pageid)){
		socialwiki_delete_like($USER->id,$pageid);
		$likes=socialwiki_numlikes($pageid);
		//delete pages with no likes as long as it's not the first page
		if($likes==0){
			$pagelist = socialwiki_get_linked_from_pages($pageid);
			$parentid=socialwiki_get_parent($pageid);
			$children=socialwiki_get_children($pageid);
			//change the child's parent to be the parent of the page being deleted
			foreach($children as $child){
				$child->parent=$parentid->parent;
				$DB->update_record('socialwiki_pages',$child);
			}
			//remove the page from the database
			socialwiki_delete_pages($context,array($pageid));
			foreach ($pagelist as $refreshpage)
			{
					if ($refreshpage->frompageid != $refreshpage->topageid)
					{
							socialwiki_refresh_cachedcontent(socialwiki_get_page($refreshpage->frompageid));
					}
			}
			redirect($CFG->wwwroot .'/mod/socialwiki/home.php?id='.$cm->id);
		}
	}else{
		socialwiki_add_like($USER->id,$pageid,$subwiki->id);
	}
	redirect($from);
