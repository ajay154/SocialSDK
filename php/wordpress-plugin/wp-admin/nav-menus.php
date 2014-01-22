***REMOVED***
/**
 * WordPress Administration for Navigation Menus
 * Interface functions
 *
 * @version 2.0.0
 *
 * @package WordPress
 * @subpackage Administration
 */

/** Load WordPress Administration Bootstrap */
require_once( dirname( __FILE__ ) . '/admin.php' );

// Load all the nav menu interface functions
require_once( ABSPATH . 'wp-admin/includes/nav-menu.php' );

if ( ! current_theme_supports( 'menus' ) && ! current_theme_supports( 'widgets' ) )
	wp_die( __( 'Your theme does not support navigation menus or widgets.' ) );

// Permissions Check
if ( ! current_user_can('edit_theme_options') )
	wp_die( __( 'Cheatin&#8217; uh?' ) );

wp_enqueue_script( 'nav-menu' );

if ( wp_is_mobile() )
	wp_enqueue_script( 'jquery-touch-punch' );

// Container for any messages displayed to the user
$messages = array();

// Container that stores the name of the active menu
$nav_menu_selected_title = '';

// The menu id of the current menu being edited
$nav_menu_selected_id = isset( $_REQUEST['menu'] ) ? (int) $_REQUEST['menu'] : 0;

// Get existing menu locations assignments
$locations = get_registered_nav_menus();
$menu_locations = get_nav_menu_locations();
$num_locations = count( array_keys( $locations ) );

// Allowed actions: add, update, delete
$action = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : 'edit';

