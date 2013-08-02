<?php
//this file is used for ajax calls from search.js
require_once('../../config.php');
require_once($CFG->dirroot . '/mod/socialwiki/locallib.php');
$action=required_param('action',PARAM_TEXT);
$pageid=optional_param('pageid',null,PARAM_INT);
$userid=optional_param('uid',null,PARAM_INT);
$pages=optional_param('pages',null,PARAM_RAW);
$peers=optional_param('peers',null,PARAM_RAW);
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
		case 'pages':
			if(isset($pages)&&isset($peers)){
				//decode from JavaScript
				$pages=json_decode($pages);
				$peers=json_decode($peers);
				$pages=socialwiki_order_pages_using_peers($peers,$pages);
				$table = new html_table();
				$table->attributes['class'] = 'socialwiki_editor generalbox colourtext';
				$table->align = array('center');
				if(count($pages)>0){
					foreach ($pages as $page) {
						$table->data[] = array(html_writer::link($CFG->wwwroot.'/mod/socialwiki/view.php?pageid='.$page->id,$page->title.' (ID:'.$page->id.')',array('class'=>'socialwiki_link')));
					}
				}else{
					$table->data[] =array('<h3 socialwiki_titleheader>No Pages Found</h3>');
				}
				echo html_writer::table($table);
			}
			break;
		//return nothing if no action is passed
		default:
			break;
	}