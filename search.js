console.log("search.js included");

var searchTree = new tree();

searchTree.nodes = searchResults.nodes;
mTree = new TreeControl(searchTree, "socialwiki_content_area");
console.log(searchResults);
$(document).ready(function() {
	$(".phptree").css("display", "none");
    mTree.display();
});
