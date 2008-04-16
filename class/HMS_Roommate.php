<?php

/**
 * HMS Roommate class - Handles creating, confirming, and deleting roommate groups
 *
 * @author Jeremy Booker <jbooker at tux dot appstate dot edu>
 * @author Jeff Tickle <jtickle at tux dot appstate dot edu>
 */

define('ROOMMATE_REQ_TIMEOUT', 72); // The number of hours before a roommate request expires

class HMS_Roommate
{

    var $id                = 0;
    var $term              = null;
    var $requestor         = null;
    var $requestee         = null;
    var $confirmed         = 0;
    var $requested_on      = 0;
    var $confirmed_on      = null;

    /**
     * Constructor
     */
    function HMS_Roommate($id = 0)
    {
        if(!$id) {
            return;
        }

        $this->id = $id;
        $db = new PHPWS_DB('HMS_Roommate');
        $db->addWhere('id', $this->id);
        $result = $db->loadObject($this);
        if(!$result || PHPWS_Error::logIfError($result)) {
            $this->id = 0;
        }
    }

    function request($requestor, $requestee)
    {
        if(HMS_Roommate::can_live_together($requestor, $requestee) != E_SUCCESS) {
            return false;
        }

        $this->term         = $_SESSION['application_term'];
        $this->requestor    = $requestor;
        $this->requestee    = $requestee;
        $this->confirmed    = 0;
        $this->requested_on = mktime();

        return true;
    }

    function confirm()
    {
        if($id == 0)
            return false;

        $this->confirmed    = 1;
        $this->confirmed_on = mktime();

        return true;
    }

    function save()
    {
        $db = new PHPWS_DB('hms_roommate');
        $result = $db->saveObject($this);
        if(!$result || PHPWS_Error::logIfError($result)) {
            return false;
        }
        return true;
    }

    function delete()
    {
        $db = new PHPWS_DB('hms_roommate');
        $db->addWhere('id', $this->id);
        $result = $db->delete();

        if(PHPWS_Error::logIfError($result)) {
            return FALSE;
        }

        $this->id = 0;

        return TRUE;
    }

    function get_other_guy($username)
    {
        if(trim($this->requestor) == trim($username)) {
            return $this->requestee;
        }
        return $this->requestor;
    }

    /******************
     * Static Methods *
     ******************/
     
    function main()
    {
        switch($_REQUEST['op'])
        {
            default:
                echo "Unknown roommate op {$REQUEST['op']}";
                break;
        }
    }

    /**
     * Checks whether a given pair are involved in a roommate request already.
     *
     * @returns TRUE if so, FALSE if not
     *
     * @param a A user to check on
     * @param b Another user to check on
     */
    function have_requested_each_other($a, $b)
    {
        $db = new PHPWS_DB('hms_roommate');
        $db->addWhere('requestor', $a, 'ILIKE', 'AND', 'ab');
        $db->addWhere('requestee', $b, 'ILIKE', 'AND', 'ab');
        $db->addWhere('requestor', $b, 'ILIKE', 'AND', 'ba');
        $db->addWhere('requestee', $a, 'ILIKE', 'AND', 'ba');
        $db->addWhere('confirmed', 0, NULL, 'AND');
        $db->setGroupConj('ab', 'OR');
        $db->setGroupConj('ba', 'OR');
        $result = $db->count();

        if($result > 1) {
            // TODO: Log Weird Situation
        }

        return ($result > 0 ? TRUE : FALSE);
    }

    /* 
     * Returns TRUE if the student has a confirmed roommate, FALSE otherwise
     */ 
    function has_confirmed_roommate($asu_username)
    {
        $db = new PHPWS_DB('hms_roommate');
        $db->addWhere('requestor', $asu_username, 'ILIKE', 'OR', 'grp');
        $db->addwhere('requestee', $asu_username, 'ILIKE', 'OR', 'grp');
        $db->setGroupConj('grp', 'AND');
        $db->addWhere('confirmed', 1);
        $result = (int)$db->count();

        if(PHPWS_Error::logIfError($result))
            return $result;

        if($result > 1) {
            // TODO: Log Weird Situation
        }

        return ($result > 0 ? TRUE : FALSE);
    }
    