switch ( $action ) {
	case 'add-menu-item':
		check_admin_referer( 'add-menu_item', 'menu-settings-column-nonce' );
		if ( isset( $_REQUEST['nav-menu-locations'] ) )
			set_theme_mod( 'nav_menu_locations', array_map( 'absint', $_REQUEST['menu-locations'] ) );
		elseif ( isset( $_REQUEST['menu-item'] ) )
			wp_save_nav_menu_items( $nav_menu_selected_id, $_REQUEST['menu-item'] );
		break;
	case 'move-down-menu-item' :
		// moving down a menu item is the same as moving up the next in order
		check_admin_referer( 'move-menu_item' );
		$menu_item_id = isset( $_REQUEST['menu-item'] ) ? (int) $_REQUEST['menu-item'] : 0;
		if ( is_nav_menu_item( $menu_item_id ) ) {
			$menus = isset( $_REQUEST['menu'] ) ? array( (int) $_REQUEST['menu'] ) : wp_get_object_terms( $menu_item_id, 'nav_menu', array( 'fields' => 'ids' ) );
			if ( ! is_wp_error( $menus ) && ! empty( $menus[0] ) ) {
				$menu_id = (int) $menus[0];
				$ordered_menu_items = wp_get_nav_menu_items( $menu_id );
				$menu_item_data = (array) wp_setup_nav_menu_item( get_post( $menu_item_id ) );

				// set up the data we need in one pass through the array of menu items
				$dbids_to_orders = array();
				$orders_to_dbids = array();
				foreach( (array) $ordered_menu_items as $ordered_menu_item_object ) {
					if ( isset( $ordered_menu_item_object->ID ) ) {
						if ( isset( $ordered_menu_item_object->menu_order ) ) {
							$dbids_to_orders[$ordered_menu_item_object->ID] = $ordered_menu_item_object->menu_order;
							$orders_to_dbids[$ordered_menu_item_object->menu_order] = $ordered_menu_item_object->ID;
						}
					}
				}

				// get next in order
				if (
					isset( $orders_to_dbids[$dbids_to_orders[$menu_item_id] + 1] )
				) {
					$next_item_id = $orders_to_dbids[$dbids_to_orders[$menu_item_id] + 1];
					$next_item_data = (array) wp_setup_nav_menu_item( get_post( $next_item_id ) );

					// if not siblings of same parent, bubble menu item up but keep order
					if (
						! empty( $menu_item_data['menu_item_parent'] ) &&
						(
							empty( $next_item_data['menu_item_parent'] ) ||
							$next_item_data['menu_item_parent'] != $menu_item_data['menu_item_parent']
						)
					) {

						$parent_db_id = in_array( $menu_item_data['menu_item_parent'], $orders_to_dbids ) ? (int) $menu_item_data['menu_item_parent'] : 0;

						$parent_object = wp_setup_nav_menu_item( get_post( $parent_db_id ) );

						if ( ! is_wp_error( $parent_object ) ) {
							$parent_data = (array) $parent_object;
							$menu_item_data['menu_item_parent'] = $parent_data['menu_item_parent'];
							update_post_meta( $menu_item_data['ID'], '_menu_item_menu_item_parent', (int) $menu_item_data['menu_item_parent'] );

						}

					// make menu item a child of its next sibling
					} else {
						$next_item_data['menu_order'] = $next_item_data['menu_order'] - 1;
						$menu_item_data['menu_order'] = $menu_item_data['menu_order'] + 1;

						$menu_item_data['menu_item_parent'] = $next_item_data['ID'];
						update_post_meta( $menu_item_data['ID'], '_menu_item_menu_item_parent', (int) $menu_item_data['menu_item_parent'] );

						wp_update_post($menu_item_data);
						wp_update_post($next_item_data);
					}

				// the item is last but still has a parent, so bubble up
				} elseif (
					! empty( $menu_item_data['menu_item_parent'] ) &&
					in_array( $menu_item_data['menu_item_parent'], $orders_to_dbids )
				) {
					$menu_item_data['menu_item_parent'] = (int) get_post_meta( $menu_item_data['menu_item_parent'], '_menu_item_menu_item_parent', true);
					update_post_meta( $menu_item_data['ID'], '_menu_item_menu_item_parent', (int) $menu_item_data['menu_item_parent'] );
				}
			}
		}

		break;
	case 'move-up-menu-item' :
		check_admin_referer( 'move-menu_item' );
		$menu_item_id = isset( $_REQUEST['menu-item'] ) ? (int) $_REQUEST['menu-item'] : 0;
		if ( is_nav_menu_item( $menu_item_id ) ) {
			$menus = isset( $_REQUEST['menu'] ) ? array( (int) $_REQUEST['menu'] ) : wp_get_object_terms( $menu_item_id, 'nav_menu', array( 'fields' => 'ids' ) );
			if ( ! is_wp_error( $menus ) && ! empty( $menus[0] ) ) {
				$menu_id = (int) $menus[0];
				$ordered_menu_items = wp_get_nav_menu_items( $menu_id );
				$menu_item_data = (array) wp_setup_nav_menu_item( get_post( $menu_item_id ) );

				// set up the data we need in one pass through the array of menu items
				$dbids_to_orders = array();
				$orders_to_dbids = array();
				foreach( (array) $ordered_menu_items as $ordered_menu_item_object ) {
					if ( isset( $ordered_menu_item_object->ID ) ) {
						if ( isset( $ordered_menu_item_object->menu_order ) ) {
							$dbids_to_orders[$ordered_menu_item_object->ID] = $ordered_menu_item_object->menu_order;
							$orders_to_dbids[$ordered_menu_item_object->menu_order] = $ordered_menu_item_object->ID;
						}
					}
				}

				// if this menu item is not first
				if ( ! empty( $dbids_to_orders[$menu_item_id] ) && ! empty( $orders_to_dbids[$dbids_to_orders[$menu_item_id] - 1] ) ) {

					// if this menu item is a child of the previous
					if (
						! empty( $menu_item_data['menu_item_parent'] ) &&
						in_array( $menu_item_data['menu_item_parent'], array_keys( $dbids_to_orders ) ) &&
						isset( $orders_to_dbids[$dbids_to_orders[$menu_item_id] - 1] ) &&
						( $menu_item_data['menu_item_parent'] == $orders_to_dbids[$dbids_to_orders[$menu_item_id] - 1] )
					) {
						$parent_db_id = in_array( $menu_item_data['menu_item_parent'], $orders_to_dbids ) ? (int) $menu_item_data['menu_item_parent'] : 0;
						$parent_object = wp_setup_nav_menu_item( get_post( $parent_db_id ) );

						if ( ! is_wp_error( $parent_object ) ) {
							$parent_data = (array) $parent_object;

							// if there is something before the parent and parent a child of it, make menu item a child also of it
							if (
								! empty( $dbids_to_orders[$parent_db_id] ) &&
								! empty( $orders_to_dbids[$dbids_to_orders[$parent_db_id] - 1] ) &&
								! empty( $parent_data['menu_item_parent'] )
							) {
								$menu_item_data['menu_item_parent'] = $parent_data['menu_item_parent'];

							// else if there is something before parent and parent not a child of it, make menu item a child of that something's parent
							} elseif (
								! empty( $dbids_to_orders[$parent_db_id] ) &&
								! empty( $orders_to_dbids[$dbids_to_orders[$parent_db_id] - 1] )
							) {
								$_possible_parent_id = (int) get_post_meta( $orders_to_dbids[$dbids_to_orders[$parent_db_id] - 1], '_menu_item_menu_item_parent', true);
								if ( in_array( $_possible_parent_id, array_keys( $dbids_to_orders ) ) )
									$menu_item_data['menu_item_parent'] = $_possible_parent_id;
								else
									$menu_item_data['menu_item_parent'] = 0;

							// else there isn't something before the parent
							} else {
								$menu_item_data['menu_item_parent'] = 0;
							}

							// set former parent's [menu_order] to that of menu-item's
							$parent_data['menu_order'] = $parent_data['menu_order'] + 1;

							// set menu-item's [menu_order] to that of former parent
							$menu_item_data['menu_order'] = $menu_item_data['menu_order'] - 1;

							// save changes
							update_post_meta( $menu_item_data['ID'], '_menu_item_menu_item_parent', (int) $menu_item_data['menu_item_parent'] );
							wp_update_post($menu_item_data);
							wp_update_post($parent_data);
						}

					// else this menu item is not a child of the previous
					} elseif (
						empty( $menu_item_data['menu_order'] ) ||
						empty( $menu_item_data['menu_item_parent'] ) ||
						! in_array( $menu_item_data['menu_item_parent'], array_keys( $dbids_to_orders ) ) ||
						empty( $orders_to_dbids[$dbids_to_orders[$menu_item_id] - 1] ) ||
						$orders_to_dbids[$dbids_to_orders[$menu_item_id] - 1] != $menu_item_data['menu_item_parent']
					) {
						// just make it a child of the previous; keep the order
						$menu_item_data['menu_item_parent'] = (int) $orders_to_dbids[$dbids_to_orders[$menu_item_id] - 1];
						update_post_meta( $menu_item_data['ID'], '_menu_item_menu_item_parent', (int) $menu_item_data['menu_item_parent'] );
						wp_update_post($menu_item_data);
					}
				}
			}
		}
		break;

	case 'delete-menu-item':
		$menu_item_id = (int) $_REQUEST['menu-item'];

		check_admin_referer( 'delete-menu_item_' . $menu_item_id );

		if ( is_nav_menu_item( $menu_item_id ) && wp_delete_post( $menu_item_id, true ) )
			$messages[] = '<div id="message" class="updated"><p>' . __('The menu item has been successfully deleted.') . '</p></div>';
		break;

	case 'delete':
		check_admin_referer( 'delete-nav_menu-' . $nav_menu_selected_id );
		if ( is_nav_menu( $nav_menu_selected_id ) ) {
			$deletion = wp_delete_nav_menu( $nav_menu_selected_id );
		} else {
			// Reset the selected menu
			$nav_menu_selected_id = 0;
			unset( $_REQUEST['menu'] );
		}

		if ( ! isset( $deletion ) )
			break;

		if ( is_wp_error( $deletion ) )
			$messages[] = '<div id="message" class="error"><p>' . $deletion->get_error_message() . '</p></div>';
		else
			$messages[] = '<div id="message" class="updated"><p>' . __( 'The menu has been successfully deleted.' ) . '</p></div>';
		break;

	case 'delete_menus':
		check_admin_referer( 'nav_menus_bulk_actions' );
		foreach ( $_REQUEST['delete_menus'] as $menu_id_to_delete ) {
			if ( ! is_nav_menu( $menu_id_to_delete ) )
				continue;

			$deletion = wp_delete_nav_menu( $menu_id_to_delete );
			if ( is_wp_error( $deletion ) ) {
				$messages[] = '<div id="message" class="error"><p>' . $deletion->get_error_message() . '</p></div>';
				$deletion_error = true;
			}
		}

		if ( empty( $deletion_error ) )
			$messages[] = '<div id="message" class="updated"><p>' . __( 'Selected menus have been successfully deleted.' ) . '</p></div>';
		break;

	case 'update':
		check_admin_referer( 'update-nav_menu', 'update-nav-menu-nonce' );

		// Remove menu locations that have been unchecked
		foreach ( $locations as $location => $description ) {
			if ( ( empty( $_POST['menu-locations'] ) || empty( $_POST['menu-locations'][ $location ] ) ) && isset( $menu_locations[ $location ] ) && $menu_locations[ $location ] == $nav_menu_selected_id )
				unset( $menu_locations[ $location ] );
		}

		// Merge new and existing menu locations if any new ones are set
		if ( isset( $_POST['menu-locations'] ) ) {
			$new_menu_locations = array_map( 'absint', $_POST['menu-locations'] );
			$menu_locations = array_merge( $menu_locations, $new_menu_locations );
		}

		// Set menu locations
		set_theme_mod( 'nav_menu_locations', $menu_locations );

		// Add Menu
		if ( 0 == $nav_menu_selected_id ) {
			$new_menu_title = trim( esc_html( $_POST['menu-name'] ) );

			if ( $new_menu_title ) {
				$_nav_menu_selected_id = wp_update_nav_menu_object( 0, array('menu-name' => $new_menu_title) );

				if ( is_wp_error( $_nav_menu_selected_id ) ) {
					$messages[] = '<div id="message" class="error"><p>' . $_nav_menu_selected_id->get_error_message() . '</p></div>';
				} else {
					$_menu_object = wp_get_nav_menu_object( $_nav_menu_selected_id );
					$nav_menu_selected_id = $_nav_menu_selected_id;
					$nav_menu_selected_title = $_menu_object->name;
					if ( isset( $_REQUEST['menu-item'] ) )
						wp_save_nav_menu_items( $nav_menu_selected_id, absint( $_REQUEST['menu-item'] ) );
					if ( isset( $_REQUEST['zero-menu-state'] ) ) {
						// If there are menu items, add them
						wp_nav_menu_update_menu_items( $nav_menu_selected_id, $nav_menu_selected_title );
						// Auto-save nav_menu_locations
						$locations = get_nav_menu_locations();
						foreach ( $locations as $location => $menu_id ) {
								$locations[ $location ] = $nav_menu_selected_id;
								break; // There should only be 1
						}
						set_theme_mod( 'nav_menu_locations', $locations );
					}
					if ( isset( $_REQUEST['use-location'] ) ) {
						$locations = get_registered_nav_menus();
						$menu_locations = get_nav_menu_locations();
						if ( isset( $locations[ $_REQUEST['use-location'] ] ) )
							$menu_locations[ $_REQUEST['use-location'] ] = $nav_menu_selected_id;
						set_theme_mod( 'nav_menu_locations', $menu_locations );
					}
					// $messages[] = '<div id="message" class="updated"><p>' . sprintf( __( '<strong>%s</strong> has been created.' ), $nav_menu_selected_title ) . '</p></div>';
					wp_redirect( admin_url( 'nav-menus.php?menu=' . $_nav_menu_selected_id ) );
					exit();
				}
			} else {
				$messages[] = '<div id="message" class="error"><p>' . __( 'Please enter a valid menu name.' ) . '</p></div>';
			}

		// Update existing menu
		} else {

			$_menu_object = wp_get_nav_menu_object( $nav_menu_selected_id );

			$menu_title = trim( esc_html( $_POST['menu-name'] ) );
			if ( ! $menu_title ) {
				$messages[] = '<div id="message" class="error"><p>' . __( 'Please enter a valid menu name.' ) . '</p></div>';
				$menu_title = $_menu_object->name;
			}

			if ( ! is_wp_error( $_menu_object ) ) {
				$_nav_menu_selected_id = wp_update_nav_menu_object( $nav_menu_selected_id, array( 'menu-name' => $menu_title ) );
				if ( is_wp_error( $_nav_menu_selected_id ) ) {
					$_menu_object = $_nav_menu_selected_id;
					$messages[] = '<div id="message" class="error"><p>' . $_nav_menu_selected_id->get_error_message() . '</p></div>';
				} else {
					$_menu_object = wp_get_nav_menu_object( $_nav_menu_selected_id );
					$nav_menu_selected_title = $_menu_object->name;
				}
			}

			// Update menu items
			if ( ! is_wp_error( $_menu_object ) ) {
				$messages = array_merge( $messages, wp_nav_menu_update_menu_items( $nav_menu_selected_id, $nav_menu_selected_title ) );
			}
		}
		break;
	case 'locations':
		if ( ! $num_locations ) {
			wp_redirect( admin_url( 'nav-menus.php' ) );
			exit();
		}

		add_filter( 'screen_options_show_screen', '__return_false' );

		if ( isset( $_POST['menu-locations'] ) ) {
			check_admin_referer( 'save-menu-locations' );

			$new_menu_locations = array_map( 'absint', $_POST['menu-locations'] );
			$menu_locations = array_merge( $menu_locations, $new_menu_locations );
			// Set menu locations
			set_theme_mod( 'nav_menu_locations', $menu_locations );

			$messages[] = '<div id="message" class="updated"><p>' . __( 'Menu locations updated.' ) . '</p></div>';
		}
		break;
}

