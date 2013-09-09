<?php // $Id$

/**
 * Page containing hierarchy item search results
 *
 * @copyright Totara Learning Solution Limited
 * @author Simon Coggins
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package learningpool
 * @subpackage dialog
 */

require_once(dirname(__FILE__).'/../../../../config.php');

global $CFG, $PAGE;

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/blocks/lp_hierarchy/lib.php');
require_once($CFG->dirroot . '/local/learningpool/dialogs/search_form.php');
require_once($CFG->dirroot . '/local/learningpool/dialogs/dialog_content_hierarchy.class.php');


require_login();

$PAGE->set_context(build_context_path());

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

/**
 * How many search results to show before paginating
 *
 * @var integer
 */
define('HIERARCHY_SEARCH_NUM_PER_PAGE', 50);

$query = optional_param('query', null, PARAM_TEXT); // search query
$page = optional_param('page', 0, PARAM_INT); // results page number

$strsearch = get_string('search');

$hierarchy = Hierarchy::get_instance();

// Trim whitespace off seach query
$query = urldecode(trim($query));

$hidden = '';

// Create form
$mform = new dialog_search_form($CFG->wwwroot. '/local/learningpool/hierarchy/item/search.php',
    compact('hidden', 'query'));

// Display form
$mform->display();

// Display results
if (strlen($query)) {
    global $DB,$OUTPUT;

    // extract quoted strings from query
    $keywords = hierarchy_search_parse_keywords($query);

    $fields = 'SELECT id,fullname';
    $count = 'SELECT COUNT(*)';
    $from = " FROM {lp_hierarchy}";
    $order = ' ORDER BY sortorder';
    $params = array();

    // match search terms
    $where = hierarchy_search_get_keyword_where_clause($keywords,$params);


    // don't show hidden items
    $where .= ' AND visible=1';

    $total = $DB->count_records_sql($count . $from . $where, $params);
    $start = $page * HIERARCHY_SEARCH_NUM_PER_PAGE;

    if($total) {
        if($results = $DB->get_records_sql($fields . $from . $where .
            $order, $params, $start, HIERARCHY_SEARCH_NUM_PER_PAGE)) {

            $data = array('query' => urlencode(stripslashes($query)));
            $url = new moodle_url($CFG->wwwroot . '/local/learningpool/hierarchy/item/search.php', $data);
            print '<div class="search-paging">';
            $pagingbar = new paging_bar($total, $page, HIERARCHY_SEARCH_NUM_PER_PAGE, $url, 'page');
            echo $OUTPUT->render($pagingbar);
            print '</div>';

            $addbutton_html = '<img src="'.$OUTPUT->pix_url('add', 'theme').'" class="addbutton" />';

            // Generate some treeview data
            $dialog = new lp_dialog_content_hierarchy('organisation');
            $dialog->items = array();
            $dialog->parent_items = array();

            foreach($results as $result) {
                $title = hierarchy_search_get_path($hierarchy, $result->id);

                $item = new object();
                $item->id = $result->id;
                $item->fullname = $result->fullname;
                $item->hover = $title;

                $dialog->items[$item->id] = $item;
            }

            echo $dialog->generate_treeview();

        } else {
            // if count succeeds, query shouldn't fail
            // must be something wrong with query
            print $strqueryerror;
        }
    } else {
        $params = new object();
        $params->query = stripslashes($query);
        print '<p class="message">' . get_string('noresultsfor', 'local_learningpool', $params). '</p>';
    }
} else {
    print '<br />';
}


/**
 * Parse a query into individual keywords, treating quoted phrases one item
 *
 * Pairs of matching double or single quotes are treated as a single keyword.
 *
 * @param string $query Text from user search field
 *
 * @return array Array of individual keywords parsed from input string
 */
function hierarchy_search_parse_keywords($query) {
    // query arrives with quotes escaped, but quotes have special meaning
    // within a query. Strip out slashes, then re-add any that are left
    // after parsing done (to protect against SQL injection)
    $query = stripslashes($query);

    $out = array();
    // break query down into quoted and unquoted sections
    $split_quoted = preg_split('/(\'[^\']+\')|("[^"]+")/', $query, 0,
        PREG_SPLIT_DELIM_CAPTURE);
    foreach($split_quoted as $item) {
        // strip quotes from quoted strings but leave spaces
        if(preg_match('/^(["\'])(.*)\\1$/', trim($item), $matches)) {
            $out[] = addslashes($matches[2]);
        } else {
            $keyword = array();
            // split unquoted text on whitespace
            $split = preg_split('/\s/', $item, 0,PREG_SPLIT_NO_EMPTY);
            foreach ($split as $s){
                $keyword []= addslashes($s);
            }
            $out = array_merge($out, $keyword);
        }
    }
    return $out;
}


/**
 * Return an SQL WHERE clause to search for the given keywords
 *
 * @param array $keywords Array of strings to search for
 * @param array &$params ref of params to fill
 *
 * @return string SQL WHERE clause to match the keywords provided
 */
function hierarchy_search_get_keyword_where_clause($keywords,&$params) {
    global $DB;

    if (empty($params)){
        $params = array();
    }
    
    // fields to search
    $fields = array('fullname', 'shortname', 'description');

    $queries = array();
    $count = 0;
    foreach($keywords as $keyword) {
        ++$count;
        $matches = array();
        foreach($fields as $field) {
            $matches[] = $DB->sql_like($field,":{$field}{$count}",false);
            $params[$field.$count] = '%'.$keyword.'%';
            #$matches[] = $field . ' ' . sql_ilike() . " '%" . $keyword . "%'";
        }
        // look for each keyword in any field
        $queries[] = '(' . implode(' OR ', $matches) . ')';
    }
    // all keywords must be found in at least one field
    return ' WHERE ' . implode(' AND ', $queries);
}


/**
 * Returns the name of the item, preceeded by all parent nodes that lead to it
 *
 * @param object $hierarchy Hierarchy object that this item belongs to
 * @param integer $id ID of the hierarchy item to generate path for
 *
 * @return string Text string containing ordered path to this item in hierarchy
 */
function hierarchy_search_get_path($hierarchy, $id) {
    $path = '';

    // this gives all items in path, but not in order
   /* $members = $hierarchy->get_item_lineage($id);

    // find order by starting from parent id of 0 (top
    // of tree) and working down

    // prevent infinite loop in case of bad members list
    $escape = 0;

    // start at top of tree
    $parentid = 0;
    while(count($members) && $escape < 100) {
        foreach($members as $key => $member) {
            if($member->parentid == $parentid) {
                // add to path
                if($parentid) {
                    // include ' > ' before name except on top element
                    $path .= ' &gt; ';
                }
                $path .= $member->fullname;
                // now update parent id and
                // unset this element
                $parentid = $member->id;
                unset($members[$key]);
            }
        }
        $escape++;
    } */

    return $path;
}