    /*
     * Returns the given user's confirmed roommate or FALSE if the roommate is unconfirmed
     */
    function get_confirmed_roommate($asu_username)
    {
        $db = new PHPWS_DB('hms_roommate');
        $db->addWhere('requestor', $asu_username, 'ILIKE', 'OR', 'grp');
        $db->addWhere('requestee', $asu_username, 'ILIKE', 'OR', 'grp');
        $db->setGroupConj('grp', 'AND');
        $db->addWhere('confirmed', 1);
        $db->addColumn('requestor');
        $db->addColumn('requestee');
        $result = $db->select('row');

        if(count($result) > 1) {
            // TODO: Log Weird Situation
        }

        if(count($result) == 0)
            return null;

        if(trim($result['requestor']) == trim($asu_username)) {
            return $result['requestee'];
        }

        return $result['requestor'];
    }

    /**
     * Checks whether a given user has made a roommate request which is still pending.
     *
     * @returns TRUE if so, FALSE if not
     *
     * @param username The user to check on
     */
    function has_roommate_request($username)
    {
        $db = new PHPWS_DB('hms_roommate');
        $db->addWhere('requestor', $username, 'ILIKE');
        $db->addWhere('confirmed', 0);
        $db->addWhere('requested_on', mktime() - (ROOMMATE_REQ_TIMEOUT * 60 * 60), '>=');
        $result = $db->count();

        if(PHPWS_Error::logIfError($result))
            return $result;

        return ($result > 0 ? TRUE : FALSE);
    }

    /**
     * Returns the asu username of the student which the given user has requested, or NULL
     * if either the user has not requested anyone or the pairing is confirmed.
     */
    function get_unconfirmed_roommate($asu_username)
    {
        $db = new PHPWS_DB('hms_roommate');
        $db->addWhere('requestor', $asu_username, 'ILIKE');
        $db->addWhere('confirmed', 0);
        $db->addWhere('requested_on', mktime() - (ROOMMATE_REQ_TIMEOUT * 60 * 60), '>=');
        $db->addColumn('requestee');
        $result = $db->select('col');

        if(count($result) > 1) {
            // TODO: Log Weird Situation
        }

        return $result[0];
    }

    /**
     * Returns an array of requests in which the given user is requestee
     */
    function get_pending_requests($asu_username)
    {
        $db = new PHPWS_DB('hms_roommate');
        $db->addWhere('requestee', $asu_username, 'ILIKE');
        $db->addWhere('confirmed', 0);
        $db->addWhere('requested_on', mktime() - (ROOMMATE_REQ_TIMEOUT * 60 * 60), '>=');
        $result = $db->getObjects('HMS_Roommate');

        return $result;
    }

    /**
     * Returns a count of pending requests
     */
    function count_pending_requests($asu_username)
    {
        $db = new PHPWS_DB('hms_roommate');
        $db->addWhere('requestee', $asu_username, 'ILIKE');
        $db->addWhere('confirmed', 0);
        $result = $db->count();

        return $result;
    }

    /**
     * Gets all Roommate objects in which this user is involved
     */
    function get_all_roommates($asu_username, $term)
    {
        $db = new PHPWS_DB('hms_roommate');
        $db->addWhere('requestor', $asu_username, 'ILIKE', 'OR', 'grp');
        $db->addWhere('requestee', $asu_username, 'ILIKE', 'OR', 'grp');
        $db->setGroupConj('grp', 'AND');
        $db->addWhere('term', $term);
        $result = $db->getObjects('HMS_Roommate');

        if(PHPWS_Error::logIfError($result))
            return FALSE;

        return $result;
    }

    /**
     * Removes all pending requests.  THIS DOES WORK SO BE CAREFUL.  Used when roommates are confirmed.
     * Logs each individual removal to cover our butts.
     */
    function remove_outstanding_requests($asu_username)
    {
        $db = new PHPWS_DB('hms_roommate');
        $db->addWhere('requestee', $asu_username, 'ILIKE');
        $db->addWhere('confirmed', 0);
        $requests = $db->getObjects('HMS_Roommate');

        if(PHPWS_Error::logIfError($requests)) {
            return FALSE;
        }

        if($requests == null)
            return TRUE;

        foreach($requests as $request) {
            $request->delete();
        }

        return TRUE;
    }

