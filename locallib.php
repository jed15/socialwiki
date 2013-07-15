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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * This contains functions and classes that will be used by scripts in wiki module
 *
 * @package mod-wiki-2.0
 * @copyrigth 2009 Marc Alier, Jordi Piguillem marc.alier@upc.edu
 * @copyrigth 2009 Universitat Politecnica de Catalunya http://www.upc.edu
 *
 * @author Jordi Piguillem
 * @author Marc Alier
 * @author David Jimenez
 * @author Josep Arus
 * @author Daniel Serrano
 * @author Kenneth Riba
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/mod/socialwiki/lib.php');
require_once($CFG->dirroot . '/mod/socialwiki/parser/parser.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->dirroot.'/tag/lib.php');

define('SOCIALWIKI_REFRESH_CACHE_TIME', 30); // @TODO: To be deleted.
define('SOCIALFORMAT_CREOLE', '37');
define('SOCIALFORMAT_NWIKI', '38');
define('SOCIAL_NO_VALID_RATE', '-999');
define('SOCIALIMPROVEMENT', '+');
define('SOCIALEQUAL', '=');
define('SOCIALWORST', '-');

define('SOCIALLOCK_TIMEOUT', 30);

/**
 * Get a wiki instance
 * @param int $wikiid the instance id of wiki
 */
function socialwiki_get_wiki($wikiid) {
    global $DB;

    return $DB->get_record('socialwiki', array('id' => $wikiid));
}

/**
 * Get sub wiki instances with same wiki id
 * @param int $wikiid
 */
function socialwiki_get_subwikis($wikiid) {
    global $DB;
    return $DB->get_records('socialwiki_subwikis', array('wikiid' => $wikiid));
}

/**
 * Get a sub wiki instance by wiki id and group id
 * @param int $wikiid
 * @param int $groupid
 * @return object
 */
function socialwiki_get_subwiki_by_group($wikiid, $groupid, $userid = 0) {
    global $DB;
    return $DB->get_record('socialwiki_subwikis', array('wikiid' => $wikiid, 'groupid' => $groupid, 'userid' => $userid));
}

/**
 * Get a sub wiki instace by instance id
 * @param int $subwikiid
 * @return object
 */
function socialwiki_get_subwiki($subwikiid) {
    global $DB;
    return $DB->get_record('socialwiki_subwikis', array('id' => $subwikiid));

}

/**
 * Add a new sub wiki instance
 * @param int $wikiid
 * @param int $groupid
 * @return int $insertid
 */
function socialwiki_add_subwiki($wikiid, $groupid, $userid = 0) {
    global $DB;

    $record = new StdClass();
    $record->wikiid = $wikiid;
    $record->groupid = $groupid;
    $record->userid = $userid;

    $insertid = $DB->insert_record('socialwiki_subwikis', $record);
    return $insertid;
}

/**
 * Get a wiki instance by pageid
 * @param int $pageid
 * @return object
 */
function socialwiki_get_wiki_from_pageid($pageid) {
    global $DB;

    $sql = "SELECT w.*
            FROM {socialwiki} w, {socialwiki_subwikis} s, {socialwiki_pages} p
            WHERE p.id = ? AND
            p.subwikiid = s.id AND
            s.wikiid = w.id";

    return $DB->get_record_sql($sql, array($pageid));
}

/**
 * Get a wiki page by pageid
 * @param int $pageid
 * @return object
 */
function socialwiki_get_page($pageid) {
    global $DB;
    return $DB->get_record('socialwiki_pages', array('id' => $pageid));
}

/**
 * Get latest version of wiki page
 * @param int $pageid
 * @return object
 */
function socialwiki_get_current_version($pageid) {
    global $DB;

    // @TODO: Fix this query
    $sql = "SELECT *
            FROM {socialwiki_versions}
            WHERE pageid = ?
            ORDER BY version DESC";
    $records = $DB->get_records_sql($sql, array($pageid), 0, 1);
    return array_pop($records);

}

/**
 * Alias of wiki_get_current_version
 * @TODO, does the exactly same thing as wiki_get_current_version, should be removed
 * @param int $pageid
 * @return object
 */
function socialwiki_get_last_version($pageid) {
    return socialwiki_get_current_version($pageid);
}

/**
 * Get page section
 * @param int $pageid
 * @param string $section
 */
function socialwiki_get_section_page($page, $section) {

    $version = socialwiki_get_current_version($page->id);
    return socialwiki_parser_proxy::get_section($version->content, $version->contentformat, $section);
}

/**
 * Get a wiki page by page title
 * @param int $swid, sub wiki id
 * @param string $title
 * @return object
 */
function socialwiki_get_page_by_title($swid, $title) {
    global $DB;
	$records = $DB->get_records('socialwiki_pages', array('subwikiid' => $swid, 'title' => $title));
	if (count($records) > 0)
	{
    	return $records[max(array_keys($records))];
	}
	else
	{
		return $records;
	}
}

/**
 * Get a version record by record id
 * @param int $versionid, the version id
 * @return object
 */
function socialwiki_get_version($versionid) {
    global $DB;
    return $DB->get_record('socialwiki_versions', array('id' => $versionid));
}

/**
 * Get first page of wiki instace
 * @param int $subwikiid
 * @param int $module, wiki instance object
 */
function socialwiki_get_first_page($subwikid, $module = null) {
    global $DB, $USER;

    $sql = "SELECT p.*
            FROM {socialwiki} w, {socialwiki_subwikis} s, {socialwiki_pages} p
            WHERE s.id = ? AND
            s.wikiid = w.id AND
            w.firstpagetitle = p.title AND
            p.subwikiid = s.id ORDER BY id ASC";
	$records = $DB->get_records_sql($sql, array($subwikid));
    return $records[max(array_keys($records))];
}

function socialwiki_save_section($wikipage, $sectiontitle, $sectioncontent, $userid) {

    $wiki = socialwiki_get_wiki_from_pageid($wikipage->id);
    $cm = get_coursemodule_from_instance('socialwiki', $wiki->id);
    $context = context_module::instance($cm->id);

    if (has_capability('mod/socialwiki:editpage', $context)) {
        $version = socialwiki_get_current_version($wikipage->id);
        $content = socialwiki_parser_proxy::get_section($version->content, $version->contentformat, $sectiontitle, true);

        $newcontent = $content[0] . $sectioncontent . $content[2];

        return socialwiki_save_page($wikipage, $newcontent, $userid);
    } else {
        return false;
    }
}

/**
 * Save page content
 * @param object $wikipage
 * @param string $newcontent
 * @param int $userid
 */
function socialwiki_save_page($wikipage, $newcontent, $userid) {
    global $DB;

    $wiki = socialwiki_get_wiki_from_pageid($wikipage->id);
    $cm = get_coursemodule_from_instance('socialwiki', $wiki->id);
    $context = context_module::instance($cm->id);

    if (has_capability('mod/socialwiki:editpage', $context)) {
        $version = socialwiki_get_current_version($wikipage->id);

        $version->content = $newcontent;
        $version->userid = $userid;
        $version->version++;
        $version->timecreated = time();
        $versionid = $DB->insert_record('socialwiki_versions', $version);

        $wikipage->timemodified = $version->timecreated;
        $wikipage->userid = $userid;
        $return = socialwiki_refresh_cachedcontent($wikipage, $newcontent);

        return $return;
    } else {
        return false;
    }
}

function socialwiki_refresh_cachedcontent($page, $newcontent = null) {
    global $DB;
    $version = socialwiki_get_current_version($page->id);
    if (empty($version)) {
        return null;
    }
    if (!isset($newcontent)) {
        $newcontent = $version->content;
    }

    $options = array('swid' => $page->subwikiid, 'pageid' => $page->id);
    $parseroutput = socialwiki_parse_content($version->contentformat, $newcontent, $options);
    $page->cachedcontent = $parseroutput['toc'] . $parseroutput['parsed_text'];
    $page->timerendered = time();
    $DB->update_record('socialwiki_pages', $page);

    socialwiki_refresh_page_links($page, $parseroutput['link_count']);

    return array('page' => $page, 'sections' => $parseroutput['repeated_sections'], 'version' => $version->version);
}
/**
 * Restore a page
 */
function socialwiki_restore_page($wikipage, $newcontent, $userid) {
    $return = socialwiki_save_page($wikipage, $newcontent, $userid);
    return $return['page'];
}

function socialwiki_refresh_page_links($page, $links) {
    global $DB;

    $DB->delete_records('socialwiki_links', array('frompageid' => $page->id));
    foreach ($links as $linkname => $linkinfo) {

        $newlink = new stdClass();
        $newlink->subwikiid = $page->subwikiid;
        $newlink->frompageid = $page->id;

        if ($linkinfo['new']) {
            $newlink->tomissingpage = $linkname;
        } else {
            $newlink->topageid = $linkinfo['pageid'];
        }

        try {
            $DB->insert_record('socialwiki_links', $newlink);
        } catch (dml_exception $e) {
            debugging($e->getMessage());
        }

    }
}

/**
 * Create a new wiki page, if the page exists, return existing pageid
 * @param int $swid
 * @param string $title
 * @param string $format
 * @param int $userid
 */
function socialwiki_create_page($swid, $title, $format, $userid, $parent = NULL) {
    global $DB;
    $subwiki =socialwiki_get_subwiki($swid);
    $cm = get_coursemodule_from_instance('socialwiki', $subwiki->wikiid);
    $context = context_module::instance($cm->id);
    require_capability('mod/socialwiki:editpage', $context);
    // if page exists
    /*
    if ($page = socialwiki_get_page_by_title($swid, $title)) {
        return $page->id;
    }*/

    // Creating a new empty version
    $version = new stdClass();
    $version->content = '';
    $version->contentformat = $format;
    $version->version = 0;
    $version->timecreated = time();
    $version->userid = $userid;

    $versionid = null;
    $versionid = $DB->insert_record('socialwiki_versions', $version);

    // Createing a new empty page
    $page = new stdClass();
    $page->subwikiid = $swid;
    $page->title = $title;
    $page->cachedcontent = '';
    $page->timecreated = $version->timecreated;
    $page->timemodified = $version->timecreated;
    $page->timerendered = $version->timecreated;
    $page->userid = $userid;
    $page->pageviews = 0;
    $page->readonly = 0;
    $page->parent = $parent;

    $pageid = $DB->insert_record('socialwiki_pages', $page);

    // Setting the pageid
    $version->id = $versionid;
    $version->pageid = $pageid;
    $DB->update_record('socialwiki_versions', $version);

    socialwiki_make_cache_expire($page->title);
    return $pageid;
}

function socialwiki_make_cache_expire($pagename) {
    global $DB;

    $sql = "UPDATE {socialwiki_pages}
            SET timerendered = 0
            WHERE id IN ( SELECT l.frompageid
                FROM {socialwiki_links} l
                WHERE l.tomissingpage = ?
            )";
    $DB->execute ($sql, array($pagename));
}

/**
 * Get a specific version of page
 * @param int $pageid
 * @param int $version
 */
function socialwiki_get_wiki_page_version($pageid, $version) {
    global $DB;
    return $DB->get_record('socialwiki_versions', array('pageid' => $pageid, 'version' => $version));
}

/**
 * Get version list
 * @param int $pageid
 * @param int $limitfrom
 * @param int $limitnum
 */
function socialwiki_get_wiki_page_versions($pageid, $limitfrom, $limitnum) {
    global $DB;
    return $DB->get_records('socialwiki_versions', array('pageid' => $pageid), 'version DESC', '*', $limitfrom, $limitnum);
}

/**
 * Count the number of page version
 * @param int $pageid
 */
function socialwiki_count_wiki_page_versions($pageid) {
    global $DB;
    return $DB->count_records('socialwiki_versions', array('pageid' => $pageid));
}

/**
 * Get linked from page
 * @param int $pageid
 */
function socialwiki_get_linked_to_pages($pageid) {
    global $DB;
    return $DB->get_records('socialwiki_links', array('frompageid' => $pageid));
}

/**
 * Get linked from page
 * @param int $pageid
 */
function socialwiki_get_linked_from_pages($pageid) {
    global $DB;
    return $DB->get_records('socialwiki_links', array('topageid' => $pageid));
}

/**
 * Get pages which user have been edited
 * @param int $swid
 * @param int $userid
 */
function socialwiki_get_contributions($swid, $userid) {
    global $DB;

    $sql = "SELECT v.*
            FROM {socialwiki_versions} v, {socialwiki_pages} p
            WHERE p.subwikiid = ? AND
            v.pageid = p.id AND
            v.userid = ?";

    return $DB->get_records_sql($sql, array($swid, $userid));
}

/**
 * Get missing or empty pages in wiki
 * @param int $swid sub wiki id
 */
function socialwiki_get_missing_or_empty_pages($swid) {
    global $DB;

    $sql = "SELECT DISTINCT p.title, p.id, p.subwikiid
            FROM {socialwiki} w, {socialwiki_subwikis} s, {socialwiki_pages} p
            WHERE s.wikiid = w.id and
            s.id = ? and
            w.firstpagetitle != p.title and
            p.subwikiid = ? and
            1 =  (SELECT count(*)
                FROM {socialwiki_versions} v
                WHERE v.pageid = p.id)
            UNION
            SELECT DISTINCT l.tomissingpage as title, 0 as id, l.subwikiid
            FROM {socialwiki_links} l
            WHERE l.subwikiid = ? and
            l.topageid = 0";

    return $DB->get_records_sql($sql, array($swid, $swid, $swid));
}

/**
 * Get pages list in wiki
 * @param int $swid sub wiki id
 */
function socialwiki_get_page_list($swid) {
    global $DB;
    $records = $DB->get_records('socialwiki_pages', array('subwikiid' => $swid), 'title ASC');
    return $records;
}

/**
 * Return a list of orphaned wikis for one specific subwiki
 * @global object
 * @param int $swid sub wiki id
 */
function socialwiki_get_orphaned_pages($swid) {
    global $DB;

    $sql = "SELECT p.id, p.title
            FROM {socialwiki_pages} p, {socialwiki} w , {socialwiki_subwikis} s
            WHERE p.subwikiid = ?
            AND s.id = ?
            AND w.id = s.wikiid
            AND p.title != w.firstpagetitle
            AND p.id NOT IN (SELECT topageid FROM {socialwiki_links} WHERE subwikiid = ?)";

    return $DB->get_records_sql($sql, array($swid, $swid, $swid));
}

/**
 * Search wiki title
 * @param int $swid sub wiki id
 * @param string $search
 */
function socialwiki_search_title($swid, $search) {
    global $DB;

    return $DB->get_records_select('socialwiki_pages', "subwikiid = ? AND title LIKE ?", array($swid, '%'.$search.'%'));
}

/**
 * Search wiki content
 * @param int $swid sub wiki id
 * @param string $search
 */
function socialwiki_search_content($swid, $search) {
    global $DB;

    return $DB->get_records_select('socialwiki_pages', "subwikiid = ? AND cachedcontent LIKE ?", array($swid, '%'.$search.'%'));
}

/**
 * Search wiki title and content
 * @param int $swid sub wiki id
 * @param string $search
 */
function socialwiki_search_all($swid, $search) {
    global $DB;

    return $DB->get_records_select('socialwiki_pages', "subwikiid = ? AND (cachedcontent LIKE ? OR title LIKE ?)", array($swid, '%'.$search.'%', '%'.$search.'%'));
}

/**
 * Get user data
 */
function socialwiki_get_user_info($userid) {
    global $DB;
    return $DB->get_record('user', array('id' => $userid));
}

/**
 * Increase page view nubmer
 * @param int $page, database record
 */
function socialwiki_increment_pageviews($page) {
    global $DB;

    $page->pageviews++;
    $DB->update_record('socialwiki_pages', $page);
}

//----------------------------------------------------------
//----------------------------------------------------------

/**
 * Text format supported by wiki module
 */
function socialwiki_get_formats() {
    return array('html', 'creole', 'nwiki');
}

/**
 * Parses a string with the wiki markup language in $markup.
 *
 * @return Array or false when something wrong has happened.
 *
 * Returned array contains the following fields:
 *     'parsed_text' => String. Contains the parsed wiki content.
 *     'unparsed_text' => String. Constains the original wiki content.
 *     'link_count' => Array of array('destination' => ..., 'new' => "is new?"). Contains the internal wiki links found in the wiki content.
 *      'deleted_sections' => the list of deleted sections.
 *              '' =>
 *
 * @author Josep Arús Pous
 **/
function socialwiki_parse_content($markup, $pagecontent, $options = array()) {
    global $PAGE;

    $subwiki = socialwiki_get_subwiki($options['swid']);
    $cm = get_coursemodule_from_instance("socialwiki", $subwiki->wikiid);
    $context = context_module::instance($cm->id);

    $parser_options = array(
        'link_callback' => '/mod/socialwiki/locallib.php:socialwiki_parser_link',
        'link_callback_args' => array('swid' => $options['swid']),
        'table_callback' => '/mod/socialwiki/locallib.php:socialwiki_parser_table',
        'real_path_callback' => '/mod/socialwiki/locallib.php:socialwiki_parser_real_path',
        'real_path_callback_args' => array(
            'context' => $context,
            'component' => 'mod_socialwiki',
            'filearea' => 'attachments',
            'subwikiid'=> $subwiki->id,
            'pageid' => $options['pageid']
        ),
        'pageid' => $options['pageid'],
        'pretty_print' => (isset($options['pretty_print']) && $options['pretty_print']),
        'printable' => (isset($options['printable']) && $options['printable'])
    );

    return socialwiki_parser_proxy::parse($pagecontent, $markup, $parser_options);
}

/**
 * This function is the parser callback to parse wiki links.
 *
 * It returns the necesary information to print a link.
 *
 * NOTE: Empty pages and non-existent pages must be print in red color.
 *
 * !!!!!! IMPORTANT !!!!!!
 * It is critical that you call format_string on the content before it is used.
 *
 * @param string|page_wiki $link name of a page
 * @param array $options
 * @return array Array('content' => string, 'url' => string, 'new' => bool, 'link_info' => array)
 *
 * @TODO Doc return and options
 */
function socialwiki_parser_link($link, $options = null) {
    global $CFG;

    if (is_object($link)) {
        $parsedlink = array('content' => $link->title, 'url' => $CFG->wwwroot . '/mod/socialwiki/view.php?pageid=' . $link->id, 'new' => false, 'link_info' => array('link' => $link->title, 'pageid' => $link->id, 'new' => false));

        $version = socialwiki_get_current_version($link->id);
        if ($version->version == 0) {
            $parsedlink['new'] = true;
        }
        return $parsedlink;
    } else {
        $swid = $options['swid'];

        if ($page = socialwiki_get_page_by_title($swid, $link)) {
            $parsedlink = array('content' => $link, 'url' => $CFG->wwwroot . '/mod/socialwiki/view.php?pageid=' . $page->id, 'new' => false, 'link_info' => array('link' => $link, 'pageid' => $page->id, 'new' => false));

            $version = socialwiki_get_current_version($page->id);
            if ($version->version == 0) {
                $parsedlink['new'] = true;
            }

            return $parsedlink;

        } else {
            return array('content' => $link, 'url' => $CFG->wwwroot . '/mod/socialwiki/create.php?swid=' . $swid . '&amp;title=' . urlencode($link) . '&amp;action=new', 'new' => true, 'link_info' => array('link' => $link, 'new' => true, 'pageid' => 0));
        }
    }
}

/**
 * Returns the table fully parsed (HTML)
 *
 * @return HTML for the table $table
 * @author Josep Arús Pous
 *
 **/
function socialwiki_parser_table($table) {
    global $OUTPUT;

    $htmltable = new html_table();

    $headers = $table[0];
    $htmltable->head = array();
    foreach ($headers as $h) {
        $htmltable->head[] = $h[1];
    }

    array_shift($table);
    $htmltable->data = array();
    foreach ($table as $row) {
        $row_data = array();
        foreach ($row as $r) {
            $row_data[] = $r[1];
        }
        $htmltable->data[] = $row_data;
    }

    return html_writer::table($htmltable);
}

/**
 * Returns an absolute path link, unless there is no such link.
 *
 * @param string $url Link's URL or filename
 * @param stdClass $context filearea params
 * @param string $component The component the file is associated with
 * @param string $filearea The filearea the file is stored in
 * @param int $swid Sub wiki id
 *
 * @return string URL for files full path
 */

function socialwiki_parser_real_path($url, $context, $component, $filearea, $swid) {
    global $CFG;

    if (preg_match("/^(?:http|ftp)s?\:\/\//", $url)) {
        return $url;
    } else {

        $file = 'pluginfile.php';
        if (!$CFG->slasharguments) {
            $file = $file . '?file=';
        }
        $baseurl = "$CFG->wwwroot/$file/{$context->id}/$component/$filearea/$swid/";
        // it is a file in current file area
        return $baseurl . $url;
    }
}

/**
 * Returns the token used by a wiki language to represent a given tag or "object" (bold -> **)
 *
 * @return A string when it has only one token at the beginning (f. ex. lists). An array composed by 2 strings when it has 2 tokens, one at the beginning and one at the end (f. ex. italics). Returns false otherwise.
 * @author Josep Arús Pous
 **/
function socialwiki_parser_get_token($markup, $name) {

    return socialwiki_parser_proxy::get_token($name, $markup);
}

/**
 * Checks if current user can view a subwiki
 *
 * @param $subwiki
 */
function socialwiki_user_can_view($subwiki) {
    global $USER;

    $wiki = socialwiki_get_wiki($subwiki->wikiid);
    $cm = get_coursemodule_from_instance('socialwiki', $wiki->id);
    $context = context_module::instance($cm->id);

    // Working depending on activity groupmode
    switch (groups_get_activity_groupmode($cm)) {
    case NOGROUPS:

        if ($wiki->wikimode == 'collaborative') {
            // Collaborative Mode:
            // There is one wiki for all the class.
            //
            // Only view capbility needed
            return has_capability('mod/socialwiki:viewpage', $context);
        } else {
            //Error
            return false;
        }
    case SEPARATEGROUPS:
        // Collaborative and Individual Mode
        //
        // Collaborative Mode:
        //      There is one wiki per group.
        // Individual Mode:
        //      Each person owns a wiki.
        if ($wiki->wikimode == 'collaborative' || $wiki->wikimode == 'individual') {
            // Only members of subwiki group could view that wiki
            if (groups_is_member($subwiki->groupid)) {
                // Only view capability needed
                return has_capability('mod/socialwiki:viewpage', $context);

            } else { // User is not part of that group
                // User must have:
                //      mod/wiki:managewiki capability
                // or
                //      moodle/site:accessallgroups capability
                // and
                //      mod/wiki:viewpage capability
                $view = has_capability('mod/socialwiki:viewpage', $context);
                $manage = has_capability('mod/socialwiki:manage_socialwiki', $context);
                $access = has_capability('moodle/site:accessallgroups', $context);
                return ($manage || $access) && $view;
            }
        } else {
            //Error
            return false;
        }
    case VISIBLEGROUPS:
        // Collaborative and Individual Mode
        //
        // Collaborative Mode:
        //      There is one wiki per group.
        // Individual Mode:
        //      Each person owns a wiki.
        if ($wiki->wikimode == 'collaborative' || $wiki->wikimode == 'individual') {
            // Everybody can read all wikis
            //
            // Only view capability needed
            return has_capability('mod/socialwiki:viewpage', $context);
        } else {
            //Error
            return false;
        }
    default: // Error
        return false;
    }
}

/**
 * Checks if current user can edit a subwiki
 *
 * @param $subwiki
 */
function socialwiki_user_can_edit($subwiki) {
    global $USER;

    $wiki = socialwiki_get_wiki($subwiki->wikiid);
    $cm = get_coursemodule_from_instance('socialwiki', $wiki->id);
    $context = context_module::instance($cm->id);

    // Working depending on activity groupmode
    switch (groups_get_activity_groupmode($cm)) {
    case NOGROUPS:

        if ($wiki->wikimode == 'collaborative') {
            // Collaborative Mode:
            // There is a wiki for all the class.
            //
            // Only edit capbility needed
            return has_capability('mod/socialwiki:editpage', $context);
        } else {
            //Error
            return false;
        }
    case SEPARATEGROUPS:
        if ($wiki->wikimode == 'collaborative') {
            // Collaborative Mode:
            // There is one wiki per group.
            //
            // Only members of subwiki group could edit that wiki
            if ($subwiki->groupid == groups_get_activity_group($cm)) {
                // Only edit capability needed
                return has_capability('mod/socialwiki:editpage', $context);
            } else { // User is not part of that group
                // User must have:
                //      mod/wiki:managewiki capability
                // and
                //      moodle/site:accessallgroups capability
                // and
                //      mod/wiki:editpage capability
                $manage = has_capability('mod/socialwiki:managewiki', $context);
                $access = has_capability('moodle/site:accessallgroups', $context);
                $edit = has_capability('mod/socialwiki:editpage', $context);
                return $manage && $access && $edit;
            }
        } else {
            //Error
            return false;
        }
    case VISIBLEGROUPS:
        if ($wiki->wikimode == 'collaborative') {
            // Collaborative Mode:
            // There is one wiki per group.
            //
            // Only members of subwiki group could edit that wiki
            if (groups_is_member($subwiki->groupid)) {
                // Only edit capability needed
                return has_capability('mod/socialwiki:editpage', $context);
            } else { // User is not part of that group
                // User must have:
                //      mod/wiki:managewiki capability
                // and
                //      mod/wiki:editpage capability
                $manage = has_capability('mod/socialwiki:managewiki', $context);
                $edit = has_capability('mod/socialwiki:editpage', $context);
                return $manage && $edit;
            }
        }else {
            //Error
            return false;
        }
    default: // Error
        return false;
    }
}

//----------------
// Locks
//----------------

/**
 * Checks if a page-section is locked.
 *
 * @return true if the combination of section and page is locked, FALSE otherwise.
 */
function socialwiki_is_page_section_locked($pageid, $userid, $section = null) {
    global $DB;

    $sql = "pageid = ? AND lockedat > ? AND userid != ?";
    $params = array($pageid, time(), $userid);

    if (!empty($section)) {
        $sql .= " AND (sectionname = ? OR sectionname IS null)";
        $params[] = $section;
    }

    return $DB->record_exists_select('socialwiki_locks', $sql, $params);
}

/**
 * Inserts or updates a wiki_locks record.
 */
function socialwiki_set_lock($pageid, $userid, $section = null, $insert = false) {
    global $DB;

    if (socialwiki_is_page_section_locked($pageid, $userid, $section)) {
        return false;
    }

    $params = array('pageid' => $pageid, 'userid' => $userid, 'sectionname' => $section);

    $lock = $DB->get_record('socialwiki_locks', $params);

    if (!empty($lock)) {
        $DB->update_record('socialwiki_locks', array('id' => $lock->id, 'lockedat' => time() + SOCIALLOCK_TIMEOUT));
    } else if ($insert) {
        $DB->insert_record('socialwiki_locks', array('pageid' => $pageid, 'sectionname' => $section, 'userid' => $userid, 'lockedat' => time() + 30));
    }

    return true;
}

/**
 * Deletes wiki_locks that are not in use. (F.Ex. after submitting the changes). If no userid is present, it deletes ALL the wiki_locks of a specific page.
 */
function socialwiki_delete_locks($pageid, $userid = null, $section = null, $delete_from_db = true, $delete_section_and_page = false) {
    global $DB;

    $params = array('pageid' => $pageid);

    if (!empty($userid)) {
        $params['userid'] = $userid;
    }

    if (!empty($section)) {
        $params['sectionname'] = $section;
    }

    if ($delete_from_db) {
        $DB->delete_records('socialwiki_locks', $params);
        if ($delete_section_and_page && !empty($section)) {
            $params['sectionname'] = null;
            $DB->delete_records('socialwiki_locks', $params);
        }
    } else {
        $DB->set_field('socialwiki_locks', 'lockedat', time(), $params);
    }
}

/**
 * Deletes wiki_locks that expired 1 hour ago.
 */
function socialwiki_delete_old_locks() {
    global $DB;

    $DB->delete_records_select('socialwiki_locks', "lockedat < ?", array(time() - 3600));
}

/**
 * Deletes wiki_links. It can be sepecific link or links attached in subwiki
 *
 * @global mixed $DB database object
 * @param int $linkid id of the link to be deleted
 * @param int $topageid links to the specific page
 * @param int $frompageid links from specific page
 * @param int $subwikiid links to subwiki
 */
function socialwiki_delete_links($linkid = null, $topageid = null, $frompageid = null, $subwikiid = null) {
    global $DB;
    $params = array();

    // if link id is givien then don't check for anything else
    if (!empty($linkid)) {
        $params['id'] = $linkid;
    } else {
        if (!empty($topageid)) {
            $params['topageid'] = $topageid;
        }
        if (!empty($frompageid)) {
            $params['frompageid'] = $frompageid;
        }
        if (!empty($subwikiid)) {
            $params['subwikiid'] = $subwikiid;
        }
    }

    //Delete links if any params are passed, else nothing to delete.
    if (!empty($params)) {
        $DB->delete_records('socialwiki_links', $params);
    }
}

/**
 * Delete wiki synonyms related to subwikiid or page
 *
 * @param int $subwikiid id of sunbwiki
 * @param int $pageid id of page
 */
function socialwiki_delete_synonym($subwikiid, $pageid = null) {
    global $DB;

    $params = array('subwikiid' => $subwikiid);
    if (!is_null($pageid)) {
        $params['pageid'] = $pageid;
    }
    $DB->delete_records('socialwiki_synonyms', $params, IGNORE_MISSING);
}

/**
 * Delete pages and all related data
 *
 * @param mixed $context context in which page needs to be deleted.
 * @param mixed $pageids id's of pages to be deleted
 * @param int $subwikiid id of the subwiki for which all pages should be deleted
 */
function socialwiki_delete_pages($context, $pageids = null, $subwikiid = null) {
    global $DB;

    if (!empty($pageids) && is_int($pageids)) {
       $pageids = array($pageids);
    } else if (!empty($subwikiid)) {
        $pageids = socialwiki_get_page_list($subwikiid);
    }

    //If there is no pageid then return as we can't delete anything.
    if (empty($pageids)) {
        return;
    }

    /// Delete page and all it's relevent data
    foreach ($pageids as $pageid) {
        if (is_object($pageid)) {
            $pageid = $pageid->id;
        }

        //Delete page comments
        $comments = socialwiki_get_comments($context->id, $pageid);
        foreach ($comments as $commentid => $commentvalue) {
            socialwiki_delete_comment($commentid, $context, $pageid);
        }

        //Delete page tags
        $tags = tag_get_tags_array('socialwiki_pages', $pageid);
        foreach ($tags as $tagid => $tagvalue) {
            tag_delete_instance('socialwiki_pages', $pageid, $tagid);
        }

        //Delete Synonym
        socialwiki_delete_synonym($subwikiid, $pageid);

        //Delete all page versions
        socialwiki_delete_page_versions(array($pageid=>array(0)));

        //Delete all page locks
        socialwiki_delete_locks($pageid);

        //Delete all page links
        socialwiki_delete_links(null, $pageid);

        //Delete page
        $params = array('id' => $pageid);
        $DB->delete_records('socialwiki_pages', $params);
    }
}

/**
 * Delete specificed versions of a page or versions created by users
 * if version is 0 then it will remove all versions of the page
 *
 * @param array $deleteversions delete versions for a page
 */
function socialwiki_delete_page_versions($deleteversions) {
    global $DB;

    /// delete page-versions
    foreach ($deleteversions as $id => $versions) {
        foreach ($versions as $version) {
            $params = array('pageid' => $id);
            //If version = 0, then remove all versions of this page, else remove
            //specified version
            if ($version != 0) {
                $params['version'] = $version;
            }
            $DB->delete_records('socialwiki_versions', $params, IGNORE_MISSING);
        }
    }
}

function socialwiki_get_comment($commentid){
    global $DB;
    return $DB->get_record('comments', array('id' => $commentid));
}

/**
 * Returns all comments by context and pageid
 *
 * @param $context. Current context
 * @param $pageid. Current pageid
 **/
function socialwiki_get_comments($contextid, $pageid) {
    global $DB;

    return $DB->get_records('comments', array('contextid' => $contextid, 'itemid' => $pageid, 'commentarea' => 'socialwiki_page'));
}

/**
 * Add comments ro database
 *
 * @param object $context. Current context
 * @param int $pageid. Current pageid
 * @param string $content. Content of the comment
 * @param string editor. Version of editor we are using.
 **/
function socialwiki_add_comment($context, $pageid, $content, $editor) {
    global $CFG;
    require_once($CFG->dirroot . '/comment/lib.php');

    list($context, $course, $cm) = get_context_info_array($context->id);
    $cmt = new stdclass();
    $cmt->context = $context;
    $cmt->itemid = $pageid;
    $cmt->area = 'socialwiki_page';
    $cmt->course = $course;
    $cmt->component = 'mod_socialwiki';

    $manager = new comment($cmt);

    if ($editor == 'creole') {
        $manager->add($content, SOCIALFORMAT_CREOLE);
    } else if ($editor == 'html') {
        $manager->add($content, FORMAT_HTML);
    } else if ($editor == 'nwiki') {
        $manager->add($content, SOCIALFORMAT_NWIKI);
    }

}

/**
 * Delete comments from database
 *
 * @param $idcomment. Id of comment which will be deleted
 * @param $context. Current context
 * @param $pageid. Current pageid
 **/
function socialwiki_delete_comment($idcomment, $context, $pageid) {
    global $CFG;
    require_once($CFG->dirroot . '/comment/lib.php');

    list($context, $course, $cm) = get_context_info_array($context->id);
    $cmt = new stdClass();
    $cmt->context = $context;
    $cmt->itemid = $pageid;
    $cmt->area = 'socialwiki_page';
    $cmt->course = $course;
    $cmt->component = 'mod_socialwiki';

    $manager = new comment($cmt);
    $manager->delete($idcomment);

}

/**
 * Delete al comments from wiki
 *
 **/
function socialwiki_delete_comments_wiki() {
    global $PAGE, $DB;

    $cm = $PAGE->cm;
    $context = context_module::instance($cm->id);

    $table = 'comments';
    $select = 'contextid = ?';

    $DB->delete_records_select($table, $select, array($context->id));

}

function socialwiki_add_progress($pageid, $oldversionid, $versionid, $progress) {
    global $DB;
    for ($v = $oldversionid + 1; $v <= $versionid; $v++) {
        $user = socialwiki_get_wiki_page_id($pageid, $v);

        $DB->insert_record('socialwiki_progress', array('userid' => $user->userid, 'pageid' => $pageid, 'versionid' => $v, 'progress' => $progress));
    }
}

function socialwiki_get_wiki_page_id($pageid, $id) {
    global $DB;
    return $DB->get_record('socialwiki_versions', array('pageid' => $pageid, 'id' => $id));
}

function socialwiki_print_page_content($page, $context, $subwikiid) {
    global $OUTPUT, $CFG, $PAGE;

    if ($page->timerendered + SOCIALWIKI_REFRESH_CACHE_TIME < time()) {
        $content = socialwiki_refresh_cachedcontent($page);
        $page = $content['page'];
    }

    if (isset($content)) {
        $box = '';
        foreach ($content['sections'] as $s) {
            $box .= '<p>' . get_string('repeatedsection', 'socialwiki', $s) . '</p>';
        }

        if (!empty($box)) {
            echo $OUTPUT->box($box);
        }
    }
    $html = file_rewrite_pluginfile_urls($page->cachedcontent, 'pluginfile.php', $context->id, 'mod_socialwiki', 'attachments', $subwikiid);
    $html = format_text($html, FORMAT_MOODLE, array('overflowdiv'=>true, 'allowid'=>true));
	$wikioutput = $PAGE->get_renderer('mod_socialwiki');
	
	echo $wikioutput->viewing_area($page->title, $html, $page);
    //echo $OUTPUT->box($html);

    if (!empty($CFG->usetags)) {
        $tags = tag_get_tags_array('socialwiki_pages', $page->id);
        echo $OUTPUT->container_start('socialwiki-tags');
        echo '<span class="socialwiki-tags-title">'.get_string('tags').': </span>';
        $links = array();
        foreach ($tags as $tagid=>$tag) {
            $url = new moodle_url('/tag/index.php', array('tag'=>$tag));
            $links[] = html_writer::link($url, $tag, array('title'=>get_string('tagtitle', 'socialwiki', $tag)));
        }
        echo join($links, ", ");
        echo $OUTPUT->container_end();
    }

    socialwiki_increment_pageviews($page);
}

/**
 * This function trims any given text and returns it with some dots at the end
 *
 * @param string $text
 * @param string $limit
 *
 * @return string
 */
function socialwiki_trim_string($text, $limit = 25) {

    if (textlib::strlen($text) > $limit) {
        $text = textlib::substr($text, 0, $limit) . '...';
    }

    return $text;
}

/**
 * Prints default edit form fields and buttons
 *
 * @param string $format Edit form format (html, creole...)
 * @param integer $version Version number. A negative number means no versioning.
 */

function socialwiki_print_edit_form_default_fields($format, $pageid, $version = -1, $upload = false, $deleteuploads = array()) {
    global $CFG, $PAGE, $OUTPUT;

    echo '<input type="hidden" name="sesskey" value="' . sesskey() . '" />';

    if ($version >= 0) {
        echo '<input type="hidden" name="version" value="' . $version . '" />';
    }

    echo '<input type="hidden" name="format" value="' . $format . '"/>';

    //attachments
    require_once($CFG->dirroot . '/lib/form/filemanager.php');

    $filemanager = new MoodleQuickForm_filemanager('attachments', get_string('wikiattachments', 'socialwiki'), array('id' => 'attachments'), array('subdirs' => false, 'maxfiles' => 99, 'maxbytes' => $CFG->maxbytes));

    $value = file_get_submitted_draft_itemid('attachments');
    if (!empty($value) && !$upload) {
        $filemanager->setValue($value);
    }

    echo "<fieldset class=\"socialwiki-upload-section clearfix\"><legend class=\"ftoggler\">" . get_string("uploadtitle", 'socialwiki') . "</legend>";

    echo $OUTPUT->container_start('mdl-align socialwiki-form-center aaaaa');
    print $filemanager->toHtml();
    echo $OUTPUT->container_end();

    $cm = $PAGE->cm;
    $context = context_module::instance($cm->id);

    echo $OUTPUT->container_start('mdl-align socialwiki-form-center socialwiki-upload-table');
    socialwiki_print_upload_table($context, 'socialwiki_upload', $pageid, $deleteuploads);
    echo $OUTPUT->container_end();

    echo "</fieldset>";

    echo '<input class="socialwiki_button" type="submit" name="editoption" value="' . get_string('save', 'socialwiki') . '"/>';
    echo '<input class="socialwiki_button" type="submit" name="editoption" value="' . get_string('upload', 'socialwiki') . '"/>';
    echo '<input class="socialwiki_button" type="submit" name="editoption" value="' . get_string('preview') . '"/>';
    echo '<input class="socialwiki_button" type="submit" name="editoption" value="' . get_string('cancel') . '" />';
}

/**
 * Prints a table with the files attached to a wiki page
 * @param object $context
 * @param string $filearea
 * @param int $fileitemid
 * @param array deleteuploads
 */
function socialwiki_print_upload_table($context, $filearea, $fileitemid, $deleteuploads = array()) {
    global $CFG, $OUTPUT;

    $htmltable = new html_table();

    $htmltable->head = array(get_string('deleteupload', 'socialwiki'), get_string('uploadname', 'socialwiki'), get_string('uploadactions', 'socialwiki'));

    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'mod_socialwiki', $filearea, $fileitemid); //TODO: this is weird (skodak)

    foreach ($files as $file) {
        if (!$file->is_directory()) {
            $checkbox = '<input type="checkbox" name="deleteupload[]", value="' . $file->get_pathnamehash() . '"';

            if (in_array($file->get_pathnamehash(), $deleteuploads)) {
                $checkbox .= ' checked="checked"';
            }

            $checkbox .= " />";

            $htmltable->data[] = array($checkbox, '<a href="' . file_encode_url($CFG->wwwroot . '/pluginfile.php', '/' . $context->id . '/socialwiki_upload/' . $fileitemid . '/' . $file->get_filename()) . '">' . $file->get_filename() . '</a>', "");
        }
    }

    print '<h3 class="upload-table-title">' . get_string('uploadfiletitle', 'socialwiki') . "</h3>";
    print html_writer::table($htmltable);
}

