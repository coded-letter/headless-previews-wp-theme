<?php
// Disable unnecessary theme features
add_action('after_setup_theme', function () {
    remove_action('wp_head', 'wp_generator');
    add_theme_support('post-thumbnails');
});


// Enqueue a minimal style for previews
function headless_preview_styles()
{
    wp_enqueue_style('headless-preview-style', get_stylesheet_uri());
}
add_action('wp_enqueue_scripts', 'headless_preview_styles');







// Custom avatar - pole wyboru w profilu użytkownika

function custom_avatar_media_library_field($user)
{
    $avatar_url = get_user_meta($user->ID, 'custom_avatar', true);
    ?>
    <table class="form-table">
        <tr>
            <th><label for="custom_avatar">Choose an Avatar</label></th>
            <td>
                <img id="custom_avatar_preview" src="<?php echo esc_url($avatar_url ? $avatar_url : ''); ?>"
                    style="width: 100px; height: 100px; <?php echo !$avatar_url ? 'display:none;' : ''; ?>" />
                <br>
                <input type="hidden" name="custom_avatar" id="custom_avatar" value="<?php echo esc_url($avatar_url); ?>" />
                <button type="button" class="button" id="custom_avatar_button">Choose Avatar</button>
                <button type="button" class="button" id="remove_custom_avatar"
                    style="<?php echo !$avatar_url ? 'display:none;' : ''; ?>">Delete Avatar</button>
            </td>
        </tr>
    </table>
    <script>
        jQuery(document).ready(function ($) {
            var mediaUploader;

            $('#custom_avatar_button').click(function (e) {
                e.preventDefault();
                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }
                mediaUploader = wp.media.frames.file_frame = wp.media({
                    title: 'Choose Avatar',
                    button: { text: 'Choose Avatar' },
                    multiple: false
                });
                mediaUploader.on('select', function () {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    $('#custom_avatar').val(attachment.url);
                    $('#custom_avatar_preview').attr('src', attachment.url).show();
                    $('#remove_custom_avatar').show();
                });
                mediaUploader.open();
            });

            $('#remove_custom_avatar').click(function (e) {
                e.preventDefault();
                $('#custom_avatar').val('');
                $('#custom_avatar_preview').hide();
                $(this).hide();
            });
        });
    </script>
    <?php
}
add_action('personal_options', 'custom_avatar_media_library_field');




// Save custom Avatar
function save_custom_avatar_from_media_library($user_id)
{
    if (isset($_POST['custom_avatar'])) {
        update_user_meta($user_id, 'custom_avatar', esc_url_raw($_POST['custom_avatar']));
    }
}
add_action('personal_options_update', 'save_custom_avatar_from_media_library');
add_action('edit_user_profile_update', 'save_custom_avatar_from_media_library');




// Display the custom avatar
function custom_avatar($avatar, $id_or_email, $size)
{
    $user_id = null;

    if (is_numeric($id_or_email)) {
        $user_id = $id_or_email;
    } elseif (is_object($id_or_email) && isset($id_or_email->user_id)) {
        $user_id = $id_or_email->user_id;
    } elseif (is_string($id_or_email) && filter_var($id_or_email, FILTER_VALIDATE_EMAIL)) {
        $user = get_user_by('email', $id_or_email);
        if ($user) {
            $user_id = $user->ID;
        }
    }

    if ($user_id) {
        $custom_avatar = get_user_meta($user_id, 'custom_avatar', true);
        if ($custom_avatar) {
            return sprintf(
                '<img src="%s" class="avatar avatar-%s photo" width="%s" height="%s" alt="User Avatar" />',
                esc_url($custom_avatar),
                esc_attr($size),
                esc_attr($size),
                esc_attr($size)
            );
        }
    }

    return $avatar;
}
// Make WordPress (and WPGraphQL) use the custom avatar URL everywhere
add_filter('pre_get_avatar_data', function ($args, $id_or_email) {
    $user_id = 0;
    $email = null;

    if (is_numeric($id_or_email)) {
        $user_id = (int) $id_or_email;
    } elseif (is_object($id_or_email)) {
        // WP_Comment or similar
        if (!empty($id_or_email->user_id)) {
            $user_id = (int) $id_or_email->user_id;
        }
        if (empty($user_id) && !empty($id_or_email->comment_author_email)) {
            $email = $id_or_email->comment_author_email;
        }
    } elseif (is_string($id_or_email) && is_email($id_or_email)) {
        $email = $id_or_email;
    }

    if (!$user_id && $email) {
        $user = get_user_by('email', $email);
        if ($user) {
            $user_id = (int) $user->ID;
        }
    }

    if ($user_id) {
        $custom = get_user_meta($user_id, 'custom_avatar', true);
        if (!empty($custom)) {
            $args['url'] = esc_url($custom);
            $args['found_avatar'] = true;
            $args['class'][] = 'avatar-custom';
        }
    }

    return $args;
}, 10, 2);


