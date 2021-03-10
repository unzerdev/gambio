<h2><?php echo unzercw_translate("Debit the customer"); ?></h2>

<form action="<?php echo $formActionUrl; ?>" class="form-horizontal" method="POST">

	<?php if (isset($_SESSION['CSRFName'])): ?>
		<input type="hidden" name="<?php echo $_SESSION['CSRFName']; ?>" value="<?php echo $_SESSION['CSRFToken']; ?>" />
	<?php endif; ?>

	<?php echo $visibleFields; ?>

	<?php echo $hiddenFields; ?>
	
	<input type="submit" class="btn btn-success" value="<?php echo unzercw_translate('Debit the customer'); ?>" />
	
</form>