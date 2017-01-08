<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Special form type for a drop-down menu of all available "galleries"
 */
class GalleryChoiceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('galleries', 'entity', array(
            'class' => 'SpoutletBundle:Gallery',
            'choice_label' => 'name',
            'multiple' => true,
            'expanded' => true,
            'query_builder' => function(EntityRepository $er) {
                $qb = $er->createQueryBuilder('ga')
                    ->leftJoin('ga.categories', 'cat')
                    ->where('cat.name = :category')
                    ->orderBy('ga.name', 'ASC')
                    ->setParameter('category', 'image');
                return $qb;
            },
        ));
    }

    public function getName()
    {
        return 'platformd_spoutletbundle_gallery_choice';
    }
}
