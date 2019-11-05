// Call this function in the Document Type onLoad event
function TransformLinks() {
   var find = new RegExp("<A", "g");
   var source = document.all.divRTContent.innerHTML
   var target = source.replace(find, "<A target='_blank' ");
   document.all.divRTContent.innerHTML = target
}
