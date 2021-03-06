<?php

namespace Homestead\Command;

use \Homestead\UserStatus;
use \Homestead\Term;
use \Homestead\NotificationView;
use \Homestead\BannerQueue;
use \Homestead\CommandFactory;
use \Homestead\Exception\PermissionException;

class ProcessBannerQueueCommand extends Command {
    private $term;

    public function setTerm($term) {
        $this->term = $term;
    }

    public function getRequestVars() {
        $vars = array('action' => 'ProcessBannerQueue');

        if(isset($this->term)) {
            $vars['term'] = $this->term;
        }

        return $vars;
    }

    public function execute(CommandContext $context) {
        if(!UserStatus::isAdmin() || !\Current_User::allow('hms', 'banner_queue')){
            throw new PermissionException('You do not have permission to enable/disable the Banner queue.');
        }

        if(is_null($this->term)) {
            $this->term = $context->get('term');
        }

        $term = $this->term;

        if(is_null($term)) {
            throw new \InvalidArgumentException('No term was specified to DisableBannerQueue');
        }

        $term = new Term($term);

        if(!$term->getBannerQueue()) {
            \NQ::Simple('hms', NotificationView::ERROR, 'The Banner Queue is not enabled for ' . Term::toString($term->term) . '.');
        } else {
            if($term->getQueueCount() < 1) {
                \NQ::Simple('hms', NotificationView::WARNING, 'The Banner Queue was already empty for ' . Term::toString($term->term) . '.');
                $term->setBannerQueue(FALSE);
                $term->save();
                \NQ::Simple('hms', NotificationView::SUCCESS, 'Banner Queue has been disabled for ' . Term::toString($term->term) . '.');
            } else {
                $result = BannerQueue::processAll($term->term);
                if($result === TRUE) {
                    \NQ::Simple('hms', NotificationView::SUCCESS, 'Banner Queue has been processed for ' . Term::toString($term->term) . '.');
                    $term->setBannerQueue(FALSE);
                    $term->save();
                    \NQ::Simple('hms', NotificationView::SUCCESS, 'Banner Queue has been disabled for ' . Term::toString($term->term) . '.');
                } else {
                    // TODO: This is just awful.
                    $text = 'The following failures occurred reporting to Banner:<br /><br /><ul>';
                    foreach($result as $error) {
                        $text .= "<li>{$error['username']}: ({$error['code']}) - {$error['message']}</li>";
                    }
                    $text .= '</ul>The queue was not disabled.';
                    \NQ::Simple('hms', NotificationView::ERROR, $text);
                }
            }
        }

        $cmd = CommandFactory::getCommand('ShowEditTerm');
        $cmd->redirect();
    }
}