/**
 * Generate wiki's page tree
 *
 * @param page_wiki $page. A wiki page object
 * @param navigation_node $node. Starting navigation_node
 * @param array $keys. An array to store keys
 * @return an array with all tree nodes
 */
function socialwiki_build_tree($page, $node, &$keys) {
    $content = array();
    static $icon;
    $icon = new pix_icon('f/odt', '');
    $pages = socialwiki_get_linked_pages($page->id);
    foreach ($pages as $p) {
        $key = $page->id . ':' . $p->id;
        if (in_array($key, $keys)) {
            break;
        }
        array_push($keys, $key);
        $l = socialwiki_parser_link($p);
        $link = new moodle_url('/mod/socialwiki/view.php', array('pageid' => $p->id));
        // navigation_node::get_content will format the title for us
        $nodeaux = $node->add($p->title, $link, null, null, null, $icon);
        if ($l['new']) {
            $nodeaux->add_class('socialwiki_newentry');
        }
        socialwiki_build_tree($p, $nodeaux, $keys);
    }
    $content[] = $node;
    return $content;
}

/**
 * Get linked pages from page
 * @param int $pageid
 */
function socialwiki_get_linked_pages($pageid) {
    global $DB;

    $sql = "SELECT p.id, p.title
            FROM {socialwiki_pages} p
            JOIN {socialwiki_links} l ON l.topageid = p.id
            WHERE l.frompageid = ?
            ORDER BY p.title ASC";
    return $DB->get_records_sql($sql, array($pageid));
}

