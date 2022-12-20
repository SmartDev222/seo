<?php

global $account, $studio;
global $market_count, $update_count, $noTranslationRedir;

$showQuickStartBanner = $studio->getopt('welcome-guide') === 'On';
$showQuickStartLink = $studio->getopt('welcome-guide-finished') !== 'On' || DEMO;
$showMissingTranslationsBanner = $studio->getopt('update-missing-translations') == "1" && !isset($noTranslationRedir);

?>
<!DOCTYPE HTML>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>SEO Studio</title>

        <meta name="viewport" content="width=device-width, initial-scale=1">

        <link rel="stylesheet" type="text/css" href="//fonts.googleapis.com/css?family=RobotoDraft:300,400,500,700|Source+Code+Pro">
        <link rel="stylesheet" type="text/css" href="//fonts.googleapis.com/icon?family=Material+Icons">
        <link rel="stylesheet" type="text/css" href="<?php echo $this->getPath(); ?>resources/styles/linea.css">
        <link rel="stylesheet" type="text/css" href="<?php echo $this->getPath(); ?>resources/styles/bootstrap.min.css">
        <link rel="stylesheet" type="text/css" href="<?php echo $this->getPath(); ?>resources/styles/admin.css?v=<?php echo \Studio\Base\Studio::VERSION_STR; ?>">
        <link rel="stylesheet" type="text/css" href="<?php echo $this->getPath(); ?>resources/styles/codemirror.css">

        <!--[if lte IE 7]><script src="<?php echo $this->getPath(); ?>resources/scripts/icons-lte-ie7.js"></script><![endif]-->

        <script type="text/javascript" src="<?php echo $this->getPath(); ?>resources/scripts/jquery.min.js"></script>
        <script type="text/javascript" src="<?php echo $this->getPath(); ?>resources/scripts/jquery-ui.min.js"></script>
        <script type="text/javascript" src="<?php echo $this->getPath(); ?>resources/scripts/codemirror.js"></script>
    </head>
    <body>
        <div class="mobile-header">
            <div class="logo">
                <a href="<?php echo $this->getPath(); ?>admin/">
                    <img src="<?php echo $this->getPath(); ?>resources/images/admin-logo.png" alt="SEO Studio">
                </a>
            </div>
            <div class="button">
                <button class="navbar-toggler" type="button" id="navbarToggle">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-list" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5z"/>
                    </svg>
                </button>
            </div>
        </div>

        <div class="sidebar">
            <div class="scroll-wrapper">
                <div class="logo">
                    <a href="<?php echo $this->getPath(); ?>admin">
                        <img src="<?php echo $this->getPath(); ?>resources/images/admin-logo.png" alt="SEO Studio">
                    </a>
                    <a href="<?php echo $this->getPath(); ?>" title="View website" data-bs-toggle="tooltip" data-bs-placement="right" target="_blank">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-globe2" viewBox="0 0 16 16">
                        <path d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm7.5-6.923c-.67.204-1.335.82-1.887 1.855-.143.268-.276.56-.395.872.705.157 1.472.257 2.282.287V1.077zM4.249 3.539c.142-.384.304-.744.481-1.078a6.7 6.7 0 0 1 .597-.933A7.01 7.01 0 0 0 3.051 3.05c.362.184.763.349 1.198.49zM3.509 7.5c.036-1.07.188-2.087.436-3.008a9.124 9.124 0 0 1-1.565-.667A6.964 6.964 0 0 0 1.018 7.5h2.49zm1.4-2.741a12.344 12.344 0 0 0-.4 2.741H7.5V5.091c-.91-.03-1.783-.145-2.591-.332zM8.5 5.09V7.5h2.99a12.342 12.342 0 0 0-.399-2.741c-.808.187-1.681.301-2.591.332zM4.51 8.5c.035.987.176 1.914.399 2.741A13.612 13.612 0 0 1 7.5 10.91V8.5H4.51zm3.99 0v2.409c.91.03 1.783.145 2.591.332.223-.827.364-1.754.4-2.741H8.5zm-3.282 3.696c.12.312.252.604.395.872.552 1.035 1.218 1.65 1.887 1.855V11.91c-.81.03-1.577.13-2.282.287zm.11 2.276a6.696 6.696 0 0 1-.598-.933 8.853 8.853 0 0 1-.481-1.079 8.38 8.38 0 0 0-1.198.49 7.01 7.01 0 0 0 2.276 1.522zm-1.383-2.964A13.36 13.36 0 0 1 3.508 8.5h-2.49a6.963 6.963 0 0 0 1.362 3.675c.47-.258.995-.482 1.565-.667zm6.728 2.964a7.009 7.009 0 0 0 2.275-1.521 8.376 8.376 0 0 0-1.197-.49 8.853 8.853 0 0 1-.481 1.078 6.688 6.688 0 0 1-.597.933zM8.5 11.909v3.014c.67-.204 1.335-.82 1.887-1.855.143-.268.276-.56.395-.872A12.63 12.63 0 0 0 8.5 11.91zm3.555-.401c.57.185 1.095.409 1.565.667A6.963 6.963 0 0 0 14.982 8.5h-2.49a13.36 13.36 0 0 1-.437 3.008zM14.982 7.5a6.963 6.963 0 0 0-1.362-3.675c-.47.258-.995.482-1.565.667.248.92.4 1.938.437 3.008h2.49zM11.27 2.461c.177.334.339.694.482 1.078a8.368 8.368 0 0 0 1.196-.49 7.01 7.01 0 0 0-2.275-1.52c.218.283.418.597.597.932zm-.488 1.343a7.765 7.765 0 0 0-.395-.872C9.835 1.897 9.17 1.282 8.5 1.077V4.09c.81-.03 1.577-.13 2.282-.287z"/>
                    </svg>
                    </a>
                </div>
                <div class="navigation">
                    <ul>
                        <li <?php $this->activeLink(1, 'active'); ?>>
                            <a href="<?php echo $this->getPath(); ?>admin/">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-house" viewBox="0 0 16 16">
                                    <path fill-rule="evenodd" d="M2 13.5V7h1v6.5a.5.5 0 0 0 .5.5h9a.5.5 0 0 0 .5-.5V7h1v6.5a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 2 13.5zm11-11V6l-2-2V2.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5z"/>
                                    <path fill-rule="evenodd" d="M7.293 1.5a1 1 0 0 1 1.414 0l6.647 6.646a.5.5 0 0 1-.708.708L8 2.207 1.354 8.854a.5.5 0 1 1-.708-.708L7.293 1.5z"/>
                                </svg>
                                Home
                            </a>
                        </li>
                        <li <?php $this->activeLink(30, 'active'); ?>>
                            <a href="<?php echo $this->getPath(); ?>admin/news.php">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-newspaper" viewBox="0 0 16 16">
                                    <path d="M0 2.5A1.5 1.5 0 0 1 1.5 1h11A1.5 1.5 0 0 1 14 2.5v10.528c0 .3-.05.654-.238.972h.738a.5.5 0 0 0 .5-.5v-9a.5.5 0 0 1 1 0v9a1.5 1.5 0 0 1-1.5 1.5H1.497A1.497 1.497 0 0 1 0 13.5v-11zM12 14c.37 0 .654-.211.853-.441.092-.106.147-.279.147-.531V2.5a.5.5 0 0 0-.5-.5h-11a.5.5 0 0 0-.5.5v11c0 .278.223.5.497.5H12z"/>
                                    <path d="M2 3h10v2H2V3zm0 3h4v3H2V6zm0 4h4v1H2v-1zm0 2h4v1H2v-1zm5-6h2v1H7V6zm3 0h2v1h-2V6zM7 8h2v1H7V8zm3 0h2v1h-2V8zm-3 2h2v1H7v-1zm3 0h2v1h-2v-1zm-3 2h2v1H7v-1zm3 0h2v1h-2v-1z"/>
                                </svg>
                                News
                            </a>
                        </li>
                        <?php if ($showQuickStartLink) { ?>
                        <li <?php $this->activeLink(0x3000, 'active'); ?>>
                            <a class="quick-start-link" href="<?php echo $this->getPath(); ?>admin/quickstart/index.php">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-star" viewBox="0 0 16 16">
                                    <path d="M2.866 14.85c-.078.444.36.791.746.593l4.39-2.256 4.389 2.256c.386.198.824-.149.746-.592l-.83-4.73 3.522-3.356c.33-.314.16-.888-.282-.95l-4.898-.696L8.465.792a.513.513 0 0 0-.927 0L5.354 5.12l-4.898.696c-.441.062-.612.636-.283.95l3.523 3.356-.83 4.73zm4.905-2.767-3.686 1.894.694-3.957a.565.565 0 0 0-.163-.505L1.71 6.745l4.052-.576a.525.525 0 0 0 .393-.288L8 2.223l1.847 3.658a.525.525 0 0 0 .393.288l4.052.575-2.906 2.77a.565.565 0 0 0-.163.506l.694 3.957-3.686-1.894a.503.503 0 0 0-.461 0z"/>
                                </svg>
                                Quick start
                            </a>
                        </li>
                        <?php } ?>
                        <li class="title">
                            Website
                        </li>
                        <!-- <li <?php $this->activeLink(8, 'active'); ?>>
                            <a href="<?php echo $this->getPath(); ?>admin/tools.php"><i class="material-icons">&#xE87B;</i> Tools</a>
                        </li> -->
                        <li <?php $this->activeLink(19, 'active'); ?>>
                            <a href="<?php echo $this->getPath(); ?>admin/customize/branding.php">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-palette" viewBox="0 0 16 16">
                                    <path d="M8 5a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3zm4 3a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3zM5.5 7a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm.5 6a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3z"/>
                                    <path d="M16 8c0 3.15-1.866 2.585-3.567 2.07C11.42 9.763 10.465 9.473 10 10c-.603.683-.475 1.819-.351 2.92C9.826 14.495 9.996 16 8 16a8 8 0 1 1 8-8zm-8 7c.611 0 .654-.171.655-.176.078-.146.124-.464.07-1.119-.014-.168-.037-.37-.061-.591-.052-.464-.112-1.005-.118-1.462-.01-.707.083-1.61.704-2.314.369-.417.845-.578 1.272-.618.404-.038.812.026 1.16.104.343.077.702.186 1.025.284l.028.008c.346.105.658.199.953.266.653.148.904.083.991.024C14.717 9.38 15 9.161 15 8a7 7 0 1 0-7 7z"/>
                                </svg>
                                Customize
                            </a>
                        </li>
                        <li <?php $this->activeLink(11, 'active'); ?>>
                            <a href="<?php echo $this->getPath(); ?>admin/content/languages/index.php">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-card-text" viewBox="0 0 16 16">
                                    <path d="M14.5 3a.5.5 0 0 1 .5.5v9a.5.5 0 0 1-.5.5h-13a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h13zm-13-1A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h13a1.5 1.5 0 0 0 1.5-1.5v-9A1.5 1.5 0 0 0 14.5 2h-13z"/>
                                    <path d="M3 5.5a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5zM3 8a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9A.5.5 0 0 1 3 8zm0 2.5a.5.5 0 0 1 .5-.5h6a.5.5 0 0 1 0 1h-6a.5.5 0 0 1-.5-.5z"/>
                                </svg>
                                Content
                            </a>
                        </li>
                        <li <?php $this->activeLink(9, 'active'); ?>>
                            <a href="<?php echo $this->getPath(); ?>admin/users/index.php">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-people" viewBox="0 0 16 16">
                                    <path d="M15 14s1 0 1-1-1-4-5-4-5 3-5 4 1 1 1 1h8zm-7.978-1A.261.261 0 0 1 7 12.996c.001-.264.167-1.03.76-1.72C8.312 10.629 9.282 10 11 10c1.717 0 2.687.63 3.24 1.276.593.69.758 1.457.76 1.72l-.008.002a.274.274 0 0 1-.014.002H7.022zM11 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm3-2a3 3 0 1 1-6 0 3 3 0 0 1 6 0zM6.936 9.28a5.88 5.88 0 0 0-1.23-.247A7.35 7.35 0 0 0 5 9c-4 0-5 3-5 4 0 .667.333 1 1 1h4.216A2.238 2.238 0 0 1 5 13c0-1.01.377-2.042 1.09-2.904.243-.294.526-.569.846-.816zM4.92 10A5.493 5.493 0 0 0 4 13H1c0-.26.164-1.03.76-1.724.545-.636 1.492-1.256 3.16-1.275zM1.5 5.5a3 3 0 1 1 6 0 3 3 0 0 1-6 0zm3-2a2 2 0 1 0 0 4 2 2 0 0 0 0-4z"/>
                                </svg>
                                Users
                            </a>
                        </li>
                        <?php
                            // Extensions can insert their own nav links here
                            $this->studio->getPluginManager()->call("admin_nav");
                        ?>
                        <li class="title">
                            System
                        </li>
                        <li <?php $this->activeLink(3, 'active'); ?>>
                            <a href="<?php echo $this->getPath(); ?>admin/settings.php">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-gear" viewBox="0 0 16 16">
                                    <path d="M8 4.754a3.246 3.246 0 1 0 0 6.492 3.246 3.246 0 0 0 0-6.492zM5.754 8a2.246 2.246 0 1 1 4.492 0 2.246 2.246 0 0 1-4.492 0z"/>
                                    <path d="M9.796 1.343c-.527-1.79-3.065-1.79-3.592 0l-.094.319a.873.873 0 0 1-1.255.52l-.292-.16c-1.64-.892-3.433.902-2.54 2.541l.159.292a.873.873 0 0 1-.52 1.255l-.319.094c-1.79.527-1.79 3.065 0 3.592l.319.094a.873.873 0 0 1 .52 1.255l-.16.292c-.892 1.64.901 3.434 2.541 2.54l.292-.159a.873.873 0 0 1 1.255.52l.094.319c.527 1.79 3.065 1.79 3.592 0l.094-.319a.873.873 0 0 1 1.255-.52l.292.16c1.64.893 3.434-.902 2.54-2.541l-.159-.292a.873.873 0 0 1 .52-1.255l.319-.094c1.79-.527 1.79-3.065 0-3.592l-.319-.094a.873.873 0 0 1-.52-1.255l.16-.292c.893-1.64-.902-3.433-2.541-2.54l-.292.159a.873.873 0 0 1-1.255-.52l-.094-.319zm-2.633.283c.246-.835 1.428-.835 1.674 0l.094.319a1.873 1.873 0 0 0 2.693 1.115l.291-.16c.764-.415 1.6.42 1.184 1.185l-.159.292a1.873 1.873 0 0 0 1.116 2.692l.318.094c.835.246.835 1.428 0 1.674l-.319.094a1.873 1.873 0 0 0-1.115 2.693l.16.291c.415.764-.42 1.6-1.185 1.184l-.291-.159a1.873 1.873 0 0 0-2.693 1.116l-.094.318c-.246.835-1.428.835-1.674 0l-.094-.319a1.873 1.873 0 0 0-2.692-1.115l-.292.16c-.764.415-1.6-.42-1.184-1.185l.159-.291A1.873 1.873 0 0 0 1.945 8.93l-.319-.094c-.835-.246-.835-1.428 0-1.674l.319-.094A1.873 1.873 0 0 0 3.06 4.377l-.16-.292c-.415-.764.42-1.6 1.185-1.184l.292.159a1.873 1.873 0 0 0 2.692-1.115l.094-.319z"/>
                                </svg>
                                Settings
                            </a>
                        </li>
                        <!-- <li <?php $this->activeLink(11, 'active'); ?>>
                            <a href="<?php echo $this->getPath(); ?>admin/languages.php"><i class="material-icons">&#xE894;</i> Language</a>
                        </li> -->
                        <li <?php $this->activeLink(22, 'active'); ?>>
                            <a href="<?php echo $this->getPath(); ?>admin/services/cron.php">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-cloud" viewBox="0 0 16 16">
                                    <path d="M4.406 3.342A5.53 5.53 0 0 1 8 2c2.69 0 4.923 2 5.166 4.579C14.758 6.804 16 8.137 16 9.773 16 11.569 14.502 13 12.687 13H3.781C1.708 13 0 11.366 0 9.318c0-1.763 1.266-3.223 2.942-3.593.143-.863.698-1.723 1.464-2.383zm.653.757c-.757.653-1.153 1.44-1.153 2.056v.448l-.445.049C2.064 6.805 1 7.952 1 9.318 1 10.785 2.23 12 3.781 12h8.906C13.98 12 15 10.988 15 9.773c0-1.216-1.02-2.228-2.313-2.228h-.5v-.5C12.188 4.825 10.328 3 8 3a4.53 4.53 0 0 0-2.941 1.1z"/>
                                </svg>
                                <div class="flex-grow-1">Services</div>
                                <?php
                                    $count = 0;

                                    if ($this->studio->getopt('cron-last-run') < (time() - 43200)) $count++;
                                    if ($count > 0) echo "<span class=\"notification issue\">$count</span>";
                                ?>
                            </a>
                        </li>
                        <!-- <li <?php $this->activeLink(17, 'active'); ?>>
                            <a href="<?php echo $this->getPath(); ?>admin/tool-usage.php"><i class="material-icons">history</i> Usage</a>
                        </li> -->
                        <li <?php $this->activeLink(14, 'active'); ?>>
                            <a href="<?php echo $this->getPath(); ?>admin/updates.php">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-down-circle" viewBox="0 0 16 16">
                                    <path fill-rule="evenodd" d="M1 8a7 7 0 1 0 14 0A7 7 0 0 0 1 8zm15 0A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM8.5 4.5a.5.5 0 0 0-1 0v5.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V4.5z"/>
                                </svg>
                                <div class="flex-grow-1">Updates</div>
                                <?php
                                    if ($update_count > 0) {
                                        echo "<span class=\"notification\">$update_count</span>";
                                    }
                                ?>
                            </a>
                        </li>
                        <li <?php $this->activeLink(16, 'active'); ?>>
                            <a href="<?php echo $this->getPath(); ?>admin/support.php">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-question-circle" viewBox="0 0 16 16">
                                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                    <path d="M5.255 5.786a.237.237 0 0 0 .241.247h.825c.138 0 .248-.113.266-.25.09-.656.54-1.134 1.342-1.134.686 0 1.314.343 1.314 1.168 0 .635-.374.927-.965 1.371-.673.489-1.206 1.06-1.168 1.987l.003.217a.25.25 0 0 0 .25.246h.811a.25.25 0 0 0 .25-.25v-.105c0-.718.273-.927 1.01-1.486.609-.463 1.244-.977 1.244-2.056 0-1.511-1.276-2.241-2.673-2.241-1.267 0-2.655.59-2.75 2.286zm1.557 5.763c0 .533.425.927 1.01.927.609 0 1.028-.394 1.028-.927 0-.552-.42-.94-1.029-.94-.584 0-1.009.388-1.009.94z"/>
                                </svg>
                                Help
                            </a>
                        </li>
                        <li class="title">
                            Account
                        </li>
                        <!-- <li>
                            <a href="<?php echo $this->getPath(); ?>" class="public">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-play-circle" viewBox="0 0 16 16">
                                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                                    <path d="M6.271 5.055a.5.5 0 0 1 .52.038l3.5 2.5a.5.5 0 0 1 0 .814l-3.5 2.5A.5.5 0 0 1 6 10.5v-5a.5.5 0 0 1 .271-.445z"/>
                                </svg>
                                View website
                            </a>
                        </li> -->
                        <li>
                            <a href="<?php echo $this->getPath(); ?>account/settings.php" class="public">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-circle" viewBox="0 0 16 16">
                                    <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/>
                                    <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1z"/>
                                </svg>
                                User settings
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo $this->getPath(); ?>account/signout.php" class="public">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-box-arrow-left" viewBox="0 0 16 16">
                                    <path fill-rule="evenodd" d="M6 12.5a.5.5 0 0 0 .5.5h8a.5.5 0 0 0 .5-.5v-9a.5.5 0 0 0-.5-.5h-8a.5.5 0 0 0-.5.5v2a.5.5 0 0 1-1 0v-2A1.5 1.5 0 0 1 6.5 2h8A1.5 1.5 0 0 1 16 3.5v9a1.5 1.5 0 0 1-1.5 1.5h-8A1.5 1.5 0 0 1 5 12.5v-2a.5.5 0 0 1 1 0v2z"/>
                                    <path fill-rule="evenodd" d="M.146 8.354a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L1.707 7.5H10.5a.5.5 0 0 1 0 1H1.707l2.147 2.146a.5.5 0 0 1-.708.708l-3-3z"/>
                                </svg>
                                Sign out
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="sidebar-fade"></div>

        <script>
            var toggle = $('#navbarToggle');
            var sidebar = $('.sidebar');
            var sidebarFade = $('.sidebar-fade');

            toggle.on('click', function() {
                sidebar.toggleClass('active');
                sidebarFade.toggleClass('active');

                toggle.blur();
            });

            sidebarFade.on('click', function() {
                sidebar.removeClass('active');
                sidebarFade.removeClass('active');
            });
        </script>

        <div class="body">
            <?php if ($showQuickStartBanner) { ?>
            <a href="<?php echo $this->getPath(); ?>admin/quickstart/index.php" style="text-decoration: none !important;">
                <div class="qsbanner">
                    <i class="material-icons">star</i>
                    <span>Click here to get started with our new quick start utility.</span>
                </div>
            </a>
            <?php } ?>

            <?php
            if (isset($_GET['success'])) {
            ?>
            <div class="success">
                <i class="material-icons">check</i>
                <span>Operation was successful.</span>
            </div>
            <?php
            }

            $data = unserialize($this->studio->getopt("tools"));
            $catData = unserialize($this->studio->getopt("categories"));

            $allToolsList = array();
            foreach ($data as $category => $tools) {
                foreach ($tools as $id) {
                    $allToolsList[] = $id;
                }
            }

            $list = $this->studio->getTools();
            $missing = array();

            foreach ($list as $tool) {
                if (!in_array($tool->id, $allToolsList)) {
                    $missing[] = $tool;
                }
            }

            if (count($missing) > 0) {
            ?>
            <a href="<?php echo $this->getPath(); ?>admin/content/pages/tools.php#tab-tools" style="text-decoration: none !important;">
                <div class="warning">
                    <i class="material-icons">error_outline</i>
                    <span>There are tools that have been installed but are not added to the tools page. Click here to add them.</span>
                </div>
            </a>
            <?php
            }

            if ($showMissingTranslationsBanner) {
            ?>
            <a href="<?php echo $this->getPath(); ?>admin/update-translations.php" style="text-decoration: none !important;">
                <div class="warning">
                    <i class="material-icons">error_outline</i>
                    <span>New words or phrases have been added to the script. Click here to register and translate them.</span>
                </div>
            </a>
            <?php
            }

            $this->studio->getPluginManager()->call("admin_head");
            ?>

            <?php if (isset($navigation)) { ?>

            <header class="tabbed">
                <h1><?php echo $navigation['title']; ?></h1>
                <p><?php echo $navigation['description']; ?></p>

                <nav>
                    <ul>
                        <?php
                            foreach ($navigation['links'] as $link) {
                                $active = $currentPage === $link['url'] ? 'active' : '';
                        ?>
                        <li class="<?php echo $active; ?>">
                            <a href="<?php echo $this->getPath(); ?>admin/<?php echo $link['url']; ?>">
                                <?php
                                    if (isset($link['icon'])) {
                                        echo '<i class="material-icons">' . sanitize_html($link['icon']) . '</i>';
                                    }
                                ?>

                                <?php echo $link['title']; ?>

                                <?php if (isset($link['issues']) && $link['issues'] > 0) { ?>
                                    <span class="badge bg-warning"><?php echo $link['issues']; ?></span>
                                <?php } ?>
                            </a>
                        </li>
                        <?php
                            }
                        ?>
                    </ul>
                </nav>
            </header>

            <?php } ?>

            <main>
