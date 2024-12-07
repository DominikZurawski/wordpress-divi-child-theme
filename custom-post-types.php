<?php
function create_realizations_post_type() {
    $labels = array(
        'name' => 'Realizacje',
        'singular_name' => 'Realizacja',
        'add_new' => 'Dodaj nową',
        'add_new_item' => 'Dodaj nową realizację',
        'edit_item' => 'Edytuj realizację',
        'new_item' => 'Nowa realizacja',
        'view_item' => 'Zobacz realizację',
        'search_items' => 'Szukaj realizacji',
        'not_found' => 'Nie znaleziono realizacji',
        'not_found_in_trash' => 'Brak realizacji w koszu',
        'parent_item_colon' => '',
        'menu_name' => 'Realizacje'
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'rewrite' => array('slug' => 'realizacje'),
        'supports' => array('title', 'editor', 'thumbnail'),
        'publicly_queryable' => true,
        'taxonomies' => array('realization_type'),
    );

    register_post_type('realizations', $args);
}
add_action('init', 'create_realizations_post_type');

function realizations_custom_query($query) {
    if (!is_admin() && $query->is_main_query() && is_post_type_archive('realizations')) {
        $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
        $query->set('post_type', 'realizations');
        $query->set('posts_per_page', 4);
        $query->set('paged', $paged);
    }
}
add_action('pre_get_posts', 'realizations_custom_query');

function custom_realizations_loop($atts = array()) {
    // Pobierz parametr typu realizacji lub ustaw domyślną wartość
    $atts = shortcode_atts(array(
        'type' => '' // domyślnie pusty - pokaże wszystkie realizacje
    ), $atts);

    $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
    $args = array(
        'post_type' => 'realizations',
        'posts_per_page' => 8,
        'paged' => $paged,
    );

    // taxanomy filter
    if (!empty($atts['type'])) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'realization_type',
                'field'    => 'slug',
                'terms'    => $atts['type']
            )
        );
    }

    $wp_query = new WP_Query($args);
    ob_start();

    if ($wp_query->have_posts()) :
        echo '<div class="custom-post-list">';
        while ($wp_query->have_posts()) : $wp_query->the_post();
            $post_link = get_permalink();
            echo '<div class="post-item">';
            echo '<a href="' . esc_url($post_link) . '">';
            if (has_post_thumbnail()) {
                echo '<div class="post-thumbnail">' . get_the_post_thumbnail() . '</div>';
            }
            echo '<h2 class="post-title">' . get_the_title() . '</h2>';
            echo '<div class="post-date">' . get_the_date() . '</div>';
            echo '</a>';
            echo '</div>';
        endwhile;
        echo '</div>';
        echo '<div class="custom-pagination">';
        echo paginate_links(array(
            'base'    => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
            'format'  => '?paged=%#%',
            'current' => max(1, get_query_var('paged')),
            'total'   => $wp_query->max_num_pages,
            'prev_text' => __('« Poprzednia'),
            'next_text' => __('Następna »'),
        ));
        echo '</div>';
    else :
        echo '<p>Brak realizacji.</p>';
    endif;

    wp_reset_postdata();
    return ob_get_clean();
}

// Register shortcodes
function register_realization_shortcodes() {
    add_shortcode('custom_realizations', 'custom_realizations_loop');

    // Shortcode for stairs
    add_shortcode('custom_realizations_stairs', function($atts) {
        return custom_realizations_loop(array('type' => 'schody'));
    });
for gates
    add_shortcode('custom_realizations_gates', function($atts) {
        return custom_realizations_loop(array('type' => 'bramy'));
    });

    // Shortcode for others
    add_shortcode('custom_realizations_other', function($atts) {
        return custom_realizations_loop(array('type' => 'pozostale-realizacje'));
    });
}
add_action('init', 'register_realization_shortcodes');

