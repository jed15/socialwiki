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
		$this->children[]='l'.$child;
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
			$parents=array();
			foreach($this->nodes as $node){
				if($node->parent==-1){
					$parents[]=$node;
				}
			
			}
			//add the children of the nodes
			for($i=0;$i<count($parents);$i++){
				$treear=$this->build_tree($parents[$i]);
				$sorted=array_merge($sorted,$treear);
			}
			$this->nodes=$sorted;
		}
		function build_tree($parent){
			$treear=array(); //an array of nodes that is an entire tree for a parent node
			$treear[]=$parent;
			for($i=0;$i<count($treear);$i++){
				foreach($treear[$i]->children as $childid){
					$treear[]=$this->find_node($childid);
				}	
			}
			return $treear;
		}
		
		function display(){
			$i=0;
			while($i<count($this->nodes)){
				$this->nodes[$i]->display();
				echo '<br/><br/>';
				for($j=1;$j<=count($this->nodes[$i]->children);$j++){
						$this->nodes[$i+$j]->display();
				}
				echo '<br/><br/><br/><br/>';
				$i+=count($this->nodes[$i]->children)+1;
			}
		}
	}
