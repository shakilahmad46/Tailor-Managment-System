// function printDiv(raseedPrint){
// 	var printContents = document.getElementById(raseedPrint).innerHTML;
// 	var originalContents = document.body.innerHTML;

// 	document.body.innerHTML = printContents;

// 	window.print();

// 	document.body.innerHTML = originalContents;

// }
// document.getElementById(".measerPopup a").onclick = function() {printDiv()};

// jQuery(".printBtn").click(function () {
//   jQuery("#raseedPrint").print();

// });

jQuery('.printBtn').on('click', function () {
  jQuery('#raseedPrint').print();
});
