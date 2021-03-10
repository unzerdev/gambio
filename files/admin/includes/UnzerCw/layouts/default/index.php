<!DOCTYPE html>
<html>
<head>
	<title>Unzer</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="content-type" content="text/html; charset=utf-8"> 
	<link href="//netdna.bootstrapcdn.com/bootstrap/3.0.3/css/bootstrap.min.css" rel="stylesheet" />
	<link href="//netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.css" rel="stylesheet" />
	<link href="includes/UnzerCw/assets/css/bootstrap-glyphicons.css" rel="stylesheet" media="screen" />
	<link href="includes/UnzerCw/assets/css/unzercw.css" rel="stylesheet"  media="screen" />
</head>
<body>
	
	<?php require_once 'menu.php'; ?>
	
	<div class="container main-content">
		<?php require_once 'messages.php'; ?>
		<?php echo $mainContent ; ?>
	</div>
	
	<div class="footer">
		<?php require_once 'footer.php'; ?>
	</div>

	<script src="//code.jquery.com/jquery.js"></script>
	<script src="includes/UnzerCw/assets/js/bootstrap.js"></script>
	<script src="includes/UnzerCw/assets/js/unzercw.js"></script>
	<script src="includes/UnzerCw/assets/js/line-item-grid.js"></script>
	</body>
</html>
