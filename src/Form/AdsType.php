<?php

namespace App\Form;

use App\Entity\Ads;
use App\Entity\Category;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Positive;

class AdsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Title',
                'constraints' => [
                    new NotBlank(message: 'Please enter a title'),
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'constraints' => [
                    new NotBlank(message: 'Please provide a description'),
                ],
            ])
            ->add('price', IntegerType::class, [
                'label' => 'Price (£)',
                'constraints' => [
                    new NotBlank(message: 'Please enter a price'),
                    new Positive(message: 'Price must be positive'),
                ],
            ])
            ->add('location', TextType::class, [
                'label' => 'Location',
                'constraints' => [
                    new NotBlank(message: 'Please enter a location'),
                ],
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'label' => 'Category',
                'placeholder' => 'Select a category',
                'constraints' => [
                    new NotBlank(message: 'Please select a category'),
                ],
                'attr' => [
                    'class' => 'form-select',
                ],
            ])
            ->add('quantity', IntegerType::class, [
                'label' => 'Quantity Available',
                'constraints' => [
                    new NotBlank(message: 'Please enter quantity'),
                    new Positive(message: 'Quantity must be positive'),
                ],
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Product Image',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File(
                        maxSize: '5M',
                        mimeTypes: [
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                            'image/webp',
                        ],
                        mimeTypesMessage: 'Please upload a valid image file (JPG, PNG, GIF, WEBP)',
                    ),
                ],
            ])
        ;
    }

     public function configureOptions(OptionsResolver $resolver): void
     {
        $resolver->setDefaults([
            'data_class' => Ads::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'ads_form',
        ]);
     }
}
