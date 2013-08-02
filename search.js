//patterns to determine the view mode
var pattern1=/option=1/; 
var pattern2=/option=2/;
var pattern3=/option/;
var pattern4=/option=3/;


function likedTree(peer,pageid){
	$.ajax({
		url:'ajax.php',
		type:'get',
		data: {action:'liked',uid:peer.id,pageid:pageid},
		success: function(output){
				//add peer's score if they like the page
				if(output){
					searchTree.nodes['l'+pageid].priority+=peer.score;
				}
		}
	});
}

function time(pageid){

	$.ajax({
			url:'ajax.php',
			type:'get',
			data: {action:'time',pageid:pageid},
			success: function(output){
				//set priority to time created/ current time
				searchTree.nodes['l'+pageid].priority=parseFloat(output);
			}
		});
}
//recalculates the score for the tree nodes
function rescoreTree (){
	for(i in searchTree.nodes){
		//set priority to time created/ current time
		time(searchTree.nodes[i].id.substr(1));
		//readjust score
		for(j in peers){
			//add peer's score if they like the page
			likedTree(peers[j],searchTree.nodes[i].id.substr(1))		
		}
	}
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

//function for weight sliders
function weightSliders (divID){
	//array containing labels for sliders
	var labels={like:'like similarity',follow:'follow similarity',trust:'trust<br/>&nbsp',popular:'popularity<br/>&nbsp'};
	$("#"+divID).prepend('<div id="sliders"></div>');
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
		  slide:function( event, ui ) {
				//update the scale
				scale[this.id]=ui.value;
				//update peer scores with new scale
				for(i in peers){
					peers[i].score=peers[i].trust*scale['trust']+peers[i].likesim*scale['like']+peers[i].followsim*scale['follow']+peers[i].popularity*scale['popular'];
				}
				if(pattern1.test(document.URL)||!pattern3.test(document.URL)){
					//rescore and display the tree
					rescoreTree();
					//delete the old tree
					$('#tree_container_div').remove();
					$("#tree_zoommessage").remove();
					$('#instruction').remove();
					mTree = new TreeControl(searchTree, "socialwiki_content_area");
					mTree.display();
				}else if(pattern2.test(document.URL)){
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
	searchTree.nodes = searchResults.nodes;
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
