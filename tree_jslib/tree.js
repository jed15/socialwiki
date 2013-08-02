/* This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.*/
/*Cameron Blanchard
 * July 5, 2013*/
/* In this file:
 * class tree
 * class node
 * class TreeControl*/

function TreeControl(myTree, divID) {

        /*Need to make these hidden elements, or else the css won't load in time*/
        $('head').prepend('<link rel="stylesheet" type="text/css" href="' + $('script[src$="tree.js"]').attr('src').replace('tree.js', '') + 'tree_styles.css"></link>');
        $("#" + divID).append('<ul class="tree_column" style="display:none"></ul>');
        $("#" + divID).append('<ul class="tree_node hideme" style="display:none"></ul>');
        $("#" + divID).append('<div class="relation_line hideme" style="display:none"></div>');
        $(".wikititle").append("<p class='colourtext' id='tree_zoommessage'>Return to original zoom level or refresh the page for the best experience</p>");
        $("#tree_zoommessage").css("display", "none");
        $("#" + divID).css('text-align', 'left');
		//add instruction text before tree
		$("#"+divID).append('<p id="instruction" class="colourtext">Click On A Box To Display Parent</p>');
        $("#" + divID).append('<div id="tree_container_div" class="tree_container"></div>');
        $("#tree_container_div").append('<div id="tree_div" class="tree_wrapper"></div>');

        /*Object variables*/
        divID = "tree_div";
        this.divID = "tree_div";
        this.columns = Array(); //2 dimensional array, stores the structure of the tree's columns before adding them to html
        this.childDepths = Array();
        this.myTree = myTree;
        this.divID = divID;
        this.zoom = document.documentElement.clientWidth / window.innerWidth;

        /*Functions*/
        this.toggleParent = toggleParent;
        this.showNode = showNode;
        this.hideNode = hideNode;
        this.hideChildren = hideChildren;
        this.addNodeToColumn = addNodeToColumn;
        this.updateNodePositions = updateNodePositions;
        this.updateLines = updateLines;
        this.display = display;
        this.showTree = showTree;
        this.async = async;
        this.when = when;
        this.cssLoaded = cssLoaded;
        this.addListElements = addListElements;

        $(window).resize(this, function(e){
        newZoom = document.documentElement.clientWidth / window.innerWidth;
        if (newZoom != this.zoom)
        {/*
                $(".relation_line").empty();
                $(".tree_node").empty();
                e.data.addListElements();

                e.data.updateNodePositions();

                //Make sure that the container is not scrolled at all, to avoid all kinds of nasty positioning problems
                $("#" + e.data.divID).scrollTop(0);

                e.data.updateLines();*/
        }
        });     


        /*Runs a function fn after 20 milliseconds*/

        function async(fn) {
                setTimeout(fn, 20);
        }

        /*Runs the function fn() with params once the conditional function cond() is true*/

        function when(cond, fn, params) {
                async(function () {
                        if (cond()) {
                                fn.call(params);
                        } else {
                                async(arguments.callee);
                        }
                });
        }

        /*Conditional function for when() to check whether or not css has been loaded yet*/

        function cssLoaded() {
                return $('.relation_line').css("position") == "absolute";
        }

        /*Displays the tree once the css has been loaded*/

        function display() {
                when(cssLoaded, this.showTree, this);
        }

        /*The main function for the tree control. 
         *IF CSS IS NOT LOADED AT THIS POINT,  NONE OF THE TREE WILL BE POSITIONED CORRECTLY*/

        function showTree() {
                //Find the maximum depth of the tree
                for (var node in this.myTree.nodes) {
                        if (this.myTree.nodes[node].children.length == 0) {
                                this.childDepths.push(this.myTree.depth(node));
                        }
                }

                this.maxDepth = Math.max.apply(Math, this.childDepths);

                //Building the columns structure, need to do this before anything can be added to html
                for (var node in this.myTree.nodes) {
                        this.addNodeToColumn(this.myTree.nodes[node].id, this.maxDepth);
                }

                this.addListElements();

                this.updateNodePositions();

                //Make sure that the container is not scrolled at all, to avoid all kinds of nasty positioning problems
                $("#" + this.divID).scrollTop(0);

                this.updateLines();
        }


        /* Toggles a node's parent's visibility
         * The tree behaves such that clicking on a node with a visible parent will hide its parent and all other branches descended from that parent
         * Clicking on a node with a hidden parent will show the parent, and all branches descended from that parent
         * If a node to be hidden is the last visible child of its parent, this parent will also be hidden; this acts recursively*/

        function toggleParent(id) {
                //Do nothing when a root node is clicked, there are  no parents
                if (this.myTree.nodes[id].parent == -1) {
                        return;
                }

                //Hide nodes
                if (this.myTree.nodes[this.myTree.nodes[id].parent].hidden == false) {
                        this.hideNode(this.myTree.nodes[id].parent);
                        childrenOf = this.myTree.nodes[id].parent;
                        this.hideChildren(childrenOf, id);
                }
                //Show nodes
                else {
                        this.showNode(this.myTree.nodes[id].parent);
                }
        }

        /*Shows a node, and all of its descendant branches*/

        function showNode(id) {
                this.myTree.nodes[id].hidden = false;

                $('#tree_' + id).stop();
                $('#tree_' + id).animate({
                        opacity: 1
                }, duration = 500);
                $('#tree_' + id).css("visibility", "visible");

                $('[id^=line_' + id + 'b]').stop();
                $('[id^=line_' + id + 'b]').animate({
                        opacity: 1
                }, duration = 500);
                $('[id^=line_' + id + 'b]').css("visibility", "visible");

                $('[id^=line_tick_' + id + 'b]').stop();
                $('[id^=line_tick_' + id + 'b]').animate({
                        opacity: 1
                }, duration = 500);
                $('[id^=line_tick_' + id + 'b]').css("visibility", "visible");

                $('[id^=line_extender_' + id + 'b]').stop();
                $('[id^=line_extender_' + id + 'b]').animate({
                        opacity: 1
                }, duration = 500);
                $('[id^=line_extender_' + id + 'b]').css("visibility", "visible");

                if (this.myTree.nodes[id].parent != -1) {
                        $('#line_' + this.myTree.nodes[id].parent + 'b' + id).stop();
                        $('#line_' + this.myTree.nodes[id].parent + 'b' + id).animate({
                                opacity: 1
                        }, duration = 500);
                        $('#line_' + this.myTree.nodes[id].parent + 'b' + id).css("visibility", "visible");
                }

                for (var i = 0; i < this.myTree.nodes[id].children.length; i++) {
                        this.showNode(this.myTree.nodes[id].children[i])
                }
        }

        /*Hides a node  and all of its other descendant branches. If the node is the last visible child of another node, hide the parent node as well*/

        function hideNode(id) {
                if (id == -1) {
                        return
                }

                this.myTree.nodes[id].hidden = true;
                var flag = false;

                //Stop animations to prevent wierd lag
                $('[id^=line_' + id + 'b]').stop();
                $('[id^=line_tick_' + id + 'b]').stop();
                $('[id^=line_extender_' + id + 'b]').stop();
                $('#line_' + this.myTree.nodes[id].parent + 'b' + id).stop();
                $('#tree_' + id).stop();

                //Animate the fading of the elements
                $('[id^=line_' + id + 'b]').animate({
                        opacity: 0
                }, duration = 500, complete = function () {
                        $(this).css("visibility", "hidden")
                });
                $('[id^=line_tick_' + id + 'b]').animate({
                        opacity: 0
                }, duration = 500, complete = function () {
                        $(this).css("visibility", "hidden")
                });
                $('[id^=line_extender_' + id + 'b]').animate({
                        opacity: 0
                }, duration = 500, complete = function () {
                        $(this).css("visibility", "hidden")
                });
                $('#line_' + this.myTree.nodes[id].parent + 'b' + id).animate({
                        opacity: 0
                }, duration = 500, complete = function () {
                        $(this).css("visibility", "hidden")
                });
                $('#tree_' + id).animate({
                        opacity: 0
                }, duration = 500, complete = function () {
                        $(this).css("visibility", "hidden")
                });

                //If the node being hidden is the last visible child of its parent, its parent should be hidden as well
                pid = this.myTree.nodes[id].parent;
                if (pid != -1) {
                        for (var i = 0; i < this.myTree.nodes[pid].children.length; i++) {
                                if (!this.myTree.nodes[this.myTree.nodes[pid].children[i]].hidden) {
                                        flag = true;
                                }
                        }
                }
                if (flag == false) {
                        this.hideNode(pid);
                }

        }

        /*Hides all the children of a node, except the node specifed by noHide*/

        function hideChildren(id, noHide) {
                for (var x = 0; x < this.myTree.nodes[id].children.length; x++) {
                        if (this.myTree.nodes[id].children[x] != noHide && this.myTree.nodes[this.myTree.nodes[id].children[x]].column != 0 && this.myTree.nodes[this.myTree.nodes[id].children[x]].children.length > 0) {
                                this.hideNode(this.myTree.nodes[id].children[x]);
                                this.hideChildren(this.myTree.nodes[id].children[x], id);
                        }
                }
        }


        /* Adds the node with id id to the tree's column array.
         *The order in which nodes are added to each column is a priority system:
         *1. Sibling nodes are all grouped together in each column
         *2. Cousins and relations in the same column are all grouped together
         *3. Nodes with a higher priority are added closer to the start of the column*/

        function addNodeToColumn(id, treeDepth) {               
			   var level = treeDepth - this.myTree.depth(id);
                if (this.myTree.nodes[id].children.length == 0) {
                        level = 0;
                }

                //Making sure there are enough columns to hold a node at this level, if not, add one
                while (level >= (this.columns.length)) {
                        this.columns.push([]);
                        $('#' + this.divID).append('<ul class="tree_column" id="tree_col' + (this.columns.length - 1) + '"></ul>');
                }

                if (this.columns[level].indexOf(id) < 0) {
                        var firstSiblingLocation = -1;
                        var myRelations = this.myTree.getRelationsInColumn(id, level);

                        /*Go through all the related nodes in this column, see if they have been added yet
                         *If so, add the new node in a position adjacent to thiers, to avoid drawing lines over nodes*/
                        if (myTree.nodes[id].parent != -1) {

                                for (var i = 0; i < myRelations.length; i++) {
                                        
                                        if (this.myTree.nodes[myRelations[i]].parent == this.myTree.nodes[id].parent) {
											   firstSiblingLocation = this.columns[level].indexOf(myRelations[i]);
											   break;
                                        }

                                }
								
								
                                /*If there is a sibling that has already been added to the column, check to see which priority is higher
                                 *Splice the new node into the array above or below the related node, depending on the priority
                                 *If no sibling has been added, we need to go through the column to find the place that it should be inserted based on its priority*/
                                if (firstSiblingLocation >= 0) {
                                        if (this.myTree.nodes[id].hasOwnProperty('priority')) {
                                                if (this.myTree.nodes[id].priority > this.myTree.nodes[this.columns[level][firstSiblingLocation]].priority) {
                                                        this.columns[level].splice(firstSiblingLocation, 0, id);
                                                } else {
                                                        this.columns[level].splice(firstSiblingLocation + 1, 0, id);
                                                }
                                        } else {
                                                this.columns[level].splice(firstSiblingLocation + 1, 0, id);
                                        }
                                } else {
                                        if (this.myTree.nodes[id].hasOwnProperty('priority')) {
                                                var spliceAt = -1;

                                                for (var p = 0; p < this.columns[level].length; p++) {
                                                        if (this.myTree.nodes[this.columns[level][p]].priority <= this.myTree.nodes[id]) {
                                                                spliceAt = p;
                                                        }
                                                }
                                                if (spliceAt != -1) {
                                                        this.columns[level].splice(spliceAt, 0, id);
                                                } else {
                                                        this.columns[level].push(id);
                                                }
                                        } else {
                                                this.columns[level].push(id);
                                        }
                                }
                        } else {
                                this.columns[level].push(id);
                        }
                        this.myTree.nodes[id].column = level;
						
                }
        }

        /*Adds an html list element for each node in the tree. The columns array should be built at this point, or no list items will be added*/

        function addListElements() {
                for (var i = 0; i < this.columns.length; i++) {
                        for (var j = 0; j < this.columns[i].length; j++) {
                                $('#tree_col' + i).append('<li tabindex=5 class="tree_node" id="tree_' + this.columns[i][j] + '" index=' + this.columns[i][j] + '><p id="socialwiki_node_description" class="test_label colourtext">' + this.myTree.nodes[this.columns[i][j]].content + '</p></li><br/>  ');
                                if (i != 0) {
                                        $('#tree_' + this.columns[i][j]).css("opacity", "0");
                                        $('#tree_' + this.columns[i][j]).css("visibility", "hidden");
                                        this.myTree.nodes[this.columns[i][j]].hidden = true;
                                }

                                $('#tree_' + this.columns[i][j]).click(this, function (e) {
                                        var pattern = /^tree_/;
                                        if (e.target.attributes.id.ownerElement.id == "socialwiki_node_description" || pattern.test(e.target.attributes.id.ownerElement.id)) {
                                                e.data.toggleParent($(this).attr("index"));
                                        }
                                });

                                //to make this somewhat more accessible, provide some keyboard navigation through tabbing and pressing enter. This should be improved - look at using arrow keys to navigate around the tree
                                $('#tree_' + this.columns[i][j]).keypress(function (e) {
                                        if (e.which == 13) {
                                                $(this).click();
                                        }
                                });
                        }
                }
        }


        /*Positions the nodes. Nodes are positioned so that they are at the vertical center of all of their children. Nodes are not moved horizontally*/

        function updateNodePositions() {
                for (var j = 0; j < this.columns.length; j++) {
                        for (var i = 0; i < this.columns[j].length; i++) {
                                if (j == 0) {
                                        continue;
                                }
                                childNodes = this.myTree.nodes[this.columns[j][i]].children;
                                var numChildren = 0;
                                var totY = 0;

                                for (var k = 0; k < childNodes.length; k++) {
                                        totY += $('#tree_' + childNodes[k]).offset().top;
                                        numChildren += 1;
                                }
                                var avY = $('#tree_' + this.columns[j][i]).offset().top;
                                var avY = totY / numChildren;
                                var myY = $('#tree_' + this.columns[j][i]).offset().top;
                                var offset = avY - myY;
                                $('#tree_' + this.columns[j][i]).css("position", "relative");
                                $('#tree_' + this.columns[j][i]).css("top", offset + "px");
                        }
                }
        }

        /*Adds and positions the lines that show relationships
         *line_extender - vertical lines that span from the first child node to the last child node
         *line_ - horizontal lines that connect the child nodes to the extender line for their parent
         *line_tick - horizontal lines that connect each parent to their extender line*/

        function updateLines() {
                for (var j = 0; j < this.columns.length; j++) {
                        for (var n = 0; n < this.columns[j].length; n++) {
                                var childrenTops = Array();
                                if (this.myTree.nodes[this.columns[j][n]].children.length > 0) {
                                        //Add and position the little lines that connect the extenders to the nodes
                                        $('#' + this.divID).append('<div class="relation_line" id = "line_tick_' + this.columns[j][n] + 'b""></div>')
                                        lineTop = $('#tree_' + this.myTree.nodes[this.columns[j][n]].id).position().top + parseInt($('#tree_' + this.myTree.nodes[this.columns[j][n]].id).css("height")) / 2 + parseInt($('#tree_' + this.myTree.nodes[this.columns[j][n]].id).css("margin-top"));
                                        lineLeft = $('#tree_' + this.myTree.nodes[this.columns[j][n]].id).position().left + parseInt($('#tree_' + this.myTree.nodes[this.columns[j][n]].id).css("margin-left"));
                                        $('#line_tick_' + this.columns[j][n] + 'b').css("top", lineTop);
                                        $('#line_tick_' + this.columns[j][n] + 'b').css("left", lineLeft);
                                        $('#line_tick_' + this.columns[j][n] + 'b').css("height", "12px");
                                        $('#line_tick_' + this.columns[j][n] + 'b').css("transform", "rotate(" + Math.PI / 2 + "rad)");
                                        $('#line_tick_' + this.columns[j][n] + 'b').css("-webkit-transform", "rotate(" + Math.PI / 2 + "rad)");
                                        $('#line_tick_' + this.columns[j][n] + 'b').css("-ms-transform", "rotate(" + Math.PI / 2 + "rad)");
                                        $('#line_tick_' + this.columns[j][n] + 'b').css("visiblity", "hidden");
                                        $('#line_tick_' + this.columns[j][n] + 'b').css("opacity", "0");

                                        //Add the extender lines
                                        $('#' + this.divID).append('<div class="relation_line" id = "line_extender_' + this.columns[j][n] + 'b""></div>')
                                        $('#line_extender_' + this.columns[j][n] + 'b').css("visibility", "hidden");
                                        $('#line_extender_' + this.columns[j][n] + 'b').css("opacity", "0");
                                }

                                //Add and position the main lines
                                for (var m = 0; m < this.myTree.nodes[this.columns[j][n]].children.length; m++) {
                                        $('#' + this.divID + '').append('<div class="relation_line" id = "line_' + this.columns[j][n] + 'b' + this.myTree.nodes[this.columns[j][n]].children[m] + '""></div>');
                                        lineBottom = $('#tree_' + this.myTree.nodes[this.columns[j][n]].children[m]).position().top + parseInt($('#tree_' + this.myTree.nodes[this.columns[j][n]].id).css("height")) / 2 + parseInt($('#tree_' + this.myTree.nodes[this.columns[j][n]].id).css("margin-top"));
                                        lineRight = $('#tree_' + this.myTree.nodes[this.columns[j][n]].children[m]).position().left + parseInt($('#tree_' + this.myTree.nodes[this.columns[j][n]].id).css("width")) + parseInt($('#tree_' + this.myTree.nodes[this.columns[j][n]].id).css("margin-top")) + 13;
                                        childrenTops.push(lineBottom);
                                        length = lineLeft - lineRight - 15;
                                        $('#line_' + this.columns[j][n] + 'b' + this.myTree.nodes[this.columns[j][n]].children[m]).css("height", length);
                                        $('#line_' + this.columns[j][n] + 'b' + this.myTree.nodes[this.columns[j][n]].children[m]).css("top", lineBottom);
                                        $('#line_' + this.columns[j][n] + 'b' + this.myTree.nodes[this.columns[j][n]].children[m]).css("left", lineRight + length);
                                        $('#line_' + this.columns[j][n] + 'b' + this.myTree.nodes[this.columns[j][n]].children[m]).css("transform", 'rotate(' + ((Math.PI / 2)) + 'rad)');
                                        $('#line_' + this.columns[j][n] + 'b' + this.myTree.nodes[this.columns[j][n]].children[m]).css("-webkit-transform", 'rotate(' + ((Math.PI / 2)) + 'rad)');
                                        $('#line_' + this.columns[j][n] + 'b' + this.myTree.nodes[this.columns[j][n]].children[m]).css("-ms-transform", 'rotate(' + ((Math.PI / 2)) + 'rad)');
                                        $('#line_' + this.columns[j][n] + 'b' + this.myTree.nodes[this.columns[j][n]].children[m]).css("opacity", "0");
                                        $('#line_' + this.columns[j][n] + 'b' + this.myTree.nodes[this.columns[j][n]].children[m]).css("visibility", "hidden");
                                }

                                //Position the extender lines
                                if (this.myTree.nodes[this.columns[j][n]].children.length > 0) {
                                        var extenderLength = Math.max.apply(Math, childrenTops) - Math.min.apply(Math, childrenTops) + 3;
                                        var lineLeft = $('#tree_' + this.myTree.nodes[this.columns[j][n]].id).position().left + parseInt($('#tree_' + this.myTree.nodes[this.columns[j][n]].id).css("margin-left")) - 15;
                                        $('#line_extender_' + this.columns[j][n] + 'b').css("top", Math.min.apply(Math, childrenTops));
                                        $('#line_extender_' + this.columns[j][n] + 'b').css("left", lineLeft);
                                        $('#line_extender_' + this.columns[j][n] + 'b').css("height", extenderLength);
                                }
                        }
                }
        }
}


