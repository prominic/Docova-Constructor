function loadBookmarkPreview() {
	if (validateUrl()) {
		if (doc.bookmarkurl.value) {
			doc.fraBookmarkPreview.src = doc.bookmarkurl.value;
		}
	}
}

function openBookmark(url) {
	if (!doc.bookmarkurl && !url) {
		return false;
	}
	var pageUrl = "";

	if (url) {
		pageUrl = url;
	} else {
		pageUrl = doc.bookmarkurl.value;
	}

	var winoptions = "scrollbars=1, toolbar=1, status=1, location=1,resizable=1, menubar=1, width=700, height=500, top=50, left=50";
	window.open(pageUrl, "_new", winoptions);

}

function togglePreview() {
	if (validateUrl()) {
		if (doc.showpreview.checked) {
			doc.imgBookmarkPreviewRefresh.style.display = "";
			doc.divBookmarkPreview.style.display = "";
			loadBookmarkPreview();
		} else {
			doc.imgBookmarkPreviewRefresh.style.display = "none";
			doc.divBookmarkPreview.style.display = "none";
			doc.fraBookmarkPreview.src = "";
		}
	}
}

function validateUrl() {
	if (doc.bookmarkurl.value.indexOf("http") == -1) {
		alert("You must put 'http://' or https:// in front of the URL before you can preview or save.");
		return false;
	} else {
		return true;
	}
}