<?php

namespace Homestead\Command;

use \Homestead\CommandFactory;
use \Homestead\NotificationView;

class HousingApplicationFormSubmitCommand extends Command {

    private $term;

    public function setTerm($term)
    {
        $this->term = $term;
    }

    public function getRequestVars()
    {
        return array (
                'action' => 'HousingApplicationFormSubmit',
                'term' => $this->term
        );
    }

    public function execute(CommandContext $context)
    {
        $term = $context->get('term');

        $errorCmd = CommandFactory::getCommand('ShowHousingApplicationForm');
        $errorCmd->setTerm($term);

        /* Phone number sanity checking */
        $doNotCall = $context->get('do_not_call');
        $number = $context->get('number');

        if (is_null($doNotCall)) {
            // do not call checkbox was not selected, so check the number
            if (empty($number)) {
                \NQ::simple('hms', NotificationView::ERROR, 'Please provide a cell-phone number or click the checkbox stating that you do not wish to share your number with us.');
                $errorCmd->redirect();
            }
        }

        /* Emergency Contact Sanity Checking */
        $emergencyName = $context->get('emergency_contact_name');
        $emergencyRelationship = $context->get('emergency_contact_relationship');
        $emergencyPhone = $context->get('emergency_contact_phone');
        $emergencyEmail = $context->get('emergency_contact_email');

        if (empty($emergencyName) || empty($emergencyRelationship) || empty($emergencyPhone) || empty($emergencyEmail)) {
            \NQ::simple('hms', NotificationView::ERROR, 'Please complete all of the emergency contact person information.');
            $errorCmd->redirect();
        }


        /* Missing Persons Sanity Checking */
        $missingPersonName = $context->get('missing_person_name');
        $missingPersonRelationship = $context->get('missing_person_relationship');
        $missingPersonPhone = $context->get('missing_person_phone');
        $missingPersonEmail = $context->get('missing_person_email');

        if (empty($missingPersonName) || empty($missingPersonRelationship) || empty($missingPersonPhone) || empty($missingPersonEmail)) {
            \NQ::simple('hms', NotificationView::ERROR, 'Please complete all of the missing persons contact information.');
            $errorCmd->redirect();
        }

        /* Meal plan, lifestyle, preferred bedtime, room condition error checking */
        // TODO: this, correctly (should be inside of a sub-class since it's term specific)
        $sem = substr($term, 4, 2);
        if ($sem == 10 || $sem == 40) {

            $mealOption = $context->get('meal_option');
            $lifestyleOption = $context->get('lifestyle_option');
            $preferredBedtime = $context->get('preferred_bedtime');
            $roomCondition = $context->get('room_condition');

            if (!is_numeric($mealOption) || !is_numeric($lifestyleOption) || !is_numeric($preferredBedtime) || !is_numeric($roomCondition)) {
                \NQ::simple('hms', NotificationView::ERROR, 'Invalid values entered. Please try again.');
                $errorCmd->redirect();
            }
        } else if ($sem == 20 || $sem == 30) {
            /* Private/double room sanity checking for Summer terms */
            $roomType = $context->get('room_type');

            if (!is_numeric($roomType)) {
                \NQ::simple('hms', NotificationView::ERROR, 'Invalid values entered.  Please try again.');
                $errorCmd->redirect();
            }
        }

        // Session the current application data
        $_SESSION['application_data'] = $_REQUEST;

        // NB: This command grabs the current context and passes the data forward
        $reviewCmd = CommandFactory::getCommand('ShowFreshmenApplicationReview');
        $reviewCmd->setTerm($term);

        $reviewCmd->redirect();
    }
}