/*Prototype of a node
 * hide() sets the node to be hidden
 * show() sets the node to be visible
 * parent - the single parent of a node
 * children - list of the ids of the node's children
 * column - the column in the tree display that the node is assigned to*/

function node(id, parent, content, priority) {
        this.id = id;
        this.content = content;
        this.parent = parent;
        this.children = new Array();
        this.hidden = false;
        this.hide = hide;
        this.show = show;
        this.added = false;
        this.level = -1;
        this.family = -1;
        this.priority = priority || 0;

        function hide() {
                this.hidden = true;
        }

        function show() {
                this.hidden = false;
        }
}


/*
 * addNode() - adds a new node that is the child of the node specified by parentID
 * depth() - gets the distance of a node from the root node
 * nodes - list of all the node objects in the tree
 * */

function tree() {
        this.nodes = new Array();
        this.idCount = 0;
        this.addNode = addNode;
        this.depth = depth;
        this.addRoot = addRoot;
        this.getRoot = getRoot;
        this.getRelationsInColumn = getRelationsInColumn;

        function addRoot() {
                this.nodes['l' + this.idCount] = new node('l' + this.idCount, -1, this.idCount);
                this.idCount++;
        }

        function addNode(parentID) {
                this.nodes['l' + this.idCount] = new node('l' + this.idCount, 'l' + parentID, this.idCount, 0);

                if (this.nodes['l' + parentID].parent == -1) {
                        this.nodes['l' + this.idCount].family = 'l' + this.idCount;
                } else {
                        this.nodes['l' + this.idCount].family = this.nodes['l' + parentID].family;
                }
                this.nodes['l' + parentID].children.push(['l' + this.idCount]);
                this.idCount++;
        }

        /* Finds the depth of a node*/

        function depth(ID) {
                if (ID[0] != 'l') {
                        ID = 'l' + ID;
                }
                var depth = 0;
                var nextNode = this.nodes[ID];
                while (nextNode.parent != -1) {
                        depth += 1;
                        nextNode = this.nodes[nextNode.parent];
                }
                return depth;
        }

        /*FInds the root node of the tree that id belongs to*/

        function getRoot(id) {
                nextNode = this.nodes[id];
                while (nextNode.parent != -1) {
                        nextNode = this.nodes[nextNode.parent];
                }
                return nextNode;
        }

        /*Returns a list of all the relations that a node has in a column*/

        function getRelationsInColumn(id, column) {
                var root = this.getRoot(id);
                var returnList = Array();
                for (var node in this.nodes) {
                        if (this.nodes[node].column == column && this.getRoot(node) == root) {
                                returnList.push(node);
                        }
                }
                return returnList;
        }
}