    function check_rlc_applications($a, $b, $term)
    {
        PHPWS_Core::initModClass('hms','HMS_RLC_Application.php');
        $result = HMS_RLC_Application::check_for_application($a, $term, FALSE);

        if(PHPWS_Error::isError($result)) {
            test($result,1);    // TODO: Break Cleanly
        }

        if($result == FALSE || $result == NULL)
            return TRUE;
        

        $resultb = HMS_RLC_Application::check_for_application($b, $term, FALSE);

        if($result == FALSE || $result == NULL)
            echo "roommate has not applied for an RLC";

        // Check to see if any of a's choices match any of b's choices
        if($result['rlc_first_choice_id']  == $resultb['rlc_first_choice_id'] ||
           $result['rlc_first_choice_id']  == $resultb['rlc_second_choice_id'] ||
           $result['rlc_first_choice_id']  == $resultb['rlc_third_choice_id'] ||
           $result['rlc_second_choice_id'] == $resultb['rlc_first_choice_id'] ||
           $result['rlc_second_choice_id'] == $resultb['rlc_second_choice_id'] ||
           $result['rlc_second_choice_id'] == $resultb['rlc_third_choice_id'] ||
           $result['rlc_third_choice_id']  == $resultb['rlc_first_choice_id'] ||
           $result['rlc_third_choice_id']  == $resultb['rlc_second_choice_id'] ||
           $result['rlc_third_choice_id']  == $resultb['rrlc_third_choice_id']){
            echo "applications match";
            return TRUE;
        }
    }

    function check_rlc_assignments($a, $b, $term)
    {
        PHPWS_Core::initModClass('hms','HMS_RLC_Assignment.php');
        $result = HMS_RLC_Assignment::check_for_assignment($a, $term);

        if(PHPWS_Error::isError($result)) {
            test($result,1);    // TODO: Break Cleanly
        }

        if($result == FALSE)
            return TRUE;

        $resultb = HMS_RLC_Assignment::check_for_assignment($b, $term);

        if($result == FALSE)
            return FALSE;

        return $result['rlc_id'] == $resultb['rlc_id'];
    }

    /**
     * In the spring, FT can request C.  In the fall, not so much.
     */
    function can_ft_and_c_live_together_this_term($requestor, $requestee)
    {
        $a_type = $requestor->student_type;
        $b_type = $requestee->student_type;

        // If there are no continuing students involved, we're good
        if(($a_type == TYPE_FRESHMEN ||
            $a_type == TYPE_TRANSFER) &&
           ($b_type == TYPE_FRESHMEN ||
            $b_type == TYPE_TRANSFER))
            return TRUE;

        $term = substr($_SESSION['application_term'], 4, 2);

        // This is acceptable in the spring
        if($term == TERM_SPRING && $b_type == TYPE_CONTINUING)
            return TRUE;

        // Any other time or types and we have a problem.
        return FALSE;
    }

    /**
     * Gets pager tags for the Student Main Menu page
     */
    function get_requested_pager_tags()
    {
        PHPWS_Core::initModClass('hms', 'HMS_SOAP.php');
        $name = HMS_SOAP::get_full_name($this->requestor);
        $tpl['NAME'] = PHPWS_Text::secureLink($name, 'hms', array('type'=>'student','op'=>'show_roommate_confirmation','id'=>$this->id));
        $expires = floor(($this->calc_req_expiration_date() - mktime()) / 60 / 60);
        if($expires == 0) {
            $expires = floor(($this->calc_req_expiration_date() - mktime()) / 60);
            $tpl['EXPIRES'] = $expires . ' minute' . ($expires > 1 ? 's' : '');
        } else {
            $tpl['EXPIRES'] = $expires . ' hour' . ($expires > 1 ? 's' : '');
        }
        return $tpl;
    }