add_action('graphql_register_types', function () {
    $config = [
        'type' => 'String',
        'description' => __('Custom Avatar URL from media library'),
        'resolve' => function ($entity, $args, $context, $info) {
            $user_id = null;
            $email = null;

            // Try common shapes across User / Commenter / CommentAuthor
            if (isset($entity->ID)) {
                // WPGraphQL User model usually has ->ID
                $user_id = (int) $entity->ID;
            }
            if (isset($entity->databaseId) && !$user_id) {
                $user_id = (int) $entity->databaseId;
            }
            if (isset($entity->userId) && !empty($entity->userId)) {
                $user_id = (int) $entity->userId;
            }
            if (isset($entity->email)) {
                $email = $entity->email;
            }

            // If this is a CommentAuthor w/o userId, try map by email -> user
            if (!$user_id && $email) {
                $user = get_user_by('email', $email);
                if ($user) {
                    $user_id = (int) $user->ID;
                }
            }

            if ($user_id) {
                $custom = get_user_meta($user_id, 'custom_avatar', true);
                if (!empty($custom)) {
                    return esc_url($custom);
                }
            }

            return null; // let the client fall back to avatar.url if needed
        },
    ];

    register_graphql_field('User', 'customAvatar', $config);
    register_graphql_field('Commenter', 'customAvatar', $config);
    register_graphql_field('CommentAuthor', 'customAvatar', $config);
});

// add rating to comments and f-e mutation

// ⚪️ Add a "Rating" column to wp-admin Comments list
add_filter('manage_edit-comments_columns', function ($columns) {
    $columns['rating'] = __('Rating', 'your-textdomain');
    return $columns;
});


// ⚫️ Render the "Rating" column in wp-admin Comments
add_action('manage_comments_custom_column', function ($column, $comment_ID) {
    if ($column === 'rating') {
        $rating = get_comment_meta($comment_ID, 'rating', true);
        echo $rating ? str_repeat('⭐', intval($rating)) : '—';
    }
}, 10, 2);



add_action('graphql_register_types', function () {
    register_graphql_field('Comment', 'rating', [
        'type' => 'Int',
        'description' => 'Rating value from comment meta',
        'resolve' => function ($comment) {
            return (int) get_comment_meta($comment->commentId, 'rating', true);
        },
    ]);
});

add_action('graphql_register_types', function () {

    register_graphql_input_type('CreateReviewInput', [
        'description' => 'Input for creating a review',
        'fields' => [
            'commentOn' => ['type' => 'Int', 'description' => 'Post ID to comment on'],
            'content' => ['type' => 'String', 'description' => 'Comment content'],
            'author' => ['type' => 'String', 'description' => 'Author name'],
            'authorEmail' => ['type' => 'String', 'description' => 'Author email'],
            'rating' => ['type' => 'Int', 'description' => 'Rating from 1 to 5'],
        ],
    ]);

    register_graphql_object_type('CreateReviewPayload', [
        'description' => 'Payload returned after creating a review',
        'fields' => [
            'comment' => ['type' => 'Comment', 'description' => 'The created comment'],
        ],
    ]);

    register_graphql_mutation('createReview', [
        'inputFields' => [
            'input' => ['type' => 'CreateReviewInput']
        ],
        'outputFields' => [
            'comment' => [
                'type' => 'Comment',
                'resolve' => function ($payload) {
                    return get_comment($payload['comment_id']);
                }
            ]
        ],
        'mutateAndGetPayload' => function ($input) {
            $post_id = absint($input['commentOn']);
            $content = sanitize_text_field($input['content'] ?? '');
            $author = sanitize_text_field($input['author'] ?? '');
            $author_email = sanitize_email($input['authorEmail'] ?? '');
            $rating = intval($input['rating'] ?? 0);

            if (!$post_id || empty($content) || empty($author) || empty($author_email)) {
                throw new \GraphQL\Error\UserError(__('Missing required fields', 'your-textdomain'));
            }

            // Insert comment
            $comment_id = wp_insert_comment([
                'comment_post_ID' => $post_id,
                'comment_content' => $content,
                'comment_author' => $author,
                'comment_author_email' => $author_email,
                'comment_approved' => 0,
                'comment_type' => '', // You could use 'review' if needed
            ]);

            if (is_wp_error($comment_id) || !$comment_id) {
                throw new \GraphQL\Error\UserError(__('Failed to create review', 'your-textdomain'));
            }

            // Save rating and custom meta
            if ($rating >= 1 && $rating <= 5) {
                update_comment_meta($comment_id, 'rating', $rating);
                add_comment_meta($comment_id, 'is_review', true);
            }

            // WooCommerce-specific logic
            $post_type = get_post_type($post_id);
            if ($post_type === 'product') {
                update_comment_meta($comment_id, 'verified', 0);
                if (class_exists('WC_Comments')) {
                    WC_Comments::clear_transients($post_id);
                }
                wc_delete_product_transients($post_id);
            }

            return ['comment_id' => $comment_id];
        }
    ]);
});

//extra translations data for covering var prods.

add_action('graphql_register_types', function () {
    register_graphql_object_type('ProductTranslation', [
        'description' => __('A translation of a WooCommerce product', 'your-textdomain'),
        'fields' => [
            'id' => ['type' => 'ID'],
            'uri' => ['type' => 'String'],
            'languageCode' => [
                'type' => 'String',
                'description' => __('The language code of the translation'),
            ],
        ],
    ]);

    register_graphql_field('Product', 'customTranslations', [
        'type' => ['list_of' => 'ProductTranslation'],
        'description' => __('List of Polylang translations for this product', 'your-textdomain'),
        'resolve' => function ($product) {
            $post_id = isset($product->databaseId)
                ? $product->databaseId
                : (isset($product->ID) ? $product->ID : null);

            if (!$post_id || !function_exists('pll_get_post_translations') || !function_exists('pll_get_post_language')) {
                return [];
            }

            $translations = pll_get_post_translations($post_id);

            if (!$translations || !is_array($translations)) {
                return [];
            }

            $results = [];

            foreach ($translations as $lang_code => $translated_id) {
                if ((int) $translated_id === (int) $post_id) {
                    continue;
                }

                $results[] = [
                    'id' => (string) $translated_id,
                    'uri' => get_permalink($translated_id),
                    'languageCode' => strtoupper($lang_code),
                ];
            }

            return $results;
        },
    ]);
});



