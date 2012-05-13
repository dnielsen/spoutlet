<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\Extension\Core\ChoiceList\ArrayChoiceList;
use Platformd\SpoutletBundle\Tenant\MultitenancyManager;

/**
 * Special form type for a drop-down menu of all available "sites"
 */
class SiteChoiceType extends AbstractType
{
    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */

    public function getName()
    {
        return 'site_choice';
    }

    public function getParent(array $options)
    {
        return 'choice';
    }

    public function getDefaultOptions(array $options)
    {
        
        return array(
            'label' => (isset($options['multiple']) && $options['multiple']) ? 'Sites' : 'Site',
            'empty_value' => 'Choose a site',
            'choice_list' => new ArrayChoiceList(
                MultitenancyManager::getSiteChoices()
            ),
        );
    }
}
