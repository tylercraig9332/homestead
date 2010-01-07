<?php

class ShowCreateTermCommand extends Command {

    function getRequestVars() {
        $vars = array('action' => 'ShowCreateTerm');

        return $vars;
    }

    function execute(CommandContext $context)
    {
        if(!Current_User::allow('hms', 'edit_terms')) {
            PHPWS_Core::initModClass('hms', 'exception/PermissionException.php');
            throw new PermissionException('You do not have permission to edit terms.');
        }

        $context->setContent('Under Construction!');
    }
}

?>