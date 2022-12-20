<?php
$title = rt($this->getTitle());
$description = '';

$customCssTime = $this->studio->getopt('customCssTime', '0');

?>
<!DOCTYPE HTML>
<html lang="<?php echo $language->locale; ?>">
    <head>
        <meta charset="utf-8">
        <title><?php echo $title ?></title>

        <meta name="viewport" content="width=device-width, initial-scale=1">

        <link rel="stylesheet" type="text/css" href="//fonts.googleapis.com/css?family=Roboto:400,100,300,400italic,500,700,700italic,900">
        <link rel="stylesheet" type="text/css" href="//fonts.googleapis.com/icon?family=Material+Icons">
        <link rel="stylesheet" type="text/css" href="<?php echo $this->getPath(); ?>resources/styles/linea.css">
        <link rel="stylesheet" type="text/css" href="<?php echo $this->getPath(); ?>resources/styles/grid.css">
        <link rel="stylesheet" type="text/css" href="<?php echo $this->getPath(); ?>resources/styles/studio.css?b=<?php echo $this->studio->getVersion(); ?>">
        <link rel="stylesheet" type="text/css" href="<?php echo $this->getPath(); ?>resources/styles/custom.css?t=<?php echo $customCssTime; ?>">

        <!--[if lte IE 7]><script src="<?php echo $this->getPath(); ?>resources/scripts/icons-lte-ie7.js"></script><![endif]-->

<?php $this->studio->getPluginManager()->call("custom_head"); ?>
<?php echo $this->studio->getopt("custom-head-html"); ?>

    </head>
    <body class="embed <?php echo $language->dir; ?>">
