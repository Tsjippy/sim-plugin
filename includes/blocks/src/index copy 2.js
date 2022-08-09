const { __ }                            = wp.i18n;
const { createHigherOrderComponent }    = wp.compose;
const { Fragment }                      = wp.element;
const { InspectorControls }             = wp.blockEditor;
const { PanelBody, ToggleControl, CheckboxControl }      = wp.components;
import { SearchControl, Spinner, __experimentalInputControl as InputControl } from '@wordpress/components';
import {useState } from "@wordpress/element";
import { useSelect } from '@wordpress/data';
import { store as coreDataStore } from '@wordpress/core-data';
import { decodeEntities } from '@wordpress/html-entities';	

// Add attributes
function addFilterAttribute(settings) {
    if (typeof settings.attributes !== 'undefined') {
        settings.attributes = Object.assign(settings.attributes, {
            hideOnMobile: {
                type: 'boolean',
            },
            onlyOnHomePage: {
                type: 'boolean',
            },
            onlyLoggedIn: {
                type: 'boolean',
            },
            onlyOn: {
                type: 'object'
            },
            phpFilters: {
                type: 'array'
            }
        });
    }
    return settings;
}
 
wp.hooks.addFilter(
    'blocks.registerBlockType',
    'sim/block-filter-attribute',
    addFilterAttribute
);

