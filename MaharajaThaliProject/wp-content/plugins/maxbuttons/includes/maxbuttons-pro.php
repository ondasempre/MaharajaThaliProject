<?php

?>

<div id="maxbuttons">
	<div class="wrap">
		<div class="icon32">
			<a href="http://maxbuttons.com" target="_blank"><img src="<?php echo MAXBUTTONS_PLUGIN_URL ?>/images/mb-32.png" alt="MaxButtons" /></a>
		</div>
		
		<h2 class="title"><?php _e('MaxButtons: Go Pro', 'maxbuttons') ?></h2>
		
		<div class="logo">
			<?php _e('Brought to you by', 'maxbuttons') ?>
			<a href="http://maxfoundry.com/?ref=mbfree" target="_blank"><img src="<?php echo MAXBUTTONS_PLUGIN_URL ?>/images/max-foundry.png" alt="Max Foundry" /></a>
			<?php printf(__('Upgrade to MaxButtons Pro today! %sClick Here%s', 'maxbuttons'), '<a href="http://www.maxbuttons.com/pricing/?utm_source=wordpress&utm_medium=mbrepo&utm_content=button-go-pro-upgrade&utm_campaign=plugin">', '</a>' ) ?>
		</div>
		
		<div class="clear"></div>
		
		<h2 class="tabs">
			<span class="spacer"></span>
			<a class="nav-tab" href="<?php echo admin_url() ?>admin.php?page=maxbuttons-controller&action=list"><?php _e('Buttons', 'maxbuttons') ?></a>
			<a class="nav-tab nav-tab-active" href=""><?php _e('Go Pro', 'maxbuttons') ?></a>
			<?php if(current_user_can('manage_options')) { ?>
			<a class="nav-tab" href="<?php echo admin_url() ?>admin.php?page=maxbuttons-settings"><?php _e('Settings', 'maxbuttons') ?></a>
			<a class="nav-tab" href="<?php echo admin_url() ?>admin.php?page=maxbuttons-support"><?php _e('Support', 'maxbuttons') ?></a>
			<?php } ?>
		</h2>
		
		<div class="pro-offer">
			<h2><?php _e('Upgrade to MaxButtons Pro - It\'s Only $19!', 'maxbuttons') ?></h2>
			<p><?php _e('If you\'ve created at least one button with MaxButtons, then you know what it can do: Unlimited colors. Rounded corners. Gradients. Text shadowing. Box shadowing.', 'maxbuttons') ?></p>
			<p><?php printf(__('Now you can take your buttons to the next level with %sMaxButtons Pro%s. The Pro version gives you all the CSS3 goodness and so much more:', 'maxbuttons'), '<a href="http://maxbuttons.com/pricing/?utm_source=wordpressorg&utm_medium=plugin&utm_content=maxbuttons-pro&utm_campaign=mbfree" target="_blank">', '</a>') ?></p>
			<ul>
				<li><strong><?php _e('Font Awesome Integration', 'maxbuttons') ?></strong> - <?php _e('Use icons from the most popular icon font set on the web!', 'maxbuttons') ?></li>
				<li><strong><?php _e('Icon Support', 'maxbuttons') ?></strong> - <?php _e('Like buttons, everyone loves icons. Use Font Awesome or upload yor own.', 'maxbuttons') ?></li>
				<li><strong><?php _e('Multi-Line Text', 'maxbuttons') ?></strong> - <?php _e('Use a second a line of text to communicate extra information to your users and customers.', 'maxbuttons') ?></li>
				<li><strong><?php _e('Google Web Fonts', 'maxbuttons') ?></strong> - <?php _e('Great typography can take your buttons up several notches.', 'maxbuttons') ?></li>
				<li><strong><?php _e('Button Packs', 'maxbuttons') ?></strong> - <?php _e('Import sets of pre-defined buttons, which you can then use as-is or customize as you need.', 'maxbuttons') ?></li>
				<li><strong><?php _e('Shopp Integration', 'maxbuttons') ?></strong> - <?php _e('Use buttons created with MaxButtons Pro as the shopping cart buttons of the Shopp e-commerce plugin.', 'maxbuttons') ?></li>
			</ul>
			<p><?php printf(__('And did we mention you can %sget MaxButtons Pro for only $19%s', 'maxbuttons'), '<a href="http://maxbuttons.com/pricing/?utm_source=wordpressorg&utm_medium=plugin&utm_content=only-19&utm_campaign=mbfree" target="_blank">', '</a>') ?></a>?</p>
			
			<h2><?php _e('CSS3 Button Examples', 'maxbuttons') ?></h2>
			<p><?php _e('Just take a look at the types of buttons you can make with MaxButtons Pro:', 'maxbuttons') ?></p>
			<p><img src="<?php echo MAXBUTTONS_PLUGIN_URL ?>/images/css3-button-examples.png" alt="CSS3 Button Examples" /></p>

			<h2><?php _e('Button Packs: Pre-Made Sets of CSS3 and Icon Goodness', 'maxbuttons') ?></h2>
			<p><?php printf(__('Button packs are sets of buttons with icons and settings already predefined for you, saving you loads of time. We have an %sever-growing collection of button packs%s that you can buy and import into your website (only $5 each). You can then use those buttons as they are, or customize them to fit your needs (below are a few to get you started).', 'maxbuttons'), '<a href="http://maxbuttons.com/shop/category/button-packs/?utm_source=wordpressorg&utm_medium=plugin&utm_content=ever-growing&utm_campaign=mbfree" target="_blank">', '</a>') ?></p>
			<div class="button-packs">
				<div class="pack">
					<a href="http://maxbuttons.com/shop/e-commerce-buttons/?ref=mbfree" target="_blank"><img src="<?php echo MAXBUTTONS_PLUGIN_URL ?>/images/button-pack-e-commerce-buttons.png" alt="E-Commerce Buttons" border="0" /></a>
					<br />
					<a href="http://maxbuttons.com/shop/e-commerce-buttons/?ref=mbfree" target="_blank"><?php _e('E-Commerce Buttons', 'maxbuttons') ?></a>
				</div>
				<div class="pack">
					<a href="http://maxbuttons.com/shop/colored-round-social-icons-1/?ref=mbfree" target="_blank"><img src="<?php echo MAXBUTTONS_PLUGIN_URL ?>/images/button-pack-colored-round-social-icons-1.png" alt="Colored Round Social Icons 1" border="0" /></a>
					<br />
					<a href="http://maxbuttons.com/shop/colored-round-social-icons-1/?ref=mbfree" target="_blank"><?php _e('Colored Round Social Icons Set #1', 'maxbuttons') ?></a>
				</div>
				<div class="pack">
					<a href="http://maxbuttons.com/shop/chrome-icons/?ref=mbfree" target="_blank"><img src="<?php echo MAXBUTTONS_PLUGIN_URL ?>/images/button-pack-chrome-icons.png" alt="Chrome Icons" border="0" /></a>
					<br />
					<a href="http://maxbuttons.com/shop/chrome-icons/?ref=mbfree" target="_blank"><?php _e('Chrome Icons', 'maxbuttons') ?></a>
				</div>
				<div class="pack">
					<a href="http://maxbuttons.com/shop/shopping-cart-blue/?ref=mbfree" target="_blank"><img src="<?php echo MAXBUTTONS_PLUGIN_URL ?>/images/button-pack-shopping-cart-blue.png" alt="Shopping Cart Blue" border="0" /></a>
					<br />
					<a href="http://maxbuttons.com/shop/shopping-cart-blue/?ref=mbfree" target="_blank"><?php _e('Shopping Cart Blue', 'maxbuttons') ?></a>
				</div>
				<div class="pack">
					<a href="http://maxbuttons.com/shop/pace/?ref=mbfree" target="_blank"><img src="<?php echo MAXBUTTONS_PLUGIN_URL ?>/images/button-pack-pace.png" alt="Pace" border="0" /></a>
					<br />
					<a href="http://maxbuttons.com/shop/pace/?ref=mbfree" target="_blank"><?php _e('Pace', 'maxbuttons') ?></a>
				</div>
				<div class="pack">
					<a href="http://maxbuttons.com/shop/social-sign-in/?ref=mbfree" target="_blank"><img src="<?php echo MAXBUTTONS_PLUGIN_URL ?>/images/button-pack-social-sign-in.png" alt="Social Sign-In" border="0" /></a>
					<br />
					<a href="http://maxbuttons.com/shop/social-sign-in/?ref=mbfree" target="_blank"><?php _e('Social Sign-In', 'maxbuttons') ?></a>
				</div>
			</div>
			
			<h2><?php _e('Feature Comparison', 'maxbuttons') ?></h2>
			<p><?php _e('There\'s nothing like a nice, straightforward checklist to do a product comparison, so here\'s another one:', 'maxbuttons') ?></p>
			<table class="compare" cellpadding="0" cellspacing="0">
				<tr>
					<th>&nbsp;</th>
					<th><?php _e('Free Version', 'maxbuttons') ?></th>
					<th><?php _e('MaxButtons Pro', 'maxbuttons') ?></th>
				</tr>
				<tr>
					<td><?php _e('Icon support (put icons to the left, right, top, or bottom of your text)', 'maxbuttons') ?></td>
					<td align="center">&nbsp;</td>
					<td align="center"><img src="<?php echo MAXBUTTONS_PLUGIN_URL ?>/images/checkmark-16.png" alt="Checkmark" /></td>
				</tr>
				<tr>
					<td><?php _e('Multi-line text, to add a second line of text for communicating extra information', 'maxbuttons') ?></td>
					<td align="center">&nbsp;</td>
					<td align="center"><img src="<?php echo MAXBUTTONS_PLUGIN_URL ?>/images/checkmark-16.png" alt="Checkmark" /></td>
				</tr>
				<tr>
					<td><?php _e('Google Web Fonts, to make your buttons stand out with beautiful typography', 'maxbuttons') ?></td>
					<td align="center">&nbsp;</td>
					<td align="center"><img src="<?php echo MAXBUTTONS_PLUGIN_URL ?>/images/checkmark-16.png" alt="Checkmark" /></td>
				</tr>
				<tr>
					<td><?php _e('Button packs, to import pre-made sets of buttons into your website', 'maxbuttons') ?></td>
					<td align="center">&nbsp;</td>
					<td align="center"><img src="<?php echo MAXBUTTONS_PLUGIN_URL ?>/images/checkmark-16.png" alt="Checkmark" /></td>
				</tr>
				<tr>
					<td><?php _e('Import/export functionality (useful for backing up and/or moving your buttons)', 'maxbuttons') ?></td>
					<td align="center">&nbsp;</td>
					<td align="center"><img src="<?php echo MAXBUTTONS_PLUGIN_URL ?>/images/checkmark-16.png" alt="Checkmark" /></td>
				</tr>
				<tr>
					<td><?php _e('Explicit height and width options, to ensure your buttons are the same size', 'maxbuttons') ?></td>
					<td align="center">&nbsp;</td>
					<td align="center"><img src="<?php echo MAXBUTTONS_PLUGIN_URL ?>/images/checkmark-16.png" alt="Checkmark" /></td>
				</tr>
				<tr>
					<td><?php _e('Shopp integration for buttons in your e-commerce shopping cart', 'maxbuttons') ?></td>
					<td align="center">&nbsp;</td>
					<td align="center"><img src="<?php echo MAXBUTTONS_PLUGIN_URL ?>/images/checkmark-16.png" alt="Checkmark" /></td>
				</tr>
				<tr>
					<td><?php _e('Major browser support (Firefox, Chrome, Opera, Safari, and IE9)', 'maxbuttons') ?></td>
					<td align="center"><img src="<?php echo MAXBUTTONS_PLUGIN_URL ?>/images/checkmark-16.png" alt="Checkmark" /></td>
					<td align="center"><img src="<?php echo MAXBUTTONS_PLUGIN_URL ?>/images/checkmark-16.png" alt="Checkmark" /></td>
				</tr>
				<tr>
					<td><?php _e('Gradients for background colors', 'maxbuttons') ?></td>
					<td align="center"><img src="<?php echo MAXBUTTONS_PLUGIN_URL ?>/images/checkmark-16.png" alt="Checkmark" /></td>
					<td align="center"><img src="<?php echo MAXBUTTONS_PLUGIN_URL ?>/images/checkmark-16.png" alt="Checkmark" /></td>
				</tr>
				<tr>
					<td><?php _e('Border radius for rounded corners', 'maxbuttons') ?></td>
					<td align="center"><img src="<?php echo MAXBUTTONS_PLUGIN_URL ?>/images/checkmark-16.png" alt="Checkmark" /></td>
					<td align="center"><img src="<?php echo MAXBUTTONS_PLUGIN_URL ?>/images/checkmark-16.png" alt="Checkmark" /></td>
				</tr>
				<tr>
					<td><?php _e('Text and box shadowing', 'maxbuttons') ?></td>
					<td align="center"><img src="<?php echo MAXBUTTONS_PLUGIN_URL ?>/images/checkmark-16.png" alt="Checkmark" /></td>
					<td align="center"><img src="<?php echo MAXBUTTONS_PLUGIN_URL ?>/images/checkmark-16.png" alt="Checkmark" /></td>
				</tr>
				<tr>
					<td><?php _e('Hover effects and styles', 'maxbuttons') ?></td>
					<td align="center"><img src="<?php echo MAXBUTTONS_PLUGIN_URL ?>/images/checkmark-16.png" alt="Checkmark" /></td>
					<td align="center"><img src="<?php echo MAXBUTTONS_PLUGIN_URL ?>/images/checkmark-16.png" alt="Checkmark" /></td>
				</tr>
				<tr>
					<td><?php _e('Shortcodes to use anywhere in your WordPress site', 'maxbuttons') ?></td>
					<td align="center"><img src="<?php echo MAXBUTTONS_PLUGIN_URL ?>/images/checkmark-16.png" alt="Checkmark" /></td>
					<td align="center"><img src="<?php echo MAXBUTTONS_PLUGIN_URL ?>/images/checkmark-16.png" alt="Checkmark" /></td>
				</tr>
			</table>
			
			<h2><?php _e('Get Your Copy Today', 'maxbuttons') ?></h2>
			<p><?php printf(__('So what are you waiting for? %sGrab your copy of MaxButtons Pro today%s!', 'maxbuttons'), '<a href="http://maxbuttons.com/pricing/?utm_source=wordpressorg&utm_medium=plugin&utm_content=grab&utm_campaign=mbfree" target="_blank">', '</a>') ?></p>
		</div>
	</div>
</div>
