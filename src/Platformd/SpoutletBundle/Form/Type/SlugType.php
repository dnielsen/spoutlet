<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class SlugType extends TextType
{
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);

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

    public function getName()
    {
        return 'slug';
    }

    public function buildViewBottomUp(FormView $view, FormInterface $form)
    {
        parent::buildViewBottomUp($view, $form);

        $view->set('help', 'This field will be generated automatically if left blank. It\'s format
                            should be lowercase letters and dashes, without any http (e.g. my-new-giveaway).
                            This becomes *part* of the URL for this item.'
        );
    }
}
