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
		
		function __construct($page){
			$this->id=$page->id;
			if($page->parent==NULL){
				$this->parent=-1;
			}else{
				$this->parent=$page->parent;
			}
			$this->column=-1;
			$this->added=false;
			$this->set_content($page->title,$page->userid);
			
		}
		
		private function set_content($title,$userid){
			$user = socialwiki_get_user_info($userid);
			$this->content=$title.'\n'.fullname($user);
		}
		
		function add_child($child){
		$this->children []=$child;
		}
	}

	class socialwiki_tree{
		//an array of socialwiki_nodes
		public $nodes=array();
		
		function add_node($page){
			$this->nodes []=new socialwiki_node($page);
			//if the array has a parent add it to the parents child array
			if($page->parent!=NULL){
				$parent=$this->find_node($page->parent);
				if($parent){
					$parent->add_child($page->id);
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
			foreach($this->nodes as $node){
				if($node->parent==-1){
					$sorted[]=$node;
				}
			
			}
			//add the children of the nodes
			for($i=0;$i<count($sorted);$i++){
				foreach($sorted[$i]->children as $childid){
					$sorted[]=$this->find_node($childid);
				}
			
			}
			$this->nodes=$sorted;
		}
		
	}