<?php
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
	
	if(socialwiki_liked($USER->id,$pageid)){
		socialwiki_delete_like($USER->id,$pageid);
		$likes=socialwiki_numlikes($pageid);
		$firstpage=socialwiki_get_first_page($subwiki->id);
		//delete pages with no likes as long as it's not the first page
		if($likes==0&&$page!=$firstpage){
                        $pagelist = socialwiki_get_linked_from_pages($pageid);
			socialwiki_delete_pages($context,array($pageid));
                        foreach ($pagelist as $refreshpage)
                        {
                                if ($refreshpage->frompageid != $refreshpage->topageid)
                                {
                                        socialwiki_refresh_cachedcontent(socialwiki_get_page($refreshpage->frompageid));
                                }
                        }
			redirect($CFG->wwwroot .'/mod/socialwiki/view.php?pageid='.$firstpage->id);
		}
	}else{
		socialwiki_add_like($USER->id,$pageid);
	}
	redirect($from);
