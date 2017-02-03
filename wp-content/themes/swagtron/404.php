<?php
get_header();
?>

  <!-- Start Body Wrapper -->
  <div class="body_warp">
    <div class="container">
   		<div class="row">
				<div class="alert">
					<h2>Page not found</h2>
					<h5><?php _e('Sorry, but the page you were trying to view does not exist.', 'swagtron'); ?></h5>

					<p>
						<?php _e('It looks like this was the result of either:', 'swagtron'); ?>
					</p>
					<ul>
						<li>
							<?php _e('a mistyped address', 'swagtron'); ?>
						</li>
						<li>
							<?php _e('an out-of-date link', 'swagtron'); ?>
						</li>
					</ul>
				</div>

			</div>
    </div>
  </div>
  <!-- End Body Wrapper -->

<?php
get_footer();
