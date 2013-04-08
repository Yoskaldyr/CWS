/** @param {jQuery} $ jQuery Object */
!function($, window, document, _undefined)
{

	XenForo.WidgetOptions = function($element) { this.__construct($element); };

	XenForo.WidgetOptions.prototype =
	{
		__construct: function($input)
		{
			this.$input = $input;
			this.url = $('.WidgetOptions').data('optionsUrl');
			this.$widgetOptions = $('#WidgetOptions');

			$('.AutoComplete.WidgetOptions').bind(
			{
				//click: $.context(this, 'fetchTextDelayed'),
				keyup: $.context(this, 'fetchTextDelayed'),
				change: $.context(this, 'fetchTextDelayed')
			});

			$input.bind(
			{
				click: $.context(this, 'fetchText')
			});

			this.fetchText();
		},

		fetchTextDelayed: function()
		{
			if (this.delayTimer)
			{
				clearTimeout(this.delayTimer);
			}

			this.delayTimer = setTimeout($.context(this, 'fetchText'), 250);
		},

		fetchText: function()
		{
			if (!$('#WidgetClass').val())
			{
				return;
			}

			if (this.xhr)
			{
				this.xhr.abort();
			}

			this.xhr = XenForo.ajax(
				this.url,
				{ callback_class: $('#WidgetClass').val(), widget_id: $('#WidgetForm').data('widgetId')},
				$.context(this, 'ajaxSuccess'),
				{ error: false }
			);
		},

		ajaxSuccess: function(ajaxData)
		{
			if (ajaxData.templateHtml)
			{
				this.$widgetOptions.html(ajaxData.templateHtml);
			}
            else
            {
                this.$widgetOptions.html('');
            }
		}
	};

	XenForo.register('input.WidgetOptions', 'XenForo.WidgetOptions');

}
(jQuery, this, document);