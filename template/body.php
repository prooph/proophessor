<?php
/**
 * This file is part of the prooph/proophessor.
 * (c) 2017 prooph software GmbH <contact@prooph.de>
 * (c) 2017 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
$cssBootswatch = getenv('CSS_BOOTSWATCH') ?: 'cerulean';

?>
<body data-spy="scroll" data-target="#sideNav" data-offset="50" class="bbt-theme-<?php echo $cssBootswatch; ?>">
<div class="page-wrapper">
    <?php echo $this->render('core'); ?>
</div>
<?= $this->forkOnGithub(); ?>
<?= $this->render("script"); ?>
</body>