
<div class="navbar navbar-inverse navbar-fixed-top bs-docs-nav">
	<div class="container">
		<button class="navbar-toggle" type="button" data-toggle="collapse" data-target=".bs-navbar-collapse">
			<span class="icon-bar"></span> <span class="icon-bar"></span> <span class="icon-bar"></span>
		</button>
		<div class="">
			<ul class="nav navbar-nav">
			<?php 
			$menus = array(
					'default' => array(
						'url' => '#',
						'title' => unzercw_translate("Close"),
						'onclick' => 'javascript:window.close()'
					),
					'default' => array(
						'url' => UnzerCw_AbstractController::getControllerUrl('default', 'index'),
						'title' => unzercw_translate("Home"),
					),
					'settings' => array(
						'url' => UnzerCw_AbstractController::getControllerUrl('settings', 'edit', array('module_class' => 'unzercw')),
						'title' => unzercw_translate("Base Configuration"),
					),
					'transactionmanagement' => array(
						'url' => UnzerCw_AbstractController::getControllerUrl('transactionmanagement', 'index'),
						'title' =>  unzercw_translate("Transaction Management"),
					),
					'logmessages' => array(
						'url' => UnzerCw_AbstractController::getControllerUrl('logmessages', 'index'),
						'title' =>  unzercw_translate("Log Messages"),
					),
				);
				$adapter = UnzerCw_Util::getBackendFormAdapter();
				if ($adapter !== null) {
					$forms = $adapter->getForms();
					foreach ($forms as $form) {
						$menus['form_' . $form->getMachineName()] = array(
							'url' => UnzerCw_AbstractController::getControllerUrl('forms', 'index', array('form' => $form->getMachineName())),
							'title' =>  $form->getTitle(),
						);
					}
				}
				
			?>
			
			<?php foreach ($menus as $menu): ?>
				<li>
					<a href="<?php echo $menu['url'] ?>" 
					<?php if(isset($menu['onclick'])):?>
					onclick="<?php echo $menu['onclick']?>" 
					<?php endif;?>
					><?php echo $menu['title']; ?></a>
				</li>
			<?php endforeach; ?>
			</ul>
		</div>
	</div>
</div>
