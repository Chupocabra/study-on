<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegisterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', EmailType::class, [
                'label' => 'Электронная почта пользователя',
                'required' => true,
                'constraints' => [
                    new NotBlank(null, 'Укажите электронную почту'),
                    new Email(null, 'Почта не соответствует формату'),
                ]
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'Пароли не совпадают',
                'options' => ['attr' => ['class' => 'password-field']],
                'required' => true,
                'first_options'  => ['label' => 'Пароль'],
                'second_options' => ['label' => 'Повторите пароль'],
                'label' => 'Пароль пользователя',
                'constraints' => [
                    new NotBlank(null, 'Введите пароль пользователя'),
                    new Length(
                        null,
                        6,
                        16,
                        null,
                        null,
                        null,
                        'Минимальная длина пароля 6 символов',
                        'Максимальная длина пароля 16 символов'
                    ),
                ],
            ]);
    }
}
