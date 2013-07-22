socialwiki
==========
Installation:

Copy socialwiki directory to moodle's mod directory.
Navigate to the website's Notifications page, follow directions on screen.

New Styles:
to add a new style create a new css file name it stylename_style.css
add your style name to the array in the locallib.php function socialwiki_get_styles
add a string in language file with the name you added in locallib set it equal to what you want displayed in the selector
all pictures are in the pix folder
changes to pix names must be fixed in renerer.php
to hide tabs your style must hide the tabtree class to hide the toolbar the socialwiki_container must be hidden

