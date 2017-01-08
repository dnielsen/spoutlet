<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SlugType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $options['required'] = false;

        $label = isset($options['label_override']) ? $options['label_override'] : 'URL string';

        if (isset($options['url_prefix'])) {
            $options['label'] = $label . ' - ' . $options['url_prefix'];
        } else {
            $options['label'] = $label;
        }

        $options['url_prefix'] = null;

        $resolver->setDefaults($options);
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars = array_replace($view->vars, [
            'help' => 'This field will be generated automatically if left blank. It\'s format
                            should be lowercase letters and dashes, without any http (e.g. my-new-giveaway).
                            This becomes *part* of the URL for this item.',
        ]);
    }

    /**
     * @return mixed
     */
    public function getParent()
    {
        return TextType::class;
    }

    public function getBlockPrefix()
    {
        return 'slug';
    }
}
