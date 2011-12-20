<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\Extension\Core\ChoiceList\ArrayChoiceList;

/**
 * Special form type for a drop-down menu of all available "sites"
 */
class SiteChoiceType extends ChoiceType
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
            'label' => 'Site',
            'empty_value' => 'Choose a site',
        );
    }


    public function buildForm(FormBuilder $builder, array $options)
    {
        $options['choice_list'] = new ArrayChoiceList(array(
            'en' => 'Demo',
            'ja' => 'Japan',
            'zh' => 'China',
        ));

        parent::buildForm($builder, $options);
    }


}