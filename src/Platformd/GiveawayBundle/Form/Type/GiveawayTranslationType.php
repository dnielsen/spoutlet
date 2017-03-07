<?php

namespace Platformd\GiveawayBundle\Form\Type;

use Platformd\GiveawayBundle\Entity\GiveawayTranslation;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GiveawayTranslationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('locale', EntityType::class, array(
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
        $builder->add('name', TextareaType::class, [
            'label' => 'Name',
        ]);
        $builder->add('content', TextareaType::class, array(
            'required' => false,
            'attr' => [
                'class' => 'ckeditor',
            ],
            'label' => 'Description',
        ));
        $builder->add('backgroundImage', FileType::class, array(
                'label' => 'Background file'
            ));
        $builder->add('removeBannerImage', CheckboxType::class, array(
            'label' => 'Remove Banner',
        ));
        $builder->add('removeBackgroundImage', CheckboxType::class, array(
            'label' => 'Remove Background',
        ));
        $builder->add('bannerImageFile', FileType::class, array(
                'label' => 'Banner file'
            ));
        $builder->add('backgroundLink', TextType::class, array(
                'label' => 'Background link'
            ));
        $builder->add('redemptionInstructionsArray', CollectionType::class, array(
            'entry_type' => 'textarea',
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

    public function getBlockPrefix()
    {
        return 'giveaway_translation';
    }
}
