<?php

namespace App\Form;

use App\Entity\Category;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

class CategoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Category Name',
                'constraints' => [
                    new NotBlank(message: 'Please enter a category name'),
                    new Length(
                        min: 3,
                        max: 255,
                        minMessage: 'Category name must be at least {{ limit }} characters',
                        maxMessage: 'Category name cannot be longer than {{ limit }} characters',
                    ),
                ],
                'attr' => [
                    'placeholder' => 'e.g. Electronics, Furniture, Vehicles',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'constraints' => [
                    new NotBlank(message: 'Please provide a description'),
                    new Length(
                        min: 10,
                        minMessage: 'Description must be at least {{ limit }} characters',
                    ),
                ],
                'attr' => [
                    'rows' => 5,
                    'placeholder' => 'Describe what types of items belong in this category',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Category::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'category_form',
        ]);
    }
}

