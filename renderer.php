<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Moodle socialwiki 2.0 Renderer
 *
 * @package   mod-socialwiki
 * @copyright 2010 Dongsheng Cai <dongsheng@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class mod_socialwiki_renderer extends plugin_renderer_base {
    public function page_index() {
        global $CFG;
        $html = '';
        // Checking wiki instance
        if (!$wiki = socialwiki_get_wiki($this->page->cm->instance)) {
            return false;
        }

        // @TODO: Fix call to socialwiki_get_subwiki_by_group
        $gid = groups_get_activity_group($this->page->cm);
        $gid = !empty($gid) ? $gid : 0;
        if (!$subwiki = socialwiki_get_subwiki_by_group($this->page->cm->instance, $gid)) {
            return false;
        }
        $swid = $subwiki->id;
        $pages = socialwiki_get_page_list($swid);
        $selectoptions = array();
        foreach ($pages as $page) {
            $selectoptions[$page->id] = format_string($page->title, true, array('context' => $this->page->context));
        }
        $label = get_string('pageindex', 'socialwiki') . ': ';
        $select = new single_select(new moodle_url('/mod/socialwiki/view.php'), 'pageid', $selectoptions);
        $select->label = $label;
        return $this->output->container($this->output->render($select), 'socialwiki_index');
    }

	//compares two pages
    public function diff($pageid, $old, $new) {
        global $CFG;
		$page=socialwiki_get_page($pageid);
	   if (!empty($options['total'])) {
            $total = $options['total'];
        } else {
            $total = 0;
        }
        $diff1 = format_text($old->diff, FORMAT_HTML, array('overflowdiv'=>true));
        $diff2 = format_text($new->diff, FORMAT_HTML, array('overflowdiv'=>true));
        $strdatetime = get_string('strftimedatetime', 'langconfig');

        $olduser = $old->user;
        $versionlink = new moodle_url('/mod/socialwiki/view.php', array('pageid' => $old->pageid));
        $userlink = new moodle_url('/mod/socialwiki/viewuserpages.php', array('userid' => $olduser->id, 'subwikiid' => $page->subwikiid));
        // view version link
        $oldversionview = ' ';
        $oldversionview .= html_writer::link($versionlink->out(false), get_string('view', 'socialwiki'), array('class' => 'socialwiki_diffview'));

        // userinfo container
        $oldheading = $this->output->container_start('socialwiki_diffuserleft');
        // username
        $oldheading .= html_writer::link($CFG->wwwroot . '/mod/socialwiki/viewuserpages.php?userid=' . $olduser->id.'&subwikiid='.$page->subwikiid, fullname($olduser)) . '&nbsp;';
        // user picture
        $oldheading .= html_writer::link($userlink->out(false), $this->output->user_picture($olduser, array('popup' => true)), array('class' => 'notunderlined'));
        $oldheading .= $this->output->container_end();

        // version number container
        $oldheading .= $this->output->container_start('socialwiki_diffversion');
        $oldheading .= get_string('page') . ' ' . $old->pageid . $oldversionview;
        $oldheading .= $this->output->container_end();
        // userdate container
        $oldheading .= $this->output->container_start('socialwiki_difftime');
        $oldheading .= userdate($old->timecreated, $strdatetime);
        $oldheading .= $this->output->container_end();

        $newuser = $new->user;
        $versionlink = new moodle_url('/mod/socialwiki/view.php', array('pageid' => $new->pageid));
        $userlink = new moodle_url('/mod/socialwiki/viewuserpages.php', array('userid' => $newuser->id, 'subwikiid' => $page->subwikiid));

        $newversionview = ' ';
        $newversionview .= html_writer::link($versionlink->out(false), get_string('view', 'socialwiki'), array('class' => 'socialwiki_diffview'));
        // new user info
        $newheading = $this->output->container_start('socialwiki_diffuserright');
        $newheading .= $this->output->user_picture($newuser, array('popup' => true));

        $newheading .= html_writer::link($userlink->out(false), fullname($newuser), array('class' => 'notunderlined'));
        $newheading .= $this->output->container_end();

        // version
        $newheading .= $this->output->container_start('socialwiki_diffversion');
        $newheading .= get_string('page') . '&nbsp;' . $new->pageid . $newversionview;
        $newheading .= $this->output->container_end();
        // userdate
        $newheading .= $this->output->container_start('socialwiki_difftime');
        $newheading .= userdate($new->timecreated, $strdatetime);
        $newheading .= $this->output->container_end();

        $oldheading = html_writer::tag('div', $oldheading, array('class'=>'socialwiki-diff-heading header clearfix'));
        $newheading = html_writer::tag('div', $newheading, array('class'=>'socialwiki-diff-heading header clearfix'));

        $html  = '';
        $html .= html_writer::start_tag('div', array('class'=>'socialwiki-diff-container clearfix'));
        $html .= html_writer::tag('div', $oldheading.$diff1, array('class'=>'socialwiki-diff-leftside'));
        $html .= html_writer::tag('div', $newheading.$diff2, array('class'=>'socialwiki-diff-rightside'));
        $html .= html_writer::end_tag('div');
	
		//add the paging bars
		$html .= '<div class="socialwiki_diff_paging">';
		$html .= $this->output->container($this->diff_paging_bar( $old->pageid, $CFG->wwwroot . '/mod/socialwiki/diff.php?pageid=' . $pageid . '&amp;comparewith=' . $new->pageid . '&amp;', 'compare', false, true), 'socialwiki_diff_oldpaging');
		$html .= $this->output->container($this->diff_paging_bar($new->pageid, $CFG->wwwroot . '/mod/socialwiki/diff.php?pageid=' . $pageid . '&amp;compare=' . $old->pageid . '&amp;', 'comparewith', false, true), 'socialwiki_diff_newpaging');
		$html.='</div>';

        return $html;
    }

    /**
     * Prints a single paging bar to provide access to other versions
     *
     * @param int $pageid The pageid of one of the pages being compared
     * @param mixed $baseurl If this  is a string then it is the url which will be appended with $pagevar, an equals sign and the page number.
     *                          If this is a moodle_url object then the pagevar param will be replaced by the page no, for each page.
     * @param string $pagevar This is the variable name that you use for the page number in your code (ie. 'tablepage', 'blogpage', etc)
     * @param bool $nocurr do not display the current page as a link
     * @param bool $return whether to return an output string or echo now
     * @return bool or string
     */
    public function diff_paging_bar($pageid, $baseurl, $pagevar = 'page', $nocurr = false) {
        //get all pages related to the page being compared
		$relations=socialwiki_get_relations($pageid);
		//get the index of the current page id in the array
		$pageindex=socialwiki_indexof_page($pageid,$relations);
		$totalcount = count($relations)-1;
        $maxdisplay = 2;
        $html = '';
		
		if($pageindex==-1){
			print_error('invalidparameters','socialwiki');
		}
		//if there is more than one page create html for paging bar
		if ($totalcount > 1) {
            $html .= '<div class="paging">';
			
			//add first link to first page
			if($pageindex!=0){
			
				//print link to parent page
				if (!is_a($baseurl, 'moodle_url')) {
					$html .= '&nbsp;<a href="' . $baseurl . $pagevar . '=' . $relations[0]->id . '">' . $relations[0]->id . '</a>&nbsp;';
				} else {
					$html .= '&nbsp;<a href="' . $baseurl->out(false, array($pagevar => $relations[0]->id)) . '">' . $relations[0]->id . '</a>&nbsp;';
				}
				
				//print link to page before current
				if($pageindex>2){
					//print page that is before the current page in relations array
					if (!is_a($baseurl, 'moodle_url')) {
						$html .= '...&nbsp;<a href="' . $baseurl . $pagevar . '=' . $relations[$pageindex-1]->id . '">' . $relations[$pageindex-1]->id. '</a>&nbsp;';
					} else {
						$html .= '...&nbsp;<a href="' . $baseurl->out(false, array($pagevar => $relations[$pageindex-1]->id)) . '">' . $relations[$pageindex-1]->id . '</a>&nbsp;';
					}
				}else if($pageindex>1){
					if (!is_a($baseurl, 'moodle_url')) {
						$html .= '&nbsp;<a href="' . $baseurl . $pagevar . '=' . $relations[$pageindex-1]->id . '">' . $relations[$pageindex-1]->id . '</a>&nbsp;';
					} else {
						$html .= '&nbsp;<a href="' . $baseurl->out(false, array($pagevar => $relations[$pageindex-1]->id)) . '">' . $relations[$pageindex-1]->id . '</a>&nbsp;';
					}
				}
			}
			//print current page
			$html.=$pageid;
			if($pageindex!=$totalcount){
				if($pageindex<$totalcount-2){
					if (!is_a($baseurl, 'moodle_url')) {
						$html .= '&nbsp;<a href="' . $baseurl . $pagevar . '=' . $relations[$pageindex+1]->id . '">' . $relations[$pageindex+1]->id . '</a>&nbsp;...';
					} else {
						$html .= '&nbsp;<a href="' . $baseurl->out(false, array($pagevar => $relations[$pageindex+1]->id)) . '">' . $relations[$pageindex+1]->id . '</a>&nbsp;...';
					}
				}else if($pageindex<$totalcount-1){
					if (!is_a($baseurl, 'moodle_url')) {
						$html .= '&nbsp;<a href="' . $baseurl . $pagevar . '=' . $relations[$pageindex+1]->id . '">' . $relations[$pageindex+1]->id . '</a>&nbsp;';
					} else {
						$html .= '&nbsp;<a href="' . $baseurl->out(false, array($pagevar => $relations[$pageindex+1]->id)) . '">' . $relations[$pageindex+1]->id . '</a>&nbsp;';
					}
				}
				//print last page in the array
				if (!is_a($baseurl, 'moodle_url')) {
						$html .= '&nbsp;<a href="' . $baseurl . $pagevar . '=' . $relations[$totalcount]->id . '">' . $relations[$totalcount]->id . '</a>&nbsp;';
					} else {
						$html .= '&nbsp;<a href="' . $baseurl->out(false, array($pagevar => $relations[$totalcount]->id)) . '">' . $relations[$totalcount]->id . '</a>&nbsp;';
					}
				}
		$html .= '</div>';
		}		
	 return $html;
	}

    public function socialwiki_info() {
        global $PAGE;
        return $this->output->box(format_module_intro('socialwiki', $this->page->activityrecord, $PAGE->cm->id), 'generalbox', 'intro');
    }

    public function tabs($page, $tabitems, $options) {
        global $PAGE;
        $tabs = array();
        $context = context_module::instance($this->page->cm->id);

        $pageid = null;
        if (!empty($page)) {
            $pageid = $page->id;
        }

        $selected = $options['activetab'];

        // make specific tab linked even it is active
        if (!empty($options['linkedwhenactive'])) {
            $linked = $options['linkedwhenactive'];
        } else {
            $linked = '';
        }

        if (!empty($options['inactivetabs'])) {
            $inactive = $options['inactivetabs'];
        } else {
            $inactive = array();
        }

        foreach ($tabitems as $tab) {
            if ($tab == 'edit' && !has_capability('mod/socialwiki:editpage', $context)) {
                continue;
            }
            if ($tab == 'comments' && !has_capability('mod/socialwiki:viewcomment', $context)) {
                continue;
            }
            if ($tab == 'files' && !has_capability('mod/socialwiki:viewpage', $context)) {
                continue;
            }
            if (($tab == 'view' || $tab == 'home' || $tab == 'history') && !has_capability('mod/socialwiki:viewpage', $context)) {
                continue;
            }
            if ($tab == 'admin' && !has_capability('mod/socialwiki:managewiki', $context)) {
                continue;
            }
            
            
            $link = new moodle_url('/mod/socialwiki/'. $tab. '.php', array('pageid' => $pageid));
			if($tab== 'home'){
				$link = new moodle_url('/mod/socialwiki/'. $tab. '.php', array('id' => $PAGE->cm->id));
			}
            if ($tab == 'like')
            {
                $link = new moodle_url('/mod/socialwiki/'. $tab. '.php', array('pageid' => $pageid, 'from' => $PAGE->url->out()));
            }elseif ($tab == 'unlike')
            {
                $link = new moodle_url('/mod/socialwiki/like.php', array('pageid' => $pageid, 'from' => $PAGE->url->out()));   
            }
            
            if ($tab == 'follow')
            {
                $link = new moodle_url('/mod/socialwiki/'. $tab. '.php', array('pageid' => $pageid, 'from' => $PAGE->url->out()));
            }elseif ($tab == 'unfollow')
            {
                $link = new moodle_url('/mod/socialwiki/follow.php', array('pageid' => $pageid, 'from' => $PAGE->url->out()));   
            }
            
            
            
            if ($linked == $tab) {
                $tabs[] = new tabobject($tab, $link, get_string($tab, 'socialwiki'), '', true);
            } else {
                $tabs[] = new tabobject($tab, $link, get_string($tab, 'socialwiki'));
            }
        }

        return $this->tabtree($tabs, $selected, $inactive);
    }

    public function prettyview_link($page) {
        $html = '';
        $link = new moodle_url('/mod/socialwiki/prettyview.php', array('pageid' => $page->id));
        $html .= $this->output->container_start('socialwiki_right');
        $html .= $this->output->action_link($link, get_string('prettyprint', 'socialwiki'), new popup_action('click', $link));
        $html .= $this->output->container_end();
        return $html;
    }

    public function socialwiki_print_subwiki_selector($wiki, $subwiki, $page, $pagetype = 'view') {
        global $CFG, $USER;
        require_once($CFG->dirroot . '/user/lib.php');
        switch ($pagetype) {
        case 'files':
            $baseurl = new moodle_url('/mod/socialwiki/files.php');
            break;
        case 'view':
        default:
            $baseurl = new moodle_url('/mod/socialwiki/view.php');
            break;
        }

        $cm = get_coursemodule_from_instance('socialwiki', $wiki->id);
        $context = context_module::instance($cm->id);
        // @TODO: A plenty of duplicated code below this lines.
        // Create private functions.
        switch (groups_get_activity_groupmode($cm)) {
        case NOGROUPS:
            if ($wiki->wikimode == 'collaborative') {
                // No need to print anything
                return;
            } else if ($wiki->wikimode == 'individual') {
                // We have private wikis here

                $view = has_capability('mod/socialwiki:viewpage', $context);
                $manage = has_capability('mod/socialwiki:managewiki', $context);

                // Only people with these capabilities can view all wikis
                if ($view && $manage) {
                    // @TODO: Print here a combo that contains all users.
                    $users = get_enrolled_users($context);
                    $options = array();
                    foreach ($users as $user) {
                        $options[$user->id] = fullname($user);
                    }

                    echo $this->output->container_start('socialwiki_right');
                    $params = array('wid' => $wiki->id, 'title' => $page->title);
                    if ($pagetype == 'files') {
                        $params['pageid'] = $page->id;
                    }
                    $baseurl->params($params);
                    $name = 'uid';
                    $selected = $subwiki->userid;
                    echo $this->output->single_select($baseurl, $name, $options, $selected);
                    echo $this->output->container_end();
                }
                return;
            } else {
                // error
                return;
            }
        case SEPARATEGROUPS:
            if ($wiki->wikimode == 'collaborative') {
                // We need to print a select to choose a course group

                $params = array('wid'=>$wiki->id, 'title'=>$page->title);
                if ($pagetype == 'files') {
                    $params['pageid'] = $page->id;
                }
                $baseurl->params($params);

                echo $this->output->container_start('socialwiki_right');
                groups_print_activity_menu($cm, $baseurl);
                echo $this->output->container_end();
                return;
            } else if ($wiki->wikimode == 'individual') {
                //  @TODO: Print here a combo that contains all users of that subwiki.
                $view = has_capability('mod/socialwiki:viewpage', $context);
                $manage = has_capability('mod/socialwiki:managewiki', $context);

                // Only people with these capabilities can view all wikis
                if ($view && $manage) {
                    $users = get_enrolled_users($context);
                    $options = array();
                    foreach ($users as $user) {
                        $groups = groups_get_all_groups($cm->course, $user->id);
                        if (!empty($groups)) {
                            foreach ($groups as $group) {
                                $options[$group->id][$group->name][$group->id . '-' . $user->id] = fullname($user);
                            }
                        } else {
                            $name = get_string('notingroup', 'socialwiki');
                            $options[0][$name]['0' . '-' . $user->id] = fullname($user);
                        }
                    }
                } else {
                    $group = groups_get_group($subwiki->groupid);
                    if (!$group) {
                        return;
                    }
                    $users = groups_get_members($subwiki->groupid);
                    foreach ($users as $user) {
                        $options[$group->id][$group->name][$group->id . '-' . $user->id] = fullname($user);
                    }
                }
                echo $this->output->container_start('socialwiki_right');
                $params = array('wid' => $wiki->id, 'title' => $page->title);
                if ($pagetype == 'files') {
                    $params['pageid'] = $page->id;
                }
                $baseurl->params($params);
                $name = 'groupanduser';
                $selected = $subwiki->groupid . '-' . $subwiki->userid;
                echo $this->output->single_select($baseurl, $name, $options, $selected);
                echo $this->output->container_end();

                return;

            } else {
                // error
                return;
            }
        CASE VISIBLEGROUPS:
            if ($wiki->wikimode == 'collaborative') {
                // We need to print a select to choose a course group
                // moodle_url will take care of encoding for us
                $params = array('wid'=>$wiki->id, 'title'=>$page->title);
                if ($pagetype == 'files') {
                    $params['pageid'] = $page->id;
                }
                $baseurl->params($params);

                echo $this->output->container_start('socialwiki_right');
                groups_print_activity_menu($cm, $baseurl);
                echo $this->output->container_end();
                return;

            } else if ($wiki->wikimode == 'individual') {
                $users = get_enrolled_users($context);
                $options = array();
                foreach ($users as $user) {
                    $groups = groups_get_all_groups($cm->course, $user->id);
                    if (!empty($groups)) {
                        foreach ($groups as $group) {
                            $options[$group->id][$group->name][$group->id . '-' . $user->id] = fullname($user);
                        }
                    } else {
                        $name = get_string('notingroup', 'socialwiki');
                        $options[0][$name]['0' . '-' . $user->id] = fullname($user);
                    }
                }

                echo $this->output->container_start('socialwiki_right');
                $params = array('wid' => $wiki->id, 'title' => $page->title);
                if ($pagetype == 'files') {
                    $params['pageid'] = $page->id;
                }
                $baseurl->params($params);
                $name = 'groupanduser';
                $selected = $subwiki->groupid . '-' . $subwiki->userid;
                echo $this->output->single_select($baseurl, $name, $options, $selected);
                echo $this->output->container_end();

                return;

            } else {
                // error
                return;
            }
        default:
            // error
            return;

        }

    }
	
	function menu_search($cmid, $currentselect,$searchstring) {
		Global $COURSE;
        $options = array('tree', 'list','popular');
        $items = array();
        foreach ($options as $opt) {
            $items[] = get_string($opt, 'socialwiki');
        }
        $selectoptions = array();
        foreach ($items as $key => $item) {
            $selectoptions[$key + 1] = $item;
        }
        $select = new single_select(new moodle_url('/mod/socialwiki/search.php',array('searchstring'=>$searchstring,'courseid'=>$COURSE->id,'cmid'=>$cmid)), 'option', $selectoptions, $currentselect);
        $select->label = get_string('searchmenu', 'socialwiki') . ': ';
        return $this->output->container($this->output->render($select), 'midpad colourtext');
    }

    function menu_home($cmid, $currentselect) {
        $options = array('userpages', 'orphaned','pagelist', 'updatedpages','teacherpages','recommended');
        $items = array();
        foreach ($options as $opt) {
            $items[] = get_string($opt, 'socialwiki');
        }
        $selectoptions = array();
        foreach ($items as $key => $item) {
            $selectoptions[$key + 1] = $item;
        }
        $select = new single_select(new moodle_url('/mod/socialwiki/home.php', array('id' => $cmid)), 'option', $selectoptions, $currentselect);
        $select->label = get_string('homemenu', 'socialwiki') . ': ';
        return $this->output->container($this->output->render($select), 'midpad colourtext');
    }
	
    public function socialwiki_files_tree($context, $subwiki) {
        return $this->render(new socialwiki_files_tree($context, $subwiki));
    }
	
    public function render_socialwiki_files_tree(socialwiki_files_tree $tree) {
        if (empty($tree->dir['subdirs']) && empty($tree->dir['files'])) {
            $html = $this->output->box(get_string('nofilesavailable', 'repository'));
        } else {
            $htmlid = 'socialwiki_files_tree_'.uniqid();
            $module = array('name'=>'mod_socialwiki', 'fullpath'=>'/mod/socialwiki/module.js');
            $this->page->requires->js_init_call('M.mod_socialwiki.init_tree', array(false, $htmlid), false, $module);
            $html = '<div id="'.$htmlid.'">';
            $html .= $this->htmllize_tree($tree, $tree->dir);
            $html .= '</div>';
        }
        return $html;
    }

    function menu_admin($pageid, $currentselect) {
        $options = array('removepages', 'deleteversions');
        $items = array();
        foreach ($options as $opt) {
            $items[] = get_string($opt, 'socialwiki');
        }
        $selectoptions = array();
        foreach ($items as $key => $item) {
            $selectoptions[$key + 1] = $item;
        }
        $select = new single_select(new moodle_url('/mod/socialwiki/admin.php', array('pageid' => $pageid)), 'option', $selectoptions, $currentselect);
        $select->label = get_string('adminmenu', 'socialwiki') . ': ';
        return $this->output->container($this->output->render($select), 'midpad');
    }


    //Outputs the html for the socialwiki navbar
    public function pretty_navbar($pageid)
	{
		global $CFG,$PAGE,$USER,$COURSE;
		
		$page = socialwiki_get_page($pageid);
		
		$html  = '';
		$html .= html_writer::start_div('', array('id' => 'socialwiki_nav'));
		$html .= html_writer::start_div('', array('id' => 'socialwiki_container', 'class' => ''));

		//Page navigation buttons
		$html .= html_writer::start_div('', array('id' => 'socialwiki_navbuttons'));
		$html .= html_writer::start_tag('ul', array('id' => 'socialwiki_navlist', 'class' => 'socialwiki_horizontal_list'));

		$html .= html_writer::start_tag('li', array('class' => 'socialwiki_navlistitem'));
		$html .= html_writer::start_span('socialwiki_navspan');
		$html .= html_writer::link($CFG->wwwroot.'/mod/socialwiki/home.php?id='.$PAGE->cm->id,'', array('class' => 'socialwiki_toolbarlink','id' => 'socialwiki_homebutton', 'title' => get_string('homepagetooltip', 'mod_socialwiki')));
		$html .= html_writer::end_span();
		$html .= html_writer::end_tag('li');
		
		$html .= html_writer::start_tag('li', array('class' => 'socialwiki_navlistitem'));
		$html .= html_writer::start_span('socialwiki_navspan');
		$html .= html_writer::link($CFG->wwwroot.'/mod/socialwiki/view.php?pageid='.$pageid,'', array('class' => 'socialwiki_toolbarlink', 'id' => 'socialwiki_viewbutton','title' => get_string('viewpagetooltip', 'mod_socialwiki')));
		$html .= html_writer::end_span();
		$html .= html_writer::end_tag('li');
		$html .= html_writer::start_tag('li', array('class' => 'socialwiki_navlistitem'));
		$html .= html_writer::start_span('socialwiki_navspan');
		$html .= html_writer::link($CFG->wwwroot.'/mod/socialwiki/edit.php?pageid='.$pageid,'', array('class' => 'socialwiki_toolbarlink','id' => 'socialwiki_editbutton', 'title' => get_string('editpagetooltip', 'mod_socialwiki')));
		$html .= html_writer::end_span();
		$html .= html_writer::end_tag('li');
		$html .= html_writer::start_tag('li', array('class' => 'socialwiki_navlistitem'));
		$html .= html_writer::start_span('socialwiki_navspan');
		$html .= html_writer::link($CFG->wwwroot.'/mod/socialwiki/history.php?pageid='.$pageid,'', array('class' => 'socialwiki_toolbarlink','id' => 'socialwiki_versionbutton','title' => get_string('versiontooltip', 'mod_socialwiki')));
		$html .= html_writer::end_span();
		$html .= html_writer::end_tag('ul');
		$html .= html_writer::end_div();

		//Search box
		$html .=  '<div id="socialwiki_search">
			<form id="socialwiki_searchform" action="'.$CFG->wwwroot.'/mod/socialwiki/search.php" method="get">
				<input id="socialwiki_searchbox" name="searchstring" type="text" value="Search..."></input>
				<input type="hidden" name="cmid" value="'.$this->page->cm->id.'"></input>
				<input type="hidden" name="courseid" value="'.$COURSE->id.'"></input>
				<input type="hidden" name="pageid" value="'.$pageid.'"></input>
			</form>
		</div>';	

		//Social buttons
		$html .= html_writer::start_div('', array('id' => 'socialwiki_socialbuttons'));
		$html .= html_writer::start_tag('ul', array('id' => 'socialwiki_socialbuttons', 'class' => 'socialwiki_horizontal_list'));
		$html .= html_writer::start_tag('li', array('class' => 'socialwiki_navlistitem'));
		require_once($CFG->dirroot . '/mod/socialwiki/locallib.php');

		if (socialwiki_liked($USER->id, $pageid))
		{
				$html .= html_writer::link($CFG->wwwroot.'/mod/socialwiki/like.php?pageid='.$pageid.'&from='.urlencode($PAGE->url->out()),'', array('class' => 'socialwiki_toolbarlink','id' => 'socialwiki_likebutton', 'like' =>'no','title' => get_string('liketooltip', 'mod_socialwiki')));
		}else
		{
				$html .= html_writer::link($CFG->wwwroot.'/mod/socialwiki/like.php?pageid='.$pageid.'&from='.urlencode($PAGE->url->out()),'', array('class' => 'socialwiki_toolbarlink','id' => 'socialwiki_likebutton', 'like' =>'yes','title' => get_string('liketooltip', 'mod_socialwiki')));
		}
		$html .= html_writer::end_tag('li');
		$html .= html_writer::start_tag('li', array('class' => 'socialwiki_navlistitem'));
		$userto = socialwiki_get_author($pageid);
		if (socialwiki_is_following($USER->id,$userto->userid,$page->subwikiid))
		{
		$html .= html_writer::link($CFG->wwwroot.'/mod/socialwiki/follow.php?pageid='.$pageid.'&from='.urlencode($PAGE->url->out()),'', array('class' => 'socialwiki_toolbarlink','id' => 'socialwiki_friendbutton',  'friend' => 'no', 'title' => get_string('followtooltip', 'mod_socialwiki')));
		}
		else
		{
		$html .= html_writer::link($CFG->wwwroot.'/mod/socialwiki/follow.php?pageid='.$pageid.'&from='.urlencode($PAGE->url->out()),'', array('class' => 'socialwiki_toolbarlink','id' => 'socialwiki_friendbutton',  'friend' => 'yes', 'title' => get_string('followtooltip', 'mod_socialwiki')));
		}
		$html .= html_writer::end_tag('li');
		$html .= html_writer::end_tag('li');
		$html .= html_writer::start_tag('li', array('class' => 'socialwiki_navlistitem'));
		$html .= html_writer::link($CFG->wwwroot.'/mod/socialwiki/manage.php?pageid='.$pageid,'', array('class' => 'socialwiki_toolbarlink','id' => 'socialwiki_managebutton', 'title' => get_string('managetooltip', 'mod_socialwiki')));
		$html .= html_writer::end_tag('li');
		
		$html .= html_writer::start_tag('li', array('class' => 'socialwiki_navlistitem'));
		$html .= html_writer::start_span('socialwiki_navspan');
		$html .= html_writer::link($CFG->wwwroot.'/mod/socialwiki/comments.php?pageid='.$pageid,'', array('class' => 'socialwiki_toolbarlink','id' => 'socialwiki_commentsbutton', 'title' => get_string('commentstooltip', 'mod_socialwiki')));
		$html .= html_writer::end_span();
		$html .= html_writer::end_tag('li');
		$html .= html_writer::end_tag('ul');
		$html .= html_writer::end_div();
		

		$html .= html_writer::end_div();
		$html .= html_writer::end_div();
		
		return $html;	
	}
	
	public function content_area_begin()
	{
			$html = '';
			$html .= html_writer::start_div('socialwiki_wikicontent', array("id"=>"socialwiki_content_area"));
			return $html;
	}

	public function content_area_end()
	{
			$html = '';
			$html .= html_writer::end_div();
			return $html;
	}
	
	public function search_results_area()
	{
			$html = '';
			$html .= html_writer::div('', '',array("id"=>"socialwiki_searchresults_area"));
			return $html;
	}
	
	public function title_block($title)
	{
			$html = '';
			$html .= html_writer::start_div('wikititle');
			$html .= html_writer::tag('h1', $title, array('class' => 'colourtext'));
			$html .= html_writer::end_div('wikititle colourtext');
			return $html;
	}

	
	//Outputs the main socialwiki view area, under the toolbar
	public function viewing_area($pagetitle, $pagecontent, $page)
	{
			global $PAGE,$USER;

			$html = '';
			
			$html .= $this->content_area_begin();
			$html .= html_writer::start_div('wikipage');
			$html .= html_writer::start_div('wikititle');
			$html .= html_writer::tag('h1', $pagetitle);
			
			$html .= html_writer::tag('p', "Likes: ".socialwiki_numlikes($page->id));
			
			
			$user = socialwiki_get_user_info($page->userid);
			$userlink = new moodle_url('/mod/socialwiki/viewuserpages.php', array('userid' => $user->id, 'subwikiid' => $page->subwikiid)); 
			$html.=html_writer::link($userlink->out(false),fullname($user));
			
			$html .= html_writer::end_div();
			$html .= html_writer::start_div('', array('id' => 'socialwiki_wikicontent'));
			$html .= $pagecontent;
			$html .= html_writer::end_div();
			$html .= html_writer::end_div();
			$html .= $this->content_area_end();
			return $html;
	}
	
	public function help_area_start(){
		$html = '';
		$html .= $this->content_area_begin();
		$html .= html_writer::start_div('wikipage');
		return $html;
	}

	public function help_content($heading,$content){
		$html='';
		$html .= html_writer::tag('h2', $heading,array('class'=>'wikititle'));
		$html .= html_writer::start_div('', array('id' => 'socialwiki_wikicontent'));
		$html .= $content;
		$html .= html_writer::end_div();
		return $html;
	}
	
	public function help_area_end(){
		$html='';
		$html .= html_writer::end_div();
		$html .= $this->content_area_end();
		return $html;
	}
        
    /**
     * Internal function - creates htmls structure suitable for YUI tree.
     */
    protected function htmllize_tree($tree, $dir) {
        global $CFG;
        $yuiconfig = array();
        $yuiconfig['type'] = 'html';

        if (empty($dir['subdirs']) and empty($dir['files'])) {
            return '';
        }
        $result = '<ul>';
        foreach ($dir['subdirs'] as $subdir) {
            $image = $this->output->pix_icon(file_folder_icon(), $subdir['dirname'], 'moodle', array('class'=>'icon'));
            $result .= '<li yuiConfig=\''.json_encode($yuiconfig).'\'><div>'.$image.' '.s($subdir['dirname']).'</div> '.$this->htmllize_tree($tree, $subdir).'</li>';
        }
        foreach ($dir['files'] as $file) {
            $url = file_encode_url("$CFG->wwwroot/pluginfile.php", '/'.$tree->context->id.'/mod_socialwiki/attachments/' . $tree->subwiki->id . '/'. $file->get_filepath() . $file->get_filename(), true);
            $filename = $file->get_filename();
            $image = $this->output->pix_icon(file_file_icon($file), $filename, 'moodle', array('class'=>'icon'));
            $result .= '<li yuiConfig=\''.json_encode($yuiconfig).'\'><div>'.$image.' '.html_writer::link($url, $filename).'</div></li>';
        }
        $result .= '</ul>';

        return $result;
    }
}



class socialwiki_files_tree implements renderable {
    public $context;
    public $dir;
    public $subwiki;
    public function __construct($context, $subwiki) {
        $fs = get_file_storage();
        $this->context = $context;
        $this->subwiki = $subwiki;
        $this->dir = $fs->get_area_tree($context->id, 'mod_socialwiki', 'attachments', $subwiki->id);
    }
}
