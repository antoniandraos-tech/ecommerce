<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isEdit = $options['is_edit'];

        $builder
            ->add('email', EmailType::class, [
                'label' => 'Adresse email',
                'constraints' => new Length(null, 5, 50)
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'Veuillez confirmer votre mot de passe',
                'first_options'  => [
                    'label' => $isEdit ? 'Nouveau mot de passe (laisser vide pour ne pas modifier)' : 'Mot de passe'
                ],
                'second_options' => [
                    'label' => $isEdit ? 'Confirmation du nouveau mot de passe' : 'Confirmation du mot de passe'
                ],
                'constraints' => new Length(null, 6, 15),
                'required' => !$isEdit, // Optionnel en mode édition
                'mapped' => false, // Ne pas mapper automatiquement à l'entité
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'constraints' => new Length(null, 2, 25)
            ])
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'constraints' => new Length(null, 2, 25)
            ])
            ->add('datedenaissance', DateType::class, [
                'label' => 'Date de naissance',
                'years' => range(1900, date('Y')),
                'widget' => 'single_text', // Utilise un input HTML5 type="date"
            ])
            ->add('telephone', TelType::class, [
                'label' => 'Téléphone'
            ])
            ->add('submit', SubmitType::class, [
                'label' => $isEdit ? 'Enregistrer' : "S'inscrire",
                'attr' => ['class' => 'btn btn-primary btn-block']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'is_edit' => false, // Par défaut, on est en mode création
        ]);
    }
}