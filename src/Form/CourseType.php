<?php

namespace App\Form;

use App\Entity\Course;
use Doctrine\DBAL\Types\FloatType;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PositiveOrZero;
use Symfony\Component\Validator\Constraints\Unique;

class CourseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, [
                'label' => 'Символьный код',
                'required' => true,
                'constraints' => [
                    new NotBlank(null, 'Символьный код не может быть пустым'),
                    new Length(
                        null,
                        3,
                        255,
                        null,
                        null,
                        null,
                        'Символьный код должен быть не менее 3 символов',
                        'Символьный код должен быть не более 255 символов'
                    ),
                ]
            ])
            ->add('name', TextType::class, [
                'label' => 'Название',
                'required' => true,
                'constraints' => [
                    new NotBlank(null, 'Название не может быть пустым'),
                    new Length(
                        null,
                        3,
                        255,
                        null,
                        null,
                        null,
                        'Название должно быть не менее 3 символов',
                        'Название должно быть не более 255 символов'
                    )
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Описание',
                'required' => false,
                'constraints' => [
                    new Length(
                        null,
                        0,
                        1000,
                        null,
                        null,
                        null,
                        null,
                        'Описание не должно превышать 1000 символов'
                    )
                ]
            ])
            ->add('price', NumberType::class, [
                'label' => 'Цена',
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new PositiveOrZero(null, 'Укажите корректную стоимость')
                ]
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Тип курса',
                'mapped' => false,
                'required' => true,
                'choices' => [
                    'Аренда' => 'rent',
                    'Бесплатный' => 'free',
                    'Полный' => 'buy',
                ],
                'constraints' => [
                    new NotBlank(null, 'Укажите тип курса'),
                    new Choice(
                        null,
                        ['rent', 'free', 'buy'],
                        null,
                        null,
                        null,
                        null,
                        null,
                        'Выберите тип курса'
                    )
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Course::class,
        ]);
    }
}
