<?php

class WP_Query_State {
    var $single = false;
    var $archive = false;
    var $date = false;
    var $author = false;
    var $category = false;
    var $search = false;
    var $feed = false;
    var $home = false;
    
    function init () {
        $this->single = false;
        $this->archive = false;
        $this->date = false;
        $this->author = false;
        $this->category = false;
        $this->search = false;
        $this->feed = false;
        $this->home = false;
    }

    function parse_query ($query) {
        parse_str($query);
        $this->init();

        if ('' != $name) {
            $this->single = true;
        }

        if (($p != '') && ($p != 'all')) {
            $this->single = true;
        }

        if ('' != $m) {
            $this->date = true;
        }

        if ('' != $hour) {
            $this->date = true;            
        }

        if ('' != $minute) {
            $this->date = true;
        }

        if ('' != $second) {
            $this->date = true;
        }

        if ('' != $year) {
            $this->date = true;
        }

        if ('' != $monthnum) {
            $this->date = true;
        }

        if ('' != $day) {
            $this->date = true;
        }

        if ('' != $w) {
            $this->date = true;
        }

        // If year, month, day, hour, minute, and second are set, a single 
        // post is being queried.        
        if (('' != $hour) && ('' != $minute) &&('' != $second) && ('' != $year) && ('' != $monthnum) && ('' != $day)) {
            $this->single = true;
        }

        if (!empty($s)) {
            $this->search = true;
        }

        if (empty($cat) || ($cat == 'all') || ($cat == '0')) {
            $this->category = false;
        } else {
            if (stristr($cat,'-')) {
                $this->category = false;
            } else {
                $this->category = true;
            }
        }

        if ('' != $category_name) {
            $this->category = true;
        }
            
        // single, date, and search override category.
        if ($this->single || $this->date || $this->search) {
            $this->category = false;                
        }

        if ((empty($author)) || ($author == 'all') || ($author == '0')) {
            $this->author = false;
        } else {
            $this->author = true;
        }

        if ('' != $author_name) {
            $this->author = true;
        }

        if ('' != $feed) {
            $this->feed = true;
        }

        if ( ($this->date || $this->author || $this->category)
             && (! $this->single)) {
            $this->archive = true;
        }

        if ( ! ($this->archive || $this->single || $this->search || $this->feed)) {
            $this->home = true;
        }

    }

    function WP_Query_State ($query = '') {
        if (! empty($query)) {
            $this->parse_query($query);
        }
    }
}

// Make a global instance.
if (! isset($wp_query_state)) {
    $wp_query_state = new WP_Query_State();
}

?>
