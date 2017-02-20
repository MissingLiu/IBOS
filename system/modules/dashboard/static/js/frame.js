/**
 * frame.js
 * 后台框架
 * @module		Dashboard
 * @submodule 	frame
 * @modified	2013-04-23
 * @version		$Id: frame.js 920 2013-07-31 10:19:03Z gzzcs $
 * @author 		Inaki
 */

/**
 * Framework
 */
(function(){
	/**
	 * 调整布局宽高以适应屏幕
	 * @method adjustLayout
	 * @todo 改进，避免页面跳动
	 */
	// var adjustLayout = function () {
	// 	var size = Layout.getValidSize();
	// 	var logo = Dom.byId("logo"),
	// 		bar = Dom.byId("bar"),
	// 		aside = Dom.byId("aside"),
	// 		mc = Dom.byId("mc");
	// 	// aside.style.height = mc.style.height = size.height - 50 + "px";
	// 	if(size.width * 0.2 > 230){
	// 		aside.style.width = logo.style.width = bar.style.marginLeft = mc.style.marginLeft = size.width * 0.2 + "px";
	// 	}else{
	// 		aside.style.width = logo.style.width = bar.style.marginLeft = mc.style.marginLeft = "230px";
	// 	}
	// }

	// window.onload = window.onresize = adjustLayout;

	//展开网站地图
	// $("#logo").on("click", function(evt){
	// 	// var that = $(this);
	// 	// if($(this).hasClass("active")){
	// 	// 	$("#db_map").slideUp(200, function(){
	// 	// 		that.removeClass("active");
	// 	// 	});
	// 	// }else{
	// 	// 	$("#db_map").slideDown(200);
	// 	// 	$(this).addClass("active")
	// 	// }
	// 	// evt.stopPropagation();
	// })
	// $(document).on("click", function(){
	// 	$("#db_map").hide(0);
	// 	$("#logo").removeClass("active");
	// })
})()
