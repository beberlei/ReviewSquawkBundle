<?php
/**
 * Whitewashing
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to kontakt@beberlei.de so I can send you a copy immediately.
 */

namespace Whitewashing\ReviewSquawkBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Whitewashing\ReviewSquawkBundle\Model\CodeSnifferService;

class GithubProjectType extends AbstractType
{
    /**
     * @param FormBuilder $builder
     * @param array $options
     */
    public function buildForm(FormBuilder $builder, array $options)
    {
        $choices = CodeSnifferService::getInstalledStandards();

        $builder->add('repositoryUrl', null, array('label' => 'URL', 'error_bubbling' => true));
        $builder->add('codingStandard', 'choice', array('choices' => array_combine($choices, $choices), 'error_bubbling' => true));
    }

    public function getName()
    {
        return 'squawkd_github_project';
    }

    public function getDefaultOptions(array $options)
    {
        return array(
            'data_class'        => 'Whitewashing\ReviewSquawkBundle\Entity\Project',
            'csrf_protection'   => true,
            'csrf_field_name'   => '_token',
            // a unique key to help generate the secret token
            'intention'         => 'github_project',
            'validation_groups' => array('Default', 'Github'),
        );
    }
}