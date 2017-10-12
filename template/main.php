<?php
/**
 * This file is part of the prooph/proophessor.
 * (c) 2017 prooph software GmbH <contact@prooph.de>
 * (c) 2017 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

putenv('MENU_LOGO=http://getprooph.org/images/prooph-logo.svg');

$templatePath = __DIR__ . '/../vendor/bookdown/themes/templates';

require_once $templatePath . '/helper/tocList.php';

$config = $this->page->getRoot()->getConfig();

// register view helper
$helpers = $this->getHelpers();

$helpers->set('tocListHelper', function () use ($config) {
    return new \tocListHelper($this->get('anchorRaw'), $config);
});

// register the templates
$templates = $this->getViewRegistry();

$templates->set('head', __DIR__ . '/head.php');
$templates->set('meta', __DIR__ . '/meta.php');
$templates->set('style', $templatePath . '/style.php');
$templates->set('styleProoph', __DIR__ . '/style.php');
$templates->set('body', __DIR__ . '/body.php');
$templates->set('script', $templatePath . '/script.php');
$templates->set('nav', __DIR__ . '/nav.php');
$templates->set('core', $templatePath . '/core.php');
$templates->set('navheader', $templatePath . '/navheader.php');
$templates->set('navfooter', $templatePath . '/navfooter.php');
$templates->set('toc', $templatePath . '/toc.php');
$templates->set('partialTopNav', $templatePath . '/partial/topNav.php');
$templates->set('partialBreadcrumb', $templatePath . '/partial/breadcrumb.php');
$templates->set('partialSideNav', $templatePath . '/partial/sideNav.php');
?>

<!DOCTYPE html>
<html>
<?= $this->render("head"); ?>
<?= $this->render("body"); ?>
</html>
