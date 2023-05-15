//filter the content
//the filter props are passed on to wp_ajax_query_attachments
// https://developer.wordpress.org/reference/functions/wp_ajax_query_attachments/

document.addEventListener("DOMContentLoaded", function() {

	if(window['categoryFilterAdded'] == undefined){
		window['categoryFilterAdded']	= true;
		
		window.wp = window.wp || {};

		// filters attachment on their public or private apperance
		var CategoryFilter = wp.media.view.AttachmentFilters.extend({
			id: 'category-filter',
			createFilters: function() {
				var filters = {};

				filters.all = {
					text: 'Select a category',
					priority: 10
				};
				filters['all']['props'] = {};
				filters['all']['props']['category'] = '';

				categories.forEach(cat=>{
					filters[cat.slug] = {
						text: cat.name,
					};
					filters[cat.slug]['props'] = {};
					filters[cat.slug]['props']['category'] = cat.slug;
				});

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
				that.toolbar.set( 'CategoryFilter', new CategoryFilter({
					controller: that.controller,
					model: that.collection.props,
					priority: -70,
				}).render() );
			}
		});
	}
});
