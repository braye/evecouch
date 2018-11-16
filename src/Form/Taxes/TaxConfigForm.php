<?php

namespace App\Form\Taxes;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\PercentType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\OptionsResolver\OptionsResolver;

use App\Entity\CorpTaxConfig;

class TaxConfigForm extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => CorpTaxConfig::class
        ));
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('_id', HiddenType::class)
            ->add('tax_rate', PercentType::class, ['label' => 'Tax rate for member corps: '])
            ->add('save', SubmitType::class, array('label' => 'Save'));
    }
}