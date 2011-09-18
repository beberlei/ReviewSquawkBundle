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

namespace Whitewashing\ReviewSquawkBundle\Command;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Whitewashing\ReviewSquawkBundle\Model\Github\RestV3API;

class GithubCommentCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('review-squawk:github:comment')
            ->setDescription('Create a comment on a commit')
            ->addArgument('access_token', InputArgument::REQUIRED, 'Access Token')
            ->addArgument('username', InputArgument::REQUIRED, 'User/Organization')
            ->addArgument('repo', InputArgument::REQUIRED, 'Repository')
            ->addArgument('sha1', InputArgument::REQUIRED, 'Commit Sha1')
            ->addArgument('path', InputArgument::REQUIRED, 'Path')
            ->addArgument('line', InputArgument::REQUIRED, 'Line')
            ->addArgument('message', InputArgument::REQUIRED, 'Message')
            ->setHelp(<<<EOT
The <info>review-squawk:github:comment</info> command creates a commit comment.

<info>php app/console review-squawk:github:diff <token> <user> <repo> <sha1> <path> <line> <message></info>
EOT
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $api = $this->getContainer()->get('whitewashing.review_squawk.github_client');

        $api->commentCommit(
            $input->getArgument('access_token'),
            $input->getArgument('username'),
            $input->getArgument('repo'),
            $input->getArgument('sha1'),
            $input->getArgument('path'),
            $input->getArgument('line'),
            1,
            $input->getArgument('message')
        );
    }
}