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
<a href="https://github.com/prooph/proophessor"><img style="position: absolute; top: 0; right: 0; border: 0;" src="https://camo.githubusercontent.com/38ef81f8aca64bb9a64448d0d70f1308ef5341ab/68747470733a2f2f73332e616d617a6f6e6177732e636f6d2f6769746875622f726962626f6e732f666f726b6d655f72696768745f6461726b626c75655f3132313632312e706e67" alt="Fork me on GitHub" data-canonical-src="https://s3.amazonaws.com/github/ribbons/forkme_right_darkblue_121621.png"></a>
<?= $this->render("script"); ?>
</body>