function display_realization_details($atts) {
    global $post;
    ob_start();

    // get post metadata
    $post_id = get_the_ID();

    // get gallery images from metabox
    $gallery_images_string = get_post_meta($post_id, '_realizations_gallery', true);
    $gallery_images = !empty($gallery_images_string) ? explode(',', $gallery_images_string) : array();

    // get post content and extract images
    $content = apply_filters('the_content', get_the_content());
    preg_match_all('/<img[^>]+>/i', $content, $images);
    $text_content = preg_replace('/<img[^>]+>/i', '', $content);

    // add featured image as first image in gallery if exists
    if (has_post_thumbnail()) {
        $featured_image_id = get_post_thumbnail_id($post_id);
        $featured_image_url = wp_get_attachment_image_src($featured_image_id, 'large')[0];
        if (!in_array($featured_image_id, $gallery_images)) {
            array_unshift($gallery_images, $featured_image_id);
        }
    }

    // display post content
    if (is_singular('realizations')) {
        echo '<div class="realization-details">';

        // post title
        echo '<h1 class="realization-title">' . get_the_title() . '</h1>';

        // post date
        echo '<div class="realization-date">' . get_the_date() . '</div>';

        // post content in box
        echo '<div class="realization-content-box"><div class="realization-content">' . $text_content . '</div></div>';

        // gallery
        if (!empty($gallery_images) || !empty($images[0])) {
            echo '<div class="realization-gallery">';

            // display gallery images
            foreach ($gallery_images as $image_id) {
                $image_full = wp_get_attachment_image_src($image_id, 'large');
                $image_thumb = wp_get_attachment_image_src($image_id, 'medium');
                if ($image_full && $image_thumb) {
                    echo '<a href="' . esc_url($image_full[0]) . '" data-lightbox="realization-gallery" class="realization-gallery-link lightbox-center">';
                    echo '<img src="' . esc_url($image_thumb[0]) . '" class="realization-gallery-image" />';
                    echo '</a>';
                }
            }

            // display images from post content
            foreach ($images[0] as $image) {
                if (preg_match('/src="([^"]+)"/', $image, $match)) {
                    $image_url = $match[1];
                    echo '<a href="' . esc_url($image_url) . '" data-lightbox="realization-gallery" class="realization-gallery-link lightbox-center">';
                    echo '<img src="' . esc_url($image_url) . '" class="realization-gallery-image" />';
                    echo '</a>';
                }
            }

            echo '</div>';
        }

        echo '</div>';
    }

    return ob_get_clean();
}
add_shortcode('realization_details', 'display_realization_details');

function create_realizations_taxonomy() {
    $labels = array(
        'name'              => 'Typy realizacji',
        'singular_name'     => 'Typ realizacji',
        'search_items'      => 'Szukaj typów realizacji',
        'all_items'         => 'Wszystkie typy realizacji',
        'parent_item'       => 'Typ nadrzędny',
        'parent_item_colon' => 'Typ nadrzędny:',
        'edit_item'         => 'Edytuj typ realizacji',
        'update_item'       => 'Zaktualizuj typ realizacji',
        'add_new_item'      => 'Dodaj nowy typ realizacji',
        'new_item_name'     => 'Nazwa nowego typu realizacji',
        'menu_name'         => 'Typy realizacji',
    );

    $args = array(
        'labels' => $labels,
        'hierarchical' => true,
        'public' => true,
        'show_admin_column' => true,
        'rewrite' => array(
            'slug' => '',
            'with_front' => false
        ),
    );

    register_taxonomy('realization_type', array('realizations'), $args);

    // add terms to taxonomy with custom slugs
    if (!term_exists('Schody', 'realization_type')) {
        wp_insert_term(
            'Schody',
            'realization_type',
            array('slug' => 'schody')
        );
    }
    if (!term_exists('Bramy', 'realization_type')) {
        wp_insert_term(
            'Bramy',
            'realization_type',
            array('slug' => 'bramy')
        );
    }
    if (!term_exists('Inne realizacje', 'realization_type')) {
        wp_insert_term(
            'Inne realizacje',
            'realization_type',
            array('slug' => 'pozostale-realizacje')
        );
    }
}
add_action('init', 'create_realizations_taxonomy');

// add custom rewrite rules
function custom_taxonomy_rewrite_rules($rules) {
    $new_rules = array();

    // add rule for single terms
    $new_rules['(schody|bramy|pozostale-realizacje)/?$'] = 'index.php?realization_type=$matches[1]';

    return $new_rules + $rules;
}
add_filter('rewrite_rules_array', 'custom_taxonomy_rewrite_rules');

