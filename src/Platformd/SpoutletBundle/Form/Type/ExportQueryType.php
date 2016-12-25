<?php

namespace Platformd\SpoutletBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Platformd\SpoutletBundle\Model\ExportQueryManager;
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
        $builder->add('reportTypes', 'choice', array(
            'label'     => 'Report',
            'choices'   => $reportTypes,
            'required'  => false,
            'data'      => key($reportTypes),
        ));

        $builder->add('fromDate', 'datetime', array(
            'label'     => 'Start Date',
            'widget'    => 'single_text',
            'attr'      => array(
                'class' => 'datetime-picker'
            ),
            'required'  => false,
        ));

        $builder->add('thruDate', 'datetime', array(
            'label'     => 'End Date',
            'widget'    => 'single_text',
            'attr'      => array(
                'class' => 'datetime-picker'
            ),
            'required'  => false,
        ));

        $builder->add('sites', 'entity', array(
            'class'    => 'SpoutletBundle:Site',
            'multiple' => true,
            'expanded' => true,
            'property' => 'name',
            'required' => false,
        ));
    }

    public function getName()
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
