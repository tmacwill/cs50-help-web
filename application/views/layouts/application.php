<!doctype html>
<html>
<head>
    <title>CS50 Help</title>
	<?php $this->carabiner->display(); ?>
</head>

<body>
    	
	<?php echo $this->template->message(); ?>
    
    <?php echo $this->template->yield(); ?>
    
	<p>Page rendered in {elapsed_time} seconds using {memory_usage}.</p>

</body>
</html>
