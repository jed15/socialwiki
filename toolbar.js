$(document).ready(function(){
    
    /*Making use of these buttons would require some heavy modifications
    $("#socialwiki_backbutton").css("display", "inline-block");
    $("#socialwiki_forwardbutton").css("display", "inline-block");
    */
    
    //The searchbox should be cleared automatically when the user clicks on it, and "Search..." should appear if box has lost focus
    $("#socialwiki_searchbox").click(function()
    {
        $("#socialwiki_searchbox").attr("value", "");
            
    });
        
    $("#socialwiki_searchbox").bind("blur", function()
    {
        $("#socialwiki_searchbox").attr("value", "Search...");
    });
    
    $(".socialwiki_toolbarlink").html("");

});
