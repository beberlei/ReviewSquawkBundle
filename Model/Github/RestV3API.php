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

        $treeUrl = $commitResponse['body']['tree']['url'];
        $treeResponse = $this->curl->request('GET', $treeUrl . "?recursive=true");

        $parentTreeCommitUrl = $commitResponse['body']['parents'][0]['url']; // TODO: Merge commits
        $parentTreeCommitResponse =  $this->curl->request('GET', $parentTreeCommitUrl);
        $parentTreeUrl = $parentTreeCommitResponse['body']['tree']['url'];
        $parentTreeResponse = $this->curl->request('GET', $parentTreeUrl);

        $diffs = array();
        foreach ($treeResponse['body']['tree'] AS $file) {
            if (substr($file['path'], -4) == ".php") {
                foreach ($parentTreeResponse['body']['tree'] AS $parentFile) {
                    if ($file['path'] == $parentFile['path'] && $file['sha'] != $parentFile['sha']) {
                        $oldCodeResponse = $this->curl->request('GET', $parentFile['url']);
                        $newCodeResponse = $this->curl->request('GET', $file['url']);

                        if ($oldCodeResponse['body']['encoding'] == 'base64') {
                            $oldCode = base64_decode($oldCodeResponse['body']['content']);
                        } else {
                            $oldCode = $oldCodeResponse['body']['content'];
                        }

                        if ($newCodeResponse['body']['encoding'] == 'base64') {
                            $newCode = base64_decode($newCodeResponse['body']['content']);
                        } else {
                            $newCode = $newCodeResponse['body']['content'];
                        }

                        $diffs[] = new Diff($file['path'], $oldCode, $newCode);
                        break;
                    }
                }
            }
        }

        return $diffs;
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
}