/**
 * Get updated pages from wiki
 * @param int $pageid
 */
function socialwiki_get_updated_pages_by_subwiki($swid) {
    global $DB, $USER;

    $sql = "SELECT *
            FROM {socialwiki_pages}
            WHERE subwikiid = ? AND timemodified > ?
            ORDER BY timemodified DESC";
    return $DB->get_records_sql($sql, array($swid, $USER->lastlogin));
}

/**
 *returns an array of all the people the user is following
 *@param int $userid the users id
 */
function socialwiki_get_follows($userid,$subwikiid){
	global $DB;
	$sql='SELECT usertoid 
		  FROM {socialwiki_follows}
		  WHERE userfromid=? AND subwikiid=?';
	return $DB->get_records_sql($sql,array($userid,$subwikiid));
}

/*
 *checks if a user is following another user
 *@param int $userfromid the user doing the following
 *@param int $usertoid the user being followed
 */
function socialwiki_is_following($userfromid,$usertoid,$subwikiid)
{
	Global $DB;
	$sql='SELECT usertoid 
		  FROM {socialwiki_follows}
		  WHERE userfromid=? AND usertoid=? AND subwikiid= ?';
		  
	return $DB->record_exists_sql($sql,array($userfromid,$usertoid,$subwikiid));
}

