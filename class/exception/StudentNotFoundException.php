<?php

PHPWS_Core::initModClass('hms', 'exception/HMSException.php');

class StudentNotFoundException extends HMSException {
    
    public function __construct($message, $code = 0){
        parent::__construct($message, $code);
    }
}

?>