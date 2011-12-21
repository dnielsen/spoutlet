<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;

class SlugType extends TextType
{
    public function getName()
    {
        return 'slug';
    }

    public function getDefaultOptions(array $options)
    {
        $options = parent::getDefaultOptions($options);

        $options['required'] = false;
        $options['label']    = 'URL key(e.g. my-event)';

        return $options;
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