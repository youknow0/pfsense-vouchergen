<!DOCTYPE html>
<html>

<head>
	<title>pfSense Voucher Generator</title>
	<meta http-equiv="content-type" content="text/html;charset=utf-8" />
	
	<style type="text/css">
		body {
			font-family: sans-serif;
		}
		form p {
			clear: both;
		}
		label {
			float: left;
			width: 120px;
		}
		input, select {
			float: left;
			width: 240px;
		}
		input[type=submit] {
			float: none;
			width: auto;
		}
	</style>
</head>

<body>
	<h1>Create Vouchers</h1>
	<?php
		if (!empty($message)) {
	?>
		<p><?php echo htmlspecialchars($message); ?></p>
	<?php
		}
	?>
	<form action="" method="post">
		<p>
			<label for="profile">Profile:</label>
			<select name="profile" id="profile">
				<option>(please select)</option>
				<?php
					foreach ($profiles as $p) {
				?>
				<option value="<?php echo htmlspecialchars($p->id); ?>">
					<?php echo htmlspecialchars($p->name); ?>
				</option>
				<?php
					}
				?>
			</select>
		</p>
		<p>
			<input type="hidden" value="<?php echo htmlspecialchars($token); ?>" name="token">
			<input type="submit" value="Generate" name="generate">
		</p>
	</form>
</body>

</html>
