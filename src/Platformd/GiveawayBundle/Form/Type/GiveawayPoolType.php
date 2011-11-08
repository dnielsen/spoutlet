<?php

namespace Platformd\GiveawayBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

/**
* 
*/
class GiveawayPoolType extends AbstractType
{
    /**
     * A list of giveaways
     * @var array
     */
    private $giveaways;

    public function __construct(array $giveaways = array())
    {
        $this->giveaways = $giveaways;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            ->add('giveaway', 'choice', array(
                'choices'       => $this->getGiveaways(),
                'empty_value'   => ''
            ))
            ->add('description', 'textarea')
            ->add('maxKeysPerIp', 'integer')
            ->add('upperLimit', 'integer')
            ->add('lowerLimit', 'integer')
            ->add('isActive', 'checkbox');
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        
        return 'giveway_pool';
    }

    protected function getGiveaways()
    {
        $choices = array();
        foreach ($this->giveaways as $giveaway) {
            $choices[$giveaway->getId()] = $giveaway->getName();
        }

        return $choices;
    }
}