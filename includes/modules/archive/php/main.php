<?php
namespace SIM\ARCHIVE;
use SIM;

// Registering custom post status
add_action( 'init', function (){
    register_post_status('archived', array(
        'label'                     => _x( 'Archived', 'post' ),
        'public'                    => false,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'protected'                 => true,
        'label_count'               => _n_noop( 'Archived <span class="count">(%s)</span>', 'Archived <span class="count">(%s)</span>' ),
    ) );
} );

// Using jQuery to add it to post status dropdown
/* add_action('admin_footer-edit', __NAMESPACE__.'\addPostStatus');
add_action('admin_footer-post.php', __NAMESPACE__.'\addPostStatus');
add_action('admin_footer-post-new.php', __NAMESPACE__.'\addPostStatus'); */
add_action('admin_footer', __NAMESPACE__.'\addPostStatus');
function addPostStatus(){
    global $post;
    if ( !isset($post) ) return;
    if ( !($post instanceof \WP_Post) ) return;

    $isSelected = $post->post_status == 'archived';

    ?>
    <script>
        jQuery(function() {
            var archivedSelected    = <?php echo $isSelected ? 1 : 0; ?>;
            var $postStatus         = jQuery("#post_status");
            var $postStatusDisplay  = jQuery("#post-status-display");

            $postStatus.append('<option value="archived">Archived</option>');
            
            if ( archivedSelected ) {
                $postStatus.val( 'archived' );
                $postStatusDisplay.text('Archived');
            }
        });

        // Post listing screen: Add quick edit functionality:
        jQuery(function() {
            // See: /wp-admin/js/inline-edit-post.js -> Window.inlineEditPost.edit
            var insertArchivedStatusToInlineEdit = function(t, postId, $row) {
                // t = window.inlineEditPost
                // post_id = post_id of the post (eg: div#inline_31042 -> 31042)
                // $row = The original post row <tr> which contains the quick edit button, post title, columns, etc.
                var $editRow        = jQuery('#edit-' + postId); // The quick edit row that appeared.
                var $rowData        = jQuery('#inline_' + postId); // A hidden row that contains relevant post data
                
                var status          = jQuery('._status', $rowData).text(); // Current post status
                
                var $statusSelect   = $editRow.find('select[name="_status"]'); // Dropdown to change status
                
                // Add archived status to dropdown, if not present
                if ( $statusSelect.find('option[value="archived"]').length < 1 ) {
                    $statusSelect.append('<option value="archived">Archived</option>');
                }
                
                // Select archived from dropdown if that is the current post status
                if ( status === 'archived' ) $statusSelect.val( 'archived' );
                
                // View information:
                // console.log( id, $row, $editRow, $rowData, status, $statusSelect );
            };
            
            // On click, wait for default functionality, then apply our customizations
            var inlineEditPostStatus = function() {
                var t       = window.inlineEditPost;
                var $row    = jQuery(this).closest('tr');
                var postId = t.getId(this);
                
                // Use next frame if browser supports it, or wait 0.25 seconds
                if ( typeof requestAnimationFrame === 'function' ) {
                    requestAnimationFrame(function() { return insertArchivedStatusToInlineEdit( t, postId, $row ); });
                }else{
                    setTimeout(function() { return insertArchivedStatusToInlineEdit( t, postId, $row ); }, 250 );
                }
            };
            
            // Bind click event before inline-edit-post.js has a chance to bind it
            jQuery('#the-list').on('click', '.editinline', inlineEditPostStatus);
        });
    </script>

    <?php
}


// Display "— Archived" after post name on the dashobard, like you would see "— Draft" for draft posts.
// Not shown when viewing only archived posts because that would be redundant.
add_filter( 'display_post_states', function ( $statuses ) {
    global $post; // we need it to check current post status
    
    if( get_query_var( 'post_status' ) != 'archived' ){ // not for pages with all posts of this status
        if( $post->post_status == 'archived' ){ // если статус поста - Архив
            return array('Archived'); // returning our status label
        }
    }
    
    return $statuses; // returning the array with default statuses
});