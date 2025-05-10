<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
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
                'constraints' => [
                    new IsTrue([
                        'message' => 'You should agree to our terms.',
                    ]),
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                // instead of being set onto the object directly,
                // this is read and encoded in the controller
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password'],
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
                            'minScore' => PasswordStrength::STRENGTH_STRONG, // Niveau de sécurité élevé
                            'message' => 'Le mot de passe est trop faible. Utilisez une combinaison de lettres, chiffres et caractères spéciaux.'
                    ])
                ],
            ])
            ->add('turnstile', TurnstileType::class, [
                'label' => false,
                'attr' => [
                    'data-action' => 'signup', // Optionnel : nom de l'action
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
