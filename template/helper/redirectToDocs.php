<?php
/**
 * This file is part of the prooph/mongodb-event-store.
 * (c) %year% prooph software GmbH <contact@prooph.de>
 * (c) %year% Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

final class redirectToDocs
{
    private $root = 'http://docs.getprooph.org';

    /**
     * @var \Bookdown\Bookdown\Content\Page
     */
    private $page;

    public function __construct(\Bookdown\Bookdown\Content\Page $page)
    {
        $this->page = $page;
    }

    public function __invoke()
    {
        $location = str_replace('/docs/html', '', $this->page->getHref());

        return '<p class="alert alert-info">Location of the docs has changed. New docs can be found here: <a class="alert-link" href="'.$this->root .$location.'">http://docs.getprooph.org</a></p>';
    }
}
