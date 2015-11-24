<?php

require_once( plugin_dir_path( __FILE__ ) . 'AnalyticBridgePopularPosts.php');

/**
 * The base widget for the Analytics bridge Plugin
 *
 * @since 0.2
 * @link https://github.com/INN/analytic-bridge/issues/26
 */
class AnalyticBridgePopularPostWidget extends WP_Widget {

	private $popPosts;

	/**
	 * Sets up the widget
	 *
	 * @since 0.1
	 */
	public function __construct() {

		parent::__construct(
			'analytic-bridge-popular-posts', // Base ID
			__( 'Analytic Bridge Popular Posts', 'analytic-bridge' ), // Name
			array( 'description' => __( 'List popular posts', 'analytic-bridge' ), ) // Args
		);
		// widget actual processes
	}

	/**
	 * Output the widget
	 *
	 * This widget function was copied from the Nonprofit Quarterly theme: https://bitbucket.org/projectlargo/theme-npq/src/5b7661348039e13cce66356eb85ddc975118aec6/inc/widgets/npq-popular-posts.php?at=master&fileviewer=file-view-default
	 * It contains additional bugfixes: https://github.com/INN/analytic-bridge/issues/26
	 *
	 * @since 0.2
	 * @uses $this->compare_popular_posts
	 * @param array $args
	 * @param array $instance
	 */
	function widget( $args, $instance ) {

		global $ids; // an array of post IDs already on a page so we can avoid duplicating posts

		$posts_term = of_get_option( 'posts_term_plural', 'Posts' );

		extract( $args );

		$title = apply_filters('widget_title', empty( $instance['title'] ) ? __('Recent ' . $posts_term, 'largo') : $instance['title'], $instance, $this->id_base);

		/*
		 * Start drawing the widget
		 */
		echo $before_widget;

		if ( $title ) {
			echo $before_title . $title . $after_title;
		}

		$thumb = isset( $instance['thumbnail_display'] ) ? $instance['thumbnail_display'] : 'small';
		$excerpt = isset( $instance['excerpt_display'] ) ? $instance['excerpt_display'] : 'num_sentences';

		// if we're just showing a list of headlines, wrap the elements in a ul
		if ($excerpt == 'none') {
			echo '<ul>';
		} else {
			echo '<ol>';
		}
		
		$this->popPosts = new AnayticBridgePopularPosts();
		$this->popPosts->size = $instance['num_posts'];
		$this->popPosts->query();

		$query_args = array(
			'post__in' => $this->popPosts->ids,
			'ignore_sticky_posts' => true,
			'showposts' => $instance['num_posts'],
		);

		// Get posts, sort them using the compare_popular_posts function defined elsewhere in this plugin.
		$my_query = new WP_Query( $query_args );
		usort($my_query->posts,array($this, 'compare_popular_posts'));

		if ( $my_query->have_posts() ) {

			$output = '';

			while ( $my_query->have_posts() ) {
				$my_query->the_post();
				$shown_ids[] = get_the_ID();

				// wrap the items in li's.
				$output .= '<li>';

				// The top term
				$top_term_args = array('echo' => false);
				if ( isset($instance['show_top_term']) && $instance['show_top_term'] == 1 && largo_has_categories_or_tags() ) {
					$output .= '<h5 class="top-tag">' . largo_top_term($top_term_args) . '</h5>' ;
				}

				// the headline
				$output .= '<h5><a href="' . get_permalink() . '">' . get_the_title() . '</a></h5>';

				// close the item
				$output .= '</li>';
			}

			// print all of the items
			echo $output;

		} else {
			printf(__('<p class="error"><strong>No posts found.</strong></p>', 'largo'), strtolower( $posts_term ) );
		} // end more featured posts

		// close the ul if we're just showing a list of headlines
		if ($excerpt == 'none') echo '</ol>';

		echo $after_widget;

		// Restore global $post
		wp_reset_postdata();
		$post = $preserve;
	}

	/**
	 * Outputs the options form on admin
	 *
	 * @param array $instance The widget options
	 */
	public function form( $instance ) {
		$defaults = array(
			'title' 			=> __('Recent ' . of_get_option( 'posts_term_plural', 'Posts' ), 'largo'),
			'num_posts' 		=> 5,
			'linktext' 			=> '',
			'linkurl' 			=> ''
		);
		$instance = wp_parse_args( (array) $instance, $defaults );
		$duplicates = $instance['avoid_duplicates'] ? 'checked="checked"' : '';
		$showreadmore = $instance['show_read_more'] ? 'checked="checked"' : '';
		?>

		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e('Title:', 'largo'); ?></label>
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" style="width:90%;" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'num_posts' ); ?>"><?php _e('Number of posts to show:', 'largo'); ?></label>
			<input id="<?php echo $this->get_field_id( 'num_posts' ); ?>" name="<?php echo $this->get_field_name( 'num_posts' ); ?>" value="<?php echo $instance['num_posts']; ?>" style="width:90%;" />
		</p>

		<p><strong><?php _e('More Link', 'largo'); ?></strong><br /><small><?php _e('If you would like to add a more link at the bottom of the widget, add the link text and url here.', 'largo'); ?></small></p>
		<p>
			<label for="<?php echo $this->get_field_id('linktext'); ?>"><?php _e('Link text:', 'largo'); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id('linktext'); ?>" name="<?php echo $this->get_field_name('linktext'); ?>" type="text" value="<?php echo esc_attr( $instance['linktext'] ); ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id('linkurl'); ?>"><?php _e('URL:', 'largo'); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id('linkurl'); ?>" name="<?php echo $this->get_field_name('linkurl'); ?>" type="text" value="<?php echo esc_attr( $instance['linkurl'] ); ?>" />
		</p>

	<?php
	}

	/**
	 * Processing widget options on save
	 *
	 * @param array $new_instance The new options
	 * @param array $old_instance The previous options
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = sanitize_text_field( $new_instance['title'] );
		$instance['num_posts'] = intval( $new_instance['num_posts'] );
		$instance['linktext'] = sanitize_text_field( $new_instance['linktext'] );
		$instance['linkurl'] = esc_url_raw( $new_instance['linkurl'] );
		return $instance;
	}

	/**
	 * Sort comparison.
	 *
	 * @since 0.2
	 */
	private function compare_popular_posts($a,$b) {

		$ascore = $this->popPosts->score($a->ID);
		$bscore = $this->popPosts->score($b->ID);

		return ( $ascore > $bscore ) ? -1 : 1;

	}
}

add_action( 'widgets_init', function(){
	register_widget( 'AnalyticBridgePopularPostWidget' );
});