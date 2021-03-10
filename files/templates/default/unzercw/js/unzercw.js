(function ($) {
    var cw$ = jQuery || document.jQuery || window.jQuery || $;
    var lastErrorTime = null, errorDelay = 1000;
    
    var UnzerCwGetSubmitButton = function() {
        // Ajax Authorization
    	if (typeof unzercw_ajax_authorization_form_fields != 'undefined') {
            var checkoutForm = cw$('#unzercw-confirmation-ajax-form-container').parents("form");
        }
        else if (typeof unzercw_hidden_authorization_form_fields != 'undefined') {
            var checkoutForm = cw$('#unzercw-confirmation-hidden-form-container').parents("form");
        }
        else {
        	console.error("Form could not be found.");
        	alert("Form could not be found.");
        }
        return cw$(checkoutForm).find(".action_submit,.checkout-confirmation-submit");
	}

    var UnzerCwHandleValidationError = function(errors, valid) {
        if(Date.now() - lastErrorTime < errorDelay) {
            UnzerCwGetSubmitButton().removeProp("disabled"); // may be called twice, ensure no duplicate popups
            return;
        }
        lastErrorTime = Date.now();
    	console.dir(errors);
    	alert(errors[Object.keys(errors)[0]]);
    	UnzerCwGetSubmitButton().removeProp("disabled");
    }

	var getFieldsDataArray = function () {
		var fields = {};

		var data = cw$('#unzercw-confirmation-ajax-authorization-form').serializeArray();
		cw$(data).each(function(index, value) {
			fields[value.name] = value.value;
		});

		return fields;
	};

	var UnzerCwHandleHiddenSubmit = function () {
		UnzerCwGetSubmitButton().prop("disabled", "disabled");

		if (typeof cwValidateFields != 'undefined') {
			cwValidateFields(UnzerCwHandleHiddenSuccess, UnzerCwHandleValidationError);
			return false;
		}

		UnzerCwHandleHiddenSuccess(new Array());

		return false;
	};

	var UnzerCwHandleHiddenSuccess = function(valid) {
		cw$.ajax({
			type: "POST",
			url: 'checkout_process.php?ajax=true',
			dataType: "json"
		}).done(function(data) {
			cw$('#unzercw-confirmation-hidden-authorization-form').attr('action', data.formAction);
			cw$('#unzercw-confirmation-hidden-authorization-form').append(data.hiddenFormFields);
			cw$('#unzercw-confirmation-hidden-authorization-form').submit();
		});
	}

	var UnzerCwHandleAjaxSubmit = function() {
		UnzerCwGetSubmitButton().prop("disabled", "disabled");

		if (typeof cwValidateFields != 'undefined') {
			cwValidateFields(UnzerCwHandleAjaxSuccess, UnzerCwHandleValidationError);
			return false;
		}
		UnzerCwHandleAjaxSuccess(new Array());
		return false;
	}

	var UnzerCwHandleAjaxSuccess = function(valid) {

		if (typeof unzercw_ajax_submit_callback != 'undefined') {
			unzercw_ajax_submit_callback(getFieldsDataArray());
			return false;
		}
		else {
			cw$.ajax({
				type: "POST",
				url: 'checkout_process.php?ajax=true',
				dataType: "json"
			}).done(function(data) {
				cw$.getScript(data.ajaxScriptUrl, function() {
					var callbackFunction = data.submitCallbackFunction;
					var func = eval('[' + callbackFunction + ']')[0];
					func(getFieldsDataArray());
				});
			});

			return false;
		}
	};


	cw$(document).ready(function() {

		// Hidden Authorization
		if (typeof unzercw_hidden_authorization_form_fields != 'undefined') {
			var checkoutForm = cw$('#unzercw-confirmation-hidden-form-container').parents("form");

			if (cw$('#unzercw-confirmation-hidden-authorization-form').length == 0) {
				var formContent = decodeURIComponent((unzercw_hidden_authorization_form_fields+'').replace(/\+/g, '%20'));
				var html = '<form method="POST" id="unzercw-confirmation-hidden-authorization-form" accept-charset="UTF-8">' + formContent + '</form>';
				checkoutForm.before(html);
			}

			checkoutForm.submit(UnzerCwHandleHiddenSubmit);
			UnzerCwGetSubmitButton().bind('click', UnzerCwHandleHiddenSubmit);
		}

		// Ajax Authorization
		if (typeof unzercw_ajax_authorization_form_fields != 'undefined') {
			var checkoutForm = cw$('#unzercw-confirmation-ajax-form-container').parents("form");

			if (cw$('#unzercw-confirmation-ajax-authorization-form').length == 0) {
				var formContent = decodeURIComponent((unzercw_ajax_authorization_form_fields+'').replace(/\+/g, '%20'));
				var html = '<form method="POST" id="unzercw-confirmation-ajax-authorization-form" accept-charset="UTF-8">' + formContent + '</form>';
				cw$('#unzercw-confirmation-ajax-form-container').html(html);
				cw$('#unzercw-confirmation-ajax-form-container').removeAttr('style');
				cw$('#unzercw-confirmation-ajax-authorization-form').attr('style', '{margin-right:15px}')
			}

			checkoutForm.submit(UnzerCwHandleAjaxSubmit);
			UnzerCwGetSubmitButton().bind('click', UnzerCwHandleAjaxSubmit);
		}

	});

}(unzercw_jquery));