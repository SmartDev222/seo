<?php global $studio; ?>

            </main>
        </div>

        <footer>
            <div class="row">
                <div class="col-md-6">
                    <span><img src="<?php echo $this->getPath(); ?>resources/images/admin-logo-gray.png" width="110px"> <sub><?php echo \Studio\Base\Studio::VERSION_STR . '.' . \Studio\Base\Studio::VERSION; ?></sub></span>

                    <div>
                        Copyright &copy; 2022. Source files include multiple proprietary assets
                        which have been licensed for use in this software. Please see the documentation
                        attached with your product download for more information. Do not redistribute.
                    </div>

                    <?php if ($studio->getopt("api.secretkey")) { ?>

                    <?php } else { ?>

                    <strong class="not-licensed">
                        <i class="material-icons">&#xE8B2;</i>
                        This installation has not been licensed.
                    </strong>

                    <?php } ?>
                </div>
                <div class="col-md-2">

                </div>
                <div class="col-md-2">
                    <ul>
                        <li><a href="https://codecanyon.net/user/baileyherbert" target="_blank">Developer</a></li>
                        <li><a href="https://codecanyon.net/item/seo-studio-professional-tools-for-seo/17022701" target="_blank">Product page</a></li>
                        <li><a href="<?php echo $this->getPath(); ?>content/documents/documentation.html" target="_blank">Documentation</a></li>
                    </ul>
                </div>
                <div class="col-md-2">
                    <ul>
                        <li><a href="<?php echo $this->getPath(); ?>admin/index.php">News</a></li>
                        <li><a href="<?php echo $this->getPath(); ?>admin/support.php">Support</a></li>
                        <li><a href="<?php echo $this->getPath(); ?>admin/send-feedback.php">Send feedback</a></li>
                    </ul>
                </div>
            </div>
        </footer>

        <script>var demoMode = <?php echo DEMO ? 'true' : 'false'; ?>;</script>
        <script type="text/javascript" src="<?php echo $this->getPath(); ?>resources/scripts/bootstrap.bundle.min.js?v=<?php echo \Studio\Base\Studio::VERSION_STR; ?>"></script>
        <script type="text/javascript" src="<?php echo $this->getPath(); ?>resources/scripts/admin.js?v=<?php echo \Studio\Base\Studio::VERSION_STR; ?>"></script>

        <script>
        var studioloc = window.location.href;
        studioloc = studioloc.substring(0, studioloc.indexOf("/admin")) + "/";

        // this updates the database's record of the current installation url

        if (studioloc != "<?php echo $this->studio->getopt('public-url'); ?>") {
            $.post("bgupurl.php", {url:studioloc}, function(data) {});
        }
        </script>
    </body>
</html>
