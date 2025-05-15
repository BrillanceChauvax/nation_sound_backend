<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use PixelOpen\CloudflareTurnstileBundle\Type\TurnstileType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LoginForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [ 
                'label' => 'Email',
                'attr' => ['autocomplete' => 'email'],
            ])
            ->add('password', PasswordType::class, [
                'label' => 'Mot de passe',
                'attr' => ['autocomplete' => 'current-password']
            ])
            ->add('turnstile', TurnstileType::class, [
                'label' => false,
                'attr' => [
                    'data-action' => 'login',
                    'data-language' => 'fr'
                ],
                'constraints' => [
                    new NotBlank(['message' => 'CAPTCHA requis'])
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'authenticate', // Clé essentielle
            'csrf_message' => 'Jeton CSRF invalide'
        ]);
    }

    public function getBlockPrefix(): string
    {
        return ''; 
    }
}
