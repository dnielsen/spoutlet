<?php

namespace Platformd\GiveawayBundle\ViewModel;
use Platformd\UserBundle\Entity\RegistrationSource;

class deal_index_data
{
    public $featured_deals;
    public $main_deal;
    public $all_deals;
    public $expired_deals;
    public $comments;
    public $next_expiry_in;

    public $deal_source_type =RegistrationSource::REGISTRATION_SOURCE_TYPE_DEAL;
}
