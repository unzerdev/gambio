<h2 class="underline overline">{$SHIPPING_METHOD_SELECTION}</h2>

{if empty($SHIPPING_SELECTION_BOCK)}
	<p class="no-shipping-method-required">{$NO_SHIPPING_METHOD_REQUIRED}</p>
{else}
	<div id="unzercw-shipping-method-selection-block" class="order_shipping">
		{if !empty($ERROR_MESSAGE)}
			<p class="unzercw-error" style="color: #a94442; background-color: #f2dede; border-color: #ebccd1; padding: 15px; margin-bottom: 20px;nborder: 1px solid transparent; border-radius: 4px;">{$ERROR_MESSAGE}</p>
		{/if}
		
		{$SHIPPING_SELECTION_BOCK}
	</div>
	
	
	<div class="unzercw-shipping-method-update-button" style="margin-top: 10px; text-align: right;">
		{$UPDATE_BUTTON}
		<input type="submit" name="hidden-submit" style="display:none;" value="hidden-submit" id="unzercw-shipping-method-update-button-hidden" />
	</div>
	
	{literal}
	<script type="text/javascript">
	<!-- 
	function UnzerCwUpdateShippingMethod (){ 
		document.getElementById('unzercw-shipping-method-update-button-hidden').click(); 
	}

	var block = document.getElementById('unzercw-shipping-method-selection-block');
		var inputList = block.getElementsByTagName('input');
		for(var i = 0, size = inputList.length; i < size ; i++){
			var inputElement = inputList[i];
			var type = inputElement.getAttribute('type');
			if (type == 'radio') {
				inputElement.setAttribute("onchange", 'UnzerCwUpdateShippingMethod()');
			}
		}

		
	//-->
	</script>
	{/literal}
	
	
{/if}