function socialwiki_unfollow($userfromid,$usertoid, $subwikiid){
	Global $DB;
	$select='userfromid=? AND usertoid=? AND subwikiid=?';
	$DB->delete_records_select('socialwiki_follows',$select,array($userfromid,$usertoid, $subwikiid));
}


//returns true if the user likes the page
function socialwiki_liked($userid,$pageid){
global $DB;
	$sql='SELECT * 
		  FROM {socialwiki_likes}
		  WHERE userid=? AND pageid=?';
		  
	return $DB->record_exists_sql($sql,array($userid,$pageid));
}
//add a like
function socialwiki_add_like($userid,$pageid,$subwikiid){
	Global $DB;
	$like=new stdClass();
	$like->userid=$userid;
	$like->pageid=$pageid;
	$like->subwikiid=$subwikiid;
	$DB->insert_record('socialwiki_likes',$like);
}

//delete a like
function socialwiki_delete_like($userid,$pageid){
	Global $DB;
	$select='userid=? AND pageid=?';
	$DB->delete_records_select('socialwiki_likes',$select,array($userid,$pageid));
}

//get the number of likes for a page
function socialwiki_numlikes($pageid){
	global $DB;
	$sql='SELECT * 
		  FROM {socialwiki_likes}
		  WHERE pageid=?';
	return count($DB->get_records_sql($sql,array($pageid)));
}

