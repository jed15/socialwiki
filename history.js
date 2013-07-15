console.log("history.js included");

var historyTree = new tree();

historyTree.nodes = searchResults.nodes;

mTree = new TreeControl(historyTree, "socialwiki_content_area");

$(document).ready(function() {
	$(".phptree").css("display", "none");
    mTree.display();
});