// add filter for term links
function custom_term_link($url, $term, $taxonomy) {
    if ($taxonomy === 'realization_type') {
        return home_url('/' . $term->slug . '/');
    }
    return $url;
}
add_filter('term_link', 'custom_term_link', 10, 3);

// flush rewrite rules on activation
function flush_rewrite_rules_on_activation() {
    create_realizations_taxonomy();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'flush_rewrite_rules_on_activation');

function hide_divi_project_post_type() {
    remove_menu_page('edit.php?post_type=project');
}
add_action('admin_menu', 'hide_divi_project_post_type');

function remove_project_from_admin_bar() {
    global $wp_admin_bar;
    $wp_admin_bar->remove_node('new-project');
}
add_action('admin_bar_menu', 'remove_project_from_admin_bar', 999);

// Metabox Gallery for Realizations
function add_realizations_gallery_metabox() {
    add_meta_box(
        'realizations_gallery',
        'Galeria zdjęć',
        'realizations_gallery_callback',
        'realizations', // twój typ postu
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'add_realizations_gallery_metabox');

function realizations_gallery_callback($post) {
    wp_nonce_field('save_realizations_gallery', 'realizations_gallery_nonce');
    $gallery = get_post_meta($post->ID, '_realizations_gallery', true);
    ?>
    <div id="gallery_wrapper">
        <!-- <p class="description" style="color: #666; font-style: italic; margin-bottom: 10px;">
            Pierwsze zdjęcie na liście jest zdjęciem głównym.
        </p> -->
        <ul id="gallery_images">
            <?php
            if (!empty($gallery)) {
                $gallery = explode(',', $gallery);
                foreach ($gallery as $image_id) {
                    $image = wp_get_attachment_image_src($image_id, 'thumbnail');
                    if ($image) {
                        echo '<li data-id="' . esc_attr($image_id) . '">
                            <img src="' . esc_url($image[0]) . '" />
                            <a href="#" class="remove_image" title="Usuń zdjęcie">Usuń</a>
                        </li>';
                    }
                }
            }
            ?>
        </ul>
        <input type="hidden" id="gallery_input" name="realizations_gallery" value="<?php echo esc_attr(implode(',', (array)$gallery)); ?>" />
        <button type="button" id="add_gallery_images">Dodaj zdjęcia</button>
    </div>
    <?php
}

// save gallery data
function save_realizations_gallery($post_id) {
    if (!isset($_POST['realizations_gallery_nonce']) || !wp_verify_nonce($_POST['realizations_gallery_nonce'], 'save_realizations_gallery')) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (isset($_POST['realizations_gallery'])) {
        update_post_meta($post_id, '_realizations_gallery', sanitize_text_field($_POST['realizations_gallery']));
    }
}
add_action('save_post', 'save_realizations_gallery');

// add scripts and styles
function enqueue_realizations_gallery_scripts($hook) {
    global $post;
    if (($hook == 'post-new.php' || $hook == 'post.php') && $post->post_type === 'realizations') {
        // load media uploader
        wp_enqueue_media();

        // load jQuery UI Sortable
        wp_enqueue_script('jquery-ui-sortable');

        // load gallery script
        wp_enqueue_script(
            'realizations-gallery-script',
            get_stylesheet_directory_uri() . '/assets/js/gallery.js', // Zmieniona ścieżka
            array('jquery', 'jquery-ui-sortable'),
            filemtime(get_stylesheet_directory() . '/assets/js/gallery.js'), // Dodany timestamp jako wersja
            true
        );

        // load gallery style
        wp_enqueue_style(
            'realizations-gallery-style',
            get_stylesheet_directory_uri() . '/assets/css/image-gallery.css', // Zmieniona ścieżka
            array(),
            filemtime(get_stylesheet_directory() . '/assets/css/image-gallery.css') // Dodany timestamp jako wersja
        );

        // add localization for gallery script
        wp_localize_script(
            'realizations-gallery-script',
            'realizationsGallery',
            array(
                'title'  => 'Wybierz zdjęcia',
                'button' => 'Użyj tych zdjęć'
            )
        );
    }
}
add_action('admin_enqueue_scripts', 'enqueue_realizations_gallery_scripts');
