var pattern1=/option=1/;
var pattern2=/otion=2/;
var pattern3=/option/;
function liked(peer,pageid){
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
			liked(peers[j],searchTree.nodes[i].id.substr(1))		
		}
	}
}

//function for weight sliders
function weightSliders (divID){
	$("#"+divID).prepend('<div id="sliders"></div>');
	//set up the sliders for each weighted score
	for(option in scale){
		$('#sliders').append('<div id="'+option+'container" class="slidecontainer"></div>')
		$('#'+option+'container').append('<div id="'+option+'"class="socialwiki_slider"></div>');
		$('#'+option+'container').append('<span>'+option+'</span>');
		$('#'+option).slider({
		  orientation: "vertical",
		  range: "min",
		  min: 0,
		  max:4,
		  step:1,
		  value:scale[option],
		  slide:function( event, ui ) {
				scale[this.id]=ui.value;
				for(i in peers){
					peers[i].score=peers[i].trust*scale['trust']+peers[i].likesim*scale['like']+peers[i].followsim*scale['follow']+peers[i].popularity*scale['popular'];
				}
				rescoreTree();
				//delete the old tree
				$('#tree_container_div').remove();
				$("#tree_zoommessage").remove();
				$('#instruction').remove();
				mTree = new TreeControl(searchTree, "socialwiki_content_area");
				mTree.display();
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
	
	//only display tree if in tree view
   if(pattern1.test(document.URL)||!pattern3.test(document.URL)){
		//add weight sliders
		weightSliders("socialwiki_content_area");
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