//add website url to profile


add_action('graphql_register_types', function () {
    register_graphql_field('User', 'websiteUrl', [
        'type' => 'String',
        'description' => __('The website URL of the user.', 'your-textdomain'),
        'resolve' => function ($user) {
            $user_id = $user->userId ?? null;

            if (!$user_id) {
                return null;
            }

            return get_the_author_meta('user_url', $user_id);
        },
    ]);
});




// Allow HTML in category and taxonomy descriptions
remove_filter('pre_term_description', 'wp_filter_kses'); // Allow saving HTML
remove_filter('term_description', 'wp_kses_data');       // Allow displaying HTML


// Add mutation allowing to change custom avatar
add_action('graphql_register_types', function () {
    register_graphql_mutation('setUserAvatar', [
        'inputFields' => [
            'userId' => [
                'type' => 'ID',
                'description' => 'ID of the user',
            ],
            'mediaId' => [
                'type' => 'ID',
                'description' => 'Media ID from the media library',
            ],
        ],
        'outputFields' => [
            'customAvatar' => [
                'type' => 'String',
                'description' => 'Updated custom avatar URL',
                'resolve' => function ($payload) {
                    return $payload['customAvatar'];
                },
            ],
        ],
        'mutateAndGetPayload' => function ($input, $context, $info) {
            $user_id = absint($input['userId']);
            $media_id = absint($input['mediaId']);

            if (!current_user_can('edit_user', $user_id)) {
                throw new \GraphQL\Error\UserError('You are not allowed to change this avatar.');
            }

            $url = wp_get_attachment_url($media_id);
            if (!$url) {
                throw new \GraphQL\Error\UserError('Invalid media ID.');
            }

            update_user_meta($user_id, 'custom_avatar', esc_url_raw($url));

            return [
                'customAvatar' => esc_url($url),
            ];
        },
    ]);
});

// Avatar upload to media library by customers should be allowed with file limits.
add_action('init', function () {
    $role = get_role('customer'); // or your custom role
    if ($role && !$role->has_cap('upload_files')) {
        $role->add_cap('upload_files');
    }
});

add_filter('wp_check_filetype_and_ext', function ($data, $file, $filename, $mimes) {
    // Only restrict for logged-in users with role 'customer'
    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        if (in_array('customer', (array) $user->roles)) {
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (!in_array($ext, $allowed_types)) {
                return [
                    'ext' => false,
                    'type' => false,
                    'proper_filename' => false,
                ];
            }
        }
    }

    // Default behavior for other roles
    return $data;
}, 10, 4);

// shipping methods / countries costs patch
add_action('graphql_register_types', function () {

    // Shipping method type
    register_graphql_object_type('ShippingMethodCost', [
        'description' => __('WooCommerce shipping method cost for a specific country', 'your-textdomain'),
        'fields' => [
            'id' => ['type' => 'String'],
            'label' => ['type' => 'String'],
            'cost' => ['type' => 'Float'],
            'tax' => ['type' => 'Float'],
            'total' => ['type' => 'Float'],
            'currency' => ['type' => 'String'],
            'freeShippingMinAmount' => ['type' => 'Float', 'description' => __('Minimum order amount required for free shipping', 'your-textdomain')],
        ]
    ]);

    // Country shipping group type
    register_graphql_object_type('CountryShippingMethods', [
        'description' => __('List of shipping methods for a specific country', 'your-textdomain'),
        'fields' => [
            'country' => ['type' => 'String'],
            'methods' => ['type' => ['list_of' => 'ShippingMethodCost']],
        ]
    ]);

    // Root field
    register_graphql_field('RootQuery', 'shippingMethodsData', [
        'type' => ['list_of' => 'CountryShippingMethods'],
        'args' => [
            'countries' => [
                'type' => ['list_of' => 'String'],
                'description' => __('Two-letter country codes (e.g., ["US", "DE"])', 'your-textdomain')
            ]
        ],
        'resolve' => function ($root, $args) {

            if (empty($args['countries']) || !is_array($args['countries'])) {
                return [];
            }

            if (!class_exists('WC_Shipping_Zones')) {
                return [];
            }

            $results = [];

            foreach ($args['countries'] as $country) {
                $country = strtoupper($country);

                // Create fake package
                $package = [
                    'destination' => [
                        'country' => $country,
                        'state' => '',
                        'postcode' => '',
                        'city' => '',
                        'address' => '',
                        'address_2' => ''
                    ],
                    'contents' => [],
                    'contents_cost' => 0,
                    'applied_coupons' => [],
                    'user' => [],
                ];

                // Get matching zone
                $zone = WC_Shipping_Zones::get_zone_matching_package($package);
                $shipping_methods = $zone->get_shipping_methods(true);

                $methods_list = [];

                foreach ($shipping_methods as $method) {
                    if ($method->is_enabled()) {
                        $method->calculate_shipping([$package]);

                        if (!empty($method->rates)) {
                            foreach ($method->rates as $rate) {
                                $cost = (float) $rate->get_cost();
                                $tax = array_sum($rate->get_taxes());
                                $free_min = null;

                                // Detect free shipping min amount
                                if ($method instanceof WC_Shipping_Free_Shipping) {
                                    if (!empty($method->min_amount) && is_numeric($method->min_amount)) {
                                        $free_min = (float) $method->min_amount;
                                    }
                                }

                                $methods_list[] = [
                                    'id' => $rate->get_id(),
                                    'label' => $rate->get_label(),
                                    'cost' => $cost,
                                    'tax' => $tax,
                                    'total' => $cost + $tax,
                                    'currency' => get_woocommerce_currency(),
                                    'freeShippingMinAmount' => $free_min
                                ];
                            }
                        }
                    }
                }

                $results[] = [
                    'country' => $country,
                    'methods' => $methods_list
                ];
            }

            return $results;
        }
    ]);
});