    /**
     * Checks to see if two people hypothetically could live together based on
     * our rules.
     *
     * @returns TRUE if so, FALSE if not
     *
     * @param requestor The person requesting a roommate
     * @param requestee The person requested as a roommate
     */
    function can_live_together($requestor, $requestee)
    {
        // This is always a good idea
        $requestor = strToLower($requestor);
        $requestee = strToLower($requestee);

        // Sanity Checking
        if(is_null($requestor)) {
            return E_ROOMMATE_MALFORMED_USERNAME;
        }

        if(is_null($requestee)) {
            return E_ROOMMATE_MALFORMED_USERNAME;
        }

        // Make sure requestor didn't request self
        if($requestor == $requestee) {
            return E_ROOMMATE_REQUESTED_SELF;
        }

        // Check if the requestor has a confirmed roommate
        if(HMS_Roommate::has_confirmed_roommate($requestor)){
            return E_ROOMMATE_ALREADY_CONFIRMED;
        }

        // Check if the requestee has a confirmed roommate
        if(HMS_Roommate::has_confirmed_roommate($requestee)){
            return E_ROOMMATE_REQUESTED_CONFIRMED;
        }

        // Make sure requestor and requestee are not requesting each other
        if(HMS_Roommate::have_requested_each_other($requestor, $requestee)) {
            return E_ROOMMATE_ALREADY_REQUESTED;
        }

        // Make sure requestor does not have a pending roommate request
        if(HMS_Roommate::has_roommate_request($requestor)) {
            return E_ROOMMATE_PENDING_REQUEST;
        }

        // Use SOAP for the rest of the checks
        PHPWS_Core::initModClass('hms', 'HMS_SOAP.php');
        $requestor_info = HMS_SOAP::get_student_info($requestor, $_SESSION['application_term']);
        $requestee_info = HMS_SOAP::get_student_info($requestee, $_SESSION['application_term']);

        // Make sure the requestee is actually a user
        if(empty($requestee_info->last_name)) {
            return E_ROOMMATE_USER_NOINFO;
        }

        // Make sure we have compatible genders
        if($requestor_info->gender != $requestee_info->gender) {
            return E_ROOMMATE_GENDER_MISMATCH;
        }

        PHPWS_Core::initModClass('hms', 'HMS_Application.php');
        // Make sure the requestee has filled out an application
        if(HMS_Application::check_for_application($requestee, $_SESSION['application_term']) === false) {
            return E_ROOMMATE_NO_APPLICATION;
        }

        // Depending on term, freshmen and continuing may or may not be able to live
        // together... so this function makes sure of that.
        if(!HMS_Roommate::can_ft_and_c_live_together_this_term($requestor_info, $requestee_info)) {
            return E_ROOMMATE_TYPE_MISMATCH;
        }

        // If requestor is assigned to a different RLC, STOP and call HRL
        if(!HMS_Roommate::check_rlc_assignments($requestor, $requestee, $requestor_info->application_term)) {
            return E_ROOMMATE_RLC_ASSIGNMENT;
        }

        // If requestor applied to a different RLC, ask to remove application
        if(!HMS_Roommate::check_rlc_applications($requestor, $requestee, $requestor_info->application_term)) {
            return E_ROOMMATE_RLC_APPLICATION;
        }

        return E_SUCCESS;
    }

    /*******************
     * Utility Methods *
     *******************/

    /**
     * Calculates the date (in seconds since epoch) when a request made *now* will expire
     */
    function calc_req_expiration_date()
    {
        return ($this->requested_on + (ROOMMATE_REQ_TIMEOUT * 60 * 60));
    }
    
    /*****************
     * Email Methods *
     *****************/
     