//return all the pages the user likes
function socialwiki_getlikes($userid,$subwikiid){
global $DB;
	$sql='SELECT pageid 
		  FROM {socialwiki_likes}
		  WHERE userid=? and subwikiid=?';
	return $DB->get_records_sql($sql,array($userid,$subwikiid));
}

//get page's author
function socialwiki_get_author($pageid)
{
	global $DB;
		$sql='SELECT userid 
		  FROM {socialwiki_pages}
		  WHERE id=?';
		  
	return $DB->get_record_sql($sql,array($pageid));
}

//returns the id of the parent page
function socialwiki_get_parent($pageid){
	Global $DB;
	$sql='SELECT parent
		  FROM {socialwiki_pages}
		  WHERE id=?';
	return $DB->get_record_sql($sql,array($pageid));
}

//returns the children of a page
function socialwiki_get_children($pageid){
	Global $DB;
	$sql='SELECT *
		  FROM {socialwiki_pages}
		  WHERE parent=?';
	return $DB->get_records_sql($sql,array($pageid));
}

//returns an array with all the parent and child pages
function socialwiki_get_relations($pageid){
	$relations=array();
	$added=array();  //an array of page id's already added to $relations
	//add all parents up to root node
	while($pageid!=Null&&$pageid!=0){
		$relations[]=socialwiki_get_page($pageid);
		$added[]=$pageid;
		$pageid=socialwiki_get_parent($pageid)->parent;
	}
	//add all the children 
	for($i=0;$i<count($relations);$i++){
		$pages=socialwiki_get_children($relations[$i]->id);
		foreach($pages as $page){
			//make sure it hasn't already been added
			if(!in_array($page->id,$added)){
				$relations[]=socialwiki_get_page($page->id);
			}
		}
	}
	 sort($relations);
	 return $relations;
}
	
