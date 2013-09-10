<?php

namespace Platformd\GiveawayBundle\ViewModel;
use Platformd\UserBundle\Entity\RegistrationSource;
public $giveaway_source_type =RegistrationSource::REGISTRATION_SOURCE_TYPE_GIVEAWAY;

class giveaway_index_data
{
    public $giveaways;
    public $giveaway_source_type =RegistrationSource::REGISTRATION_SOURCE_TYPE_GIVEAWAY;
}
