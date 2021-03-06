<?php

namespace Homestead\Command;

use \Homestead\RoommateProfileSearchView;

class RoommateProfileSearchCommand extends Command {

    private $term;

    public function setTerm($term)
    {
        $this->term = $term;
    }

    public function getRequestVars()
    {
        return array('action' => 'RoommateProfileSearch',
                     'term'   => $this->term);
    }

    public function execute(CommandContext $context)
    {
        $term = $context->get('term');

        $tags = array();
        $tags['RESULTS'] = RoommateProfileSearchView::profile_search_pager($term);

        $context->setContent(\PHPWS_Template::process($tags, 'hms', 'student/profile_search_results.tpl'));
    }
}
