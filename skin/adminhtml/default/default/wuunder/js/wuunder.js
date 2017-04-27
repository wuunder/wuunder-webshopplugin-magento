var $j = jQuery.noConflict();

$j(document).ready(function() {

	$j(".wuunder").fancybox({
		type: 'ajax',
		width: '600',
		openEffect: 'elastic',
		afterClose: function () {
			parent.location.reload(true);
		}
	})
});