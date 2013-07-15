<?php

/**
 * Parser utils and default callbacks.
 *
 * @author Josep Arús
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package socialwiki
 */

require_once($CFG->dirroot . "/lib/outputcomponents.php");
    
class socialparser_utils {
        
    public static function h($tag, $text = null, $options = array(), $escape_text = false) {
        $tag = htmlentities($tag, ENT_COMPAT, 'UTF-8');
        if(!empty($text) && $escape_text) {
                $text = htmlentities($text, ENT_COMPAT, 'UTF-8');
            }
        return html_writer::tag($tag, $text, $options);
    }
    
    /**
     * Default link generator
     */

    public static function socialwiki_parser_link_callback($link, $options) {
        $l = urlencode($link);

        if(!empty($options['anchor'])) {
            $l .= "#".urlencode($options['anchor']);
        }
        return array('content' => $link, 'url' => "http://".$l);
    }


    /**
     * Default table generator
     */

    public static function socialwiki_parser_table_callback($table) {
        $html = "";
        $headers = $table[0];
        $columncount = count($headers);
        $headerhtml = "";
        foreach($headers as $h) {
            $text = trim($h[1]);
            if($h[0] == 'header') {
                $headerhtml .= "\n".socialparser_utils::h('th', $text)."\n";
                $hasheaders = true;
            }
            else if($h[0] == 'normal'){
                $headerhtml .= "\n".socialparser_utils::h("td", $text)."\n";
            }
        }
        $headerhtml = "\n".socialparser_utils::h('tr', $headerhtml)."\n";
        $bodyhtml = "";
        if(isset($hasheaders)) {
            $html = "\n".socialparser_utils::h('thead', $headerhtml)."\n";
        }
        else {
            $bodyhtml .= $headerhtml;
        }

        array_shift($table);
        foreach($table as $row) {
            $htmlrow = "";
            for($i = 0; $i < $columncount; $i++) {
                $text = "";
                if(!isset($row[$i])) {
                    $htmlrow .= "\n".socialparser_utils::h('td', $text)."\n";
                }
                else {
                    $text = trim($row[$i][1]);
                    if($row[$i][0] == 'header') {
                        $htmlrow .= "\n".socialparser_utils::h('th', $text)."\n";
                    }
                    else if($row[$i][0] == 'normal'){
                        $htmlrow .= "\n".socialparser_utils::h('td', $text)."\n";
                    }   
                }
            }
            $bodyhtml .= "\n".socialparser_utils::h('tr', $htmlrow)."\n";
        }

        $html .= "\n".socialparser_utils::h('tbody', $bodyhtml)."\n";
        return "\n".socialparser_utils::h('table', $html)."\n";
    }
    
    /**
     * Default path converter
     */
    
    public static function socialwiki_parser_real_path($url) {
        return $url;
    }
}