//patch multilang bio added by polylang

add_action('graphql_register_types', function () {

    // 1) Define the object type first
    register_graphql_object_type('BioTranslation', [
        'description' => 'User bio per Polylang language',
        'fields' => [
            'language' => ['type' => 'String'],
            'text' => ['type' => 'String'],
        ],
    ]);

    // 2) Add the field on User
    register_graphql_field('User', 'bioTranslations', [
        'type' => ['list_of' => 'BioTranslation'],
        'description' => __('User bio in all Polylang languages', 'your-textdomain'),
        'resolve' => function ($source) {
            // Get the DB user ID safely from WPGraphQL's model
            $user_id = 0;
            if (isset($source->userId)) {
                $user_id = (int) $source->userId;
            } elseif (isset($source->ID)) {
                $user_id = (int) $source->ID;
            } elseif (isset($source->databaseId)) {
                $user_id = (int) $source->databaseId;
            }
            if (!$user_id) {
                return [];
            }

            if (!function_exists('pll_languages_list') || !function_exists('pll_default_language')) {
                // Polylang not available; just return the core description as default
                $desc = get_user_meta($user_id, 'description', true);
                return $desc ? [['language' => 'default', 'text' => $desc]] : [];
            }

            // Get slugs like ['en','pl','de']
            $slugs = pll_languages_list(['fields' => 'slug']);
            $default = pll_default_language();

            $out = [];
            foreach ((array) $slugs as $slug) {
                $meta_key = ($slug === $default) ? 'description' : 'description_' . $slug;
                $text = get_user_meta($user_id, $meta_key, true);

                // If you want to skip empty translations, keep this if() guard.
                // If you want to always return all languages (even empty), remove it.
                if ($text !== '' && $text !== null) {
                    $out[] = [
                        'language' => $slug,
                        'text' => (string) $text,
                    ];
                }
            }

            return $out;
        },
    ]);

});



/**
 * WPGraphQL language fallback for WooCommerce Products, Categories & Tags
 * Works when Polylang for WooCommerce is NOT installed
 */
add_action('graphql_register_types', function () {
    include_once ABSPATH . 'wp-admin/includes/plugin.php';

    // If Polylang WC is active → do nothing
    if (is_plugin_active('polylang-wc/polylang-wc.php')) {
        return;
    }

    // --- Detect default language ---
    if (function_exists('PLL') && isset(PLL()->options['default_lang'])) {
        $default_lang = strtolower(PLL()->options['default_lang']); // ✅ lowercase (matches enum)
    } else {
        $default_lang = strtolower(substr(get_locale(), 0, 2));
    }

    /**
     * 1. Register Language object
     * Wraps the enum so queries can use `language { code }`
     */
    register_graphql_object_type('Language', [
        'description' => __('Fallback language object when Polylang WC is not active', 'your-textdomain'),
        'fields' => [
            'code' => [
                'type' => 'LanguageCodeEnum',
                'description' => __('Language code (e.g. en, de, fr)', 'your-textdomain'),
            ],
        ],
    ]);

    /**
     * 2. Register Translation object
     */
    register_graphql_object_type('Translation', [
        'description' => __('Fallback translation object when Polylang WC is not active', 'your-textdomain'),
        'fields' => [
            'id' => ['type' => 'ID'],
            'uri' => ['type' => 'String'],
            'language' => [
                'type' => 'Language',
                'description' => __('Language object for the translation', 'your-textdomain'),
            ],
        ],
    ]);

    /**
     * 3. Patch WooCommerce types: Product, ProductCategory, ProductTag
     */
    $types_to_patch = [
        'Product' => 'product',
        'ProductCategory' => 'productCategory',
        'ProductTag' => 'productTag',
    ];

    foreach ($types_to_patch as $label => $type_name) {
        // language field
        register_graphql_field($type_name, 'language', [
            'type' => 'Language',
            'description' => sprintf('Fallback language for %s when Polylang WC is not active', $label),
            'resolve' => function () use ($default_lang) {
                return [
                    'code' => $default_lang,
                ];
            },
        ]);

        // translations field
        register_graphql_field($type_name, 'translations', [
            'type' => ['list_of' => 'Translation'],
            'description' => sprintf('Fallback translations for %s when Polylang WC is not active', $label),
            'resolve' => function ($root) use ($default_lang) {
                return [
                    [
                        'id' => (string) $root->ID,
                        'uri' => get_permalink($root->ID),
                        'language' => [
                            'code' => $default_lang,
                        ],
                    ],
                ];
            },
        ]);
    }
});







