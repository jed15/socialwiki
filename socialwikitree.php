<?php
	
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
			if($page->parent==NULL){
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
			$this->content=html_writer::link($CFG->wwwroot.'/mod/socialwiki/view.php?pageid='.$page->id,$page->title,array("class"=>"whitetext")).'<br/>'.html_writer::link($userlink->out(false),fullname($user),array("class"=>"whitetext"));
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
			//if the array has a parent add it to the parents child array
			if($page->parent!=NULL){
				$parent=$this->find_node('l'.$page->parent);
				if($parent){
					$parent->add_child('l'.$page->id);
				}else{
					print_error('nonode','socialwiki');
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
		
		//sort the array with the children of a node apearing directly after it in the array
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
					$sorted=$this->add_children($parent->id,$sorted,1);
				}
			//set nodes to the sorted array
			$this->nodes=$sorted;
		}
		
		//recursively add chid nodes to an array
		function add_children($nodeid,$ar,$level){
			$node=$this->find_node($nodeid);
			$node->level=$level;
			//add the child to the array
			$ar[$nodeid]=$node;
			//increase level
			$level++;
			if(count($node->children)>0){
				foreach($node->children as $childid){
					$ar=$this->add_children($childid,$ar,$level);
				}
			}
			return $ar;
		}
		/*
		//returns an array of children
		function add_children($parent){
			$childar=array();
			foreach($parent->children as $childid){
				$childar[$childid]=$this->find_node($childid);
			}
			return $childar;
		}
		*/
		/*returns an array for a tree 
		 *the array starts with the parent node then the children of the parent
		 *then the children's children and so on until the leaf nodes
		 */
		/*
		function build_tree($parent){
			//add the parent to the array
			$treear[$parent->id]=$parent;
			$keys=array_keys($treear);
			//go through the array adding the children for each node until the leaf nodes
			for($i=0;$i<count($keys);$i++){
				if(count($treear[$keys[$i]]->children)>0){
					$childar=$this->add_children($treear[$keys[$i]]);
					//add the children to the end of the array
					$treear=array_merge($treear,$childar);
					//update keys array so it contains the keys for the nodes that where just added
					$keys=array_keys($treear);
				}
			}
			return $treear;
		}*/
		
		function display(){
			Global $OUTPUT;
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
