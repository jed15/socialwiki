console.log("search.js included");

var searchTree = new tree();
var zoom = document.documentElement.clientWidth / window.innerWidth;

searchTree.nodes = searchResults.nodes;
mTree = new TreeControl(searchTree, "socialwiki_content_area");
$(document).ready(function() {
	$(".phptree").css("display", "none");
    mTree.display();
    
    $(window).resize(this, function(e){
        newZoom = document.documentElement.clientWidth / window.innerWidth;
        if (newZoom != zoom)
        {
                $(".relation_line").css("display", "none");
                $(".tree_node").css("display", "none");
                $(".phptree").css("display",  "block");
        }
});
});