    function send_emails() 
    {
        PHPWS_Core::initCoreClass('Mail.php');
        PHPWS_Core::initModClass('hms', 'HMS_SOAP.php');

        // set tags for the email to the person doing the requesting
        $message = "To:     " . HMS_SOAP::get_full_name($this->requestor) . "\n"; 
        $message .= "From:   Housing Management System\n\n";
        $message .= "This is a follow-up email to let you know you have requested " . HMS_SOAP::get_full_name($this->requestee) . " as your roommate.\n\n";
        $message .= "We have sent your requested roommate an email invitation to confirm his/her desire to be your roommate. Your requested ";
        $message .= "roommate must respond to this invitation within 72 hours or the invitation will expire. You will be notified ";
        $message .= "via email when your requested roommate either accepts or rejects the invitation.\n\n";
        $message .= "Please note that you can not reply to this email.\n";

        // create the Mail object and send it
        $requestor_mail = &new PHPWS_Mail;
        $requestor_mail->addSendTo($this->requestor . "@appstate.edu");
        $requestor_mail->setFrom('hms@tux.appstate.edu');
        $requestor_mail->setSubject('HMS Roommate Request');
        $requestor_mail->setMessageBody($message);
        $success = $requestor_mail->send();
        $success = true;
       
        if($success != TRUE) {
            return "There was an error emailing your requested roommate. Please contact Housing and Residence Life.";
        }

        $expire_date = $this->calc_req_expiration_date();

        // create the Mail object and send it
        $message = "To:     " . HMS_SOAP::get_full_name($this->requestee) . "\n";
        $message .= "From:  Housing Management System\n\n";
        $message .= "This email is to let you know " . HMS_SOAP::get_full_name($this->requestor) . " has requested you as a roommate.\n\n";
        $message .= "This request will expire on " . date('l, F jS, Y', $expire_date) . " at " . date('g:i A', $expire_date) . "\n\n";
        $message .= "You can accept or reject this invitation by logging into the Housing Management System.  Please log in and follow the directions under Step 5: Select A Roommate.\n\n";
        $message .= "Click the link below to access the Housing Management System:\n\n";
        $message .= "http://hms.appstate.edu/\n\n";
        $message .= "Please note that you can not reply to this email.\n";

        $requestee_mail = &new PHPWS_Mail;
        $requestee_mail->addSendTo($this->requestee . '@appstate.edu');
        $requestee_mail->setFrom('hms@tux.appstate.edu');
        $requestee_mail->setSubject('HMS Roommate Request');
        $requestee_mail->setMessageBody($message);
        $success = $requestee_mail->send();
        $success = true;

        if($success != TRUE) {
            return "There was an error emailing your requested roommate. Please contact Housing and Residence Life.";
        }

        return TRUE;
    }

    /**************
     * UI Methods *
     **************/

    function show_request_roommate($error_message = NULL)
    {
        PHPWS_Core::initCoreClass('Form.php');

        # Make sure the user doesn't already have a request out
        $result = HMS_Roommate::has_roommate_request($_SESSION['asu_username']);
        if(PHPWS_Error::isError($result)) {
            $tpl['ERROR_MSG'] = 'There was an unexpected database error which has been reported to the administrators.  Please try again later.';
            // TODO: Log and Report
            return PHPWS_Template::process($tpl, 'hms', 'student/select_roommate.tpl');
        }
        if($result === TRUE){
            $tpl['ERROR_MSG'] = 'You have a pending roommate request. You can not request another roommate request until your current request is either denied or expires.';
            return PHPWS_Template::process($tpl, 'hms', 'student/select_roommate.tpl');
        }

        # Make sur ethe user doesn't already have a confirmed roommate
        $result = HMS_Roommate::has_confirmed_roommate($_SESSION['asu_username']);
        if(PHPWS_Error::isError($result)) {

            $tpl['ERROR_MSG'] = 'There was an unexpected database error which has been reported to the administrators.  Please try again later.';
            // TODO: Log and Report
            return PHPWS_Template::process($tpl, 'hms', 'student/select_roommate.tpl');
        }
        if($result === TRUE) {
            $tpl['ERROR_MSG'] = 'You already have a roommate so you cannot make a roommate request.';
            return PHPWS_Template::process($tpl, 'hms', 'student/select_roommate.tpl');
        }
        
        $form = &new PHPWS_Form;

        $form->addText('username');
        
        $form->addHidden('module', 'hms');
        $form->addHidden('type', 'student');
        $form->addHidden('op', 'request_roommate');
        $form->addSubmit('submit', _('Request Roommate'));
        
        $form->addButton('cancel', 'Cancel');
        $form->setExtra('cancel','onClick="document.location=\'index.php?module=hms&type=student&op=show_main_menu\'"');

        $tpl = $form->getTemplate();

        if(isset($error_message)){
            $tpl['ERROR_MSG'] = $error_message;
        }

        return PHPWS_Template::process($tpl, 'hms', 'student/select_roommate.tpl');
    }