// Fix buttons with no links for audits
add_filter('render_block', function ($block_content, $block) {
    if (empty($block['blockName']) || $block['blockName'] !== 'core/button') {
        return $block_content;
    }

    // If the rendered button has a non-empty href (not just "#"), leave it alone
    if (preg_match('/<a[^>]+href\s*=\s*([\'"])([^\'"]*)\1/i', $block_content, $m)) {
        $href = trim($m[2]);
        if ($href !== '' && $href !== '#') {
            return $block_content;
        }
    }
    // Otherwise: no href or empty href → convert <a> to <span>

    // Replace the opening and closing tag (only the first match in this block)
    $block_content = preg_replace('/<a\b/i', '<span', $block_content, 1);
    $block_content = preg_replace('/<\/a>/i', '</span>', $block_content, 1);

    // Strip link-only attributes that don't belong on a <span>
    $block_content = preg_replace('/\s(href|target|rel)=([\'"]).*?\2/i', '', $block_content);

    // (Optional) mark as visually/semantically non-interactive
    $block_content = preg_replace('/<span\b/i', '<span aria-disabled="true" role="button"', $block_content, 1);

    return $block_content;
}, 10, 2);



// add tw classes to body
function my_custom_body_classes($classes)
{
    if (is_single() && get_post_type() === 'post') {
        $classes[] = 'dark:bg-gray-950 dark:text-gray-200 max-w-screen-lg p-4 mt-0 pt-0 mx-auto'; // Post variation
    }

    if (is_page()) {
        $classes[] = 'dark:bg-gray-950 dark:text-gray-200 max-w-full p-4 mt-0 pt-0 mx-auto'; // Page variation
    }

    if (is_tag()) {
        $classes[] = 'dark:bg-gray-950 dark:text-gray-200 container p-4 mt-0 pt-0 mx-auto'; // Tag archive
    }

    if (is_single() && get_post_type() === 'product') {
        $classes[] = 'dark:bg-gray-950 dark:text-gray-200 container p-4 mt-0 pt-0 mx-auto'; // Example CPT
    }

    return $classes;
}
add_filter('body_class', 'my_custom_body_classes');



/**
 * SuperFunky WP Banner - Robust Responsive Dashboard Notice
 */

// Ensure jQuery is loaded for AJAX
add_action('admin_enqueue_scripts', function () {
    wp_enqueue_script('jquery');
});

