<?php
/*
Plugin Name: Smart Posts Widget
Plugin URI: https://wordpress.org/plugins/smart-posts-widget/
Description: Its shows Recent post, Random posts, Category wise posts, Tag wise posts. Adds a widget that shows most all type of posts from wordpress.
Author: Purab Kharat
Version: 1.0
Author URI: http://profiles.wordpress.org/purab/
*/

if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Register plugin custom styles and javascript
 */
function my_init() {
  if (is_admin()) {
    wp_enqueue_script('smart-post-js', plugins_url('/smart-posts.js',__FILE__) );    
    wp_register_style( 'smart-posts', plugins_url( 'smart-posts/admin-smart-posts.css' ));
    wp_enqueue_style( 'smart-posts' );
  }
  else
  {
        wp_register_style( 'smart-posts', plugins_url( 'smart-posts/smart-posts.css' ) );
	wp_enqueue_style( 'smart-posts' );
  }
}
add_action('init', 'my_init');

/**
 * Smart Posts Widget Class
 * Shows posts with all type of configurable options
 */
class SmartPosts extends WP_Widget {

	function SmartPosts() {
		$widget_ops = array('classname' => 'smart-posts-widget', 'description' => __('Show all type of filtered posts'));
		$this->WP_Widget('smart-posts', __('Smart Posts'), $widget_ops);
	}

	// Displays category posts widget on blog.
	function widget($args, $instance) {
		extract( $args );		
                //add line CSS - todo need to fix this
                echo '<style>'.trim($instance['inline_css']).'</style>';
                //wp_add_inline_style( 'custom-style', trim($instance['inline_css']) );
        
		// If not title, use the name of the category.
		if( !$instance["title"] ) 
                {
			$category_info = get_category($instance["cat"]);
			$instance["title"] = $category_info->name;
                }

	  $valid_sort_orders = array('date', 'title', 'comment_count', 'rand');
	  if ( in_array($instance['sort_by'], $valid_sort_orders) ) {
		$sort_by = $instance['sort_by'];
		$sort_order = (bool) isset( $instance['asc_sort_order'] ) ? 'ASC' : 'DESC';
	  } else {
		// by default, display latest first
		$sort_by = 'date';
		$sort_order = 'DESC';
	  }
          
          $query_params='';
          if( isset( $instance["sticky_post"])) {
              $stickyp= get_option( 'sticky_posts' );
              $stickyids=  implode(',', $stickyp);
              $query_params.='post_in='.$stickyids.'ignore_sticky_posts=1';
          }
		
          if( $instance["cat_tag"]=='tag') {
              $query_params.="post_type=" . $instance["post_type"] . 
                 "&showposts=" . $instance["num"] . 
		"&tag_id=" . $instance["selected_tag"] .
		"&orderby=" . $sort_by .
		"&order=" . $sort_order;
          } else if( $instance["cat_tag"]=='cat')
          {
              $query_params.="post_type=" . $instance["post_type"] . 
                "&showposts=".$instance["num"] . 
		"&cat=" . $instance["cat"] .
		"&orderby=" . $sort_by .
		"&order=" . $sort_order;
          }
                
           // Get array of post info.
	  $smart_posts = new WP_Query($query_params);

		// Excerpt length filter
		$new_excerpt_length = create_function('$length', "return " . $instance["excerpt_length"] . ";");
		if ( $instance["excerpt_length"] > 0 )
			add_filter('excerpt_length', $new_excerpt_length);
                
                //after text
		echo '<div class="smart-posts-widget">'.trim($instance['after_text']);		
		// Widget title		
                if( isset( $instance["title_link"]) && $instance["cat_tag"]=='tag')
			echo '<h3><a href="' . get_tag_link($instance["selected_tag"]) . '">' . $instance["title"] . '</a></h3>';
		else if( isset( $instance["title_link"]) &&  $instance["cat_tag"]=='cat')
			echo '<h3>'.'<a href="' . get_category_link($instance["cat"]) . '">' . $instance["title"] . '</a></h3>';
		else
			echo '<h3>'.$instance["title"].'</h3>';		

		// Post list
		echo '<ul>';		
		while ( $smart_posts->have_posts() )
		{
			$smart_posts->the_post();
		?>
			<li class="smart-post-singleitem" style="min-width:<?php echo $instance["thumb_w"]?>px;min-height:<?php echo $instance["thumb_h"]?>px;">
				
				
				<?php
					if (
						function_exists('the_post_thumbnail') &&
						current_theme_supports("post-thumbnails") &&
						isset( $instance["thumb"] ) &&
						has_post_thumbnail()
					) :
				?>
                                    <div class="left" style="float: left; padding-right: 5px;max-width:<?php echo $instance["thumb_w"]?>px;max-height:<?php echo $instance["thumb_h"]?>px;overflow:hidden">
					<a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>">
					<?php the_post_thumbnail(); ?>
					</a>
                                    </div>
				<?php endif; ?>
                            
                            <a class="post-title" href="<?php the_permalink(); ?>" rel="bookmark" title="Permanent link to <?php the_title_attribute(); ?>"><?php the_title(); ?></a>
				

				<?php if ( isset( $instance['created_date'] ) ) : ?>
				<p class="post-date">Created at: <?php the_time("j M Y"); ?></p>
				<?php endif; ?>
                                
                                <?php if ( isset( $instance['updated_date'] ) ) : ?>
				<p class="post-date">Updated on: <?php the_modified_date("j M Y"); ?></p>
				<?php endif; ?>
                                
                                <?php if ( isset( $instance['author_link'] ) ) : ?>
                                    <p>Written by: <?php the_author_link(); ?></p>
				<?php endif; ?>
							
				<?php if ( isset( $instance['excerpt'] ) ) : ?>
				<?php the_excerpt(); ?> 
				<?php endif; ?>
				
				<?php if ( isset( $instance['comment_num'] ) ) : ?>
				<p class="comment-num">(<?php comments_number(); ?>)</p>
				<?php endif; ?>
                                
                                <?php if ( isset( $instance['more_link'] ) ) : ?>
				<a class="post-title" href="<?php the_permalink(); ?>" rel="bookmark" title="Permanent link to <?php the_title_attribute(); ?>">Read More</a>
				<?php endif; ?>
			</li>
			<?php
		}		
		echo '</ul>'.trim($instance['after_text']).'</div>';                
		wp_reset_postdata();
	
	}

