<?php
/**
 * Plugin Name: Listing Reviews
 * Description: Review system for listings
 * Version: 1.0.0
 * Author: Your Name
 */

if (!defined('ABSPATH')) exit;

// Register Listing Post Type
function create_listing_post_type() {
    register_post_type('listing', array(
        'labels' => array(
            'name' => 'Listings',
            'singular_name' => 'Listing',
            'add_new' => 'Add New Listing',
            'add_new_item' => 'Add New Listing',
            'edit_item' => 'Edit Listing',
            'view_item' => 'View Listing',
        ),
        'public' => true,
        'has_archive' => true,
        'supports' => array('title', 'comments'),
        'show_in_rest' => true,
        'menu_icon' => 'dashicons-location-alt',
        'comment_status' => 'open'
    ));
}
add_action('init', 'create_listing_post_type');

// Add rating field BEFORE comment textarea
function add_rating_field_before_comment($fields) {
    $rating_html = '<p class="comment-form-rating">
        <label for="rating">Rating <span class="required">*</span></label><br>
        <select name="rating" id="rating" required style="font-size: 1.5em;">
            <option value="">Select Rating</option>
            <option value="5">⭐⭐⭐⭐⭐ (5/5)</option>
            <option value="4">⭐⭐⭐⭐ (4/5)</option>
            <option value="3">⭐⭐⭐ (3/5)</option>
            <option value="2">⭐⭐ (2/5)</option>
            <option value="1">⭐ (1/5)</option>
        </select>
    </p>';
    
    return $rating_html . $fields;
}
add_filter('comment_form_field_comment', 'add_rating_field_before_comment');

// Save rating when comment is submitted
function save_rating($comment_id) {
    if (isset($_POST['rating'])) {
        $rating = intval($_POST['rating']);
        if ($rating >= 1 && $rating <= 5) {
            add_comment_meta($comment_id, 'rating', $rating);
        }
    }
}
add_action('comment_post', 'save_rating');

// Display rating with comment
function display_rating($comment_text, $comment) {
    $rating = get_comment_meta($comment->comment_ID, 'rating', true);
    if ($rating) {
        $stars = str_repeat('⭐', $rating);
        return '<div class="comment-rating" style="font-size: 1.3em; margin-bottom: 10px;">' . $stars . '</div>' . $comment_text;
    }
    return $comment_text;
}
add_filter('comment_text', 'display_rating', 10, 2);

// Enable REST API for anonymous comments
add_filter('rest_allow_anonymous_comments', '__return_true');

// Add rating to REST API response
function add_rating_to_rest_api($response, $comment) {
    $rating = get_comment_meta($comment->comment_ID, 'rating', true);
    $response->data['rating'] = $rating ? intval($rating) : null;
    return $response;
}
add_filter('rest_prepare_comment', 'add_rating_to_rest_api', 10, 2);

// Register custom REST route for submitting reviews
function register_review_routes() {
    register_rest_route('reviews/v1', '/submit', array(
        'methods' => 'POST',
        'callback' => 'submit_review_api',
        'permission_callback' => '__return_true'
    ));
}
add_action('rest_api_init', 'register_review_routes');

// Handle review submission
function submit_review_api($request) {
    $params = $request->get_json_params();
    
    // Validate required fields
    if (empty($params['listing_id']) || empty($params['rating']) || empty($params['comment'])) {
        return new WP_Error('missing_fields', 'Missing required fields', array('status' => 400));
    }
    
    // Get or create WordPress post for this listing
    $post_id = get_or_create_listing_post($params['listing_id'], $params['listing_title'] ?? null);
    
    // Create comment
    $comment_data = array(
        'comment_post_ID' => $post_id,
        'comment_content' => sanitize_textarea_field($params['comment']),
        'comment_author' => sanitize_text_field($params['name'] ?? 'Anonymous'),
        'comment_author_email' => sanitize_email($params['email'] ?? ''),
        'comment_approved' => 0, // Pending moderation
    );
    
    $comment_id = wp_insert_comment($comment_data);
    
    if ($comment_id) {
        add_comment_meta($comment_id, 'rating', intval($params['rating']));
        
        return array(
            'success' => true,
            'message' => 'Review submitted for moderation',
            'comment_id' => $comment_id,
            'listing_id' => $params['listing_id']
        );
    }
    
    return new WP_Error('failed', 'Failed to submit review', array('status' => 500));
}

// Helper function: Get or create listing post
function get_or_create_listing_post($listing_id, $listing_title = null) {
    // Check if post already exists for this listing_id
    $existing = get_posts(array(
        'post_type' => 'listing',
        'meta_key' => 'listing_id',
        'meta_value' => $listing_id,
        'posts_per_page' => 1,
        'post_status' => 'any'
    ));
    
    if (!empty($existing)) {
        return $existing[0]->ID;
    }
    
    // Create new listing post
    $post_id = wp_insert_post(array(
        'post_type' => 'listing',
        'post_title' => $listing_title ?? 'Listing ' . $listing_id,
        'post_status' => 'publish',
        'comment_status' => 'open'
    ));
    
    // Store the listing ID as post meta
    add_post_meta($post_id, 'listing_id', sanitize_text_field($listing_id));
    
    return $post_id;
}
// Add CORS headers for API requests
function add_cors_headers() {
    // Remove any existing headers
    remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
    
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    header("Access-Control-Max-Age: 86400");
    
    // Handle preflight OPTIONS request
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        status_header(200);
        exit();
    }
}
add_action('rest_api_init', 'add_cors_headers', 15);

// Also handle OPTIONS at an earlier stage
function handle_preflight() {
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
        header("Access-Control-Max-Age: 86400");
        status_header(200);
        exit();
    }
}
add_action('init', 'handle_preflight');

// Register GET reviews endpoint
function register_get_reviews_route() {
    register_rest_route('reviews/v1', '/listing/(?P<listing_id>[a-zA-Z0-9-]+)', array(
        'methods' => 'GET',
        'callback' => 'get_reviews_by_listing_id',
        'permission_callback' => '__return_true'
    ));
}
add_action('rest_api_init', 'register_get_reviews_route');

// Get reviews for a specific listing
function get_reviews_by_listing_id($request) {
    $listing_id = $request['listing_id'];
    
    // Find WordPress post for this listing ID
    $posts = get_posts(array(
        'post_type' => 'listing',
        'meta_key' => 'listing_id',
        'meta_value' => $listing_id,
        'posts_per_page' => 1
    ));
    
    if (empty($posts)) {
        return array(
            'reviews' => array(),
            'count' => 0,
            'average_rating' => 0
        );
    }
    
    $post_id = $posts[0]->ID;
    
    // Get approved comments
    $comments = get_comments(array(
        'post_id' => $post_id,
        'status' => 'approve',
        'orderby' => 'comment_date',
        'order' => 'DESC'
    ));
    
    $reviews = array();
    $total_rating = 0;
    
    foreach ($comments as $comment) {
        $rating = get_comment_meta($comment->comment_ID, 'rating', true);
        $rating = intval($rating);
        
        $reviews[] = array(
            'id' => $comment->comment_ID,
            'author' => $comment->comment_author,
            'content' => $comment->comment_content,
            'rating' => $rating,
            'date' => $comment->comment_date,
            'date_gmt' => $comment->comment_date_gmt
        );
        
        $total_rating += $rating;
    }
    
    $average = count($reviews) > 0 ? round($total_rating / count($reviews), 1) : 0;
    
    return array(
        'reviews' => $reviews,
        'count' => count($reviews),
        'average_rating' => $average
    );
}