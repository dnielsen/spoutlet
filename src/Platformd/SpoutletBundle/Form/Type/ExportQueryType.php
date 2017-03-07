<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Platformd\SpoutletBundle\Model\ExportQueryManager;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;

class ExportQueryType extends AbstractType
{
    protected $exportQueryManager;

    public function __construct(ExportQueryManager $exportQueryManager)
    {
        $this->exportQueryManager = $exportQueryManager;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $reportTypes = $this->getReportTypes();
        $builder->add('reportTypes', ChoiceType::class, array(
            'label'     => 'Report',
            'choices'   => $reportTypes,
            'required'  => false,
            'data'      => key($reportTypes),
            'choices_as_values' => true,
        ));

        $builder->add('fromDate', DateTimeType::class, array(
            'label'     => 'Start Date',
            'widget'    => 'single_text',
            'attr'      => array(
                'class' => 'datetime-picker'
            ),
            'required'  => false,
        ));

        $builder->add('thruDate', DateTimeType::class, array(
            'label'     => 'End Date',
            'widget'    => 'single_text',
            'attr'      => array(
                'class' => 'datetime-picker'
            ),
            'required'  => false,
        ));

        $builder->add('sites', EntityType::class, array(
            'label' => 'Sites',
            'class'    => 'SpoutletBundle:Site',
            'multiple' => true,
            'expanded' => true,
            'choice_label' => 'name',
            'required' => false,
        ));
    }

    public function getBlockPrefix()
    {
        return 'platformd_export_query_type';
    }

    private function getReportTypes()
    {
        $types = array();
        foreach ($this->exportQueryManager->getReportTypes() as $reportType) {
            $types[$reportType] = $reportType;
        }
        return $types;
    }
}