    /**
     * Creates a new roommate request, doing all appropriate gender
     * checks and such to make sure they can actually room together.
     *
     * @param requestor The person requesting a roommate
     * @param requestee The person requested as a roommate
     */
    function create_roommate_request($remove_rlc_app = FALSE)
    {
        if(empty($_REQUEST['username'])) {
            $error = "You did not enter a username.";
            return HMS_Roommate::show_select_roommate($error);
        }
        if(!PHPWS_Text::isValidInput($_REQUEST['username'])) {
            $error = "You entered an invalid user name. Please use letters and numbers *only*.";
            return $error;
        }

        $requestor = $_SESSION['asu_username'];
        $requestee = $_REQUEST['username'];

        // Did they say go ahead and trash the RLC application?
        if($remove_rlc_app) {
            PHPWS_Core::initModClass('hms', 'HMS_RLC_Application.php');
            $rlcapp = &new HMS_RLC_Application($requestor, $_SESSION['application_term']);
            $rlcapp->delete();
        }

        // Attempt to Create Roommate Request
        $result = HMS_Roommate::can_live_together($requestor, $requestee);

        if($result != E_SUCCESS) {
            // Pairing Error
            $msg = "";
            switch($result) {
                case E_ROOMMATE_MALFORMED_USERNAME:
                    $msg = "Malformed Username.";
                    break;
                case E_ROOMMATE_REQUESTED_SELF:
                    $msg = "You cannot request yourself.";
                    break;
                case E_ROOMMATE_ALREADY_CONFIRMED:
                    $msg = "You already have a confirmed roommate.";
                    break;
                case E_ROOMMATE_REQUESTED_CONFIRMED:
                    $msg = "The roommate you requested already has a confirmed roommate.";
                    break;
                case E_ROOMMATE_ALREADY_REQUESTED:
                    $msg = "You already have a pending request with $requestee.  Please <a href='index.php?module=hms&type=student&op=show_main_menu'>return to the main menu</a> and look under Step 5: Select A Roommate in order to confirm this request.";
                    break;
                case E_ROOMMATE_PENDING_REQUEST:
                    $msg = "You already have an uncomfirmed roommate request.";
                    break;
                case E_ROOMMATE_USER_NOINFO:
                    $msg = "Your requested roommate does not seem to have a student record.  Please be sure you typed the username correctly.";
                    break;
                case E_ROOMMATE_NO_APPLICATION:
                    $msg = "Your requested roommate has not filled out a housing application.";
                    break;
                case E_ROOMMATE_GENDER_MISMATCH:
                    $msg = "Please select a roommate of the same sex as yourself.";
                    break;
                case E_ROOMMATE_TYPE_MISMATCH:
                    $msg = "We cannot honor roommate requests for continuing students at this time.";
                    break;
                case E_ROOMMATE_RLC_ASSIGNMENT:
                    $msg = "You are currently assigned to a different Unique Housing Option than your requested roommate.  Please contact Housing and Residence Life if you would like to be removed from your Unique Housing Option.";
                    break;
                case E_ROOMMATE_RLC_APPLICATION:
                    return HMS_Roommate::requestor_handle_rlc_application($requestor, $requestee);
                default:
                    $msg = "Unknown Error $result.";
                    // TODO: Log Weirdness
                    break;
            }
            return HMS_Roommate::show_request_roommate($msg);
        }

        // Create request object and initialize
        $request = &new HMS_Roommate();
        $result = $request->request($requestor,$requestee);

        HMS_Activity_Log::log_activity($requestee,
                                       ACTIVITY_REQUESTED_AS_ROOMMATE,
                                       $requestor);

        if(!$result) {
            // TODO: Log and Notify
            $msg = "An unknown error has occurred.";
            return HMS_Roommate::show_request_roommate($msg);
        }

        // Save the Roommate object
        $result = $request->save();

        if(!$result) {
            // TODO: Log and Notify
            $msg = "An unknown error has occurred.";
            return HMS_Roommate::show_request_roommate($msg);
        }

        // Email both parties
        $result = $request->send_emails();
        if($result !== TRUE) {
            // TODO: Log and Notify
            $msg = "An unknown error has occurred.";
            return HMS_Roommate::show_request_roommate($msg);
        }

        return HMS_Roommate::show_requested_confirmation();
    }

