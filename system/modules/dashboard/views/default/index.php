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
        <link rel="stylesheet" href="<?php echo $assetUrl; ?>/css/style.css?<?php echo VERHASH; ?>">
        <link rel="stylesheet" href="<?php echo STATICURL; ?>/js/lib/artDialog/skins/ibos.css?<?php echo VERHASH; ?>">
    </head>
    <body class="pace-done">
        <div class="db-map" id="db_map" style="display:none;">
            <ul class="dbm-main-list">
                <?php foreach ($routes as $cate => $routeA): ?>
                    <li class="dbm-main-item clearfix">
                        <div class="dbm-main-item-name"><?php echo $lang[$cateConfig[$cate]['lang']]; ?></div>
                        <ul class="dbm-sub-list">
                            <?php foreach ($routeA as $route => $config): ?>
                                <?php if ($config['config']['isShow']): ?>
                                    <li>
                                        <a href="<?php echo $config['url']; ?>" target="main"
                                           title="<?php echo $lang[$config['config']['lang']]; ?>">
                                            <?php echo $lang[$config['config']['lang']]; ?>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="header">
            <div class="logo" id="logo">
                <h2 class="logo-bg">IBOS</h2>
                <a href="javascript:;" id="db_map_ctrl" class="cbtn db-map-ctrl"></a>
            </div>
            <div class="hdbar clearfix" id="bar">
                <form method="post" autocomplete="off" target="main" action="<?php echo $this->createUrl('default/search'); ?>">
                    <div class="dbsearch">
                        <input type="text" name="keyword" placeholder="<?php echo $lang['Search']; ?>" x-webkit-speech=""
                               speech="" class="input-small">
                        <input type="hidden" name="formhash" value="<?php echo FORMHASH; ?>"/>
                    </div>
                </form>
                <div class="user-info pull-right">
                            <span class="user-name">
                                <a href="<?php echo Ibos::app()->user->space_url; ?>" target="_blank"><img width="30"
                                                                                                           height="30"
                                                                                                           class="radius msep"
                                                                                                           src="<?php echo Ibos::app()->user->avatar_middle; ?>"
                                                                                                           title="<?php echo Ibos::app()->user->realname; ?>"></a>
                                <strong><?php echo Ibos::app()->user->realname; ?></strong>
                            </span>
                    <a href="<?php echo Ibos::app()->urlManager->createUrl('/'); ?>" target="_blank"
                       class="msep cbtn o-homepage" title="<?php echo Ibos::lang('Return to home page'); ?>"></a>
                    <a href="<?php echo $this->createUrl('default/logout', array('formhash' => FORMHASH)); ?>"
                       class="cbtn o-logout" title="<?php echo $lang['Logout']; ?>"></a>
                </div>
            </div>
        </div>
        <div class="mainer" id="wrapper">
           <nav class="navbar-default navbar-static-side" role="navigation">
                <div class="sidebar-collapse">
                    <ul class="nav metismenu" id="side-menu">
                        <?php foreach ($cateConfig as $key => $value):?>
                            <li>
                                <a href="#">
                                    <span class="nav-label"><?php echo $lang[$value['lang']];?></span>
                                    <span class="fa arrow"></span>      
                                </a>
                                <?php if (!empty($routes[$value['id']])):?>
                                    <ul class="nav nav-second-level collapse">
                                    <?php foreach ($routes[$value['id']] as $routekey => $routevalue):?>
                                            <?php $config = $routevalue['config'];?>
                                            <?php if ($config['isShow']):?>
                                                <li>
                                                    <a href="<?php echo $routevalue['url'];?>" target="main" title="<?php echo $lang[$config['lang']];?>"><?php echo $lang[$config['lang']];?></a>
                                                </li>
                                            <?php endif;?>
                                    <?php endforeach;?>
                                    </ul>
                                <?php endif;?>
                            </li>
                        <?php endforeach;?>
                    </ul>
                </div>
           </nav>
           <div class="mc gray-bg" id="page-wrapper">
                <iframe src="<?php echo $def; ?>" width="100%" height="100%" frameborder="0" name="main" id="main"></iframe>
           </div>
        </div>
        <!-- load js -->
        <script src="<?php echo STATICURL; ?>/js/src/core.js?<?php echo VERHASH; ?>"></script>
        <script src="<?php echo STATICURL; ?>/js/lib/artDialog/artDialog.min.js?<?php echo VERHASH; ?>"></script>
        <script src="<?php echo STATICURL; ?>/js/src/base.js?<?php echo VERHASH; ?>"></script>
        <script src="<?php echo STATICURL; ?>/js/src/common.js?<?php echo VERHASH; ?>"></script>
        <script src="<?php echo $assetUrl; ?>/js/plugins/metisMenu/jquery.metisMenu.js?<?php echo VERHASH; ?>"></script>
        <script src="<?php echo $assetUrl; ?>/js/plugins/slimscroll/jquery.slimscroll.min.js?<?php echo VERHASH; ?>"></script>
        <script src="<?php echo $assetUrl; ?>/js/inspinia.js?<?php echo VERHASH; ?>"></script>
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
