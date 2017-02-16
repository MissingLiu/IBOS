<?php

use application\core\utils\Ibos;
?>
<!doctype html>
<!-- <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN"> -->
<html lang="en">
    <head>
        <meta charset="<?php echo CHARSET; ?>">
        <title><?php echo $lang['Home page']; ?></title>
        <!-- load css -->
        <link rel="stylesheet" href="<?php echo STATICURL; ?>/css/base.css?<?php echo VERHASH; ?>">
        <!-- IE8 fixed -->
        <!--[if lt IE 9]>
            <link rel="stylesheet" href="<?php echo STATICURL; ?>/css/iefix.css?<?php echo VERHASH; ?>">
        <![endif]-->
        <!-- private css -->
        <link rel="stylesheet" href="<?php echo $assetUrl; ?>/css/animate.css?<?php echo VERHASH; ?>">
        <link rel="stylesheet" href="<?php echo $assetUrl; ?>/css/index.css?<?php echo VERHASH; ?>">
        <link rel="stylesheet" href="<?php echo STATICURL; ?>/js/lib/artDialog/skins/ibos.css?<?php echo VERHASH; ?>">
    </head>
    <body>
        <script>
            var adjustSidebarWidth = function() {
                document.body.className = (window.innerWidth || document.documentElement.clientWidth) > 1150 ? "db-widen" : "";
            }
            adjustSidebarWidth();
            window.onresize = adjustSidebarWidth;
        </script>
        <div style="margin-top: 100px;">
        <ul class="nav metismenu">
            <?php foreach ($cateConfig as $key => $value):?>
                <li>
                    <a href="#"><span class="nav-label"><?php echo $lang[$value['lang']];?></span></a>
                    <?php if (!empty($routes[$value['id']])):?>
                        <ul class="nav nav-second-level collapse" style="height: 0px;">
                        <?php foreach ($routes[$value['id']] as $routekey => $routevalue):?>
                                <?php $config = $routevalue['config'];?>
                                <?php if ($config['isShow']):?>
                                    <li>
                                        <a href="<?php echo $routevalue['url'];?>"><?php echo $lang[$config['lang']];?></a>
                                    </li>
                                <?php endif;?>
                        <?php endforeach;?>
                        </ul>
                    <?php endif;?>
                </li>
            <?php endforeach;?>
        </ul>
        </div>
        <!-- load js -->
        <script src="<?php echo STATICURL; ?>/js/src/core.js?<?php echo VERHASH; ?>"></script>
        <script src="<?php echo STATICURL; ?>/js/lib/artDialog/artDialog.min.js?<?php echo VERHASH; ?>"></script>
        <script src="<?php echo STATICURL; ?>/js/src/base.js?<?php echo VERHASH; ?>"></script>
        <script src="<?php echo STATICURL; ?>/js/src/common.js?<?php echo VERHASH; ?>"></script>
        <script src="<?php echo $assetUrl; ?>/js/frame.js?<?php echo VERHASH; ?>"></script>
        <script>
            $(function() {
                var refer = U.getUrlParam().refer;
                if (refer !== "") {
                    var $referElem = $('#sub_nav [href="' + unescape(refer) + '"]');
                    var $subMenu = $referElem.closest("ul");
                    var $nav = $('[data-href="#' + $subMenu.attr("id") + '"]');
                    $nav.click();
                    $referElem.click();
                }

                $(document).on("click", "a[target='main']", function() {
                    var title = '<?php echo $lang['Admin control']; ?> -' + $(this).html();
                    document.title = title;
                })
            });
        </script>
    </body>
</html>
