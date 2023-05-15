//filter the content
//the filter props are passed on to wp_ajax_query_attachments
// https://developer.wordpress.org/reference/functions/wp_ajax_query_attachments/

document.addEventListener("DOMContentLoaded", function() {
	// do not run twice
	if(window['visibilityFilterAdded']	!= undefined){
		return;
	}

	window['visibilityFilterAdded']	= true;

	window.wp = window.wp || {};

	var VisibilityFilter = wp.media.view.AttachmentFilters.extend({
		id: 'visibility-filter',
		createFilters: function() {
			var filters = {};

			// Add the option to select ALL
			filters.all = {
				text: 'Show all',
				priority: 10
			};
			filters['all']['props'] = {};
			filters['all']['props']['visibility'] = '';
			
			filters['public'] = {
				text: 'Show public',
			};
			filters['public']['props'] = {};
			filters['public']['props']['visibility'] = 'public';
			
			filters['private'] = {
				text: 'Show private',
			};
			filters['private']['props'] = {};
			filters['private']['props']['visibility'] = 'private';

			this.filters = filters;
		}
	});

	/**
		 * Add our filter dropdown to the menu bar
	*/
	var AttachmentsBrowser = wp.media.view.AttachmentsBrowser;
	wp.media.view.AttachmentsBrowser = wp.media.view.AttachmentsBrowser.extend({
		createToolbar: function() {
			var that = this;

			// Make sure to load the original toolbar
			AttachmentsBrowser.prototype.createToolbar.call(this);

			// Get the labels and items for each mcm_taxonomies
			that.toolbar.set( 'VisibilityFilter', new VisibilityFilter({
				controller: that.controller,
				model: that.collection.props,
				priority: -70,
			}).render() );
		}
	});
});
