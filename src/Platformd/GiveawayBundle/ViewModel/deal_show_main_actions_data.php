<?php

namespace Platformd\GiveawayBundle\ViewModel;

use Platformd\UserBundle\Entity\RegistrationSource;

class deal_show_main_actions_data
{
    public $deal_claim_code_button;
    public $deal_show_claim_button;
    public $deal_group_name;
    public $deal_group_slug;
    public $deal_has_expired;
    public $deal_has_keys;
    public $deal_redemption_steps;
    public $deal_slug;
    public $deal_id;
    public $deal_user_already_redeemed;
    public $user_is_member_of_deal_group;

    public $deal_source_type =RegistrationSource::REGISTRATION_SOURCE_TYPE_DEAL;
}
