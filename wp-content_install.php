<?php
if ( ! function_exists( 'wp_install_defaults' ) ) :
	/**
	 * Creates the initial content for a newly-installed site.
	 *
	 * Adds the default "Uncategorized" category, the first post (with comment),
	 * first page, and default widgets for default theme for the current version.
	 *
	 * @since 2.1.0
	 *
	 * @global wpdb       $wpdb         WordPress database abstraction object.
	 * @global WP_Rewrite $wp_rewrite   WordPress rewrite component.
	 * @global string     $table_prefix
	 *
	 * @param int $user_id User ID.
	 */
	function wp_install_defaults( $user_id ) {
		global $wpdb, $wp_rewrite, $table_prefix;

		// Default category.
		$cat_name = __( 'Uncategorized' );
		/* translators: Default category slug. */
		$cat_slug = sanitize_title( _x( 'Uncategorized', 'Default category slug' ) );

		if ( global_terms_enabled() ) {
			$cat_id = $wpdb->get_var( $wpdb->prepare( "SELECT cat_ID FROM {$wpdb->sitecategories} WHERE category_nicename = %s", $cat_slug ) );
			if ( null == $cat_id ) {
				$wpdb->insert(
					$wpdb->sitecategories,
					array(
						'cat_ID'            => 0,
						'cat_name'          => $cat_name,
						'category_nicename' => $cat_slug,
						'last_updated'      => current_time( 'mysql', true ),
					)
				);
				$cat_id = $wpdb->insert_id;
			}
			update_option( 'default_category', $cat_id );
		} else {
			$cat_id = 1;
		}

		$wpdb->insert(
			$wpdb->terms,
			array(
				'term_id'    => $cat_id,
				'name'       => $cat_name,
				'slug'       => $cat_slug,
				'term_group' => 0,
			)
		);
		$wpdb->insert(
			$wpdb->term_taxonomy,
			array(
				'term_id'     => $cat_id,
				'taxonomy'    => 'category',
				'description' => '',
				'parent'      => 0,
				'count'       => 1,
			)
		);
		$cat_tt_id = $wpdb->insert_id;

		// First post.
		$now             = current_time( 'mysql' );
		$now_gmt         = current_time( 'mysql', 1 );
		$first_post_guid = get_option( 'home' ) . '/?p=1';

		if ( is_multisite() ) {
			$first_post = get_site_option( 'first_post' );

			if ( ! $first_post ) {
				$first_post = "<!-- wp:paragraph -->\n<p>" .
				/* translators: First post content. %s: Site link. */
				__( 'Namespaces are a feature of the Linux kernel that partitions kernel resources such that one set of processes sees one set of resources while another set of processes sees a different set of resources. The feature works by having the same namespace for these resources in the various sets of processes, but those names referring to distinct resources. Examples of resource names that can exist in multiple spaces, so that the named resources are partitioned, are process IDs, hostnames, user IDs, file names, and some names associated with network access, and interprocess communication.
<br/>
Namespaces are a fundamental aspect of containers on Linux.' ) .
				"</p>\n<!-- /wp:paragraph -->";
			}

			$first_post = sprintf(
				$first_post,
				sprintf( '<a href="%s">%s</a>', esc_url( network_home_url() ), get_network()->site_name )
			);

			// Back-compat for pre-4.4.
			$first_post = str_replace( 'SITE_URL', esc_url( network_home_url() ), $first_post );
			$first_post = str_replace( 'SITE_NAME', get_network()->site_name, $first_post );
		} else {
			$first_post = "<!-- wp:paragraph -->\n<p>" .
			/* translators: First post content. %s: Site link. */
			__( 'Namespaces are a feature of the Linux kernel that partitions kernel resources such that one set of processes sees one set of resources while another set of processes sees a different set of resources. The feature works by having the same namespace for these resources in the various sets of processes, but those names referring to distinct resources. Examples of resource names that can exist in multiple spaces, so that the named resources are partitioned, are process IDs, hostnames, user IDs, file names, and some names associated with network access, and interprocess communication.
<br/>
Namespaces are a fundamental aspect of containers on Linux.' ) .
			"</p>\n<!-- /wp:paragraph -->";
		}

		$wpdb->insert(
			$wpdb->posts,
			array(
				'post_author'           => $user_id,
				'post_date'             => $now,
				'post_date_gmt'         => $now_gmt,
				'post_content'          => $first_post,
				'post_excerpt'          => '',
				'post_title'            => __( 'Linux namespaces' ),
				/* translators: Default post slug. */
				'post_name'             => sanitize_title( _x( 'hello-world', 'Default post slug' ) ),
				'post_modified'         => $now,
				'post_modified_gmt'     => $now_gmt,
				'guid'                  => $first_post_guid,
				'comment_count'         => 1,
				'to_ping'               => '',
				'pinged'                => '',
				'post_content_filtered' => '',
			)
		);
		$wpdb->insert(
			$wpdb->term_relationships,
			array(
				'term_taxonomy_id' => $cat_tt_id,
				'object_id'        => 1,
			)
		);

		// Default comment.
		if ( is_multisite() ) {
			$first_comment_author = get_site_option( 'first_comment_author' );
			$first_comment_email  = get_site_option( 'first_comment_email' );
			$first_comment_url    = get_site_option( 'first_comment_url', network_home_url() );
			$first_comment        = get_site_option( 'first_comment' );
		}

		$first_comment_author = ! empty( $first_comment_author ) ? $first_comment_author : __( 'A WordPress Commenter' );
		$first_comment_email  = ! empty( $first_comment_email ) ? $first_comment_email : 'wapuu@wordpress.example';
		$first_comment_url    = ! empty( $first_comment_url ) ? $first_comment_url : 'https://wordpress.org/';
		$first_comment        = ! empty( $first_comment ) ? $first_comment : __(
			'Hi, this is a comment.
To get started with moderating, editing, and deleting comments, please visit the Comments screen in the dashboard.
Commenter avatars come from <a href="https://gravatar.com">Gravatar</a>.'
		);
		$wpdb->insert(
			$wpdb->comments,
			array(
				'comment_post_ID'      => 1,
				'comment_author'       => $first_comment_author,
				'comment_author_email' => $first_comment_email,
				'comment_author_url'   => $first_comment_url,
				'comment_date'         => $now,
				'comment_date_gmt'     => $now_gmt,
				'comment_content'      => $first_comment,
				'comment_type'         => 'comment',
			)
		);

		// First page.
		if ( is_multisite() ) {
			$first_page = get_site_option( 'first_page' );
		}

		if ( empty( $first_page ) ) {
			$first_page = "<!-- wp:paragraph -->\n<p>";
			/* translators: First page content. */
			$first_page .= __( "This is an example page. It's different from a blog post because it will stay in one place and will show up in your site navigation (in most themes). Most people start with an About page that introduces them to potential site visitors. It might say something like this:" );
			$first_page .= "</p>\n<!-- /wp:paragraph -->\n\n";

			$first_page .= "<!-- wp:quote -->\n<blockquote class=\"wp-block-quote\"><p>";
			/* translators: First page content. */
			$first_page .= __( "Hi there! I'm a bike messenger by day, aspiring actor by night, and this is my website. I live in Los Angeles, have a great dog named Jack, and I like pi&#241;a coladas. (And gettin' caught in the rain.)" );
			$first_page .= "</p></blockquote>\n<!-- /wp:quote -->\n\n";

			$first_page .= "<!-- wp:paragraph -->\n<p>";
			/* translators: First page content. */
			$first_page .= __( '...or something like this:' );
			$first_page .= "</p>\n<!-- /wp:paragraph -->\n\n";

			$first_page .= "<!-- wp:quote -->\n<blockquote class=\"wp-block-quote\"><p>";
			/* translators: First page content. */
			$first_page .= __( 'The XYZ Doohickey Company was founded in 1971, and has been providing quality doohickeys to the public ever since. Located in Gotham City, XYZ employs over 2,000 people and does all kinds of awesome things for the Gotham community.' );
			$first_page .= "</p></blockquote>\n<!-- /wp:quote -->\n\n";

			$first_page .= "<!-- wp:paragraph -->\n<p>";
			$first_page .= sprintf(
				/* translators: First page content. %s: Site admin URL. */
				__( 'As a new WordPress user, you should go to <a href="%s">your dashboard</a> to delete this page and create new pages for your content. Have fun!' ),
				admin_url()
			);
			$first_page .= "</p>\n<!-- /wp:paragraph -->";
		}

		$first_post_guid = get_option( 'home' ) . '/?page_id=2';
		$wpdb->insert(
			$wpdb->posts,
			array(
				'post_author'           => $user_id,
				'post_date'             => $now,
				'post_date_gmt'         => $now_gmt,
				'post_content'          => $first_page,
				'post_excerpt'          => '',
				'comment_status'        => 'closed',
				'post_title'            => __( 'Sample Page' ),
				/* translators: Default page slug. */
				'post_name'             => __( 'sample-page' ),
				'post_modified'         => $now,
				'post_modified_gmt'     => $now_gmt,
				'guid'                  => $first_post_guid,
				'post_type'             => 'page',
				'to_ping'               => '',
				'pinged'                => '',
				'post_content_filtered' => '',
			)
		);
		$wpdb->insert(
			$wpdb->postmeta,
			array(
				'post_id'    => 2,
				'meta_key'   => '_wp_page_template',
				'meta_value' => 'default',
			)
		);

		// Privacy Policy page.
		if ( is_multisite() ) {
			// Disable by default unless the suggested content is provided.
			$privacy_policy_content = get_site_option( 'default_privacy_policy_content' );
		} else {
			if ( ! class_exists( 'WP_Privacy_Policy_Content' ) ) {
				include_once ABSPATH . 'wp-admin/includes/class-wp-privacy-policy-content.php';
			}

			$privacy_policy_content = WP_Privacy_Policy_Content::get_default_content();
		}

		if ( ! empty( $privacy_policy_content ) ) {
			$privacy_policy_guid = get_option( 'home' ) . '/?page_id=3';

			$wpdb->insert(
				$wpdb->posts,
				array(
					'post_author'           => $user_id,
					'post_date'             => $now,
					'post_date_gmt'         => $now_gmt,
					'post_content'          => $privacy_policy_content,
					'post_excerpt'          => '',
					'comment_status'        => 'closed',
					'post_title'            => __( 'Privacy Policy' ),
					/* translators: Privacy Policy page slug. */
					'post_name'             => __( 'privacy-policy' ),
					'post_modified'         => $now,
					'post_modified_gmt'     => $now_gmt,
					'guid'                  => $privacy_policy_guid,
					'post_type'             => 'page',
					'post_status'           => 'draft',
					'to_ping'               => '',
					'pinged'                => '',
					'post_content_filtered' => '',
				)
			);
			$wpdb->insert(
				$wpdb->postmeta,
				array(
					'post_id'    => 3,
					'meta_key'   => '_wp_page_template',
					'meta_value' => 'default',
				)
			);
			update_option( 'wp_page_for_privacy_policy', 3 );
		}

		// Set up default widgets for default theme.
		update_option(
			'widget_search',
			array(
				2              => array( 'title' => '' ),
				'_multiwidget' => 1,
			)
		);
		update_option(
			'widget_recent-posts',
			array(
				2              => array(
					'title'  => '',
					'number' => 5,
				),
				'_multiwidget' => 1,
			)
		);
		update_option(
			'widget_recent-comments',
			array(
				2              => array(
					'title'  => '',
					'number' => 5,
				),
				'_multiwidget' => 1,
			)
		);
		update_option(
			'widget_archives',
			array(
				2              => array(
					'title'    => '',
					'count'    => 0,
					'dropdown' => 0,
				),
				'_multiwidget' => 1,
			)
		);
		update_option(
			'widget_categories',
			array(
				2              => array(
					'title'        => '',
					'count'        => 0,
					'hierarchical' => 0,
					'dropdown'     => 0,
				),
				'_multiwidget' => 1,
			)
		);
		update_option(
			'widget_meta',
			array(
				2              => array( 'title' => '' ),
				'_multiwidget' => 1,
			)
		);
		update_option(
			'sidebars_widgets',
			array(
				'wp_inactive_widgets' => array(),
				'sidebar-1'           => array(
					0 => 'search-2',
					1 => 'recent-posts-2',
					2 => 'recent-comments-2',
				),
				'sidebar-2'           => array(
					0 => 'archives-2',
					1 => 'categories-2',
					2 => 'meta-2',
				),
				'array_version'       => 3,
			)
		);
		if ( ! is_multisite() ) {
			update_user_meta( $user_id, 'show_welcome_panel', 1 );
		} elseif ( ! is_super_admin( $user_id ) && ! metadata_exists( 'user', $user_id, 'show_welcome_panel' ) ) {
			update_user_meta( $user_id, 'show_welcome_panel', 2 );
		}

		if ( is_multisite() ) {
			// Flush rules to pick up the new page.
			$wp_rewrite->init();
			$wp_rewrite->flush_rules();

			$user = new WP_User( $user_id );
			$wpdb->update( $wpdb->options, array( 'option_value' => $user->user_email ), array( 'option_name' => 'admin_email' ) );

			// Remove all perms except for the login user.
			$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->usermeta WHERE user_id != %d AND meta_key = %s", $user_id, $table_prefix . 'user_level' ) );
			$wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->usermeta WHERE user_id != %d AND meta_key = %s", $user_id, $table_prefix . 'capabilities' ) );

			// Delete any caps that snuck into the previously active blog. (Hardcoded to blog 1 for now.)
			// TODO: Get previous_blog_id.
			if ( ! is_super_admin( $user_id ) && 1 != $user_id ) {
				$wpdb->delete(
					$wpdb->usermeta,
					array(
						'user_id'  => $user_id,
						'meta_key' => $wpdb->base_prefix . '1_capabilities',
					)
				);
			}
		}
	}
endif;
