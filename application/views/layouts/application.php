<!doctype html>
<html>
<head>
    <title>CS50 Help</title>
	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.6.1/jquery.min.js"></script>
</head>

<body>
    	
	<?php echo $this->template->message(); ?>
    
    <?php echo $this->template->yield(); ?>
    
	<p>Page rendered in {elapsed_time} seconds using {memory_usage}.</p>

</body>
</html>
