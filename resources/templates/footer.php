<?php

$basedir = $this->studio->basedir . '/resources/pages';
$tosFile = $basedir . '/tos.html';
$privacyFile = $basedir . '/privacy.html';

$showTerms = file_exists($tosFile);
$showPrivacy = file_exists($privacyFile);
$showLegal = $showTerms || $showPrivacy;

?>

<?php if (($snippet = Ads::commit('footer')) !== false) { ?>
    <?php if ($this->studio->getopt('ad-footer-container') === 'On') { ?><div class="container"><?php } ?>
    <?php echo $snippet; ?>
    <?php if ($this->studio->getopt('ad-footer-container') === 'On') { ?></div><?php } ?>
<?php } ?>

<?php if ($showLegal) { ?>
        <footer>
            <div class="container">
                <div class="copyright">
                    <?php pt('Copyright &copy; {$1}', date('Y')); ?>

                </div>
                <div class="legal">
                    <?php if ($showTerms) { ?><a href="<?php echo $this->getPath(); ?>terms.php"><?php pt("Terms of Service"); ?></a><?php } ?>

                    <?php if ($showTerms && $showPrivacy) { ?><span>|</span><?php } ?>

                    <?php if ($showPrivacy) { ?><a href="<?php echo $this->getPath(); ?>privacy.php"><?php pt("Privacy Policy"); ?></a><?php } ?>

                </div>
            </div>
        </footer>
<?php } ?>


        <?php $this->studio->getPluginManager()->call("footer"); ?>

        <template id="loader">
            <div class="loadable-overlay">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-loader-quarter" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                    <line x1="12" y1="6" x2="12" y2="3" />
                    <line x1="6" y1="12" x2="3" y2="12" />
                    <line x1="7.75" y1="7.75" x2="5.6" y2="5.6" />
                </svg>
            </div>
        </template>

        <script type="text/javascript" src="<?php echo $this->getPath(); ?>resources/scripts/jquery.min.js"></script>
        <script type="text/javascript" src="<?php echo $this->getPath(); ?>resources/scripts/jquery-ui.min.js"></script>
        <script type="text/javascript">
            var path = "<?php echo $this->getPath(); ?>";
        </script>
        <script type="text/javascript" src="<?php echo $this->getPath(); ?>resources/scripts/studio.js?b=<?php echo $this->studio->getVersion(); ?>"></script>

        <?php echo $this->studio->getopt("custom-body-html"); ?>
    </body>
</html>
