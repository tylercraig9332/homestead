<?php

PHPWS_Core::initModClass('hms', 'View.php');
PHPWS_Core::initModClass('hms', 'HMS_Util.php');
PHPWS_Core::initModClass('hms', 'ActivityLogView.php');

class StudentProfileView extends View {

	private $student;
	private $applications;
	private $assignment;
	private $roommates;

	public function __construct(Student $student, Array $applications = NULL, HMS_Assignment $assignment = NULL, Array $roommates){
		$this->student		= $student;
		$this->applications = $applications;
		$this->assignment	= $assignment;
		$this->roommates	= $roommates;
	}

	public function show()
	{
		javascript('/jquery/');
		javascript('/modules/hms/jquery_ui/');
		javascript('/modules/hms/student_info/');

		$tpl = array();

		$tpl['TITLE'] = "Search Results - " . Term::getPrintableSelectedTerm();
		$tpl['USERNAME'] = $this->student->getUsername();

		if( Current_User::allow('hms', 'login_as_student') ) {
			$loginAsStudent = CommandFactory::getCommand('LoginAsStudent');
			$loginAsStudent->setUsername($this->student->getUsername());

			$tpl['LOGIN_AS_STUDENT'] = $loginAsStudent->getLink('Login as student');
		}

		$tpl['BANNER_ID']   = $this->student->getBannerId();
		$tpl['NAME']  = $this->student->getFullName();

		$tpl['TERM'] = Term::getPrintableSelectedTerm();

		$tpl['GENDER'] = $this->student->getPrintableGender();

		$tpl['DOB'] = $this->student->getDOB();

		$tpl['CLASS'] = $this->student->getPrintableClass();

		$tpl['TYPE'] = $this->student->getPrintableType();
		$tpl['APPLICATION_TERM'] = Term::toString($this->student->getApplicationTerm());

		/*****************
		 * Phone Numbers *
		 *****************/
		foreach($this->student->getPhoneNumberList() as $phone_number){
			$tpl['phone_number'][] = array('NUMBER' =>$phone_number);
		}

		/*************
		 * Addresses *
		 *************/
		foreach($this->student->getAddressList() as $address){
			//If it's not a PS or PR address, skip it
			if($address->atyp_code != 'PR' && $address->atyp_code != 'PS'){
				continue;
			}

			switch ($address->atyp_code){
				case 'PS':
					$addr_type = 'Student Address';
					break;
				case 'PR':
					$addr_type = 'Permanent Residence Address';
					break;
				default:
					$addr_type = 'Unknown-type address';
			}

			$addr_array = array();
			$addr_array['ADDR_TYPE']	= $addr_type;
			$addr_array['ADDRESS_L1']	= $address->line1;
			if(isset($address->line2))
			    $addr_array['ADDRESS_L2']	= $address->line2;
			if(isset($address->line3))
			    $addr_array['ADDRESS_L3']	= $address->line3;
			$addr_array['CITY']			= $address->city;
			$addr_array['STATE']		= $address->state;
			$addr_array['ZIP']			= $address->zip;

			$tpl['addresses'][] = $addr_array;
		}

		/**************
		 * Assignment *
		 **************/
		if(!is_null($this->assignment)){
			$reassignCmd = CommandFactory::getCommand('ShowAssignStudent');
			$reassignCmd->setUsername($this->student->getUsername());

			$unassignCmd = CommandFactory::getCommand('ShowUnassignStudent');
			$unassignCmd->setUsername($this->student->getUsername());
			$tpl['ASSIGNMENT'] = $this->assignment->where_am_i(true) . ' ' . $reassignCmd->getLink('Reassign') . ' ' . $unassignCmd->getLink('Unassign');
		}else{
			$assignCmd = CommandFactory::getCommand('ShowAssignStudent');
			$assignCmd->setUsername($this->student->getUsername());
			$tpl['ASSIGNMENT'] = 'No [' . $assignCmd->getLink('Assign Student') . ']';
			//$tpl['ASSIGNMENT'] = 'No';
		}

		// Roommates
		if(isset($this->roommates) && !empty($this->roommates)){
			foreach($this->roommates as $roommate){
				$tpl['roommates'][]['ROOMMATE'] = $roommate;
			}
		}else{
			$tpl['roommates'][] = array('ROOMMATE' => 'No pending or confirmed roommates');
		}

		/**************
		 * RLC Status *
		 */
		PHPWS_Core::initModClass('hms', 'HMS_Learning_Community.php');
		PHPWS_Core::initModClass('hms', 'HMS_RLC_Application.php');
		PHPWS_Core::initModClass('hms', 'HMS_RLC_Assignment.php');

		$rlc_names = HMS_Learning_Community::getRLCList();

		$rlc_assignment     = HMS_RLC_Assignment::check_for_assignment($this->student->getUsername(), Term::getSelectedTerm());
		$rlc_application    = HMS_RLC_Application::check_for_application($this->student->getUsername(), Term::getSelectedTerm(), FALSE);

		if($rlc_assignment != FALSE){
			$tpl['RLC_STATUS'] = "This student is assigned to: " . $rlc_names[$rlc_assignment['rlc_id']];
		}else if ($rlc_application != FALSE){
			$tpl['RLC_STATUS'] = "This student is currently awaiting RLC approval. You can view their application " . PHPWS_Text::secureLink(_('here'), 'hms', array('type'=>'rlc', 'op'=>'view_rlc_application', 'username'=>$username));
		}else{
			$tpl['RLC_STATUS'] = "This student is not in a Learning Community and has no pending approval.";
		}

		/*************************
		 * Re-application status *
		 *************************/
		PHPWS_Core::initModClass('hms', 'HMS_Lottery.php');
		PHPWS_Core::initModClass('hms', 'HMS_Lottery_Entry.php');
		$reapplication = HMS_Lottery_Entry::check_for_entry($this->student->getUsername(), Term::getSelectedTerm());

		if($reapplication !== FALSE && !is_null($reapplication['special_interest'])){
			$special_interest_groups = HMS_Lottery::get_special_interest_groups();
			$tpl['SPECIAL_INTEREST'] = $special_interest_groups[$reapplication['special_interest']];
		}else{
			$tpl['SPECIAL_INTEREST'] = 'No';
		}

		/****************
		 * Applications *
		 */
		# Show a row for each application
		if(isset($this->applications)){
			foreach($this->applications as $app){
				$term = Term::toString($app->getTerm());
				$meal_plan = HMS_Util::formatMealOption($app->getMealPlan());
				$phone = HMS_Util::formatCellPhone($app->getCellPhone());
				
				$type = $app->getStudentType() == TYPE_CONTINUING ? 'Returning' : 'Freshmen';

				$viewCmd = CommandFactory::getCommand('ShowApplicationView');
				$viewCmd->setAppId($app->getId());
				$actions = $viewCmd->getLink('View');

				$app_rows[] = array('term'=>$term, 'type'=>$type, 'meal_plan'=>$meal_plan, 'cell_phone'=>$phone, 'actions'=>$actions);
			}

			$tpl['APPLICATIONS'] = $app_rows;
		}else{
			$tpl['APPLICATIONS_EMPTY'] = 'No applications found.';
		}

		/*********
		 * Notes *
		 *********/
		$form = &new PHPWS_Form('add_note_dialog');
		$form->addTextarea('note');
		$form->addHidden('module',   'hms');
		$form->addHidden('type',     'student');
		$form->addHidden('op',       'get_matching_students');
		$form->addHidden('username', $this->student->getUsername());
		$form->addSubmit('Add Note');

		/********
		 * Logs *
		 ********/
		PHPWS_Core::initModClass('hms', 'HMS_Activity_Log.php');
		$everything_but_notes = HMS_Activity_Log::get_activity_list();
		unset($everything_but_notes[array_search(ACTIVITY_ADD_NOTE, $everything_but_notes)]);

		if( Current_User::allow('hms', 'view_activity_log') && Current_User::allow('hms', 'view_student_log') ){
			PHPWS_Core::initModClass('hms', 'HMS_Activity_Log.php');
			$activityLogPager = new ActivityLogPager(null, $this->student->getUsername(), null, true, null, null, $everything_but_notes, true, 10);
			$activityNotePager = new ActivityLogPager(null, $this->student->getUsername(), null, true, null, null, array(0 => ACTIVITY_ADD_NOTE), true, 10);
			 
			$tpl['LOG_PAGER'] = $activityLogPager->show(); 
			$tpl['NOTE_PAGER'] = $activityNotePager->show();

			$tpl['LOG_PAGER'] .= '<div align=center>[<a href="index.php?module=hms&type=student&op=get_matching_students&username='.$this->student->getUsername() .'&tab=student_logs">View More</a>]';
			$tpl['NOTE_PAGER'] .= '<div align=center>[<a href="index.php?module=hms&type=student&op=get_matching_students&username='.$this->student->getUsername().'&tab=student_logs&a'. ACTIVITY_ADD_NOTE .'=1">View More</a>]';
		}

		$tpl = array_merge($tpl, $form->getTemplate());

		// TODO logs

		// TODO tabs

		return PHPWS_Template::process($tpl, 'hms', 'admin/fancy_student_info.tpl');
	}
}

?>