    /**
     * Handle a requestor that has an RLC Application problem.
     */
    function requestor_handle_rlc_application($requestor, $requestee)
    {
        $form = &new PHPWS_Form;
        $form->addHidden('module', 'hms');
        $form->addHidden('type', 'student');
        $form->addHidden('op', 'roommate_confirm_rlc_removal');
        $form->addHidden('username', $requestee);

        $form->addSubmit('submit', 'Withdraw Unique Housing Options Application');

        $form->addButton('cancel', 'Cancel');
        $form->setExtra('cancel','onClick="document.location=\'index.php?module=hms&type=student&op=show_main_menu\'"');

        return PHPWS_Template::process($form->getTemplate(), 'hms', 'student/requestor_handle_rlc_application.tpl');
    }

    /*
     * Shows a "you successfully requested ab1234" as your roommate" message
     */
    function show_requested_confirmation()
    {
        PHPWS_Core::initModClass('hms', 'HMS_SOAP.php');
        $tpl['REQUESTED_ROOMMATE_NAME'] = HMS_SOAP::get_full_name($_REQUEST['username']);
        $tpl['MENU_LINK']               = PHPWS_Text::secureLink('Click here to return to the main menu.', 'hms', array('module'=>'hms', 'type'=>'student', 'op'=>'show_main_menu'));
        return PHPWS_Template::process($tpl, 'hms', 'student/select_roommate_confirmation.tpl');
    }

    /**
     * Shows the Approve/Reject Screen
     */
    function show_approve_reject($request)
    {
        $accept_form = new PHPWS_Form;
        $accept_form->addHidden('module', 'hms');
        $accept_form->addHidden('type', 'student');
        $accept_form->addHidden('op', 'confirm_accept_roommate');
        $accept_form->addHidden('id', $request->id);
        $accept_form->addSubmit('Accept Roommate');

        $reject_form = new PHPWS_Form;
        $reject_form->addHidden('module', 'hms');
        $reject_form->addHidden('type', 'student');
        $reject_form->addHidden('op', 'confirm_reject_roommate');
        $reject_form->addHidden('id', $request->id);
        $reject_form->addSubmit('Reject Roommate');

        $cancel_form = new PHPWS_Form;
        $cancel_form->setMethod('get');
        $cancel_form->addHidden('module', 'hms');
        $cancel_form->addHidden('type', 'student');
        $cancel_form->addHidden('op', 'show_main_menu');
        $cancel_form->addSubmit('Cancel');

        // TODO: This thing needs to handle RLC Assignments, but it's broken right now so I'm not going to waste my time.

        PHPWS_Core::initModClass('hms', 'HMS_SOAP.php');
        $tpl['REQUESTOR_NAME'] = HMS_SOAP::get_full_name($request->requestor);

        $tpl['ACCEPT'] = PHPWS_Template::process($accept_form->getTemplate(), 'hms', 'student/roommate_accept_reject_form.tpl');
        $tpl['REJECT'] = PHPWS_Template::process($reject_form->getTemplate(), 'hms', 'student/roommate_accept_reject_form.tpl');
        $tpl['CANCEL'] = PHPWS_Template::process($cancel_form->getTemplate(), 'hms', 'student/roommate_accept_reject_form.tpl');

        return PHPWS_Template::process($tpl, 'hms', 'student/roommate_accept_reject_screen.tpl');
    }

