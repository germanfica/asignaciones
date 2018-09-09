<?php

namespace EMM\UserBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class UserType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username')
            ->add('firstName')
            ->add('lastName')
            ->add('email', EmailType::class)
            ->add('password', PasswordType::class)
            ->add('role', ChoiceType::class, array(
                'choices'=> array(
                  'Select a role' => 'null',
                  'Administrator' => 'ROLE_ADMIN',
                  'User' => 'ROLE_USER',
              )))
        ;
        //$builder->add('isActive', CheckboxType::class);
        $builder->add('save', SubmitType::class, array('label' => 'Save user'));
    }

    private function isAct()
    {
      //Buscar manera de consultar si se esta trabajando desde edit.html.twig
      //o add.html.twig para tener en cuenta cuando añadir isActive.
      //$builder->add('isActive', CheckboxType::class);


      return $resultado;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'EMM\UserBundle\Entity\User'
        ));
    }
}
