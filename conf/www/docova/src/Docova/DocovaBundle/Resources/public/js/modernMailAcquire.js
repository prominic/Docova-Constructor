var page = total = 0;
$(document).ready(function() {
	$('#submitpass').button().click(function() {
		if ($.trim($('#pass').val()))
		{
			$.ajax({
				type : 'POST',
				url : 'encryptPass',
				data : 'pass=' + encodeURIComponent($('#pass').val()),
				async : false
			})
			.done(function( response ) {
				var pssNode = $(response).find('Result[ID="Ret1"]').text();
				if (pssNode == 'OK') {
					$('#hiddenForm').submit();
				}
				else {
					alert(prmptMessages.msgMEA001);
					$('#pass').val('');
				}
			})
			.fail(function(jqXHR, status, errmsg) {
				alert(prmptMessages.msgMEA002 + errmsg);
			});
		}
	});

	$('#mail_folders').on('click', '.tr-open', function() {
		$(this).next().next().hide();
		$(this).removeClass('tr-open');
		$(this).addClass('tr-close');
	});

	$('#mail_folders').on('click', '.tr-close', function() {
		$(this).next().next().show();
		$(this).removeClass('tr-close');
		$(this).addClass('tr-open');
	});

	$('#mail_folders a:not(#mailbox)').click(function(e) {
		e.preventDefault();
		$('.selected').removeClass('selected');
		$(this).addClass('selected');
		var viewName = $(this).attr('href').substring(1);
		page = 1;
		loadMailList(viewName);
	});

	$('#navigators li:first').button({
		icons: {
			primary: "ui-icon-seek-first"
		},
		text: false
	})
	.click(function() {
		if (page != 0) {
			var viewName = $('#mail_folders a.selected').attr('href').substring(1);
			page = 1;
			loadMailList(viewName);
		}
	})
	.next().button({
		icons: {
			primary: "ui-icon-seek-prev"
		},
		text: false
	})
	.click(function() {
		if (page > 1) {
			var viewName = $('#mail_folders a.selected').attr('href').substring(1);
			var start = (page - 1) * 20 + 1;
			page--;
			loadMailList(viewName, start, 20);
		}
	})
	.next().button({
		icons: {
			primary: "ui-icon-seek-next"
		},
		text: false
	})
	.click(function() {
		if (page != 0 && page < Math.ceil(total/20)) {
			var viewName = $('#mail_folders a.selected').attr('href').substring(1);
			var start =page * 20 + 1;
			page++;
			loadMailList(viewName, start, 20);
		}
	})
	.next().button({
		icons: {
			primary: "ui-icon-seek-end"
		},
		text: false
	})
	.click(function() {
		if (page != 0 && page < Math.ceil(total/20)) {
			var viewName = $('#mail_folders a.selected').attr('href').substring(1);
			var start = Math.floor(total/20) * 20 + 1;
			page = Math.ceil(total/20);
			loadMailList(viewName, start, 20);
		}
	});
	
	$('#closeme').button().click(function(){window.returnValue = 0;
		window.close();});
	$('#search').button();
	$('#clear').button();
});

/*-------------------------------------------------------------------------------------------------------------------------------------------- 
 * Function: GetImportCount
 * returns the total number of records imported
 * Returns: integer - total number of records imported
 *------------------------------------------------------------------------------------------------------------------------------------------- */
function GetImportCount(){
	return importedCount;
}//--end GetImportCount

