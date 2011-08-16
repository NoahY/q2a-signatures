(function($){
    $.fn.textLimiter = function(options) {
	return this.each(function() {
	    var $this = $(this);
	    options = $.extend(
		{
		    'maxLength': signature_max_length,
		    'align': 'right',
		    'valign': 'bottom',
		    'show': 'auto',
		    'elCount': 'auto'
		}, 
		options
	    );

	    $this.attr('maxLength', options['maxLength']);

	    $this.keyup(function () { 
		processTextAreaText($this); 
	    });
	    $this.keydown(function () { 
		processTextAreaText($this); 
	    });
	    $this.change(function () { 
		processTextAreaText($this); 
	    });

	    $this.attr('divName', options['elCount']);
	    processTextAreaText($this); 
	});
    };

    function processTextAreaText($obj) {
	var maxLength = $obj.attr('maxLength');
	var text = $obj.val();
	var $cc = $('#' + $obj.attr('divName'))
	var left = maxLength - text.length;
	var color = 'green';
	if(left<20) color = (left<5?'red':'orange');

	$cc.html(left);
	$cc.attr('class','sig-left-'+color);
	if (parseInt($cc.html()) < 0)  
	    $cc.html('0');

	if (maxLength != 0 && text.length > maxLength) 
	    $obj.val(text.substr(0, maxLength));
    };

})(jQuery);
