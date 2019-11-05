$(function() {
	var parentFrame = null;
	var outline_html = '';
	var applied = initiateOutlineContainer();

	if (applied === true)
	{
		outline_html = buildHorizontalOutline();
		$('#divOutline').html(outline_html);
		var align = "";
		if (  outlineJson.Alignment && outlineJson.Alignment == "R"){
			align = "flex-end"
		}else if ( outlineJson.Alignment && outlineJson.Alignment == "J"){
			align = "justify"
		}else if ( outlineJson.Alignment && outlineJson.Alignment == "C"){
			align = "center"
		}

		if ( align != ""){
			$('#divOutline').css("justify-content",  align)	;
		}

		
	}
	
	//---if the outline is in an iframe, in a page
	if($(window.frameElement).attr("elem") == "outline"){ 
		isInIframe = true;

		//---if the outline is also in the DOCOVA tabbed structure	
		parentFrame = parent.frameElement;
		if($(parentFrame).prop("id") == "appViewMain"){
			isInTabs = true;
		}
	}else if ( window.frameElement){
		parentFrame = window.frameElement;
	}

	$("#divOutline ul li").hover(function(){
		if ( $(this).attr("isSpacer") == "1" ) return; 
		$(this).addClass("d-ui-state-hover");
		$(this).find(".expandablesys").show();
	}, function(){
		$(this).removeClass("d-ui-state-hover");
		if ( ! $(this).hasClass("d-ui-state-active"))
			$(this).find(".expandablesys").hide();
	});

	$(".expandablesys").hide();

	$("#divOutline > ul > li").each(function()
	{
		if (outlineJson.menuStyle )
		{
			
			var bordertype = outlineJson.menuStyle.border_style;
			var bordercolor = outlineJson.menuStyle.bordercolor;
		}
		if ( bordertype && bordercolor && $(this).attr("isspacer") != "1")
			$(this).css("border-right", "1px " + bordertype + " " + bordercolor);
	})

	
	var p = $(parentFrame).parents(".grid-stack-item");
	var isinbuilder = false;
	if ( p.length >  0){
		var o = p.get(0).ownerDocument;
		if ( o.location.href.indexOf("AppLayout") > 0){
			isinbuilder = true;
		}
	
	}
	
	
	if ( ! isinbuilder && (outlineJson.SubmenuPerspective == "IS" || outlineJson.SubmenuPerspective == "IC") && outlineJson.menuStyle.IconMenuDropShadow == 1 )
	{
		//icon only view...
		var p = $(parentFrame).parents(".grid-stack-item");

		if ( parseInt(p.parent().attr("pvspacing")) == 0 )
		{		
			p.css ( "box-shadow", "rgba(30, 31, 33, 0.25) 5px 4px 13px 0px");
			p.css ( "z-index", "1");
		}
	}


	$('#divOutline').on('click', 'li', function(e) {
		e.preventDefault();
		$('#divOutline .d-ui-state-active').each ( function () { 
			$(this).removeClass("d-ui-state-active");
			$(this).find(".expandablesys").hide();
		});

		
		$(this).addClass('d-ui-state-active');
		$(this).find(".expandablesys").show();

		var index = $(this).attr('itemindex');
		var menu = outlineJson.Items[index];
		var styleobj = outlineJson.menuStyle;

		var offset = $(this).offset();

		
		
		
		window.top.Docova.Utils.generateMenu({
			style : outlineJson.SubmenuPerspective == 'HP' || outlineJson.SubmenuPerspective == 'IS'? 'pane' : 'cascade',
			parent :$(this),
			items : menu.Items,
			themed : (outlineJson.Style == 'Themed'),
			styleobj : styleobj,
			xadjustment: $(this).height() + 20,
			sourcewindow : window,
			position : {
				my : 'left top',
				at : 'left bottom',

				collision : 'fit flip'
				
			}
		});
	});
	$('#divOutline').on('click', '.nav', function(){
		OpenMenuItem($(this).parent().get(0));
	});

	


	if ( isInIframe ) 
	{
		
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

		//OLHeight = parseInt($(".OutlineItems:first")[0].scrollHeight + 10)
		//$(ifrm).css("height",OLHeight );
		
	}


	
 

});