// Get all nav menus
$nav_menus = wp_get_nav_menus( array('orderby' => 'name') );
$menu_count = count( $nav_menus );

// Are we on the add new screen?
$add_new_screen = ( isset( $_GET['menu'] ) && 0 == $_GET['menu'] ) ? true : false;

$locations_screen = ( isset( $_GET['action'] ) && 'locations' == $_GET['action'] ) ? true : false;

// If we have one theme location, and zero menus, we take them right into editing their first menu
$page_count = wp_count_posts( 'page' );
$one_theme_location_no_menus = ( 1 == count( get_registered_nav_menus() ) && ! $add_new_screen && empty( $nav_menus ) && ! empty( $page_count->publish ) ) ? true : false;

$nav_menus_l10n = array(
	'oneThemeLocationNoMenus' => $one_theme_location_no_menus,
	'moveUp'       => __( 'Move up one' ),
	'moveDown'     => __( 'Move down one' ),
	'moveToTop'    => __( 'Move to the top' ),
	/* translators: %s: previous item name */
	'moveUnder'    => __( 'Move under %s' ),
	/* translators: %s: previous item name */
	'moveOutFrom'  => __( 'Move out from under %s' ),
	/* translators: %s: previous item name */
	'under'        => __( 'Under %s' ),
	/* translators: %s: previous item name */
	'outFrom'      => __( 'Out from under %s' ),
	/* translators: 1: item name, 2: item position, 3: total number of items */
	'menuFocus'    => __( '%1$s. Menu item %2$d of %3$d.' ),
	/* translators: 1: item name, 2: item position, 3: parent item name */
	'subMenuFocus' => __( '%1$s. Sub item number %2$d under %3$s.' ),
);
wp_localize_script( 'nav-menu', 'menus', $nav_menus_l10n );