// Display the banner only on the Dashboard main page
add_action('admin_notices', 'sf_wp_banner_show');
function sf_wp_banner_show()
{
    $screen = get_current_screen();
    if (!$screen || $screen->base !== 'dashboard')
        return; // Only dashboard

    $user_id = get_current_user_id();
    $dismissed = get_user_meta($user_id, 'sf_wp_banner_dismissed', true);
    $screen_option = get_user_meta($user_id, 'sf_wp_banner_screen_option', true);

    // If screen option is off, hide banner
    $visible = ($screen_option !== 'off');

    ?>
    <div class="notice is-dismissible sf-wp-banner" style="
        <?php echo $visible ? '' : 'display:none;'; ?>
        background: linear-gradient(to bottom, #111 0%, #222 80%);
        color: #fff;
        padding: 25px;
        border-radius: 12px;
        margin-bottom: 20px;
        font-family: system-ui, sans-serif;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        overflow: hidden;
        transition: all 0.3s;
    ">
        <h2 style="margin-top:0;font-size:1.6em;font-weight:700; color:#f5f5f5;">🚀 Welcome to Your Headless WordPress!</h2>
        <p style="font-size:1.1em;line-height:1.6;margin:15px 0;">
            Your headless WordPress setup is ready! 🎉
            With the <b>superfunky woocommerce</b> theme, you can manage your content effortlessly
            while your frontend runs lightning-fast via React, Next.js, or Gatsby.
        </p>

        <p style="font-size:1.05em;line-height:1.5;margin:12px 0;">
            Quick resources to get started:
        </p>

        <ul style="list-style: disc; padding-left: 22px; margin:12px 0; font-size:1em;">
            <li><a href="https://superfunky.pro/documentation" target="_blank"
                    style="color:#fff;text-decoration:underline;">Theme
                    setup & customization guide</a></li>
            <li><a href="https://tailwindcss.com/docs/styling-with-utility-classes" target="_blank"
                    style="color:#fff;text-decoration:underline;">Tailwind CSS utilities</a></li>
            <li><a href="https://www.wpgraphql.com/docs/intro-to-graphql" target="_blank"
                    style="color:#fff;text-decoration:underline;">WPGraphQL docs</a></li>
        </ul>

        <a href="https://superfunky.pro/documentation" target="_blank" style="
               display:inline-block;
               background:#fff;
               color:#000;
               font-weight:600;
               padding:10px 20px;
               border-radius:8px;
               text-decoration:none;
               margin-top: 15px;
               transition: background 0.3s, color 0.3s;
           " onmouseover="this.style.background='#eee'; this.style.color='#111';"
            onmouseout="this.style.background='#fff'; this.style.color='#000';">
            Learn More
        </a>
    </div>

    <style>
        /* Mobile friendly adjustments */
        @media (max-width: 782px) {
            .sf-wp-banner {
                padding: 18px;
                font-size: 0.95em;
            }

            .sf-wp-banner h2 {
                font-size: 1.3em;
            }

            .sf-wp-banner a {
                padding: 8px 16px;
            }
        }
    </style>

    <script>
        (function ($) {
            // Dismiss the banner
            $(document).on('click', '.sf-wp-banner .notice-dismiss', function () {
                $.post(ajaxurl, { action: 'sf_wp_banner_dismiss' }, function () {
                    $(".sf-wp-banner").slideUp();
                });
            });

            // Screen Options toggle - always show/hide banner dynamically
            $("#sf_wp_banner_toggle").on("change", function () {
                var val = $(this).is(":checked") ? "on" : "off";
                $.post(ajaxurl, { action: "sf_wp_banner_screen_toggle", value: val }, function () {
                    if (val === "on") $(".sf-wp-banner").slideDown();
                    else $(".sf-wp-banner").slideUp();
                });
            });
        })(jQuery);
    </script>
    <?php
}

// AJAX dismissal - does not remove checkbox
add_action('wp_ajax_sf_wp_banner_dismiss', function () {
    $user_id = get_current_user_id();
    if ($user_id)
        update_user_meta($user_id, 'sf_wp_banner_dismissed', 1);
    wp_send_json_success();
});

// Screen Options checkbox - always present, even if banner is dismissed
add_filter('screen_settings', function ($settings, $screen) {
    if ($screen->base === 'dashboard') {
        $user_id = get_current_user_id();
        $option = get_user_meta($user_id, 'sf_wp_banner_screen_option', true);
        $checked = $option !== 'off' ? 'checked' : '';
        $html = '<fieldset class="metabox-prefs" style="margin-top:10px;">
            <label><input type="checkbox" id="sf_wp_banner_toggle" ' . $checked . '> Show SuperFunky WP Banner</label>
        </fieldset>';
        return $settings . $html;
    }
    return $settings;
}, 10, 2);

// AJAX Screen Options toggle
add_action('wp_ajax_sf_wp_banner_screen_toggle', function () {
    $user_id = get_current_user_id();
    if ($user_id && isset($_POST['value'])) {
        update_user_meta($user_id, 'sf_wp_banner_screen_option', sanitize_text_field($_POST['value']));
    }
    wp_send_json_success();
});



/* IDEA: Extra blik payment method for orders */



// Disable "Visit Store" link in the WordPress admin bar
add_action('admin_bar_menu', function ($wp_admin_bar) {
    $wp_admin_bar->remove_node('view-store');
}, 999);





// keep perm set 755 => 777 - wp theme - sudo chmod





/* ACF extra fields */

add_action('acf/include_fields', function () {
    if (!function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group(array(
        'key' => 'group_621912c292b91',
        'title' => 'Homepage',
        'fields' => array(
            array(
                'key' => 'field_62194deb159c0',
                'label' => 'Hero',
                'name' => 'hero',
                'aria-label' => '',
                'type' => 'group',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'show_in_graphql' => 1,
                'layout' => 'block',
                'sub_fields' => array(
                    array(
                        'key' => 'field_62194e01159c2',
                        'label' => 'Kicker',
                        'name' => 'kicker',
                        'aria-label' => '',
                        'type' => 'text',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'show_in_graphql' => 1,
                        'default_value' => '',
                        'placeholder' => '',
                        'prepend' => '',
                        'append' => '',
                        'maxlength' => '',
                    ),
                    array(
                        'key' => 'field_62194df9159c1',
                        'label' => 'Heading',
                        'name' => 'heading',
                        'aria-label' => '',
                        'type' => 'text',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'show_in_graphql' => 1,
                        'default_value' => '',
                        'placeholder' => '',
                        'prepend' => '',
                        'append' => '',
                        'maxlength' => '',
                    ),
                    array(
                        'key' => 'field_62194e0f159c3',
                        'label' => 'Text',
                        'name' => 'text',
                        'aria-label' => '',
                        'type' => 'text',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'show_in_graphql' => 1,
                        'default_value' => '',
                        'placeholder' => '',
                        'prepend' => '',
                        'append' => '',
                        'maxlength' => '',
                    ),
                    array(
                        'key' => 'field_62194e14159c4',
                        'label' => 'Image',
                        'name' => 'image',
                        'aria-label' => '',
                        'type' => 'image',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'return_format' => 'url',
                        'library' => 'all',
                        'min_width' => '',
                        'min_height' => '',
                        'min_size' => '',
                        'max_width' => '',
                        'max_height' => '',
                        'max_size' => '',
                        'mime_types' => '',
                        'allow_in_bindings' => 1,
                        'preview_size' => 'medium',
                        'show_in_graphql' => 1,
                        'graphql_description' => '',
                        'graphql_field_name' => 'image',
                    ),
                    array(
                        'key' => 'field_62194e26159c5',
                        'label' => 'CTA1',
                        'name' => 'cta1',
                        'aria-label' => '',
                        'type' => 'link',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'show_in_graphql' => 1,
                        'return_format' => 'array',
                    ),
                    array(
                        'key' => 'field_62194e37159c6',
                        'label' => 'CTA2',
                        'name' => 'cta2',
                        'aria-label' => '',
                        'type' => 'link',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'show_in_graphql' => 1,
                        'return_format' => 'array',
                    ),
                    array(
                        'key' => 'field_67b77123ac7c8',
                        'label' => 'Is columns',
                        'name' => 'is_columns',
                        'aria-label' => '',
                        'type' => 'checkbox',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'choices' => array(
                            'True' => 'True',
                            'False' => 'False',
                        ),
                        'default_value' => array(
                            0 => 'True',
                        ),
                        'return_format' => 'value',
                        'allow_custom' => 0,
                        'allow_in_bindings' => 0,
                        'layout' => 'vertical',
                        'toggle' => 0,
                        'show_in_graphql' => 1,
                        'graphql_description' => '',
                        'graphql_field_name' => 'isColumns',
                        'graphql_non_null' => 0,
                        'save_custom' => 0,
                        'custom_choice_button_text' => 'Add new choice',
                    ),
                ),
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'page_type',
                    'operator' => '==',
                    'value' => 'front_page',
                ),
            ),
        ),
        'menu_order' => 0,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'hide_on_screen' => array(
            0 => 'the_content',
            1 => 'discussion',
            2 => 'comments',
            3 => 'author',
        ),
        'active' => true,
        'description' => '',
        'show_in_rest' => 0,
        'show_in_graphql' => 1,
        'graphql_field_name' => 'homepage',
        'map_graphql_types_from_location_rules' => 1,
        'graphql_types' => array(
            0 => 'Page',
        ),
    ));

    acf_add_local_field_group(array(
        'key' => 'group_67b0bf7387b52',
        'title' => 'Theme options',
        'fields' => array(
            array(
                'key' => 'field_680de84254ff2',
                'label' => 'Header',
                'name' => '',
                'aria-label' => '',
                'type' => 'tab',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'graphql_field_name' => 'header',
                'placement' => 'top',
                'endpoint' => 0,
                'selected' => 0,
            ),
            array(
                'key' => 'field_67b0bf740d3a7',
                'label' => 'Promo bar',
                'name' => 'promo_bar',
                'aria-label' => '',
                'type' => 'text',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => '',
                'maxlength' => '',
                'allow_in_bindings' => 0,
                'placeholder' => '',
                'prepend' => '',
                'append' => '',
                'show_in_graphql' => 1,
                'graphql_description' => '',
                'graphql_field_name' => 'promoBar',
                'graphql_non_null' => 0,
            ),
            array(
                'key' => 'field_67b0bf9de1168',
                'label' => 'Site logo',
                'name' => 'site_logo',
                'aria-label' => '',
                'type' => 'image',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'return_format' => 'array',
                'library' => 'all',
                'min_width' => '',
                'min_height' => '',
                'min_size' => '',
                'max_width' => '',
                'max_height' => '',
                'max_size' => '',
                'mime_types' => '',
                'allow_in_bindings' => 0,
                'preview_size' => 'medium',
                'show_in_graphql' => 1,
                'graphql_description' => '',
                'graphql_field_name' => 'siteLogo',
            ),
            array(
                'key' => 'field_680df2f830bb6',
                'label' => 'Is text logo',
                'name' => 'is_text_logo',
                'aria-label' => '',
                'type' => 'true_false',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'message' => '',
                'default_value' => 0,
                'allow_in_bindings' => 0,
                'ui' => 0,
                'show_in_graphql' => 1,
                'graphql_description' => '',
                'graphql_field_name' => 'isTextLogo',
                'graphql_non_null' => 0,
                'ui_on_text' => '',
                'ui_off_text' => '',
            ),
            array(
                'key' => 'field_67b524dea3981',
                'label' => 'Top menu object',
                'name' => 'top_menu_object',
                'aria-label' => '',
                'type' => 'textarea',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => '',
                'maxlength' => '',
                'allow_in_bindings' => 0,
                'rows' => '',
                'placeholder' => '',
                'new_lines' => '',
                'show_in_graphql' => 1,
                'graphql_description' => '',
                'graphql_field_name' => 'topMenuObject',
                'graphql_non_null' => 0,
            ),
            array(
                'key' => 'field_67b770a70f2d3',
                'label' => 'Secondary menu object',
                'name' => 'secondary_menu_object',
                'aria-label' => '',
                'type' => 'textarea',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => '',
                'maxlength' => '',
                'allow_in_bindings' => 0,
                'rows' => '',
                'placeholder' => '',
                'new_lines' => '',
                'show_in_graphql' => 1,
                'graphql_description' => '',
                'graphql_field_name' => 'secondaryMenuObject',
                'graphql_non_null' => 0,
            ),
            array(
                'key' => 'field_67b770eec2720',
                'label' => 'Has secondary menu',
                'name' => 'has_secondary_menu',
                'aria-label' => '',
                'type' => 'true_false',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'message' => '',
                'default_value' => 0,
                'allow_in_bindings' => 1,
                'ui' => 0,
                'show_in_graphql' => 1,
                'graphql_description' => '',
                'graphql_field_name' => 'hasSecondaryMenu',
                'graphql_non_null' => 0,
                'ui_on_text' => '',
                'ui_off_text' => '',
            ),
            array(
                'key' => 'field_680de85e54ff3',
                'label' => 'Contact',
                'name' => '',
                'aria-label' => '',
                'type' => 'tab',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'graphql_field_name' => 'contact',
                'placement' => 'top',
                'endpoint' => 0,
                'selected' => 0,
            ),
            array(
                'key' => 'field_6809281a09c8b',
                'label' => 'Newsletter texts',
                'name' => 'newsletter_texts',
                'aria-label' => '',
                'type' => 'textarea',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => '',
                'maxlength' => '',
                'allow_in_bindings' => 0,
                'rows' => '',
                'placeholder' => '',
                'new_lines' => '',
                'show_in_graphql' => 1,
                'graphql_description' => '',
                'graphql_field_name' => 'newsletterTexts',
                'graphql_non_null' => 0,
            ),
            array(
                'key' => 'field_6809271709c8a',
                'label' => 'Contact form data',
                'name' => 'contact_form_data',
                'aria-label' => '',
                'type' => 'textarea',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => '',
                'maxlength' => '',
                'allow_in_bindings' => 0,
                'rows' => '',
                'placeholder' => '',
                'new_lines' => '',
                'show_in_graphql' => 1,
                'graphql_description' => '',
                'graphql_field_name' => 'formData',
                'graphql_non_null' => 0,
            ),
            array(
                'key' => 'field_680dfaff0d877',
                'label' => 'Thank you',
                'name' => 'thank_you',
                'aria-label' => '',
                'type' => 'textarea',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => '',
                'maxlength' => '',
                'allow_in_bindings' => 1,
                'rows' => '',
                'placeholder' => '',
                'new_lines' => '',
                'show_in_graphql' => 1,
                'graphql_description' => '',
                'graphql_field_name' => 'thankYou',
                'graphql_non_null' => 0,
            ),
            array(
                'key' => 'field_680dffce209c4',
                'label' => 'Autoresponders',
                'name' => 'autoresponders',
                'aria-label' => '',
                'type' => 'textarea',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => '',
                'maxlength' => '',
                'allow_in_bindings' => 0,
                'rows' => '',
                'placeholder' => '',
                'new_lines' => '',
                'show_in_graphql' => 1,
                'graphql_description' => '',
                'graphql_field_name' => 'autoresponders',
                'graphql_non_null' => 0,
            ),
            array(
                'key' => 'field_680de88754ff5',
                'label' => 'Slider',
                'name' => '',
                'aria-label' => '',
                'type' => 'tab',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'graphql_field_name' => 'slider',
                'placement' => 'top',
                'endpoint' => 0,
                'selected' => 0,
            ),
            array(
                'key' => 'field_680928579270b',
                'label' => 'Slider object',
                'name' => 'slider_object',
                'aria-label' => '',
                'type' => 'textarea',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => '',
                'maxlength' => '',
                'allow_in_bindings' => 0,
                'rows' => '',
                'placeholder' => '',
                'new_lines' => '',
                'show_in_graphql' => 1,
                'graphql_description' => '',
                'graphql_field_name' => 'sliderObject',
                'graphql_non_null' => 0,
            ),
            array(
                'key' => 'field_680de87c54ff4',
                'label' => 'Footer',
                'name' => '',
                'aria-label' => '',
                'type' => 'tab',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'graphql_field_name' => 'footer',
                'placement' => 'top',
                'endpoint' => 0,
                'selected' => 0,
            ),
            array(
                'key' => 'field_680926fe09c88',
                'label' => 'Footer data',
                'name' => 'footer_data',
                'aria-label' => '',
                'type' => 'textarea',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => '',
                'maxlength' => '',
                'allow_in_bindings' => 0,
                'rows' => '',
                'placeholder' => '',
                'new_lines' => '',
                'show_in_graphql' => 1,
                'graphql_description' => '',
                'graphql_field_name' => 'footerData',
                'graphql_non_null' => 0,
            ),
            array(
                'key' => 'field_68b18327b1f35',
                'label' => 'UI Strings',
                'name' => '',
                'aria-label' => '',
                'type' => 'tab',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'graphql_field_name' => 'uiStrings',
                'placement' => 'top',
                'endpoint' => 0,
                'selected' => 0,
            ),
            array(
                'key' => 'field_680df9e90d874',
                'label' => 'Components data',
                'name' => 'components_data',
                'aria-label' => '',
                'type' => 'textarea',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => '',
                'maxlength' => '',
                'allow_in_bindings' => 0,
                'rows' => '',
                'placeholder' => '',
                'new_lines' => '',
                'show_in_graphql' => 1,
                'graphql_description' => '',
                'graphql_field_name' => 'componentsData',
                'graphql_non_null' => 0,
            ),
            array(
                'key' => 'field_680dfb9b0d879',
                'label' => 'Misc data',
                'name' => '',
                'aria-label' => '',
                'type' => 'tab',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'graphql_field_name' => 'misc',
                'placement' => 'top',
                'endpoint' => 0,
                'selected' => 0,
            ),
            array(
                'key' => 'field_680dfbb80d87a',
                'label' => 'Stripe customer portal URL',
                'name' => 'stripe_customer_portal_url',
                'aria-label' => '',
                'type' => 'url',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => '',
                'allow_in_bindings' => 0,
                'placeholder' => '',
                'show_in_graphql' => 1,
                'graphql_description' => '',
                'graphql_field_name' => 'stripeCustomerPortalUrl',
                'graphql_non_null' => 0,
            ),
            array(
                'key' => 'field_680f6b96de071',
                'label' => 'Featured category',
                'name' => 'featured_category',
                'aria-label' => '',
                'type' => 'text',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => '',
                'maxlength' => '',
                'allow_in_bindings' => 0,
                'placeholder' => '',
                'prepend' => '',
                'append' => '',
                'show_in_graphql' => 1,
                'graphql_description' => '',
                'graphql_field_name' => 'featuredCategory',
                'graphql_non_null' => 0,
            ),
            array(
                'key' => 'field_680f715f9dbc7',
                'label' => 'Featured post tag',
                'name' => 'featured_post_tag',
                'aria-label' => '',
                'type' => 'text',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => 'Featured',
                'maxlength' => '',
                'allow_in_bindings' => 0,
                'placeholder' => '',
                'prepend' => '',
                'append' => '',
                'show_in_graphql' => 1,
                'graphql_description' => '',
                'graphql_field_name' => 'featuredPostTag',
                'graphql_non_null' => 0,
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'page_type',
                    'operator' => '==',
                    'value' => 'front_page',
                ),
            ),
        ),
        'menu_order' => 0,
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'hide_on_screen' => '',
        'active' => true,
        'description' => '',
        'show_in_rest' => 0,
        'show_in_graphql' => 1,
        'graphql_field_name' => 'themeOptions',
        'map_graphql_types_from_location_rules' => 0,
        'graphql_types' => '',
    ));
});

