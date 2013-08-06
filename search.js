//patterns to determine the view mode
var pattern1=/option=1/; 
var pattern2=/option=2/;
var pattern3=/option/;
var pattern4=/option=3/;

<<<<<<< HEAD
var run=0;	//used to check if all ajax calls have been run
//check if a peer likes a page and add there score to the page if they do
=======

>>>>>>> 533278a8858bb999b7866d51f7d9b78d01d3326e
function likedTree(peer,pageid){
	$.ajax({
		url:'ajax.php',
		type:'get',
		data: {action:'liked',uid:peer.id,pageid:pageid},
		success: function(output){
			//add peer's score if they like the page
			if(output){
				searchTree.nodes['l'+pageid].priority+=peer.score;
				//console.log(searchTree.nodes['l'+pageid].priority);
			}
			run++;
			treeDisplay();
		}
	});
}


//displays the tree if all ajax functions have been run
function treeDisplay(){
	if(run>=18*3){
		mTree = new TreeControl(searchTree, "socialwiki_content_area");
		mTree.display();
		run=0;
	}
}

//get the time score for a page
function time(pageid){

	$.ajax({
			url:'ajax.php',
			type:'get',
			data: {action:'time',pageid:pageid},
			success: function(output){
				//set priority to time score
				searchTree.nodes['l'+pageid].priority+=parseFloat(output);
			}
	});
}
//recalculates the score for the tree nodes
function rescoreTree (){
	var jpeers=JSON.stringify(peers);
	var jnodes=JSON.stringify(mTree.myTree.nodes);
	$.ajax({
			url:'ajax.php',
			type:'post',
			data: {action:'tree',nodes:jnodes,peers:jpeers},
			success: function(output){
				searchTree.nodes = JSON.parse(output);
				mTree = new TreeControl(searchTree, "socialwiki_content_area");
				mTree.display();
			}
		});
}

function rescorePages(){
	var jpages=JSON.stringify(pages);
	var jpeers=JSON.stringify(peers);
	$.ajax({
			url:'ajax.php',
			type:'post',
			data: {action:'pages',pages:jpages,peers:jpeers},
			success: function(output){
				$('.socialwiki_editor').html(output);
			}
		});
}


//function create all the weight sliders and define the function to be executed when they are moved
function weightSliders (divID){
	//array containing labels for sliders
	var labels={like:'like similarity',follow:'follow similarity',trust:'trust<br/>&nbsp',popular:'popularity<br/>&nbsp'};
	$("#"+divID).prepend('<div id="sliders" class="colourtext"></div>');
	$('#sliders').append('<h2>Adjust Peer Weights<h2>');
	//set up the sliders for each weighted score
	for(option in scale){
		$('#sliders').append('<div id="'+option+'container" class="slidecontainer"></div>')
		$('#'+option+'container').append('<div id="'+option+'"class="socialwiki_slider"></div>');
		$('#'+option+'container').append('<span>'+labels[option]+'</span>');
		$('#'+option).slider({
		  orientation: "vertical",
		  range: "min",
		  min: 0,
		  max:4,
		  step:1,
		  value:scale[option],
		  //on the slide event rescore either the tree or pages depending on view
		  slide:function( event, ui ) {
				//update the scale
				scale[this.id]=ui.value;
				//update peer scores with new scale
				for(i in peers){
					peers[i].score=peers[i].trust*scale['trust']+peers[i].likesim*scale['like']+peers[i].followsim*scale['follow']+peers[i].popularity*scale['popular'];
				}
				if(pattern1.test(document.URL)||!pattern3.test(document.URL)){

					//delete the old tree
					$('#tree_container_div').remove();
					$("#tree_zoommessage").remove();
					$('#instruction').remove();
					//rescore and display the tree
					rescoreTree();
				}else if(pattern2.test(document.URL)){
					//rescore pages
					rescorePages();
				}
			}
		});
	}
}


var searchTree = new tree();
var zoom = document.documentElement.clientWidth / window.innerWidth;

//only create tree if in tree view
if(pattern1.test(document.URL)||!pattern3.test(document.URL)){
	searchTree.nodes = searchResults;
	mTree = new TreeControl(searchTree, "socialwiki_content_area");
}

$(document).ready(function() {
	$(".phptree").css("display", "none");
	
	if(!pattern4.test(document.URL)){
		weightSliders("socialwiki_content_area");
	}
	//only display tree if in tree view
   if(pattern1.test(document.URL)||!pattern3.test(document.URL)){
		//add weight sliders
		mTree.display();
		
		$(window).resize(this, function(e){
			newZoom = document.documentElement.clientWidth / window.innerWidth;
			if (newZoom != zoom)
			{
					$(".relation_line").css("display", "none");
					$(".tree_node").css("display", "none");
					$(".phptree").css("display",  "block");
					$("#tree_zoommessage").css("display", "inline");
			}
			else
			{
					$(".relation_line").css("display", "block");
					$(".tree_node").css("display", "inline-block");
					$(".phptree").css("display",  "none");
					$(".hideme").css("display", "none");
					$(".hideme").css("display", "none");
					$("#tree_zoommessage").css("display", "none");
			}
		});
	}
});
