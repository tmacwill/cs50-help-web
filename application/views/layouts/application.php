<!doctype html>
<html>
<head>
    <title>CS50 Help</title>
	<script> var site_url = "<?= site_url(); ?>"; </script>
	<?php $this->carabiner->display(); ?>
</head>

<body>
    	
	<?php echo $this->template->message(); ?>
    
    <?php echo $this->template->yield(); ?>
    
	<!--p>Page rendered in {elapsed_time} seconds using {memory_usage}.</p-->

</body>
</html>
