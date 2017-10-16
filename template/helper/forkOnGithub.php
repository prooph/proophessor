<?php

final class forkOnGithub
{
    private $githubRepo = 'https://github.com/prooph/proophessor';

    public function __construct(\Bookdown\Bookdown\Config\IndexConfig $config)
    {
        if($config->isRemote()) {
            $this->githubRepo = $this->extractGithubRepo($config);
        }
    }

    public function __invoke()
    {
        return '<span id="forkongithub"><a href="'.$this->githubRepo.'">Fork me on GitHub</a></span>';
    }

    private function extractGithubRepo(\Bookdown\Bookdown\Config\IndexConfig $config)
    {
        $match = [];
        if(preg_match('/^https:\/\/raw.githubusercontent.com\/(?P<orga>[\w-_]+)\/(?P<repo>[\w-_]+)\/.*$/', $config->getFile(), $match)) {
            return 'https://github.com/' . $match['orga'] . '/' . $match['repo'];
        }
    }
}
