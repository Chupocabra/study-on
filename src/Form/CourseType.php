<?php

namespace App\Form;

use App\Entity\Course;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Unique;

class CourseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, [
                'label' => 'Символьный код',
                'constraints' => [
                    new NotBlank(null, 'Символьный код не может быть пустым'),
                    new Length(
                        null,
                        0,
                        255,
                        null,
                        null,
                        null,
                        null,
                        'Символьный код должен быть не более 255 символов'
                    ),
                ]
            ])
            ->add('name', TextType::class, [
                'label' => 'Название',
                'constraints' => [
                    new NotBlank(null, 'Название не может быть пустым'),
                    new Length(
                        null,
                        0,
                        255,
                        null,
                        null,
                        null,
                        null,
                        'Название должно быть не более 255 символов'
                    )
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Описание',
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
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Course::class,
        ]);
    }
}
