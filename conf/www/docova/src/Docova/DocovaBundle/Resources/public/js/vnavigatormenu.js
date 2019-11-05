var parentFrame = null;

$(function() {
	var outline_html = '';
	var applied = initiateOutlineContainer();

	if (applied === true)
	{
		outline_html = buildVerticalOutline();
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

	

	$('#divOutline').on('click', '.head_nav', function(e) {
		e.preventDefault();
		if ($(this).is(':last-child'))
			return false;
		var href = $(this).prop('href');
		href = href.indexOf('#') > -1 ? href.substr(href.indexOf('#')+1) : '';
		if (!href)
		{
			$('#menu_navigator').next('ul').hide('drop', {direction: 'left'}, 700, function(){
				var outline_html = buildVerticalOutline();
				$('#divOutline').html(outline_html);
				resizeOutline();
			});
		}
		else {
			var index = href.split(',');
			var menu = outlineJson.Items;
			for (var x = 0; x < index.length; x++) 
			{
				var menu = 'items' in menu ? menu.Items[index[x]] : menu[index[x]];
			}				
			var child_html = buildVerticalOutline(menu.Items, href);
			$(this).nextAll().each(function() { $(this).remove(); });
			$('#menu_navigator').next('ul').hide('drop', {direction: 'left'}, 700, function(){
				$(this).remove();
				$('#menu_navigator').parent().append(child_html);
				resizeOutline();
			});
		}
	});

	$('#divOutline').on('click', 'li.header', function() {
		if (!$(this).attr('itemindex'))
			return false;
		var index = $(this).attr('itemindex').split(',');
		var menu = outlineJson.Items;
		for (var x = 0; x < index.length; x++) 
		{
			var menu = 'Items' in menu ? menu.Items[index[x]] : menu[index[x]];
		}
		var child_html = buildVerticalOutline(menu.Items, $(this).attr('itemindex'));
		$('#menu_navigator').append('<span class="splitter far fa-1x fa-angle-right"></span><a style="color:' + outlineJson.menuStyle.HeaderFontColor + '" class="head_nav big" href="#'+ $(this).attr('itemindex') +'" >' + menu.context + '</a>');
		$('#menu_navigator').next('ul').hide('drop', {direction: 'left'}, 700, function(){
			$(this).remove();
			$('#menu_navigator').parent().append(child_html);
			resizeOutline();
			
		});
	});	
	
	$('#divOutline').on('click', 'li', function(){
		OpenMenuItem(this);
	});

	

	
	resizeOutline();

});

function buildVerticalOutline(submenu, parentIndex)
{
	var html = '',
		menu_items = !submenu || typeof submenu == typeof undefined ? outlineJson.Items : submenu;

	parentIndex = !parentIndex || typeof parentIndex == typeof undefiend ? '' : parentIndex + ',';

	var cls = "d-ui-state-default";

	if (!parentIndex) {
		html = '<div id="menu_navigator" style="color:' + outlineJson.menuStyle.HeaderFontColor + '; background:' +outlineJson.menuStyle.HeaderBackground + '"><a class="head_nav far fa-2x fa-home" href="#" style="padding: 12px 2px 2px 12px; color: ' + outlineJson.menuStyle.HeaderFontColor + '"></a></div>';
		cls = "d-ui-widget-header";
	}
	html += '<ul class="OutlineItems">';
/*
	if (!parentIndex && outlineJson.HinkeyMenu === true)
	{
		html += '<li class="hinkey hinkey_icon"><a href="#">&#9776;</a></li><li class="hinkey hinkey_container" style="padding: 0; margin: 0; height: auto;"><ul';
		if (outlineJson.InitiallyExpanded === false) {
			html += ' style="display:none;"';
		}
		html += '>';
	}
*/	
	for (var item = 0; item < menu_items.length; item++)
	{
		html += '<li class="' + (menu_items[item].type == 'header' ? 'header d-ui-widget-header' : cls) + '" ';
		if (menu_items[item].type == 'header') {
			html += 'itemindex="' + parentIndex + item + '" ';
		}
		html += 'emenuitemtype="' + (menu_items[item].type == 'header' ? 'H' : 'M') + '" ';
		html += 'etarget="' + menu_items[item].etarget + '" ';
		html += 'eelement="' + menu_items[item].eelement + '" ';
		html += 'isSpacer="' + menu_items[item].isSpacer + '" ';
		html += 'eNoTab="' + menu_items[item].enotab +'" ';
		html += 'etype="' + menu_items[item].etype + '" ';
		html += 'eviewtype="' + menu_items[item].eviewtype + '">';
		if (menu_items[item].icontitle && (menu_items[item].type != 'header' ))
		{
			html += '<span class="';
			html += menu_items[item].icontitle ? 'nicon far fa-1x ' + menu_items[item].icontitle + '" ' : '" ';
			html += 'style=" ';
			html += menu_items[item].iconfontsize ? 'font-size: ' +  menu_items[item].iconfontsize + '; ' : '';
			html += menu_items[item].iconcolor ? 'color:' + menu_items[item].iconcolor + '; ' : '';
			html += '" ></span>';
		}
		else if (menu_items[item].type == 'header' && outlineJson.SubmenuDetector)
		{

			html += '<span class="far fa-1x fa-chevron-right expandable " style="float: right;font-size:1.2em"></span>';
		
		}
		html += '<div class="itemlabel" style="';
		html += menu_items[item].size ? 'font-size:' + menu_items[item].size + '; ' : '';
		html += menu_items[item].isbold ? 'font-weight: bold; ' : '';
		html += menu_items[item].isitalic ? 'font-style: italic; ' : '';
//		html += menu_items[item].fontcolor ? 'color: ' + menu_items[item].fontcolor + ';' : '';
		html += '">' + menu_items[item].context + '</div></li>';
	}
/*
	if (!parentIndex && outlineJson.HinkeyMenu === true)
	{
		html += '</ul>';
	}
*/
	html += '</ul>';
	return html;
}


function resizeOutline(){
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


				// Calculate the total offset top of given jquery element
		    		function totalOffsetTop($elem) {
		        		return $elem.offsetTop + ($elem.offsetParent ? totalOffsetTop($elem.offsetParent) : 0);
		    		}


				var offsettotal =  parseInt(totalOffsetTop(ifrm.get(0), 10));
				var paddingtop =  $(".grid-stack-item-content", window.parent.document ).css("padding-top");

				var paddingbottom =  $(".grid-stack-item-content", window.parent.document ).css("padding-bottom");

				paddingtop = paddingtop? parseInt(paddingtop) : 0;
				paddingbottom = paddingbottom? parseInt(paddingbottom) : 0;
				
				
				
				var newheight = parentframeheight - offsettotal -  ( paddingtop + paddingbottom) - 40 ;

				if ( $(".grid-stack-item", window.parent.document).length > 1 )
				{
					var parentgrid = $(ifrm).parent().parent().parent();
					
					if ( parentgrid && parentgrid.hasClass("grid-stack-item")){
						parentgrid.height(newheight + ifrm.get(0).offsetTop +2 );
					}
				}
				$(ifrm).attr("origHeight", presetheight);
				$(ifrm).height(newheight);
			}	
		}

		OLHeight = parseInt($(ifrm).height(), 10) - 30;
		$("#divOutline>ul:first").css("height", OLHeight);
		
	}else{
	     	//Calc height of containing div to show
		OLHeight = parseInt($("#divOutline>ul:first").css("padding-top")) + parseInt($("#divOutline>ul:first").css("padding-bottom")) + 30;
	
	
		$("#divOutline>ul:first").css("height", $(window).height()-OLHeight)
		$(window).resize(function(){
			if ( ! isInIframe ) $("#divOutline>ul:first").css("height", $(window).height()-OLHeight)
    		
		});
	} 
	$("#divOutline>ul:first").css("overflow-y", "auto");

	$("#divOutline ul li").hover(function(){
		if ( $(this).attr("isSpacer") == "1" ) return;
		$("d-ui-state-hover").removeClass("d-ui-state-hover");
    	$(this).addClass("d-ui-state-hover");
	}, function(){
		$(this).removeClass("d-ui-state-hover");
	});

	$("[isspacer='1'").each ( function () {
	
		$(this).css("border-bottom",  "0px")
	})
}