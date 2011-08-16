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
		$('#' + $this.attr('divName')).html($this.attr('maxLength') - $this.text().length);
	    }
	});
    };

    function processTextAreaText($obj) {
      var maxLength = $obj.attr('maxLength');
      var text = $obj.text();
      var $cc = $('#' + $obj.attr('divName'))

	  $cc.html(maxLength - text.length);
	
      if (parseInt($cc.html()) < 0)  
        $cc.html('0');
	
      if (maxLength != 0 && text.length > maxLength) 
        $obj.text(text.substr(0, maxLength));
    };

	function showCount($obj, align, valign, show) {
	  if (show == 'always') {
		var divName = 'textAreaMaxLengthPlugin_divCharCount_' + $obj.attr('id');
	  } else {
        var divName = 'textAreaMaxLengthPlugin_divCharCount';
	  }
      
	  if (!$('#' + divName).length) {
        $('body').append('<div id="' + divName + '"></div>');
      }

	  $obj.attr('divName', divName);

      var $cc = $('#' + divName);

	  $cc.html($obj.attr('maxLength') - $obj.text().length);

	  var x = $obj.position().left;
		
	  if (valign == 'bottom') {
		var y = $obj.position().top + $obj.height() - $cc.height();
	  } else if (valign == 'top') {
		var y = $obj.position().top + 2;
	  }          

	  $cc.css('position', 'absolute');
	  $cc.css('text-align', align);
	  $cc.css('padding', '0px 15px 0px 5px');
	  $cc.css('width', $obj.width());	
	  $cc.css('opacity', 0.5);
	  $cc.css('left', x);
	  $cc.css('top', y);

	  if (show != 'never') {
		$cc.fadeTo(200, .5);	      
	  }
	}
  
  })(jQuery);
