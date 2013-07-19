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
});
