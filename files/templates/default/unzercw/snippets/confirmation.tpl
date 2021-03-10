<h2 class="underline overline">{$CONFIRMATION_TITLE}</h2>

{if !empty($ERROR_MESSAGE)}
	<p class="unzercw-error" style="color: #a94442; background-color: #f2dede; border-color: #ebccd1; padding: 15px; margin-bottom: 20px;nborder: 1px solid transparent; border-radius: 4px;">{$ERROR_MESSAGE}</p>
{/if}

<table width="100%" border="0" cellspacing="0" cellpadding="0" class="unzercw-confirmation-address-block">
	<tr>
		<td><strong>{$TEXT_SHIPPING_ADDRESS}</strong><br />{$SHIPPING_ADDRESS}</td>
		<td><strong>{$TEXT_BILLING_ADDRESS}</strong><br />{$BILLING_ADDRESS}</td>
	</tr>
</table>

<br />
<div id="products_overview">
	<div class="product-listing">
		<table width="100%" border="0" cellspacing="0" cellpadding="0">
			<tr class="headerrow">
				<th class="main_header" style="width:70px" align="left" valign="top"><b>{$HEADER_QTY}</b></td>
				<th class="main_header" style="" align="left" valign="top"><b>{$HEADER_ARTICLE}</b></td>
				<th class="main_header" style="width:100px; padding-bottom:10px" align="right" valign="top"><b>{$HEADER_SINGLE}</b></td>
				<th class="main_header" style="width:100px;" align="right" valign="top"><b>{$HEADER_TOTAL}</b></td>
			</tr>
			{foreach name=aussen item=data from=$PRODUCTS}
				<tr>
					<td class="main_row product_quantity" align="left" valign="top">{$data.qty}</td>
					<td class="main_row product_info" align="left" valign="top" style="padding-bottom:10px">
						{if $CHECKOUT_SHOW_PRODUCTS_IMAGES == 'true'}
							<div style="width:90px; float:left;">{$data.image}</div>
						{/if}
						<div style="width:420px; float:left;">
							{$data.link}
							{if $data.name}<div class="small">{$data.name}</div>{/if}
							{if $data.shipping_time}<br /><span class="nobr small">{$smarty.const.SHIPPING_TIME}{$data.shipping_time}</span>{/if}
							{foreach key=a_data item=attrib_data from=$data.attributes}
								{if $attrib_data.value != ''}<br /><span class="nobr small">&nbsp;<i> - {$attrib_data.option} : {$attrib_data.value}</i></span>{/if}           
							{/foreach} 
						</div>        
					</td>      
					<td class="main_row product_price" align="right" valign="top">{$data.price_formated}</td>
					<td class="main_row product_total_price" align="right" valign="top">{$data.final_price_formated}{if $ORDER_TAX_GROUPS > 1}<br />{$data.tax} %{/if}</td>
				</tr>
			{/foreach}
		</table>
	</div>
	
	{if $TOTAL_BLOCK}
		<div class="total">
			<table align="right">
				{$TOTAL_BLOCK}
			</table>
		</div>
		<div style="clear:both;"></div>
	{/if}
</div>

{if isset($GTC)}
	<div class="unzercw-gtc-box">
		<h2 class="underline overline">{$GTC_TITLE}</h2>
		<div class="unzercw-gtc-area">
			{$GTC}
		</div>
		<div class="unzercw-gtc-checkbox">
			<label>
				<input type="checkbox" value="conditions" name="conditions" />
				{$GTC_TEXT}
			</label>
		</div>
	</div>
{/if}

<div class="unzercw-shipping-method-update-button" style="margin-top: 10px; text-align: right;">
	{$CONFIRMATION_BUTTON}
</div>