	/**
	 * Update the options
        */
	function update($new_instance, $old_instance) {
		return $new_instance;
	}

	/**
	 * The widget configuration form back end.	
	 */
	function form($instance) {
		$instance = wp_parse_args( ( array ) $instance, array(			
			'title_link'	 => __( '' ),
                        'sticky_post'	 => __( '' ),
			'excerpt'        => __( '' ),			
			'thumb'          => __( '' ),
			'thumb_w'        => __( '' ),
			'thumb_h'        => __( '' ),
                        'title'          => __( '' ),
			'cat'			 => __( '' ),
			'num'            => __( '' ),
			'sort_by'        => __( '' ),
			'asc_sort_order' => __( '' ),
                        'more_link'        => __( '' ),
                        'cat_tag'        => __( '' ),
                        'post_type'         =>    __( '' ),
                        'selected_tag'         =>    __( '' ),
                        'excerpt_length' => __( '' ),
			'comment_num'    => __( '' ),
			'created_date'           => __( '' ),
                        'updated_date'           => __( '' ),
                        'before_text'         =>    __( '' ),
                        'after_text'         =>    __( '' ),
                        'inline_css'         =>    __( '' ),
		) );

                    $title          = $instance['title'];
                    $sticky_post          = $instance['sticky_post'];                    
                    $cat 			= $instance['cat'];
                    $num            = $instance['num'];
                    $sort_by        = $instance['sort_by'];
                    $asc_sort_order = $instance['asc_sort_order'];
                    $title_link		= $instance['title_link'];		
                    $excerpt        = $instance['excerpt'];
                    $excerpt_length = $instance['excerpt_length'];
                    $comment_num    = $instance['comment_num'];
                    $created_date           = $instance['created_date'];
                    $thumb          = $instance['thumb'];
                    $thumb_w        = $instance['thumb_w'];
                    $thumb_h        = $instance['thumb_h'];
                    $more_link        = $instance['more_link'];
                    $sticky_post        = $instance['cat_tag'];
                    $post_type        = $instance['post_type'];
                    $selected_tag        = $instance['selected_tag'];
                    $before_text        = $instance['before_text'];
                    $after_text        = $instance['after_text'];
                    $inline_css        = $instance['inline_css'];
                    $updated_date        = $instance['updated_date'];                	
			?>
                        
                        <div id="smart-post-form-container">
			<p>
				<label for="<?php echo $this->get_field_id("title"); ?>">
					<?php _e( 'Title' ); ?>:
					<input class="widefat" id="<?php echo $this->get_field_id("title"); ?>" name="<?php echo $this->get_field_name("title"); ?>" type="text" value="<?php echo esc_attr($instance["title"]); ?>" />
				</label>
			</p>
                        
                        <p>
                            <label for="<?php echo $this->get_field_id("post_type"); ?>">Post type: </label>
                            <select name='<?php echo $this->get_field_name("post_type"); ?>' id='<?php echo $this->get_field_id("post_type"); ?>'>
                                <?php 
                                $args = array('public'   => true);
                                $post_types=get_post_types($args, 'objects'); foreach ($post_types as $post_type): 
//                                    if($post_type->name=='attachment')
//                                        continue;
                                ?>
                                <option value="<?php echo esc_attr($post_type->name); ?>" <?php selected( $instance["post_type"], esc_attr($post_type->name) ); ?>><?php echo esc_html($post_type->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </p>
                        
                        <p>
				<label for="<?php echo $this->get_field_id("sticky_post"); ?>">
					<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id("sticky_post"); ?>" name="<?php echo $this->get_field_name("sticky_post"); ?>"<?php checked( (bool) $instance["sticky_post"], true ); ?> />
					<?php _e( 'Show only Sticky Posts' ); ?>
				</label>
			</p>
                        
                        <p id="smart-posts-widget-choose_cat_tag">
				<label for="<?php echo $this->get_field_id("cat_tag"); ?>">
					<?php _e('Posts by Category/Tag'); ?>:
					<select onchange="change_smart_postwidget(<?php echo $this->get_field_id("cat_tag"); ?>);" id="<?php echo $this->get_field_id("cat_tag"); ?>" name="<?php echo $this->get_field_name("cat_tag"); ?>">
					  <option value="cat"<?php selected( $instance["cat_tag"], "cat" ); ?>>Category</option>
					  <option value="tag"<?php selected( $instance["cat_tag"], "tag" ); ?>>Tags</option>                                          
					</select>
				</label>
			</p>
                        
                        <?php if(!isset($instance["cat_tag"])) {$instance["cat_tag"]= 'cat';} ?>
			
                        <p style="display:<?php if($instance["cat_tag"]=='cat'){echo 'block';} else {echo 'none';} ?>" class="<?php echo $this->get_field_id('cat'); ?>">
				<label>
					<?php _e( 'Category' ); ?>:
					<?php wp_dropdown_categories( array( 'name' => $this->get_field_name("cat"), 'selected' => $instance["cat"],'show_option_all'=>'All Categories') ); ?>
				</label>
			</p>
                       
                        <p style="display:<?php if($instance["cat_tag"]=='tag'){echo 'block';}else {echo 'none';} ?>" class="<?php echo $this->get_field_id('selected_tag'); ?>">
                            <label for="<?php echo $this->get_field_id('selected_tag'); ?>">
                                        <?php _e( 'Tags' ); ?>:
                                        <select id="<?php echo $this->get_field_id('selected_tag'); ?>" name="<?php echo $this->get_field_name('selected_tag'); ?>" >
                                        <?php $tags = get_tags('orderby=name&order=ASC'); foreach($tags as $tag) {?>
                                <option value="<?php echo $tag->term_id;?>"<?php selected($instance['selected_tag'],$tag->term_id); ?> ><?php echo $tag->name; ?></option>			   
                        <?php }?>
                                        </select>
                        </label> 
                        </p>
                       
			
			<p>
				<label for="<?php echo $this->get_field_id("num"); ?>">
					<?php _e('Number of posts to show'); ?>:
					<input style="text-align: center;" id="<?php echo $this->get_field_id("num"); ?>" name="<?php echo $this->get_field_name("num"); ?>" type="text" value="<?php echo absint($instance["num"]); ?>" size='3' />
				</label>
			</p>

			<p>
				<label for="<?php echo $this->get_field_id("sort_by"); ?>">
					<?php _e('Sort by'); ?>:
					<select id="<?php echo $this->get_field_id("sort_by"); ?>" name="<?php echo $this->get_field_name("sort_by"); ?>">
					  <option value="date"<?php selected( $instance["sort_by"], "date" ); ?>>Date</option>
					  <option value="title"<?php selected( $instance["sort_by"], "title" ); ?>>Title</option>
					  <option value="comment_count"<?php selected( $instance["sort_by"], "comment_count" ); ?>>Number of comments</option>
					  <option value="rand"<?php selected( $instance["sort_by"], "rand" ); ?>>Random</option>
					</select>
				</label>
			</p>
			
			<p>
				<label for="<?php echo $this->get_field_id("asc_sort_order"); ?>">
					<input type="checkbox" class="checkbox" 
					  id="<?php echo $this->get_field_id("asc_sort_order"); ?>" 
					  name="<?php echo $this->get_field_name("asc_sort_order"); ?>"
					  <?php checked( (bool) $instance["asc_sort_order"], true ); ?> />
							<?php _e( 'Reverse sort order (ascending)' ); ?>
				</label>
			</p>

			<p>
				<label for="<?php echo $this->get_field_id("title_link"); ?>">
					<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id("title_link"); ?>" name="<?php echo $this->get_field_name("title_link"); ?>"<?php checked( (bool) $instance["title_link"], true ); ?> />
					<?php _e( 'Make widget title as Category or Tag link' ); ?>
				</label>
			</p>
			
			<p>
				<label for="<?php echo $this->get_field_id("excerpt"); ?>">
					<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id("excerpt"); ?>" name="<?php echo $this->get_field_name("excerpt"); ?>"<?php checked( (bool) $instance["excerpt"], true ); ?> />
					<?php _e( 'Show post excerpt' ); ?>
				</label>
			</p>
			
			<p>
				<label for="<?php echo $this->get_field_id("excerpt_length"); ?>">
					<?php _e( 'Excerpt length (in words):' ); ?>
				</label>
				<input style="text-align: center;" type="text" id="<?php echo $this->get_field_id("excerpt_length"); ?>" name="<?php echo $this->get_field_name("excerpt_length"); ?>" value="<?php echo $instance["excerpt_length"]; ?>" size="3" />
			</p>
			
			<p>
				<label for="<?php echo $this->get_field_id("comment_num"); ?>">
					<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id("comment_num"); ?>" name="<?php echo $this->get_field_name("comment_num"); ?>"<?php checked( (bool) $instance["comment_num"], true ); ?> />
					<?php _e( 'Show number of comments' ); ?>
				</label>
			</p>
			
			<p>
				<label for="<?php echo $this->get_field_id("created_date"); ?>">
					<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id("created_date"); ?>" name="<?php echo $this->get_field_name("created_date"); ?>"<?php checked( (bool) $instance["created_date"], true ); ?> />
					<?php _e( 'Show created post date' ); ?>
				</label>
			</p>
                        
                        <p>
				<label for="<?php echo $this->get_field_id("updated_date"); ?>">
					<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id("updated_date"); ?>" name="<?php echo $this->get_field_name("updated_date"); ?>"<?php checked( (bool) $instance["updated_date"], true ); ?> />
					<?php _e( 'Show updated post date' ); ?>
				</label>
			</p>
                        
                        <p>
				<label for="<?php echo $this->get_field_id("more_link"); ?>">
					<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id("more_link"); ?>" name="<?php echo $this->get_field_name("more_link"); ?>"<?php checked( (bool) $instance["more_link"], true ); ?> />
					<?php _e( 'Show number of more link' ); ?>
				</label>
			</p>
                        
                        <p>
				<label for="<?php echo $this->get_field_id("author_link"); ?>">
					<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id("author_link"); ?>" name="<?php echo $this->get_field_name("author_link"); ?>"<?php checked( (bool) $instance["author_link"], true ); ?> />
					<?php _e( 'Display post author and link' ); ?>
				</label>
			</p>
			
			<?php if ( function_exists('the_post_thumbnail') && current_theme_supports("post-thumbnails") ) : ?>
			<p>
				<label for="<?php echo $this->get_field_id("thumb"); ?>">
					<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id("thumb"); ?>" name="<?php echo $this->get_field_name("thumb"); ?>"<?php checked( (bool) $instance["thumb"], true ); ?> />
					<?php _e( 'Show post thumbnail' ); ?>
				</label>
			</p>
			<p>
				<label>
					<?php _e('Thumbnail dimensions (in pixels)'); ?>:<br />
					<label for="<?php echo $this->get_field_id("thumb_w"); ?>">
						Width: <input class="widefat" style="width:30%;" type="text" id="<?php echo $this->get_field_id("thumb_w"); ?>" name="<?php echo $this->get_field_name("thumb_w"); ?>" value="<?php echo $instance["thumb_w"]; ?>" />
					</label>
					
					<label for="<?php echo $this->get_field_id("thumb_h"); ?>">
						Height: <input class="widefat" style="width:30%;" type="text" id="<?php echo $this->get_field_id("thumb_h"); ?>" name="<?php echo $this->get_field_name("thumb_h"); ?>" value="<?php echo $instance["thumb_h"]; ?>" />
					</label>
				</label>
			</p>
                        
                        
                        <p>
				<label for="<?php echo $this->get_field_id("before_text"); ?>">
					<?php _e( 'Widget before Text:' ); ?>
				</label>
                            <textarea id="<?php echo $this->get_field_id("before_text"); ?>" name="<?php echo $this->get_field_name("before_text"); ?>" > <?php echo $instance["before_text"]; ?> </textarea>
				
			</p>
                        
                        <p>
				<label for="<?php echo $this->get_field_id("after_text"); ?>">
					<?php _e( 'Widget after_text Text:' ); ?>
				</label>
                            <textarea id="<?php echo $this->get_field_id("after_text"); ?>" name="<?php echo $this->get_field_name("after_text"); ?>" > <?php echo $instance["after_text"]; ?> </textarea>				
			</p>
                        
                        <p>
				<label for="<?php echo $this->get_field_id("inline_css"); ?>">
					<?php _e( 'In Line CSS:' ); ?>
				</label>
                            <textarea id="<?php echo $this->get_field_id("inline_css"); ?>" name="<?php echo $this->get_field_name("inline_css"); ?>" > <?php echo $instance["inline_css"]; ?> </textarea>				
			</p>
                        </div>
			<?php endif; ?>
                        
                        

			<?php

		}

}

add_action( 'widgets_init', create_function('', 'return register_widget("SmartPosts");') );
