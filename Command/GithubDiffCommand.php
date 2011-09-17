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

class GithubDiffCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('review-squawk:github:diff')
            ->setDescription('Create and vizualize the diffs of a Github commit')
            ->addArgument('username', InputArgument::REQUIRED, 'User/Organization')
            ->addArgument('repo', InputArgument::REQUIRED, 'Repository')
            ->addArgument('sha1', InputArgument::REQUIRED, 'Commit Sha1')
            ->setHelp(<<<EOT
The <info>review-squawk:github:diff</info> command creates and vizualizes the diff
of a github commit to its parent:

<info>php app/console review-squawk:github:diff <user> <repo> <sha1></info>
EOT
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $clientId = $this->getContainer()->getParameter('whitewashing.review_squawk.github.client_id');
        $clientSecret = $this->getContainer()->getParameter('whitewashing.review_squawk.github.client_secret');
        $api = new \Whitewashing\ReviewSquawkBundle\Model\Github\RestV3API($clientId, $clientSecret);
        
        $diffs = $api->getCommitDiffs($input->getArgument('username'), $input->getArgument('repo'), $input->getArgument('sha1'));

        foreach ($diffs AS $diff) {
            $snifferService = new \Whitewashing\ReviewSquawkBundle\Model\CodeSnifferService("Zend");
            $violations = $snifferService->scan($diff);

            foreach ($violations AS $violation) {
                $output->writeln($violation->getPath() . ":" . $violation->getLine()." " . $violation->getMessage());
            }
        }
    }
}