function CompleteDialog()
{
	var importIds = '';
	$('input[name=msgno]:checked').each(function() {
		importIds += $(this).val() + ',';
	});

	importIds = (importIds != '') ? importIds.slice(0, -1) : '';
	if (docInfo.Query_String == 'associatemail=true') {
		var request = prepareRequest('SAVEMESSAGES', $('#mail_folders a:not(#mailbox)').attr('href').substring(1), null, null, importIds);
	}
	else {
		var request = prepareRequest('IMPORTMESSAGES', $('#mail_folders a:not(#mailbox)').attr('href').substring(1), null, null, importIds);
	}
	$.ajax({
		type: 'POST',
		url : '/' + docInfo.NsfName + '/MailServices',
		data: request,
		async: false
	})
	.done(function(data) {
		if (data.Result == 'OK') {
			alert(prmptMessages.msgMEA005);
			importedCount += data.Count;
			return true;
		}
		else {
			alert(prmptMessages.msgMEA004 +  data.errmsg);
			return false;
		}
	})
	.fail(function(jqXHR, status, errmsg) {
		alert(prmptMessages.msgMEA004 + errmsg);
		return false;
	});
	return true;
}

function loadMailList(viewName, start, count)
{
	var request = prepareRequest('READMAILVIEW', viewName, start, count);
	$.ajax({
		type : 'POST',
		url : '/' + docInfo.NsfName + '/MailServices',
		async : false,
		data : request
	})
	.done(function(response) {
		if (response.count > 0) {
			$('#mail_list tr').has('td').remove();
			total = response.count;
			$.each(response.messages, function(index, node) {
				var row = '<tr><td class="s cn"><input type="checkbox" name="msgno" value="'+ node.msgno +'"></td>';
				row += '<td>'+ node.From +'</td>';
				row += '<td class="sm">'+ node.Date +'</td>';
				row += '<td class="sm">'+ node.Size +'</td>';
				row += '<td>&nbsp;</td><td>'+ node.Subject +'</td></tr>';

				$('#mail_list tr:last').after(row);
			});
			var stNo = toNo = 0;
			stNo = page > 1 ? ((page - 1) * 20 + 1) : 1;
			toNo = (page < Math.ceil(total/20)) ? stNo + 19 : total;
			$('#listInfo').text('Document ' + stNo + ' to ' + toNo + ' of ' + total);
		}
		else {
			alert(prmptMessages.msgMEA003);
		}
	})
	.fail(function(jqXHR, status, errmsg) {
		alert(prmptMessages.msgMEA004 + errmsg);
	});
}

function prepareRequest(action, viewName, start, count, importIds)
{
	var request = "<Request>";
	request += "<Action>" + action + "</Action>";
	request += "<UserName><![CDATA[" + docInfo.UserNameAB + "]]></UserName>";
	if (action == 'SAVEMESSAGES') {
		request += "<DocumentUnid>" + srcDocInfo.DocID + "</DocumentUnid>";
	}
/*
	if ( document.all.inpQuery.value != "" ){
		request += "<ftsearch><![CDATA[" + document.all.inpQuery.value + "]]></ftsearch>";
	}
*/
	request += "<viewname>" + viewName + "</viewname>";
	if (start && count) 
	{
		request += (start)? "<start>" + start + "</start>" : "<start>1</start>";
		request += (count)? "<count>" + count + "</count>" : "<count>" + docInfo.MailViewPageSize + "</count>";
	}
	else if (importIds) 
	{
		request += '<Unids>' + importIds + '</Unids>';
		request += "<FolderUnid>" + srcDocInfo.FolderID + "</FolderUnid>";
	}
	request += "</Request>";
	
	return request;
}

function generateTree(item, fname, count, prname, index)
{
	if ($.isPlainObject(item)) 
	{
		var output = '<li class="' + (item.length > 1 || count > 1 ? 'tr-default' : 'none') + '">',
			cnt = 0;
		output += '<ins class="tr-open"></ins>';
		output += '<a class="folder" href="#'+ prname + fname +'">' + fname + '</a><ul>';
		$.each(item, function(index, node) {
			cnt++;
			output += generateTree(node, index, item.length, (prname + fname + '\\'), cnt);
		});
		output += '</ul></li>';
	}
	else {
		var output = '<li class="none">';
		output += '<ins class="'+ (index == count ? 'none' : 'tr-default') +'"></ins>';
		output += '<a class="folder" href="#' + prname + fname + '">' + fname + '</a></li>';
	}

	return output;
}
