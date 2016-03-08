<?php
/**
 * Options for the rater plugin
 */

$conf['voting_restriction'] = true;              // restrict ip address voting (true or false)
$conf['vote_qty']           = 1;                 // how many times an ip address can vote
$conf['can_edit_vote']      = true;              // user can vote again, last vote will be keeped
$conf['already_rated_msg']  ="You have already rated this item. You were allowed %s vote(s).";
$conf['not_selected_msg']   ="You have not selected a rating value.";
$conf['thankyou_msg']       ="Thank you for voting.";
$conf['generic_text']       ="this item";        // generic item text
