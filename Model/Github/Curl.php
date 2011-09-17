<?php

namespace Whitewashing\ReviewSquawkBundle\Model\Github;

class Curl
{

    private $ch;

    private $headers = array();

    public function __construct()
    {
        //$this->init();
    }

    private function init()
    {
        $this->ch = curl_init();
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->ch, CURLOPT_HEADERFUNCTION, array($this, 'readHeader'));
    }

    public function request($method, $url, array $params = null)
    {
        $this->init();
        $this->headers = array();
        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($method == 'POST') {
            curl_setopt($this->ch, CURLOPT_POST, true);
            curl_setopt($this->ch, CURLOPT_POSTFIELDS, "");
        }
        if (is_array($params)) {
            curl_setopt($this->ch, CURLOPT_POSTFIELDS, json_encode($params));
        }

        $ret = curl_exec($this->ch);
        if (curl_getinfo($this->ch, CURLINFO_HTTP_CODE) >= 400) {
            throw new \RuntimeException("Curl request failed to " . $url);
        }

        if (in_array(substr($ret, 0, 1), array("{", "["))) {
            $data = json_decode($ret, true);
        } else {
            $data = $ret;
        }

        return array('headers' => $this->headers, 'body' => $data);
    }

    private function readHeader($ch, $header)
    {
        if (strpos($header, ":") !== false) {
            $parts = explode(":", $header);
            $this->headers[$parts[0]] = trim($parts[1]); // ignore multiple same name headers here
        }

        return strlen($header);
    }

}