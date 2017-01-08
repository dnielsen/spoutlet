<?php

namespace Knp\MediaBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Platformd\MediaBundle\Entity\Media;

abstract class MediaType extends AbstractType
{
    private $helpMessage;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $label = isset($options['image_label']) ? $options['image_label'] : 'Upload a file';

        if (isset($options['image_help'])) {
            $this->helpMessage = $options['image_help'];
        }

        $builder
            ->add('fileObject', FileType::class, [
                'label' => $label,
                'required' => false,
            ])
        ;

        if (isset($options['with_remove_checkbox']) && $options['with_remove_checkbox']) {
            $builder->add('removed', CheckboxType::class, [
                'label' => 'Remove',
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Media::class,
            'image_label' => 'Upload a file',
            'image_help' => null,
            'with_remove_checkbox' => false,
        ]);
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        if ($this->helpMessage) {
            if (isset($view->children->vars['fileObject'])) {
                $view->children->vars['fileObject'] = array_replace($view->children->vars['fileObject'], ['help' => $this->helpMessage]);
            }
        }

        if ($form->getData()) {
            $view->vars = array_replace($view->vars, [
                'mediaObject' => $form->getData(),
            ]);
        }
    }

    public function getBlockPrefix()
    {
        return 'knp_media';
    }
}
