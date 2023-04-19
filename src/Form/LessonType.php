<?php

namespace App\Form;

use App\Entity\Lesson;
use App\Form\DataTransformer\CourseToStringTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Range;

class LessonType extends AbstractType
{
    private CourseToStringTransformer $transformer;

    public function __construct(CourseToStringTransformer $transformer)
    {
        $this->transformer = $transformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
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
                    ),
                ]
            ])
            ->add('description', TextType::class, [
                'label' => 'Действия',
                'constraints' => [
                    new NotBlank(null, 'Название не может быть пустым'),
                ]
            ])
            ->add('number', TextType::class, [
                'label' => 'Порядковый номер',
                'constraints' => [
                    new NotBlank(null, 'Порядковый номер урока не может быть пустым'),
                    new Range(
                        null,
                        'Значение поля должно быть от {{ min }} до {{ max }}',
                        null,
                        null,
                        null,
                        null,
                        1,
                        null,
                        10000
                    ),
                ]
            ])
            ->add('course', HiddenType::class);
        $builder->get('course')->addModelTransformer($this->transformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Lesson::class,
            'course' => null,
        ]);
    }
}