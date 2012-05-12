<?php

namespace Platformd\SpoutletBundle\Form\Extension;

use Symfony\Component\Form\FormTypeExtensionInterface;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;

/**
 * Adds a "help" option to every field
 *
 * This help option is then added to the field's view which, along with
 * customizations made in our form template, causes a help message to be
 * displayed.
 */
class HelpFormTypeExtension implements FormTypeExtensionInterface
{
    /**
     * Builds the form.
     *
     * This method gets called after the extended type has built the form to
     * further modify it.
     *
     * @see FormTypeInterface::buildForm()
     *
     * @param FormBuilder   $builder The form builder
     * @param array         $options The options
     */
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder->setAttribute('help', $options['help']);
    }

    /**
     * Builds the view.
     *
     * This method gets called after the extended type has built the view to
     * further modify it.
     *
     * @see FormTypeInterface::buildView()
     *
     * @param FormView      $view The view
     * @param FormInterface $form The form
     */
    public function buildView(FormView $view, FormInterface $form)
    {
        $view->set('help', $form->getAttribute('help'));
    }

    /**
     * Builds the view.
     *
     * This method gets called after the extended type has built the view to
     * further modify it.
     *
     * @see FormTypeInterface::buildViewBottomUp()
     *
     * @param FormView      $view The view
     * @param FormInterface $form The form
     */
    public function buildViewBottomUp(FormView $view, FormInterface $form)
    {
    }

    /**
     * Overrides the default options form the extended type.
     *
     * @param array $options
     *
     * @return array
     */
    public function getDefaultOptions(array $options)
    {
        return array(
            'help' => '',
        );
    }

    /**
     * Returns the allowed option values for each option (if any).
     *
     * @param array $options
     *
     * @return array The allowed option values
     */
    public function getAllowedOptionValues(array $options)
    {
        return array();
    }

    /**
     * Returns the name of the type being extended
     *
     * @return string The name of the type being extended
     */
    public function getExtendedType()
    {
        return 'field';
    }

}