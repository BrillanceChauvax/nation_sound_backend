<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PasswordStrength;
use PixelOpen\CloudflareTurnstileBundle\Type\TurnstileType;

class RegistrationForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email')
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'label_html' => true,
                'label' => 'J\'accepte les <a href="#" data-bs-toggle="modal" data-bs-target="#conditionsModal">conditions générales d\'utilisation</a>',
                'constraints' => [
                    new IsTrue([
                        'message' => 'Vous devez accepter les conditions d\'utilisation',
                    ]),
                ],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'mapped' => false,
                'type'=> PasswordType::class,
                'invalid_message' => 'Les mots de passe doivent correspondre',
                'options' => ['attr' => ['class' => 'newPassword']],
                'required' => true,
                'first_options'  => ['label' => 'Mot de passe',],
                'second_options' => ['label' => 'Confirmation du mot de passe'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir un mot de passe',
                    ]),
                    new Length([
                        'min' => 12,
                        'minMessage' => 'Votre mot de passe doit être au minimum de {{ limit }} caractères',
                        // max length allowed by Symfony for security reasons
                        'max' => 20,
                    ]),
                    new PasswordStrength([
                            'minScore' => PasswordStrength::STRENGTH_MEDIUM, // Niveau de sécurité élevé
                            'message' => 'Le mot de passe est trop faible. Utilisez une combinaison de lettres, chiffres et caractères spéciaux.'
                    ])
                ],
            ])
            ->add('turnstile', TurnstileType::class, [
                'label' => false,
                'attr' => [
                    'data-theme' => 'light',    // light/dark/auto
                    'data-language' => 'fr'
                ],
            ]);
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