function buildHorizontalOutline()
{
	var html = '<ul class="OutlineItems" >',
		menu_items = outlineJson.Items;
	
    for (var item = 0; item < menu_items.length; item++)
	{
		html += '<li class="';
		if (menu_items[item].initselected) {
			html += 'd-ui-state-active';
		}
		/*if (menu_items[item].type == 'header' )
			html += ' d-ui-widget-header ';
		else
			html += ' d-ui-state-default ';*/

		if ( outlineJson.Style != 'Themed'){
			html += ' d-ui-widget-header ';
		}
		html += '"';

		if (menu_items[item].Items && menu_items[item].Items.length) {
			html += 'itemindex="' + item + '" ';
		}
		html += 'emenuitemtype="' + (menu_items[item].type == 'header' ? 'H' : 'M') + '" ';
	
		html += 'etarget="' + safe_quotes_js(menu_items[item].etarget) + '" ';
		html += 'eelement="' + menu_items[item].eelement + '" ';
		html += 'isSpacer="' + menu_items[item].isSpacer + '" ';
		html += 'eNoTab="' + menu_items[item].enotab +'" ';
		html += 'title="' + (menu_items[item].helptext ? menu_items[item].helptext  : '' )  + '" ';
		html += 'etype="' + menu_items[item].etype + '" ';
		html += 'eviewtype="' + menu_items[item].eviewtype + '">';

		
		if ( (outlineJson.SubmenuPerspective == 'IS' || outlineJson.SubmenuPerspective == 'IC' ) && ! menu_items[item].isSpacer  )
		{

			html += "<div class='iconwrapper'>";
			html += '<span class="';
					
			html += ' expandable ' + menu_items[item].expandicon;
			html += '" style="';
			
			html += menu_items[item].iconfontsize ? 'font-size: ' +  menu_items[item].iconfontsize + '; ' : '';
			html += menu_items[item].iconcolor ? 'color:' + menu_items[item].iconcolor + '; ' : '';
			//html += menu_items[item].iconplacement ? 'float:' + menu_items[item].iconplacement + '; ' : '';
			html += '"></span>'

		}else{

			if (menu_items[item].Items && menu_items[item].Items.length) 
			{
				var cls = " expandable ";
					
				if ( ! outlineJson.SubmenuDetector )
				{
					cls += " expandablesys ";
				}

				html += '<span class="';
				
				html += cls + menu_items[item].expandicon;
				html += '" style="';
				
				if ( menu_items[item].customicon === "1" && ! menu_items[item].isSpacer )
				{
					html += menu_items[item].iconfontsize ? 'font-size: ' +  menu_items[item].iconfontsize + '; ' : '';
					html += menu_items[item].iconcolor ? 'color:' + menu_items[item].iconcolor + '; ' : '';
					html += menu_items[item].iconplacement ? 'float:' + menu_items[item].iconplacement + '; ' : '';
				}
				html += '"></span>'

				
			}else{
				if (menu_items[item].icontitle  && ! menu_items[item].isSpacer ){
					html += '<span class="';
					html += menu_items[item].icontitle ? 'nicon  fa-1x ' + menu_items[item].icontitle + '" ' : '" ';
					html += 'style="';
					html += menu_items[item].iconfontsize ? 'font-size: ' +  menu_items[item].iconfontsize + '; ' : '';
					html += menu_items[item].iconcolor ? 'color:' + menu_items[item].iconcolor + '; ' : '';
					html += '" ></span>';
				}
			}
		}

		if ( menu_items[item].context && menu_items[item].context != "" ){
			html += '<a class="nav" href="#" ';
			html += 'style="';
			html += menu_items[item].isbold ? 'font-weight: bold; ' : '';
			html += menu_items[item].isitalic ? 'font-style: italic; ' : '';
			html += menu_items[item].fontcolor ? 'color: ' + menu_items[item].fontcolor + ';' : '';
			html += menu_items[item].size ? 'font-size:' + menu_items[item].size : '';
			html += '"';
			
			html += '>' + menu_items[item].context + '</a>';
		}
		if ( outlineJson.SubmenuPerspective == 'VS' || outlineJson.SubmenuPerspective == 'VC' ){
			html += "</div>"
		}
		html += '</li>';
	}
	html += '</ul>';
	return html;
}