// Redirect to add screen if there are no menus and this users has either zero, or more than 1 theme locations
if ( 0 == $menu_count && ! $add_new_screen && ! $one_theme_location_no_menus )
	wp_redirect( admin_url( 'nav-menus.php?action=edit&menu=0' ) );

// Get recently edited nav menu
$recently_edited = absint( get_user_option( 'nav_menu_recently_edited' ) );
if ( empty( $recently_edited ) && is_nav_menu( $nav_menu_selected_id ) )
	$recently_edited = $nav_menu_selected_id;

// Use $recently_edited if none are selected
if ( empty( $nav_menu_selected_id ) && ! isset( $_GET['menu'] ) && is_nav_menu( $recently_edited ) )
	$nav_menu_selected_id = $recently_edited;

// On deletion of menu, if another menu exists, show it
if ( ! $add_new_screen && 0 < $menu_count && isset( $_GET['action'] ) && 'delete' == $_GET['action'] )
	$nav_menu_selected_id = $nav_menus[0]->term_id;

// Set $nav_menu_selected_id to 0 if no menus
if ( $one_theme_location_no_menus ) {
	$nav_menu_selected_id = 0;
} elseif ( empty( $nav_menu_selected_id ) && ! empty( $nav_menus ) && ! $add_new_screen ) {
	// if we have no selection yet, and we have menus, set to the first one in the list
	$nav_menu_selected_id = $nav_menus[0]->term_id;
}

// Update the user's setting
if ( $nav_menu_selected_id != $recently_edited && is_nav_menu( $nav_menu_selected_id ) )
	update_user_meta( $current_user->ID, 'nav_menu_recently_edited', $nav_menu_selected_id );

// If there's a menu, get its name.
if ( ! $nav_menu_selected_title && is_nav_menu( $nav_menu_selected_id ) ) {
	$_menu_object = wp_get_nav_menu_object( $nav_menu_selected_id );
	$nav_menu_selected_title = ! is_wp_error( $_menu_object ) ? $_menu_object->name : '';
}

// Generate truncated menu names
foreach( (array) $nav_menus as $key => $_nav_menu ) {
	$nav_menus[$key]->truncated_name = wp_html_excerpt( $_nav_menu->name, 40, '&hellip;' );
}

// Retrieve menu locations
if ( current_theme_supports( 'menus' ) ) {
	$locations = get_registered_nav_menus();
	$menu_locations = get_nav_menu_locations();
}

// Ensure the user will be able to scroll horizontally
// by adding a class for the max menu depth.
global $_wp_nav_menu_max_depth;
$_wp_nav_menu_max_depth = 0;

