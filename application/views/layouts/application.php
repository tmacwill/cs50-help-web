<!doctype html>
<html>
<head>
    <title>CS50 Queue</title>
	<script> var site_url = "<?php echo site_url(); if (isset($course)) echo $course . '/'; ?>"; </script>
	<?php $this->carabiner->display(); ?>
</head>

<body>
    	
	<?php echo $this->template->message(); ?>
    
    <?php echo $this->template->yield(); ?>
    
	<!--p>Page rendered in {elapsed_time} seconds using {memory_usage}.</p-->

</body>
</html>
