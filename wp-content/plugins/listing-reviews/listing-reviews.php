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
    if (empty($params['post_id']) || empty($params['rating']) || empty($params['comment'])) {
        return new WP_Error('missing_fields', 'Missing required fields', array('status' => 400));
    }
    
    // Create comment
    $comment_data = array(
        'comment_post_ID' => intval($params['post_id']),
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
            'comment_id' => $comment_id
        );
    }
    
    return new WP_Error('failed', 'Failed to submit review', array('status' => 500));
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