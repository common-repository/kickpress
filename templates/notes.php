<?php

global $post;

$args = array( 'style' => 'div' );

$notes = kickpress_get_notes( array( 'post_id' => $post->ID ) );

?>
<div id="notes">
	<h3>Notes</h3>
	<?php kickpress_note_list( $args, $notes ); ?>
	<div id="new-note">
		<h3>Write a Note</h3>
		<?php kickpress_note_form(); ?>
	</div><!-- #new-note -->
</div><!-- #notes -->