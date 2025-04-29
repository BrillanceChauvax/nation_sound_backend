<?php

namespace App\Form;

use App\Entity\Artist;
use App\Entity\Event;
use App\Entity\MapPoint;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title')
            ->add('date')
            ->add('startTime')
            ->add('duration')
            ->add('location')
            ->add('category')
            ->add('image')
            ->add('artists', EntityType::class, [
                'class' => Artist::class,
                'choice_label' => 'id',
                'multiple' => true,
            ])
            ->add('mapPoint', EntityType::class, [
                'class' => MapPoint::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }
}