// Add controls to panel
const blockFilterControls = createHigherOrderComponent((BlockEdit) => {
    return ( props ) => {
        const { attributes, setAttributes } = props;

        /** FUNCTIONS */

        const onPhpFiltersChanged	= function(newValue){
            let oldValue    = this;

            // add a new value
            if(oldValue == ''){
                attributes.phpFilters.push(newValue);
            // value removed
            }else if(newValue == ''){
                let index   = attributes.phpFilters.findIndex(el=>el==oldValue);
                attributes.phpFilters.splice(index, 1);
            // value changed
            }else{
                let index   = attributes.phpFilters.findIndex(el=>el==oldValue);
                attributes.phpFilters[index]  = newValue;
            }
    
            setAttributes({ phpFilters: attributes.phpFilters });
    
            setPageFilters(createFilterControls(attributes.phpFilters));
        }

        const createFilterControls  = function(filters){
            return filters.map( filter =>
                <InputControl
                    isPressEnterToChange={true}
                    value={ filter }
                    onChange={ onPhpFiltersChanged.bind(filter) }
                />
            )
        };

        const onPagesChanged	    = function(checked){
            if(selectedPageList != ''){
                let index   = selectedPageList.props.pages.findIndex(el=>{
                    return el.id==this;
                });

                if(index != -1){
                    selectedPageList.props.pages.splice(index, 1);
                }
            }

            let copy = Object.assign({}, pagesArray);

            // this is the page id
            copy[this]	= checked;

            if(checked){
                pageIds.push(this);
            }
    
            setAttributes({onlyOn: copy});
    
            setPages(copy);
        }

        const PagesList             = function ( { hasResolved, pages, showLoader = true } ) {
            if ( ! hasResolved ) {
                return(
                    <>
                    <Spinner />
                    <br></br>
                    </>
                );
            }
        
            if(attributes.onlyOn == undefined){
                attributes.onlyOn = {};
            }
        
            if ( ! pages?.length ) {
                if(showLoader){
                    return <div> {__('No search results', 'sim')}</div>;
                }

                return '';
            }
        
            return (
                pages?.map( ( page ) => {
        
                    return (<CheckboxControl
                        label		= {decodeEntities( page.title.rendered )}
                        onChange	= {onPagesChanged.bind(page.id)}
                        checked		= {pagesArray[page.id]}
                    />)
                } )
            );
        }

        /** Variables */
        let phpFilterControls   = '';
        if(attributes.phpFilters != undefined){
            phpFilterControls   = createFilterControls(attributes.phpFilters);
        }

        let pageIds = [];
        if(attributes.onlyOn != undefined){
            pageIds = Object.keys(attributes.onlyOn);
        }

        let selectedPageList    = '';

        let checkedPages    = {};
        
        for (const id in attributes.onlyOn) {
            checkedPages[id] = true;
        }

        /** HOOKS */
        const [ pagesArray, setPages ]          = useState( checkedPages);
        const [ searchTerm, setSearchTerm ]     = useState( '' );
        const [ pageFilters, setPageFilters ]   = useState( phpFilterControls );

        // Selected page list
        let { selectedPages, selectedPagesResolved} = useSelect(
            ( select) => {
                // Find all selected pages
                const selectedPagesArgs = [ 'postType', 'page', {include : pageIds} ];

                return {
                    selectedPages: select( coreDataStore ).getEntityRecords(
                        ...selectedPagesArgs
                    ),
                    selectedPagesResolved: select( coreDataStore ).hasFinishedResolution(
                        'getEntityRecords',
                        selectedPagesArgs
                    )
                };
            },
            [  ]
        );

        // Search page list
        let { pages, pagesResolved} = useSelect(
            ( select) => {
                // find all pages excluding the already selected pages
                const query = {exclude : pageIds};
                if ( searchTerm ) {
                    query.search = searchTerm;
                }
                const pagesArgs         = [ 'postType', 'page', query ];

                return {
                    pages: select( coreDataStore ).getEntityRecords(
                        ...pagesArgs
                    ),
                    pagesResolved: select( coreDataStore ).hasFinishedResolution(
                        'getEntityRecords',
                        pagesArgs
                    )
                };
            },
            [ searchTerm ]
        );

        const   getSelectedPagesControls    = function(){
            if(pageIds.length > 0){
                selectedPageList    = <PagesList hasResolved={ selectedPagesResolved } pages={selectedPages} props={ props } showLoader={false}/>;
                return (
                    <>
                        <i> {__('Currently selected pages', 'sim')}:</i>
                        <br></br>
                        {selectedPageList}
                    </>
                );
            }
        }

        
        let selectedPagesControls = getSelectedPagesControls();

        return (
            <Fragment>
                <BlockEdit { ...props } />
                <InspectorControls>
                	<PanelBody title={ __( 'Block Visibility' ) }>
                        <ToggleControl
                            label={__('Hide on mobile', 'sim')}
                            checked={!!attributes.hideOnMobile}
                            onChange={(newval) => setAttributes({ hideOnMobile: !attributes.hideOnMobile })}
                        />

                        <ToggleControl
                            label={__('Only on home page', 'sim')}
                            checked={!!attributes.onlyOnHomePage}
                            onChange={(newval) => setAttributes({ onlyOnHomePage: !attributes.onlyOnHomePage })}
                        />

                        <ToggleControl
                            label={__('Hide if not logged in', 'sim')}
                            checked={!!attributes.onlyLoggedIn}
                            onChange={(newval) => setAttributes({ onlyLoggedIn: !attributes.onlyLoggedIn })}
                        />
                        
                        <InputControl
                            isPressEnterToChange={true}
                            label="Add php filters like 'isPage(12)'"
                            value={ '' }
                            onChange={onPhpFiltersChanged.bind('')}
                        />
                        {pageFilters}
                    
                        <strong>{__('Select pages', 'sim')}</strong><br></br>
                        {__('Select pages you want this widget to show on', 'sim')}.<br></br>
                        {__('Leave empty for all pages', 'sim')}<br></br>
                        <br></br>
                        {selectedPagesControls}
                        <i>{__('Use searchbox below to search for more pages to include', 'sim')}</i>
                        <SearchControl onChange={ setSearchTerm } value={ searchTerm } />
                        <PagesList hasResolved={ pagesResolved } pages={ pages } props={ props }/>
	                </PanelBody>
                </InspectorControls>
            </Fragment>
        );
    };
}, 'blockFilterControls');
 
wp.hooks.addFilter(
    'editor.BlockEdit',
    'sim/block-filter-controls',
    blockFilterControls
);