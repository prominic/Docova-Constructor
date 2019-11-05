$(function() {
	var parentFrame = null;
	var outline_html = '';
//	var menudata = $("#DECode").html();
//	outlineJson = JSON.parse(menudata);
	var applied = initiateOutlineContainer();

	if (applied === true)
	{
		outline_html = buildFooterOutline();
		$('#divOutline').html(outline_html);
	}	

	//---if the outline is in an iframe, in a page
	if($(window.frameElement).attr("elem") == "outline"){ 
		isInIframe = true;

		//---if the outline is also in the DOCOVA tabbed structure	
		parentFrame = parent.frameElement;
		if($(parentFrame).prop("id") == "appViewMain"){
			isInTabs = true;
		}
	}
	
	$('#divOutline').on('click', 'li', function(e){
		e.preventDefault();
		var trg = e.target.nodeName.toLowerCase() == 'a' ? $(e.target).parent().get(0) : e.target;
		OpenMenuItem(trg);
	});

	if ( isInIframe ) {
		var iframename = $("#DEName").text();
		var iframealias = $("#DEAlias").text();
	
	
	 	ifrm = $("iframe[outlinename='"+iframename+"']", window.parent.document)
		if ( ifrm.length ==0){
			ifrm =  $("iframe[outlinename=" + iframealias + "]", window.parent.document)
		}

		var presetheight = ($(ifrm).get(0).style.height || "");
		if(presetheight === "" || presetheight === "100%"){
			if(parentFrame){
				var parentframeheight = parseInt($(parentFrame).height(), 10);
				var iframestart = parseInt($(ifrm).offset().top, 10);
				var newheight = parentframeheight - iframestart;
				$(ifrm).height(newheight);
			}	
		}

		OLHeight = parseInt($(".OutlineItems:first")[0].scrollHeight + 10)
		$(ifrm).css("height",OLHeight );
		
	}else{
	     	//Calc height of containing div to show
		OLHeight = parseInt($(".OutlineItems:first").css("padding-top")) + parseInt($(".OutlineItems:first").css("padding-bottom")) + 20;
	
	
		$(".OutlineItems:first").css("height", $(window).height()-OLHeight)
		$(window).resize(function(){
			if ( ! isInIframe ) $(".OutlineItems:first").css("height", $(window).height()-OLHeight)
    		
		});
	} 

});

function buildFooterOutline(submenu, level)
{
	var menu_items = !submenu || typeof submenu == typeof undefined ? outlineJson.Items : submenu,
		isroot = !submenu || typeof submenu == typeof undefined ? true : false;

	level = !level || typeof level == typeof undefined ? 3 : level;
	var html = isroot === true ? '<section class="full_nav">' : '<ul>';
	for (var item = 0; item < menu_items.length; item++)
	{
		if (isroot === true) {
			html += '<div class="flex"><ul>';
		}
		html += '<li ' + (menu_items[item].Items && menu_items[item].Items.length ? '' : 'class="itmlist" ');
		html += 'emenuitemtype="' + (menu_items[item].type == 'header' ? 'H' : 'M') + '" ';
		html += 'etarget="' + menu_items[item].etarget + '" ';
		html += 'eelement="' + menu_items[item].eelement + '" ';
		html += 'etype="' + menu_items[item].etype + '" ';
		html += 'eviewtype="' + menu_items[item].eviewtype + '" ';
		html += 'eNoTab="' + menu_items[item].enotab +'" ';
		html += 'einitiallyselected="' + menu_items[item].initselected + '">';
		if (menu_items[item].icontitle && menu_items[item].type != 'header')
		{
			html += '<span class="';
			html += menu_items[item].icontitle ? 'far fa-1x ' + menu_items[item].icontitle + '" ' : '" ';
			html += 'style="left:5px; ';
			html += menu_items[item].iconposition ? 'padding-top: ' + (menu_items[item].iconposition == '0px' || menu_items[item].iconposition == '0' ? '3px' : menu_items[item].iconposition) + '; ' : '';
			html += menu_items[item].iconcolor ? 'color:' + menu_items[item].iconcolor + '; ' : '';
			html += '"></span>';
		}
		if (menu_items[item].Items && menu_items[item].Items.length) {
			html += '<h' + level + '>' + menu_items[item].context + '</h' + level + '>';
		}
		else {
			html += '<a href="#" ';
			if (menu_items[item].isbold || menu_items[item].isitalic)
			{
				html += 'style="';
//				html += menu_items[item].size ? 'font-size:' + menu_items[item].size + '; ' : '';
				html += menu_items[item].isbold ? 'font-weight: bold; ' : '';
				html += menu_items[item].isitalic ? 'font-style: italic; "' : '"';
			}
			html += '>' + menu_items[item].context + '</a>';
		}
		if (menu_items[item].Items && menu_items[item].Items.length) {
			var innerHtml = buildFooterOutline(menu_items[item].Items, level + 1);
			html += innerHtml;
		}
		html += '</li>';

		if (isroot === true) {
			html += '</ul></div>';
		}
	}
	html += isroot === true ? '</section>' : '</ul>';

	return html;
}