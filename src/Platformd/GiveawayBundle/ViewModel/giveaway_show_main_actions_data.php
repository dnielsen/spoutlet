<?php

namespace Platformd\GiveawayBundle\ViewModel;

use Platformd\UserBundle\Entity\RegistrationSource;

class giveaway_show_main_actions_data
{
    public $giveaway_allow_key_fetch;
    public $giveaway_allow_machine_code_submit;
    public $giveaway_assigned_key;
    public $giveaway_available_keys;
    public $can_user_apply_to_giveaway;
    public $giveaway_show_keys;
    public $giveaway_show_get_key_button;
    public $giveaway_slug;
    public $giveaway_id;
    public $user_is_member_of_promotion_group;
    public $promotion_group_name;
    public $promotion_group_slug;

    public $giveaway_source_type =RegistrationSource::REGISTRATION_SOURCE_TYPE_GIVEAWAY;
}