// Calling wp_get_nav_menu_to_edit generates $_wp_nav_menu_max_depth
if ( is_nav_menu( $nav_menu_selected_id ) ) {
	$menu_items = wp_get_nav_menu_items( $nav_menu_selected_id, array( 'post_status' => 'any' ) );
	$edit_markup = wp_get_nav_menu_to_edit( $nav_menu_selected_id );
}

function wp_nav_menu_max_depth($classes) {
	global $_wp_nav_menu_max_depth;
	return "$classes menu-max-depth-$_wp_nav_menu_max_depth";
}

add_filter('admin_body_class', 'wp_nav_menu_max_depth');

wp_nav_menu_setup();
wp_initial_nav_menu_meta_boxes();

if ( ! current_theme_supports( 'menus' ) && ! $num_locations )
	$messages[] = '<div id="message" class="updated"><p>' . sprintf( __( 'Your theme does not natively support menus, but you can use them in sidebars by adding a &#8220;Custom Menu&#8221; widget on the <a href="%s">Widgets</a> screen.' ), admin_url( 'widgets.php' ) ) . '</p></div>';

if ( ! $locations_screen ) : // Main tab
	$overview  = '<p>' . __( 'This screen is used for managing your custom navigation menus.' ) . '</p>';
	$overview .= '<p>' . sprintf( __( 'Menus can be displayed in locations defined by your theme, even used in sidebars by adding a &#8220;Custom Menu&#8221; widget on the <a href="%1$s">Widgets</a> screen. If your theme does not support the custom menus feature (the default themes, %2$s and %3$s, do), you can learn about adding this support by following the Documentation link to the side.' ), admin_url( 'widgets.php' ), 'Twenty Thirteen', 'Twenty Twelve' ) . '</p>';
	$overview .= '<p>' . __( 'From this screen you can:' ) . '</p>';
	$overview .= '<ul><li>' . __( 'Create, edit, and delete menus' ) . '</li>';
	$overview .= '<li>' . __( 'Add, organize, and modify individual menu items' ) . '</li></ul>';

	get_current_screen()->add_help_tab( array(
		'id'      => 'overview',
		'title'   => __( 'Overview' ),
		'content' => $overview
	) );

	$menu_management  = '<p>' . __( 'The menu management box at the top of the screen is used to control which menu is opened in the editor below.' ) . '</p>';
	$menu_management .= '<ul><li>' . __( 'To edit an existing menu, <strong>choose a menu from the drop down and click Select</strong>' ) . '</li>';
	$menu_management .= '<li>' . __( 'If you haven&#8217;t yet created any menus, <strong>click the &#8217;create a new menu&#8217; link</strong> to get started' ) . '</li></ul>';
	$menu_management .= '<p>' . __( 'You can assign theme locations to individual menus by <strong>selecting the desired settings</strong> at the bottom of the menu editor. To assign menus to all theme locations at once, <strong>visit the Manage Locations tab</strong> at the top of the screen.' ) . '</p>';

	get_current_screen()->add_help_tab( array(
		'id'      => 'menu-management',
		'title'   => __( 'Menu Management' ),
		'content' => $menu_management
	) );

	$editing_menus  = '<p>' . __( 'Each custom menu may contain a mix of links to pages, categories, custom URLs or other content types. Menu links are added by selecting items from the expanding boxes in the left-hand column below.' ) . '</p>';
	$editing_menus .= '<p>' . __( '<strong>Clicking the arrow to the right of any menu item</strong> in the editor will reveal a standard group of settings. Additional settings such as link target, CSS classes, link relationships, and link descriptions can be enabled and disabled via the Screen Options tab.' ) . '</p>';
	$editing_menus .= '<ul><li>' . __( 'Add one or several items at once by <strong>selecting the checkbox next to each item and clicking Add to Menu</strong>' ) . '</li>';
	$editing_menus .= '<li>' . __( 'To add a custom link, <strong>expand the Links section, enter a URL and link text, and click Add to Menu</strong>' ) .'</li>';
	$editing_menus .= '<li>' . __( 'To reorganize menu items, <strong>drag and drop items with your mouse or use your keyboard</strong>. Drag or move a menu item a little to the right to make it a submenu' ) . '</li>';
	$editing_menus .= '<li>' . __( 'Delete a menu item by <strong>expanding it and clicking the Remove link</strong>' ) . '</li></ul>';

	get_current_screen()->add_help_tab( array(
		'id'      => 'editing-menus',
		'title'   => __( 'Editing Menus' ),
		'content' => $editing_menus
	) );
else : // Locations Tab
	$locations_overview  = '<p>' . __( 'This screen is used for globally assigning menus to locations defined by your theme.' ) . '</p>';
	$locations_overview .= '<ul><li>' . __( 'To assign menus to one or more theme locations, <strong>select a menu from each location&#8217;s drop down.</strong> When you&#8217;re finished, <strong>click Save Changes</strong>' ) . '</li>';
	$locations_overview .= '<li>' . __( 'To edit a menu currently assigned to a theme location, <strong>click the adjacent &#8217;Edit&#8217; link</strong>' ) . '</li>';
	$locations_overview .= '<li>' . __( 'To add a new menu instead of assigning an existing one, <strong>click the &#8217;Use new menu&#8217; link</strong>. Your new menu will be automatically assigned to that theme location' ) . '</li></ul>';

	get_current_screen()->add_help_tab( array(
		'id'      => 'locations-overview',
		'title'   => __( 'Overview' ),
		'content' => $locations_overview
	) );
endif;

