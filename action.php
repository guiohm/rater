<?php
/******************************************************************************
**
**  action script related to Rater
**  Action to count votes and display message
*/
/******************************************************************************
**  must run within Dokuwiki
**/
if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'action.php');

/******************************************************************************/
class action_plugin_rater extends DokuWiki_Action_Plugin {

    var $parameter = "";

    /**
    * return some info
    */
    function getInfo(){
        return array(
            'author' => 'Taggic',
            'email'  => 'Taggic@t-online.de',
            'date'   => '2012-10-28',
            'name'   => 'rater (action plugin component)',
            'desc'   => 'to store votes and display feedback.',
            'url'    => 'http://www.dokuwiki.org/plugin:rater',
            );
    }

    /**************************************************************************
    **  Register its handlers with the dokuwiki's event controller
    */
    function register(&$controller) {
       $controller->register_hook('ACTION_ACT_PREPROCESS', 'BEFORE', $this, '_handle_act', array());
       $controller->register_hook('TPL_ACT_UNKNOWN', 'BEFORE', $this, 'output', array());
    }

    /**************************************************************************
    **  Handle the action
    */
    function _handle_act(&$event, $param) {
        if ($event->data === 'rate_voteup') {
            $this->vote = 1;
        }
        elseif ($event->data === 'rate_votedown') {
            $this->vote = 2;
        }
        else return;

        $this->raterfile = $_GET['rater_file'];
        $this->rater_id = $_GET['rater_id'];
        $this->rater_name = $_GET['rater_name'];
        $this->rater_ip = $_GET['rater_ip'];
        $this->rater_end = $_GET['rater_end'];
        $this->anker_id = $_GET['anker'];

        $event->preventDefault(); // https://www.dokuwiki.org/devel:events#event_object
    }

    /**************************************************************************
    **  Generate output
    */
    function output(&$data) {
        if (($data->data == 'rate_voteup') && ($this->vote == 1)) {
          $data->preventDefault();
          $rater_rating = 1;
        }
        elseif (($data->data == 'rate_votedown') && ($this->vote == 2)) {
          $data->preventDefault();
          $rater_rating = 2;
        }
        else { return; }

        global $ID;
        global $lang;
        global $conf;

        $rater_type = "vote";
        $rater_id   = $this->rater_id;
        $rater_name = $this->rater_name;
        $rater_ip   = $this->rater_ip;
        $rater_end  = $this->rater_end;
        $anker_id   = $this->anker_id;

        // Config settings
        $rater_ip_voting_restriction = $this->getConf('voting_restriction'); // restrict ip address voting (true or false)
        $rater_ip_vote_qty           = $this->getConf('vote_qty');           // how many times an ip address can vote
        $rater_can_edit_vote         = $this->getConf('can_edit_vote');      // overwrite vote if exists
        $rater_already_rated_msg     = sprintf($this->getConf('already_rated_msg'),$rater_ip_vote_qty);
        $rater_not_selected_msg      = $this->getConf('not_selected_msg');
        $rater_thankyou_msg          = $this->getConf('thankyou_msg');
        $rater_generic_text          = $this->getConf('generic_text');       // generic item text
        $rater_end_of_line_char      = $this->getConf('eol_char');           // to separate the records
        //          msg("eol_char = |".$rater_end_of_line_char."|",0);
        if ($rater_end_of_line_char == '') $rater_end_of_line_char = '\n';

        $msg_votended                = $this->getLang('msg_votended');
        $alink_Back                  = $this->getLang('alink_Back');
        $today                       = date('d.m.Y');

        //check if vote period already ended
        if (($rater_end!='never') && (strtotime($today) > strtotime($rater_end))){
            $rater_endmsg =sprintf($msg_votended,date('d.m.Y',strtotime($rater_end))).'<br>';

            echo '<meta http-equiv="refresh" content="1; URL=doku.php?id='.$ID.'#'.$anker_id.'"><div class="thumb__negative_feedback">'.$rater_endmsg.
            '<a href="doku.php?id='.$ID.'" />'.$alink_Back.'</a></div>';
            return;
        }


    //        save vote
        $rater_filename = metaFN('rater_'.$rater_id.'_'.$rater_name.'_'.$rater_type, '.rating');
    // trace ip or login
        $file_overwritten = false;
        $rater_file=fopen($rater_filename,"c+");
        $rater_str="";
        $rater_str = rtrim(fread($rater_file, 1024*8),$rater_end_of_line_char);
        if ($rater_str!=""){
            if ($rater_ip_voting_restriction){
                $rater_data=explode($rater_end_of_line_char,$rater_str);
                $rater_ip_vote_count=0;
                foreach ($rater_data as $d){
                    $rater_tmp=explode("|",$d);
                    $rater_oldip=str_replace($rater_end_of_line_char,"",$rater_tmp[1]);
                    if ($rater_ip==$rater_oldip) {
                        if ($rater_can_edit_vote) {
                            $rater_str = str_replace($d, $rater_rating."|".$rater_ip.$rater_end_of_line_char, $rater_str);
                            $this->overwrite_file($rater_file, $rater_str);
                            $file_overwritten = true;
                        } else {
                            $rater_ip_vote_count++;
                        }
                        break;
                    }
                }
                if ($rater_ip_vote_count > ($rater_ip_vote_qty - 1)){
                    $rater_msg=$rater_already_rated_msg;
                    $addMXG = "&info=ppp";
                } else {
                    if (!$file_overwritten) {
                        $this->append_to_file($rater_file, $rater_str, $rater_rating."|".$rater_ip);
                    }
                    $rater_msg=$rater_thankyou_msg;
                    if($rater_rating===2) {
                        $rater_msg .= $this->getLang('msg_why');
                        $addMXG = '';
                        echo '<div class="thumb__positive_feedback">'.$rater_ip.' : '.$rater_msg.'<br />'.
                        '<a href="doku.php?id='.$ID.'#'.$anker_id.'" />'.$alink_Back.'</a></div>';
                        return;
                    }
                }
            } else {
                $this->append_to_file($rater_file, $rater_str, $rater_rating."|".$rater_ip);
                $rater_msg=$rater_thankyou_msg;
            }
        } else {
            $this->append_to_file($rater_file, $rater_str, $rater_rating."|".$rater_ip);
            $rater_msg=$rater_thankyou_msg;
        }

    //            msg($rater_rating."|".$rater_ip.$rater_end_of_line_char,0);

      // reload original page
        echo '<meta http-equiv="refresh" content="2; URL=doku.php?id='.$ID.'#'.$anker_id.'"><div class="thumb__positive_feedback">'.$rater_ip.' : '.$rater_msg.'<br />'.
            '<a href="doku.php?id='.$ID.'#'.$anker_id.'" />'.$alink_Back.'</a></div>';
    }
/******************************************************************************/

    function overwrite_file($file, $str) {
        rewind($file);
        fwrite($file, $str);
        fflush($file);
        ftruncate($file, ftell($file));
        fclose($file);
    }

    function append_to_file($file, $content, $new_content) {
        $this->overwrite_file($file, $content.$rater_end_of_line_char.$new_content.$rater_end_of_line_char);
    }
}
