<?php
	use XF\Install\App;

class_exists(App::class, false) || die('Invalid');

	$templater->setTitle(\XF::phrase('oops_we_ran_into_some_problems'));
?>

<div class="blockMessage">
	<?php if ($error)
	{
		echo $error;
	}
	else {?>
	<ul>
		<?php foreach ($errors AS $error) {?>
			<li><?php echo $error; ?></li>
		<?php }
	} ?>
	</ul>
</div>
