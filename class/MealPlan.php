<?php

/**
 * Class to represent a meal plan assignment for a given student.
 *
 * @author Jeremy Booker
 * @package Homestead;
 */
class MealPlan {

    private $id;
    private $bannerId;
    private $term;
    private $planCode;
    private $status;
    private $statusTimestamp;


    // Banner Meal Plan Codes (???)
    const BANNER_MEAL_LOW   = '2';
    const BANNER_MEAL_STD   = '1';
    const BANNER_MEAL_HIGH  = '0';
    const BANNER_MEAL_SUPER = '8';
    const BANNER_MEAL_NONE  = '-1'; // NB: This is internal only. Doesn't actully exist in Banner.

    const BANNER_MEAL_SUMMER = 'S5';


    // Status strings for $status field
    const STATUS_NEW    = 'new';
    const STATUS_SENT   = 'sent';

    /**
     * Constructor - Creates a new MealPlan object.
     *
     * NB: Does not save or process the MealPlan. See MealPlanFactory::saveMealPlan()
     * and MealPlanProcessor::processMealPlan()
     *
     * @param string $bannerId Banner ID for the student this meal plan belongs to.
     * @param string $planCode Meal plan code. Must be one of the constants defined above. Two chars max.
     */
    public function __construct($bannerId, $term, $planCode)
    {
        $this->id = null;

        // Sanity checking for params

        if($bannerId === null || $bannerId === ''){
            throw new \InvalidArgumentException('Missing Banner Id.');
        }

        if($term === null || $term === ''){
            throw new \InvalidArgumentException('Missing term.');
        }

        if($planCode === null || $planCode === ''){
            throw new \InvalidArgumentException('Missing plan code.');
        }

        $this->bannerId = $bannerId;
        $this->term = $term;
        $this->planCode = $planCode;

        $this->status = MealPlan::STATUS_NEW;
        $this->statusTimestamp = time();
    }

    public function getId(){
        return $this->id;
    }

    public function setId($id){
        $this->id = $id;
    }

    public function getBannerId(){
        return $this->bannerId;
    }

    public function getTerm(){
        return $this->term;
    }

    public function getPlanCode(){
        return $this->planCode;
    }

    public function getStatus(){
        return $this->status;
    }

    public function setStatus($status){
        $this->status = $status;
    }

    public function getStatusTimestamp(){
        return $this->statusTimestamp;
    }

    public function setStatusTimestamp($timestamp){
        $this->statusTimestamp = $timestamp;
    }
}