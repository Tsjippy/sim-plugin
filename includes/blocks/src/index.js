const { __ }                            = wp.i18n;
const { createHigherOrderComponent }    = wp.compose;
const { Fragment }                      = wp.element;
const { InspectorControls }             = wp.blockEditor;
const { PanelBody, SelectControl, ToggleControl, CheckboxControl }      = wp.components;
import { SearchControl, Spinner } from '@wordpress/components';
import {useState, useEffect, withState } from "@wordpress/element";
import { useSelect, dispatch } from '@wordpress/data';
import { store as coreDataStore } from '@wordpress/core-data';
import { decodeEntities } from '@wordpress/html-entities';	

// Add attributes
function addCoverAttribute(settings, name) {
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
            }
        });
    }
    return settings;
}
 
wp.hooks.addFilter(
    'blocks.registerBlockType',
    'sim/block-filter-attribute',
    addCoverAttribute
);

// Add controls to panel
const blockFilterControls = createHigherOrderComponent((BlockEdit) => {
    return ( props ) => {

        const { attributes, setAttributes } = props;

        const [ searchTerm, setSearchTerm ] = useState( '' );

        let pageIds = [];
        if(attributes.onlyOn != undefined){
            pageIds = Object.keys(attributes.onlyOn);
        }

        let checkedPages    = {};
        
        for (const id in attributes.onlyOn) {
            checkedPages[id] = true;
        }

        const [ pagesArray, setPages ] = useState( checkedPages);

        const PagesList = function ( { hasResolved, pages, showLoader = true } ) {
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
                    return <div>No search results</div>;
                }

                return '';
            }
        
            const onPagesChanged	= function(checked){
                let copy = Object.assign({}, pagesArray);
                // this is the page id
                copy[this]	= checked;
        
                setAttributes({onlyOn: copy});
        
                setPages(copy);
            }
        
            return (
                pages?.map( ( page ) => {
        
                    return (<CheckboxControl
                        label		= {decodeEntities( page.title.rendered )}
                        onChange	= {onPagesChanged.bind(page.id)}
                        //checked		= {pagesArray.includes(String(page.id))}
                        checked		= {pagesArray[page.id]}
                    />)
                } )
            );
        }

        const { pages, selectedPages, pagesResolved,  selectedPagesResolved} = useSelect(
            ( select) => {
                // find all pages excluding the already selected pages
                const query = {exclude : pageIds};
                if ( searchTerm ) {
                    query.search = searchTerm;
                }
                const pagesArgs         = [ 'postType', 'page', query ];

                // Find all selected pages
                const selectedPagesArgs = [ 'postType', 'page', {include : pageIds} ];

                return {
                    pages: select( coreDataStore ).getEntityRecords(
                        ...pagesArgs
                    ),
                    selectedPages: select( coreDataStore ).getEntityRecords(
                        ...selectedPagesArgs
                    ),
                    pagesResolved: select( coreDataStore ).hasFinishedResolution(
                        'getEntityRecords',
                        pagesArgs
                    ),
                    selectedPagesResolved: select( coreDataStore ).hasFinishedResolution(
                        'getEntityRecords',
                        selectedPagesArgs
                    )
                };
            },
            [ searchTerm, pagesArray ]
        );

        let selectedPagesControls = '';
        if(pageIds.length > 0){
            selectedPagesControls    =   <>
                <i>Currently selected pages:</i>
                <br></br>
                <PagesList hasResolved={ selectedPagesResolved } pages={selectedPages} props={ props } showLoader={false}/>
            </>;
        }

        return (
            <Fragment>
                <BlockEdit { ...props } />
                <InspectorControls>
                	<PanelBody title={ __( 'Block Visibility' ) }>
                        <ToggleControl
                            label={wp.i18n.__('Hide on mobile', 'sim')}
                            checked={!!attributes.hideOnMobile}
                            onChange={(newval) => setAttributes({ hideOnMobile: !attributes.hideOnMobile })}
                        />

                        <ToggleControl
                            label={wp.i18n.__('Only on home page', 'sim')}
                            checked={!!attributes.onlyOnHomePage}
                            onChange={(newval) => setAttributes({ onlyOnHomePage: !attributes.onlyOnHomePage })}
                        />

                        <ToggleControl
                            label={wp.i18n.__('Hide if not logged in', 'sim')}
                            checked={!!attributes.onlyLoggedIn}
                            onChange={(newval) => setAttributes({ onlyLoggedIn: !attributes.onlyLoggedIn })}
                        />
                    
                        <strong>Select pages</strong><br></br>
                        Select pages you want this widget to show on.<br></br>
                        Leave empty for all pages<br></br>
                        <br></br>
                        {selectedPagesControls}
                        <i>Use searchbox below to search for more pages to include</i>
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


// Do something with the results
function coverApplyExtraClass(extraProps, blockType, attributes) {
    const { hideOnMobile } = attributes;
 
    if (typeof hideOnMobile !== 'undefined' && hideOnMobile) {
        extraProps.className = extraProps.className + ' hide-on-mobile';
    }
    return extraProps;
}
 
wp.hooks.addFilter(
    'blocks.getSaveContent.extraProps',
    'sim/block-filter-resultx',
    coverApplyExtraClass
);