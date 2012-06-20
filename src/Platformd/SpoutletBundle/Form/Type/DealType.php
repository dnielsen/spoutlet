<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Platformd\SpoutletBundle\Entity\Deal;
use Platformd\MediaBundle\Form\Type\MediaType;
use Platformd\SpoutletBundle\Form\Type\SiteChoiceType;
use Platformd\SpoutletBundle\Form\Type\SlugType;

class DealType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder
            ->add('name', null, array('label' => 'Deal Name'))
            ->add('externalUrl', null, array('label' => 'External URL', 'help' => '(Optional) If filled in, this URL will override the destination of any links that would normally point to this deal on this site.'))
            ->add('game', 'entity', array('class' => 'SpoutletBundle:Game', 'empty_value' => 'N/A',
                'query_builder' => function(\Platformd\SpoutletBundle\Entity\GameRepository $er) {
                    return $er->createQueryBuilder('g')
                              ->orderBy('g.name', 'ASC');
                    }))
            ->add('slug', new SlugType(), array('url_prefix' => '/deal/{slug}'))
            ->add('startsAt', 'date', array(
                'widget' => 'single_text',
                'attr' => array(
                    'class' => 'datetime-picker'
                ),
                'format' => 'yyyy-MM-dd hh:mm',
                'label' => 'Starts At'
            ))
            ->add('endsAt', 'date', array(
                'widget' => 'single_text',
                'attr' => array(
                    'class' => 'datetime-picker'
                ),
                'format' => 'yyyy-MM-dd hh:mm',
                'label' => 'Ends At'
            ))
            ->add('timezone', 'timezone', array('label' => 'Timezone'))
            ->add('banner', new MediaType(), array(
                'image_label' => 'Banner Image',
                'image_help'  => 'Recommended size: 950x610',
            ))
            ->add('openGraphOverride', null, array('label' => 'Open Graph Description'))
        ;
    }

    public function getName()
    {
        return 'platformd_spoutletbundle_dealtype';
    }
}
