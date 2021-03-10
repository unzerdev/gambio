
<h1><?php echo unzercw_translate('Settings for !title', array('!title' => $module->getTitle())); ?></h1>

<form class="form-horizontal" method="POST" action="<?php echo $this->getActionUrl('save', array('module_class' => $_GET['module_class'])); ?>" enctype="multipart/form-data">

	<?php if (isset($_SESSION['CSRFName'])): ?>
		<input type="hidden" name="<?php echo $_SESSION['CSRFName']; ?>" value="<?php echo $_SESSION['CSRFToken']; ?>" />
	<?php endif; ?>

	<?php echo $formFields; ?>

	<div class="form-group">
		<div class="col-lg-offset-3 col-lg-9">
			<input type="submit" class="btn btn-success" value="<?php echo unzercw_translate("Save"); ?>" />
		</div>
	</div>

</form>
