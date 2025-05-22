<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use PixelOpen\CloudflareTurnstileBundle\Type\TurnstileType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;


class LoginForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [ 
                'label' => 'Email',
                'attr' => ['autocomplete' => 'email',
                'class' => 'form-control',
                ],
            ])
            ->add('password', PasswordType::class, [
                'label' => 'Mot de passe',
                'attr' => ['autocomplete' => 'current-password',
                'class' => 'form-control',
                ],
            ])
            ->add('remember_me', CheckboxType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Se souvenir de moi',
            ])
            ->add('turnstile', TurnstileType::class, [
                'label' => false,
                'attr' => [
                    'data-action' => 'login',
                    'data-language' => 'fr',
                    'data-theme' => 'light',
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
            'csrf_token_id' => 'authenticate',
            'csrf_message' => 'Jeton CSRF invalide'
        ]);
    }

    public function getBlockPrefix(): string
    {
        return ''; 
    }
}