    /**
     * Shows the Confirm Accept Screen, captcha and all
     */
    function confirm_accept($request, $error = null)
    {
        PHPWS_Core::initCoreClass('Captcha.php');
        PHPWS_Core::initModClass('hms', 'HMS_SOAP.php');

        $form = &new PHPWS_Form;
        $form->addHidden('module', 'hms');
        $form->addHidden('type', 'student');
        $form->addHidden('op', 'for_realz_accept_roommate');
        $form->addHidden('id', $request->id);

        $form->addText('captcha');
        $form->addTplTag('CAPTCHA_IMAGE', Captcha::get());
        $form->addTplTag('NAME', HMS_SOAP::get_full_name($request->requestor));

        if(!HMS_Roommate::check_rlc_applications($request->requestee, $request->requestor, $_SESSION['application_term']))
            $form->addTplTag('RLC', 'ohno');

        if(!is_null($error)) {
            $form->addTplTag('ERROR', $error);
        }

        $form->addSubmit('Confirm');

        return PHPWS_Template::process($form->getTemplate(), 'hms', 'student/roommate_accept_confirm.tpl');
    }

    /**
     * Verify the captcha, and if it's all good, mark the confirmed flag
     * + Should probably also remove any outstanding requests for either roommate, and log that this happened
     */
    function accept_for_realz($request)
    {
        PHPWS_Core::initCoreClass('Captcha.php');
        if(!Captcha::verify($_POST['captcha'])) {
            return HMS_Roommate::confirm_accept($request, 'Sorry, please try again.');
        }

        HMS_Activity_Log::log_activity($request->requestor,
                                       ACTIVITY_ACCEPTED_AS_ROOMMATE,
                                       $request->requestee,
                                       "CAPTCHA: {$_POST['captcha']}");
        
        $request->confirmed = 1;
        $request->confirmed_on = mktime();
        $request->save();

        // Remove any other requests for the requestor
        HMS_Roommate::remove_outstanding_requests($request->requestor);

        // Remove any other requests for the requestee
        HMS_Roommate::remove_outstanding_requests($request->requestee);

        PHPWS_Core::initModClass('hms', 'HMS_SOAP.php');

        // If they got this far they already agreed to dump an RLC application
        if(!HMS_Roommate::check_rlc_applications($request->requestee, $request->requestor, $_SESSION['application_term'])) {
            $rlcapp = &new HMS_RLC_Application($request->requestee, $_SESSION['application_term']);
            $rlcapp->delete();
        }

        $tpl['NAME']      = HMS_SOAP::get_full_name($request->requestor);
        $tpl['MENU_LINK'] = PHPWS_Text::secureLink('Click here to return to the main menu.', 'hms', array('module'=>'hms', 'type'=>'student', 'op'=>'show_main_menu'));
        return PHPWS_Template::process($tpl, 'hms', 'student/roommate_accept_done.tpl');
    }

    /**
     * Removes the request and tells the user that if it was an oops, go back and re-request, thank you.
     */
    function reject_for_realz($request)
    {
        PHPWS_Core::initModClass('hms', 'HMS_SOAP.php');
        $tpl['NAME']      = HMS_SOAP::get_full_name($request->requestor);
        $tpl['USERNAME']  = $request->requestor;
        $tpl['MENU_LINK'] = PHPWS_Text::secureLink('Click here to return to the main menu.', 'hms', array('module'=>'hms', 'type'=>'student', 'op'=>'show_main_menu'));

        HMS_Activity_Log::log_activity($request->requestor,
                                       ACTIVITY_REJECTED_AS_ROOMMATE,
                                       $request->requestee);

        $request->delete();

        return PHPWS_Template::process($tpl, 'hms', 'student/roommate_reject_done.tpl');
    }

    /**
     * Shows a pager of roommate requests
     */
    function display_requests($asu_username)
    {
        PHPWS_Core::initCoreClass('DBPager.php');
        $pager = new DBPager('hms_roommate', 'HMS_Roommate');
        $pager->setModule('hms');
        $pager->setTemplate('student/requested_roommate_list.tpl');
        $pager->addRowTags('get_requested_pager_tags');
        $pager->db->addWhere('requestee', $asu_username, 'ILIKE');
        $pager->db->addWhere('confirmed', 0);
        return $pager->get();
    }
}

?>
