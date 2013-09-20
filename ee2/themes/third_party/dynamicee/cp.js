
$(document).ready(function()
{
	var chanInputId = "dynamicee-channel-id",
		catInputId = "dynamicee-category-id",
		statusInputId = "dynamicee-status",
		dateStartInputId = "dynamicee-date-start",
		datePrefInputId = "dynamicee-date-pref",
		limitInputId = "dynamicee-limit",
		$input = $("input[name='field_id_"+DYNAMICEE.FIELD_ID+"']"),
		$parent = $("#hold_field_"+DYNAMICEE.FIELD_ID);

	// ----------------------------

	/**
	 * Initializes all we need for our dynamic <select>'s
	 * @return {void}
	 */
	var buildMenus = function(callback)
	{
		/**
		 * @return {string}
		 */
		var channelMenu = function()
		{
			var html = '<div class="instruction_text dynamicee-menu-wrap">';
			
			html += '<p>';
			html += '<strong>Channel</strong><br>';
			html += '<select name="'+chanInputId+'" id="'+chanInputId+'" multiple>';
			html += '<option value="">--</option>';
			$.each(DYNAMICEE.CHANNELS, function(channelId, channelName)
			{
				var sel = ($.inArray(channelId, DYNAMICEE.CURRENT.channel_ids) !== -1) ? ' selected="selected"' : '';
				html += '<option value="'+channelId+'"'+sel+'>'+channelName+'</option>';
			});
			html += '</select>';
			html += '</p>';
			html += '</div>';

			return html;
		};

		// -----------

		/**
		 * @return {string}
		 */
		var categoryMenu = function()
		{
			var html = '<div class="instruction_text dynamicee-menu-wrap">';
			
			html += '<p>';
			html += '<strong>Categories (optional)</strong><br>';
			html += '<select name="'+catInputId+'" id="'+catInputId+'" multiple>';
			var sel = !DYNAMICEE.CURRENT.category_ids ? ' selected="selected"' : '';
			html += '<option value=""'+sel+'>Any/All</option>';
			html += '<option value="">---</option>';
			$.each(DYNAMICEE.CATEGORIES, function(catGroupId, catGroup)
			{
				html += '<optgroup label="'+catGroup.name+'">';
				html += catMenuItemsIterate(catGroup.categories, 0, 0);
				html += '</optgroup>';
			});
			html += '</select>';
			html += '</p>';
			html += '</div>';

			return html;
		};

		// -----------

		/**
		 * @return {string}
		 */
		 var dateMenu = function()
		 {
		 	var html = '<div class="instruction_text dynamicee-menu-wrap">';

			html += '<p>';
			html += '<strong>Time Range</strong><br>';
			html += '<select name="'+datePrefInputId+'" id="'+datePrefInputId+'">';
			$.each(
				{
					"6month": "6 Months Back",
					"1year": "1 Year Back",
					"2year": "2 Years Back",
					"none": "Do not limit by date"
				}, 
				function(key, val)
				{
					var sel = (key == DYNAMICEE.CURRENT.date_pref) ? ' selected="selected"' : '';
					html += '<option value="'+key+'"'+sel+'>'+val+'</option>';
				}
			);
			html += '</select>';
			// html += '<input type="text" name="'+dateStartInputId+'" value="'+DYNAMICEE.CURRENT.date_start+'" id="'+dateStartInputId+'" class="field hasDatepick">';
			html += '</p>';
			html += '</div>';

			return html;
		 };

		 // -----------

		/**
		 * @return {string}
		 */
		 var statusMenu = function()
		 {
		 	var html = '<div class="instruction_text dynamicee-menu-wrap">';
			
			html += '<p>';
			html += '<strong>Status</strong><br>';
			html += '<select name="'+statusInputId+'" id="'+statusInputId+'">';
			html += '<option value="open">Open</option>';
			html += '</select>';
			html += '</p>';
			html += '</div>';

			return html;
		 };

		 // -----------

		/**
		 * @return {string}
		 */
		 var limitMenu = function()
		 {
		 	var html = '<div class="instruction_text dynamicee-menu-wrap">';
			
			html += '<p>';
			html += '<strong>Limit</strong><br>';
			html += '<select name="'+limitInputId+'" id="'+limitInputId+'">';
			for(var i = 5; i <= 100; i+=5)
			{
				var sel = (i == DYNAMICEE.CURRENT.limit) ? ' selected="selected"' : '';
				html += '<option value="'+i+'"'+sel+'>'+i+'</option>';
			}
			html += '</select>';
			html += '</p>';
			html += '</div>';

			return html;
		};

		// -----------
		
		/* build and insert <select>'s */
		$parent
			.append(channelMenu())
			.append(categoryMenu())
			.append(statusMenu())
			.append(limitMenu())
			.append(dateMenu());

		/* initialize the start & end date pickers */
		// $("#"+dateStartInputId).datepicker();

		if(typeof callback == "function")
			callback.apply();

		return;
	};

	// ----------------------------

	/**
	 * @return {string}
	 */
	var catMenuItemsIterate = function(itemsObject, currentVal, indent)
	{
		var opts = '';

		var indentStr = '';
		for(var i = 0; i < indent; i++)
			indentStr += '&nbsp;&nbsp;- ';

		$.each(itemsObject, function(catId, catObject)
		{
			var sel = ($.inArray(catId, DYNAMICEE.CURRENT.category_ids) !== -1) ? ' selected="selected"' : '';
			
			opts += '<option value="'+catId+'"'+sel+'>'+indentStr+catObject.name+'</option>';
			
			/* iterate through chirren */
			if(catObject.children)
				opts += catMenuItemsIterate(catObject.children, 0, indent+1);
		});
		
		return opts;
	};

	// ----------------------------

	/**
	 * @return {void}
	 */
	var setupEvents = function()
	{
		$("#"+chanInputId+", #"+catInputId+", #"+limitInputId+", #"+datePrefInputId)
			.unbind()
			.on(
				"change", 
				function()
				{
					updateHidden();

					if($(this).attr("id") == catInputId)
						categoryAnyAll();
				}
			);

		return;
	};

	// ----------------------------

	/**
	 * @return {void}
	 */
	 var categoryAnyAll = function()
	 {
		var $cat = $("#"+catInputId);
		var val = $cat.val();

		if(!val || ((val.length == 1) && !val[0]))

			$cat
				.children("option:eq(0)")
					.attr("selected", "selected")
				.siblings()
					.attr("selected", "")
					.removeAttr("selected");
	}

	// ----------------------------

	/**
	 * @return {void}
	 */
	var updateHidden = function()
	{
		$input.val(
			JSON.stringify({
				"channel_ids"	: $("#"+chanInputId).val(),
				"category_ids"	: $("#"+catInputId).val(),
				"limit"			: $("#"+limitInputId).val(),
				"date_pref"		: $("#"+datePrefInputId).val()
			})
		);

		/* update our object too */
		DYNAMICEE.CURRENT = JSON.parse($input.val());

		return;
	};

	// ----------------------------

	if("jquery" in $input)
	{
		buildMenus(function()
		{
			setupEvents();
			updateHidden();
			categoryAnyAll();
		});
	}

});