get_current_screen()->set_help_sidebar(
	'<p><strong>' . __('For more information:') . '</strong></p>' .
	'<p>' . __('<a href="http://codex.wordpress.org/Appearance_Menus_Screen" target="_blank">Documentation on Menus</a>') . '</p>' .
	'<p>' . __('<a href="http://wordpress.org/support/" target="_blank">Support Forums</a>') . '</p>'
);

// Get the admin header
require_once( ABSPATH . 'wp-admin/admin-header.php' );
?>
<div class="wrap">
	***REMOVED*** screen_icon(); ?>
	<h2 class="nav-tab-wrapper">
		<a href="***REMOVED*** echo admin_url( 'nav-menus.php' ); ?>" class="nav-tab***REMOVED*** if ( ! isset( $_GET['action'] ) || isset( $_GET['action'] ) && 'locations' != $_GET['action'] ) echo ' nav-tab-active'; ?>">***REMOVED*** esc_html_e( 'Edit Menus' ); ?></a>
		***REMOVED*** if ( $num_locations && $menu_count ) : ?>
			<a href="***REMOVED*** echo esc_url( add_query_arg( array( 'action' => 'locations' ), admin_url( 'nav-menus.php' ) ) ); ?>" class="nav-tab***REMOVED*** if ( $locations_screen ) echo ' nav-tab-active'; ?>">***REMOVED*** esc_html_e( 'Manage Locations' ); ?></a>
		***REMOVED*** endif; ?>
	</h2>
	***REMOVED***
	foreach( $messages as $message ) :
		echo $message . "\n";
	endforeach;
	?>
	***REMOVED***
	if ( $locations_screen ) :
		echo '<p>' . sprintf( _n( 'Your theme supports %s menu. Select which menu you would like to use.', 'Your theme supports %s menus. Select which menu appears in each location.', $num_locations ), number_format_i18n( $num_locations ) ) . '</p>';
	?>
	<div id="menu-locations-wrap">
		<form method="post" action="***REMOVED*** echo esc_url( add_query_arg( array( 'action' => 'locations' ), admin_url( 'nav-menus.php' ) ) ); ?>">
			<table class="widefat fixed" cellspacing="0" id="menu-locations-table">
				<thead>
				<tr>
					<th scope="col" class="manage-column column-locations">***REMOVED*** _e( 'Theme Location' ); ?></th>
					<th scope="col" class="manage-column column-menus">***REMOVED*** _e( 'Assigned Menu' ); ?></th>
				</tr>
				</thead>
				<!--<tfoot>
				<tr>
					<th scope="col" class="manage-column column-locations">***REMOVED*** _e( 'Theme Location' ); ?></th>
					<th scope="col" class="manage-column column-menus">***REMOVED*** _e( 'Assigned Menu' ); ?></th>
				</tr>
				</tfoot>-->
				<tbody class="menu-locations">
				***REMOVED*** foreach ( $locations as $_location => $_name ) { ?>
					<tr id="menu-locations-row">
						<td class="menu-location-title"><strong>***REMOVED*** echo $_name; ?></strong></td>
						<td class="menu-location-menus">
							<select name="menu-locations[***REMOVED*** echo $_location; ?>]" id="locations-***REMOVED*** echo $_location; ?>">
								<option value="0">***REMOVED*** printf( '&mdash; %s &mdash;', esc_html__( 'Select a Menu' ) ); ?></option>
								***REMOVED*** foreach ( $nav_menus as $menu ) : ?>
									***REMOVED*** $selected = isset( $menu_locations[$_location] ) && $menu_locations[$_location] == $menu->term_id; ?>
									<option ***REMOVED*** if ( $selected ) echo 'data-orig="true"'; ?> ***REMOVED*** selected( $selected ); ?> value="***REMOVED*** echo $menu->term_id; ?>">
										***REMOVED*** echo wp_html_excerpt( $menu->name, 40, '&hellip;' ); ?>
									</option>
								***REMOVED*** endforeach; ?>
							</select>
							<div class="locations-row-links">
								***REMOVED*** if ( isset( $menu_locations[ $_location ] ) && 0 != $menu_locations[ $_location ] ) : ?>
								<span class="locations-edit-menu-link">
									<a href="***REMOVED*** echo esc_url( add_query_arg( array( 'action' => 'edit', 'menu' => $menu_locations[$_location] ), admin_url( 'nav-menus.php' ) ) ); ?>">
										***REMOVED*** _ex( 'Edit', 'menu' ); ?>
									</a>
								</span>
								***REMOVED*** endif; ?>
								<span class="locations-add-menu-link">
									<a href="***REMOVED*** echo esc_url( add_query_arg( array( 'action' => 'edit', 'menu' => 0, 'use-location' => $_location ), admin_url( 'nav-menus.php' ) ) ); ?>">
										***REMOVED*** _ex( 'Use new menu', 'menu' ); ?>
									</a>
								</span>
							</div><!-- #locations-row-links -->
						</td><!-- .menu-location-menus -->
					</tr><!-- #menu-locations-row -->
				***REMOVED*** } // foreach ?>
				</tbody>
			</table>
			<p class="button-controls">***REMOVED*** submit_button( __( 'Save Changes' ), 'primary left', 'nav-menu-locations', false ); ?></p>
			***REMOVED*** wp_nonce_field( 'save-menu-locations' ); ?>
			<input type="hidden" name="menu" id="nav-menu-meta-object-id" value="***REMOVED*** echo esc_attr( $nav_menu_selected_id ); ?>" />
		</form>
	</div><!-- #menu-locations-wrap -->
	***REMOVED*** do_action( 'after_menu_locations_table' ); ?>
	***REMOVED*** else : ?>
	<div class="manage-menus">
 		***REMOVED*** if ( $menu_count < 2 ) : ?>
		<span class="add-edit-menu-action">
			***REMOVED*** printf( __( 'Edit your menu below, or <a href="%s">create a new menu</a>.' ), esc_url( add_query_arg( array( 'action' => 'edit', 'menu' => 0 ), admin_url( 'nav-menus.php' ) ) ) ); ?>
		</span><!-- /add-edit-menu-action -->
		***REMOVED*** else : ?>
			<form method="get" action="***REMOVED*** echo admin_url( 'nav-menus.php' ); ?>">
			<input type="hidden" name="action" value="edit" />
			<label for="menu" class="selected-menu">***REMOVED*** _e( 'Select a menu to edit:' ); ?></label>
			<select name="menu" id="menu">
				***REMOVED*** if ( $add_new_screen ) : ?>
					<option value="0" selected="selected">***REMOVED*** _e( '-- Select --' ); ?></option>
				***REMOVED*** endif; ?>
				***REMOVED*** foreach( (array) $nav_menus as $_nav_menu ) : ?>
					<option value="***REMOVED*** echo esc_attr( $_nav_menu->term_id ); ?>" ***REMOVED*** selected( $_nav_menu->term_id, $nav_menu_selected_id ); ?>>
						***REMOVED***
						echo esc_html( $_nav_menu->truncated_name ) ;

						if ( ! empty( $menu_locations ) && in_array( $_nav_menu->term_id, $menu_locations ) ) {
							$locations_assigned_to_this_menu = array();
							foreach ( array_keys( $menu_locations, $_nav_menu->term_id ) as $menu_location_key ) {
								 $locations_assigned_to_this_menu[] = $locations[ $menu_location_key ];
							}
							$assigned_locations = array_slice( $locations_assigned_to_this_menu, 0, absint( apply_filters( 'wp_nav_locations_listed_per_menu', 3 ) ) );

							// Adds ellipses following the number of locations defined in $assigned_locations
							printf( ' (%1$s%2$s)',
								implode( ', ', $assigned_locations ),
								count( $locations_assigned_to_this_menu ) > count( $assigned_locations ) ? ' &hellip;' : ''
							);
						}
						?>
					</option>
				***REMOVED*** endforeach; ?>
			</select>
			<span class="submit-btn"><input type="submit" class="button-secondary" value="***REMOVED*** _e( 'Select' ); ?>"></span>
			<span class="add-new-menu-action">
				***REMOVED*** printf( __( 'or <a href="%s">create a new menu</a>.' ), esc_url( add_query_arg( array( 'action' => 'edit', 'menu' => 0 ), admin_url( 'nav-menus.php' ) ) ) ); ?>
			</span><!-- /add-new-menu-action -->
		</form>
	***REMOVED*** endif; ?>
	</div><!-- /manage-menus -->
	<div id="nav-menus-frame">
	<div id="menu-settings-column" class="metabox-holder***REMOVED*** if ( isset( $_GET['menu'] ) && '0' == $_GET['menu'] ) { echo ' metabox-holder-disabled'; } ?>">

		<div class="clear"></div>

		<form id="nav-menu-meta" action="" class="nav-menu-meta" method="post" enctype="multipart/form-data">
			<input type="hidden" name="menu" id="nav-menu-meta-object-id" value="***REMOVED*** echo esc_attr( $nav_menu_selected_id ); ?>" />
			<input type="hidden" name="action" value="add-menu-item" />
			***REMOVED*** wp_nonce_field( 'add-menu_item', 'menu-settings-column-nonce' ); ?>
			***REMOVED*** do_accordion_sections( 'nav-menus', 'side', null ); ?>
		</form>

	</div><!-- /#menu-settings-column -->
	<div id="menu-management-liquid">
		<div id="menu-management">
			<form id="update-nav-menu" action="" method="post" enctype="multipart/form-data">
				<div class="menu-edit ***REMOVED*** if ( $add_new_screen ) echo 'blank-slate'; ?>">
					***REMOVED***
					wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
					wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
					wp_nonce_field( 'update-nav_menu', 'update-nav-menu-nonce' );

					if ( $one_theme_location_no_menus ) { ?>
						<input type="hidden" name="zero-menu-state" value="true" />
					***REMOVED*** } ?>
 					<input type="hidden" name="action" value="update" />
					<input type="hidden" name="menu" id="menu" value="***REMOVED*** echo esc_attr( $nav_menu_selected_id ); ?>" />
					<div id="nav-menu-header">
						<div class="major-publishing-actions">
							<label class="menu-name-label howto open-label" for="menu-name">
								<span>***REMOVED*** _e( 'Menu Name' ); ?></span>
								<input name="menu-name" id="menu-name" type="text" class="menu-name regular-text menu-item-textbox input-with-default-title" title="***REMOVED*** esc_attr_e( 'Enter menu name here' ); ?>" value="***REMOVED*** if ( $one_theme_location_no_menus ) _e( 'Menu 1' ); else echo esc_attr( $nav_menu_selected_title ); ?>" />
							</label>
							<div class="publishing-action">
								***REMOVED*** submit_button( empty( $nav_menu_selected_id ) ? __( 'Create Menu' ) : __( 'Save Menu' ), 'button-primary menu-save', 'save_menu', false, array( 'id' => 'save_menu_header' ) ); ?>
							</div><!-- END .publishing-action -->
						</div><!-- END .major-publishing-actions -->
					</div><!-- END .nav-menu-header -->
					<div id="post-body">
						<div id="post-body-content">
							***REMOVED*** if ( ! $add_new_screen ) : ?>
							<h3>***REMOVED*** _e( 'Menu Structure' ); ?></h3>
							***REMOVED*** $starter_copy = ( $one_theme_location_no_menus ) ? __( 'Edit your default menu by adding or removing items. Drag each item into the order you prefer. Click Create Menu to save your changes.' ) : __( 'Drag each item into the order you prefer. Click the arrow on the right of the item to reveal additional configuration options.' ); ?>
							<div class="drag-instructions post-body-plain" ***REMOVED*** if ( isset( $menu_items ) && 0 == count( $menu_items ) ) { ?>style="display: none;"***REMOVED*** } ?>>
								<p>***REMOVED*** echo $starter_copy; ?></p>
							</div>
							***REMOVED***
							if ( isset( $edit_markup ) && ! is_wp_error( $edit_markup ) ) {
								echo $edit_markup;
							} else {
							?>
							<ul class="menu" id="menu-to-edit"></ul>
							***REMOVED*** } ?>
							***REMOVED*** endif; ?>
							***REMOVED*** if ( $add_new_screen ) : ?>
								<p class="post-body-plain">***REMOVED*** _e( 'Give your menu a name above, then click Create Menu.' ); ?></p>
								***REMOVED*** if ( isset( $_GET['use-location'] ) ) : ?>
									<input type="hidden" name="use-location" value="***REMOVED*** echo esc_attr( $_GET['use-location'] ); ?>" />
								***REMOVED*** endif; ?>
							***REMOVED*** endif; ?>
							<div class="menu-settings" ***REMOVED*** if ( $one_theme_location_no_menus ) { ?>style="display: none;"***REMOVED*** } ?>>
								<h3>***REMOVED*** _e( 'Menu Settings' ); ?></h3>
								***REMOVED***
								if ( ! isset( $auto_add ) ) {
									$auto_add = get_option( 'nav_menu_options' );
									if ( ! isset( $auto_add['auto_add'] ) )
										$auto_add = false;
									elseif ( false !== array_search( $nav_menu_selected_id, $auto_add['auto_add'] ) )
										$auto_add = true;
									else
										$auto_add = false;
								} ?>

								<dl class="auto-add-pages">
									<dt class="howto">***REMOVED*** _e( 'Auto add pages' ); ?></dt>
									<dd class="checkbox-input"><input type="checkbox"***REMOVED*** checked( $auto_add ); ?> name="auto-add-pages" id="auto-add-pages" value="1" /> <label for="auto-add-pages">***REMOVED*** printf( __('Automatically add new top-level pages to this menu' ), esc_url( admin_url( 'edit.php?post_type=page' ) ) ); ?></label></dd>
								</dl>

								***REMOVED*** if ( current_theme_supports( 'menus' ) ) : ?>

									<dl class="menu-theme-locations">
										<dt class="howto">***REMOVED*** _e( 'Theme locations' ); ?></dt>
										***REMOVED*** foreach ( $locations as $location => $description ) : ?>
										<dd class="checkbox-input">
											<input type="checkbox"***REMOVED*** checked( isset( $menu_locations[ $location ] ) && $menu_locations[ $location ] == $nav_menu_selected_id ); ?> name="menu-locations[***REMOVED*** echo esc_attr( $location ); ?>]" id="locations-***REMOVED*** echo esc_attr( $location ); ?>" value="***REMOVED*** echo esc_attr( $nav_menu_selected_id ); ?>" /> <label for="locations-***REMOVED*** echo esc_attr( $location ); ?>">***REMOVED*** echo $description; ?></label>
											***REMOVED*** if ( ! empty( $menu_locations[ $location ] ) && $menu_locations[ $location ] != $nav_menu_selected_id ) : ?>
											<span class="theme-location-set"> ***REMOVED*** printf( __( "(Currently set to: %s)" ), wp_get_nav_menu_object( $menu_locations[ $location ] )->name ); ?> </span>
											***REMOVED*** endif; ?>
										</dd>
										***REMOVED*** endforeach; ?>
									</dl>

								***REMOVED*** endif; ?>

							</div>
						</div><!-- /#post-body-content -->
					</div><!-- /#post-body -->
					<div id="nav-menu-footer">
						<div class="major-publishing-actions">
							***REMOVED*** if ( 0 != $menu_count && ! $add_new_screen ) : ?>
							<span class="delete-action">
								<a class="submitdelete deletion menu-delete" href="***REMOVED*** echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'delete', 'menu' => $nav_menu_selected_id, admin_url() ) ), 'delete-nav_menu-' . $nav_menu_selected_id) ); ?>">***REMOVED*** _e('Delete Menu'); ?></a>
							</span><!-- END .delete-action -->
							***REMOVED*** endif; ?>
							<div class="publishing-action">
								***REMOVED*** submit_button( empty( $nav_menu_selected_id ) ? __( 'Create Menu' ) : __( 'Save Menu' ), 'button-primary menu-save', 'save_menu', false, array( 'id' => 'save_menu_header' ) ); ?>
							</div><!-- END .publishing-action -->
						</div><!-- END .major-publishing-actions -->
					</div><!-- /#nav-menu-footer -->
				</div><!-- /.menu-edit -->
			</form><!-- /#update-nav-menu -->
		</div><!-- /#menu-management -->
	</div><!-- /#menu-management-liquid -->
	</div><!-- /#nav-menus-frame -->
	***REMOVED*** endif; ?>
</div><!-- /.wrap-->
***REMOVED*** include( ABSPATH . 'wp-admin/admin-footer.php' ); ?>
