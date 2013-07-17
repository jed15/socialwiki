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
	class socialwiki_node{
		//the page id
		public $id;
		//page title and authors name
		public $content;
		//boolean true if the node isn't a leaf
		public $hidden;
		//the column the node is in 
		public $column;
		//an array of children ids
		public $children=array();
		//the parents id
		public $parent;
		//whether the mode has been added to the tree
		public $added;
		//the level of the tree the node is on
		public $level;
		
		function __construct($page){
			$this->id='l'.$page->id;
			if($page->parent==NULL||$page->parent==0){
				$this->parent=-1;
			}else{
				$this->parent='l'.$page->parent;
			}
			$this->column=-1;
			$this->added=false;
			$this->hidden = true;
			$this->set_content($page);
			
		}
		
		private function set_content($page){
			Global $PAGE,$CFG;
			$user = socialwiki_get_user_info($page->userid);
			$userlink = new moodle_url('/user/view.php', array('id' => $user->id, 'course' => $PAGE->cm->course));
			$this->content=html_writer::link($CFG->wwwroot.'/mod/socialwiki/view.php?pageid='.$page->id,$page->title,array("class"=>"colourtext")).'<br/>'.html_writer::link($userlink->out(false),fullname($user),array("class"=>"colourtext"));
		}
		
		function add_child($child){
			$this->children[]=$child;
		}
		
		function display(){
			Global $OUTPUT;
			echo $OUTPUT->box($this->content,'socialwiki_treebox');
		}
	}

	
	class socialwiki_tree{
		//an array of socialwiki_nodes
		public $nodes=array();
		
		function add_node($page){
			$this->nodes['l'.$page->id]=new socialwiki_node($page);
		}
		
		function add_children(){
						//if the array has a parent add it to the parents child array
			foreach ($this->nodes as $node){
				if($node->parent!=-1){
					$parent=$this->find_node($node->parent);
					if($parent){
						$parent->add_child($node->id);
					}else{
						print_error('nonode','socialwiki');
					}
				}
			}
		}
		
		//finds a node given an id returns that node if found. -1 if the node doesn't exist
		function find_node($nodeid){
			foreach($this->nodes as $node){
				if($node->id==$nodeid){
					return $node;
				}
			}
			return NULL;
		}
		
		//sort the array with the children of a node appearing directly after it in the array
		function sort(){
			$sorted=array();
			$parents=array();
			foreach($this->nodes as $node){
				if($node->parent==-1){
					$parents[]=$node;
					}	
				}
				//make a tree array for each parent so the sorted array has one complete tree followed by another tree
				foreach ($parents as $parent){
					
					//add the new array to the end of the sorted array
					$sorted=$this->find_children($parent->id,$sorted,1);
				}
			//set nodes to the sorted array
			$this->nodes=$sorted;
		}
		
		//recursively add chid nodes to an array
		function find_children($nodeid,$ar,$level){
			$node=$this->find_node($nodeid);
			$node->level=$level;
			//add the child to the array
			$ar[$nodeid]=$node;
			//increase level
			$level++;
			if(count($node->children)>0){
				foreach($node->children as $childid){
					$ar=$this->find_children($childid,$ar,$level);
				}
			}
			return $ar;
		}
		
		//returns an array with all the leaves of the tree
		function find_leaves(){
			$leaves=array();
			foreach($this->nodes as $node){
				if(count($node->children)==0){
				$leaves[]=$node;
				}
			}
			return $leaves;
		}
		
		function display(){
			Global $OUTPUT;
			$this->sort();
			echo $OUTPUT->heading('OLDEST--->NEWEST',1,'colourtext');
			foreach($this->nodes as $node){
				if($node->parent==-1){
					echo'<br/><br/><br/>';
				}
				echo $OUTPUT->container_start();
				echo str_repeat('&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp',$node->level-1);
				$node->display();
				echo $OUTPUT->container_end();
			}
		}

	}
