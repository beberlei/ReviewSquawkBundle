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
namespace Whitewashing\ReviewSquawkBundle\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class GithubProject extends Constraint
{
    public $message = 'Not a valid Github repository';

    public $forkMessage = 'Forks are not valid repositories';

    public $privateMessage = 'Private repositories are not supported as of now.';

    public function validatedBy()
    {
        return 'githubproject';
    }
}