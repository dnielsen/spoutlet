<?php

namespace Platformd\GiveawayBundle\Form\Type;

use Platformd\GiveawayBundle\Entity\GiveawayTranslation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GiveawayTranslationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('locale', 'entity', array(
            'label' => 'Language',
            'class' => 'Platformd\SpoutletBundle\Entity\Site',
            'choice_label' => 'name',
            'query_builder' => function($repository) {
                return $repository->createQueryBuilder('s')
                    ->andWhere('s.defaultLocale IN (:siteNames)')
                    ->setParameter('siteNames', array('zh', 'ja', 'es'))
                ;
            },
        ));
        $builder->add('name', 'textarea');
        $builder->add('content', 'textarea', array(
            'required' => false,
            'attr' => array('class' => 'ckeditor'),
            'label' => 'Description',
        ));
        $builder->add('backgroundImage', 'file', array(
                'label' => 'Background file'
            ));
        $builder->add('removeBannerImage', 'checkbox', array(
            'label' => 'Remove Banner',
        ));
        $builder->add('removeBackgroundImage', 'checkbox', array(
            'label' => 'Remove Background',
        ));
        $builder->add('bannerImageFile', 'file', array(
                'label' => 'Banner file'
            ));
        $builder->add('backgroundLink', 'text', array(
                'label' => 'Background link'
            ));
        $builder->add('redemptionInstructionsArray', 'collection', array(
            'type' => 'textarea',
            'label' => 'Redemption Instructions',
        ));
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        if ($data = $form->getData()) {
            $view->vars = array_replace($view->vars, [
                'mediaObjects' => $data,
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => GiveawayTranslation::class,
        ]);
    }

    public function getName()
    {
        return 'giveaway_translation';
    }
}
