
<?php if (isset($messages)): ?>
	<?php foreach($messages as $type => $messagesOfType):?>
	
		<?php 
		$alertClass = 'alert-success';
		switch(strtolower($type)) {
			case 'error':
				$alertClass = 'alert-danger';
				break;
			case 'info':
				$alertClass = 'alert-info';
				break;
			case 'success':
				$alertClass = 'alert-success';
				break;
		}
		?>
	
		<?php foreach ($messagesOfType as $message):?>
			<div class="alert <?php echo $alertClass; ?>"><?php echo $message; ?></div>	
		<?php endforeach;?>
	
	<?php endforeach;?>
<?php endif; ?>