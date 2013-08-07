<?php
//this file is used for ajax calls from search.js
require_once('../../config.php');
require_once($CFG->dirroot . '/mod/socialwiki/locallib.php');
require_once($CFG->dirroot . '/mod/socialwiki/socialwikitree.php');

$action=required_param('action',PARAM_TEXT);
$pages=optional_param('pages',null,PARAM_RAW);
$peers=optional_param('peers',null,PARAM_RAW);
$nodes=optional_param('nodes',null,PARAM_RAW);

	switch($action){
		case 'tree':
				if(isset($nodes)&&isset($peers)){
					$tree=new socialwiki_tree;
					//decode from JavaScript
					$tree->nodes=(array)json_decode($nodes);
					$peers=json_decode($peers);
					//re-score nodes
					foreach($tree->nodes as $node){
						$page=socialwiki_get_page(substr($node->id,1));
						$node->priority=$page->timecreated/time();
						foreach($peers as $peer){
							if(socialwiki_liked($peer->id,substr($node->id,1))){
								$node->priority+=$peer->score;
							}
						}
					}
					//sort tree 
					$tree->sort();
					echo json_encode($tree->nodes);
				}
				break;
		//re-score pages and return the outputted html table
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