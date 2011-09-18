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

namespace Whitewashing\ReviewSquawkBundle\Model\Github;

use Whitewashing\ReviewSquawkBundle\Model\Diff;

class RestV3API implements ClientAPI
{
    private $curl;
    private $clientId;
    private $clientSecret;

    public function __construct($clientId, $clientSecret)
    {
        $this->curl = new Curl();
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
    }

    public function commentCommit($accessToken, $username, $repository, $sha1, $path, $line, $position, $message)
    {
        $params = array(
            'line' => $line,
            'path' => $path,
            'position' => $position,
            'body' => $message,
            'commit_id' => $sha1,
        );

        $commentsUrl = "https://api.github.com/repos/" . $username . "/" . $repository . "/commits/" . $sha1 . "/comments?access_token=".$accessToken;
        $this->curl->request('POST', $commentsUrl, $params);
    }

    public function commentPullRequest($accessToken, $username, $repository, $prId, $sha1, $path, $line, $message)
    {

    }

    /**
     * @TechnicalDebt 2:00 Testing and code refactoring
     * @param string $username
     * @param string $repository
     * @param string $sha1
     * @return Diff
     */
    public function getCommitDiffs($username, $repository, $sha1)
    {
        $commitUrl = "https://api.github.com/repos/" . $username . "/" . $repository . "/git/commits/" . $sha1;
        $commitResponse = $this->curl->request('GET', $commitUrl);

        $isInitialCommit = true;
        if (isset($commitResponse['body']['parents'][0])) {
            $parentSha = $commitResponse['body']['parents'][0]['sha'];
            $isInitialCommit = false;
        }

        $diffs = array();
        if ($isInitialCommit) {
            $treeUrl = $commitResponse['body']['tree']['url'];
            $treeResponse = $this->curl->request('GET', $treeUrl . "?recursive=true");

            foreach ($treeResponse['body']['tree'] AS $file) {
                if (substr($file['path'], -4) == ".php") {
                    $newCode = $this->getBlob($file['url']);

                    $diffs[] = new Diff($file['path'], "", $newCode);
                }
            }
        } else {
            $parentTreeUrl = $commitResponse['body']['tree']['url'];
            $parentTreeResponse = $this->curl->request('GET', $parentTreeUrl . "?recursive=true");

            $compareUrl = "https://api.github.com/repos/" . $username . "/" . $repository . "/compare/" . $sha1 . "..." . $parentSha;
            $compareResponse = $this->curl->request('GET', $compareUrl);

            foreach ($parentTreeResponse['body']['tree'] AS $parentTreeFile) {
                if ( substr($parentTreeFile['path'], -4) == ".php" ) {
                    foreach ($compareResponse['body']['files'] AS $compareFile) {
                        if ($compareFile['filename'] == $parentTreeFile['path']) {
                            $diffs[] = new Diff(
                                $parentTreeFile['path'],
                                $this->getBlob($parentTreeFile['url']),
                                $this->getBlob($compareFile['sha']),
                                $compareFile['patch']
                            );
                        }
                    }
                }
            }
        }

        return $diffs;
    }

    private function getBlob($url)
    {
        $codeResponse = $this->curl->request('GET', $url);

        if ($codeResponse['body']['encoding'] == 'base64') {
            $code = base64_decode($codeResponse['body']['content']);
        } else {
            $code = $codeResponse['body']['content'];
        }
        return $code;
    }

    public function claimOAuthAccessToken($temporaryCode)
    {
        $params = array(
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $temporaryCode,
        );
        $response = $this->curl->request('POST', 'https://github.com/login/oauth/access_token?' . http_build_query($params));

        if (preg_match('(access_token=([a-z0-9A-Z]+)(&(\\S*))?)', $response['body'], $match)) {
            return ($match[1]);
        } else {
            throw new \RuntimeException("no access token found in response!");
        }
    }

    public function getCurrentUser($accessToken)
    {
        $response = $this->curl->request('GET', 'https://api.github.com/user?access_token='. $accessToken);
        return $response['body'];
    }

    public function getProject($username, $repository)
    {
        $response = $this->curl->request('GET', 'https://api.github.com/repos/'. $username . '/' . $repository);
        return $response['body'];
    }

    public function getCommits($username, $repository)
    {
        $response = $this->curl->request('GET', 'https://api.github.com/repos/'. $username . '/' . $repository . '/commits');
        return $response['body'];
    }
}